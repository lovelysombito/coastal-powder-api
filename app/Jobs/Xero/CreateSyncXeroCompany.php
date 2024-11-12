<?php

namespace App\Jobs\Xero;

use App\Exceptions\Xero\ContactNotFoundException;
use App\Mail\XeroEmailTimeOut;
use App\Models\Integration\HubSpot;
use App\Models\Integration\Xero;
use App\Models\Xero\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CreateSyncXeroCompany implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $event;
    public $tries = 0;
    public $maxExceptions = 3;

    public function __construct($event)
    {
        $this->onConnection('xero');
        $this->event = $event;
    }

    public function retryUntil() {
        return now()->addHours(18);
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $event = $this->event;
        Log::info("CreateSyncXeroCompany@handle - {$event->subscriptionType}", ["event"=> $event]);

        $integration = Xero::where(['platform' => 'XERO', 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("CreateSyncXeroCompany@handle - Xero integration is not connected", ["event"=> $event, "integration"=>$integration]);
            return;
        }

        print_r("Test");

        $hubspotIntegration = HubSpot::where(['platform' => 'HUBSPOT', 'platform_account_id'=>$event->portalId, 'integration_status'=>'Connected'])->first();
        if (!$hubspotIntegration) {
            Log::warning("CreateSyncXeroCompany@handle - HubSpot integration is not connected for ".$event->portalId, ["event"=> $event, "integration"=>$integration]);
            return;
        }

        if ($hubspotRetryTimestamp = Cache::get('hubspot-api-retry-timeout', null)) {
            Log::notice("CreateSyncXeroCompany@handle - HubSpot API rate limit activated, retrying in ".$hubspotRetryTimestamp - time()." seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "companyId"=>$this->event->objectId]);
            $this->release($hubspotRetryTimestamp - time());
            return;
        }

        print_r("HS");

        if ($xeroRetryTimestamp = Cache::get('xero-api-retry-timeout', null)) {
            if ($xeroRetryTimestamp - time() > 43100) {
                $xeroRetryTimestamp = time() + 43100;
            }
            
            Log::notice("CreateSyncXeroCompany@handle - Xero API rate limit activated, retrying in ".$xeroRetryTimestamp - time()." seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "companyId"=>$this->event->objectId]);
            $this->release($xeroRetryTimestamp - time());
            $data = ['message' => "CreateSyncXeroCompany@handle - Xero API rate limit activated, retrying in ".$xeroRetryTimestamp - time()." seconds"];
            foreach (['mitchell@upstreamtech.io', 'ella@upstreamtech.io'] as $recipient) {
                Mail::to($recipient)->send(new XeroEmailTimeOut($data));
            }
            return;
        }


        try {   

            $hubspotCompany = $hubspotIntegration->getCompany($this->event->objectId);
            $xeroContact = null;
            $contact = null;

            if (!$contact = Contact::where('hubspot_company_id', $this->event->objectId)->first()) {
                try {
                    print_r("Hello");
                    $xeroContact = $integration->getContactByName($hubspotCompany->getProperties()['name']);

                    Log::info("CreateSyncXeroCompany@handle - Existing Xero contact found for ".$hubspotCompany->getProperties()['name'].", syncing with ".$xeroContact->getContactId(), ["event"=> $event, "integration"=>$integration, "companyId"=>$this->event->objectId]);
                    $contact = Contact::create(['xero_contact_id' => $xeroContact->getContactId(), 'hubspot_company_id' => $this->event->objectId]);
                    return;
                } catch(ContactNotFoundException $e) {
                    Log::info("CreateSyncXeroCompany@handle - Xero contact not found for ".$hubspotCompany->getProperties()['name'].", creating a new contact", ["event"=> $event, "integration"=>$integration, "companyId"=>$this->event->objectId]);
                    $xeroContact = $integration->createContact($hubspotCompany->getProperties()['name']);
                    $contact = Contact::create(['xero_contact_id' => $xeroContact->getContactId(), 'hubspot_company_id' => $this->event->objectId]);
                    return;
                }
            } else {
                Log::info("CreateSyncXeroCompany@handle - HubSpot company is already in sync with Xero contact ".$contact->xero_contact_id, ["event"=> $event, "integration"=>$integration, "companyId"=>$this->event->objectId]);
                $xeroContact = $integration->getContactById($contact->xero_contact_id);

                $xeroContact->setName($hubspotCompany->getProperties()['name']);

                $integration->updateContact($xeroContact);
                Log::info("CreateSyncXeroContact@handle - Contact ".$xeroContact->getContactId() . " successfully updated", ["event"=> $event, "integration"=>$integration, "companyId"=>$this->event->objectId]);
                return;
            }
        } catch (\XeroAPI\XeroPHP\ApiException $e) {

            if ($e->getCode() === 429) {
                $responseHeaders = $e->getResponseHeaders();
                $retryAfter = $responseHeaders['Retry-After'][0];
                if ($retryAfter > 43100) {
                    $retryAfter = 43100;
                }
                Log::notice("CreateSyncXeroCompany@handle - Xero API rate limit exceeded, retrying in {$retryAfter} seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "companyId"=>$this->event->objectId]);
                Cache::put('xero-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("CreateSyncXeroCompany@handle - Xero API exception - ".$e->getMessage(), ["event"=> $event, "integration"=>$integration, "companyId"=>$this->event->objectId, "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        } catch (\HubSpot\Client\Crm\Companies\ApiException $e) {
            if ($e->getCode() === 429) {
                $retryAfter = 10;
                Log::notice("CreateSyncXeroCompany@handle - HubSpot API rate limit exceeded, retrying in {$retryAfter} seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "companyId"=>$this->event->objectId]);
                Cache::put('hubspot-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("CreateSyncXeroCompany@handle - HubSpot API exception - ".$e->getMessage(), ["event"=> $event, "integration"=>$integration, "companyId"=>$this->event->objectId, "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        } catch (\Exception $e) {
            Log::error("CreateSyncXeroCompany@handle - Something has gone wrong: ".$e->getMessage(), [
                "event"=> $event, "integration"=>$integration, "companyId"=>$this->event->objectId,
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                'user'=>'hubspot_webhooks'
            ]);
            \Sentry\captureException($e);
            return;
        }
    }
}
