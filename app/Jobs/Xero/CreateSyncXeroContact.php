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
use XeroAPI\XeroPHP\Models\Accounting\ContactPerson;


class CreateSyncXeroContact implements ShouldQueue
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
        Log::info("CreateSyncXeroContact@handle - {$event->subscriptionType}", ["event"=> $event]);

        $integration = Xero::where(['platform' => 'XERO', 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("CreateSyncXeroContact@handle - Xero integration is not connected", ["event"=> $event, "integration"=>$integration]);
            return;
        }

        $hubspotIntegration = HubSpot::where(['platform' => 'HUBSPOT', 'platform_account_id'=>$event->portalId, 'integration_status'=>'Connected'])->first();
        if (!$hubspotIntegration) {
            Log::warning("CreateSyncXeroContact@handle - HubSpot integration is not connected for ".$event->portalId, ["event"=> $event, "integration"=>$integration]);
            return;
        }

        if ($hubspotRetryTimestamp = Cache::get('hubspot-api-retry-timeout', null)) {
            Log::notice("CreateSyncXeroCompany@handle - HubSpot API rate limit activated, retrying in ".$hubspotRetryTimestamp - time()." seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "contactId"=>$this->event->objectId]);
            $this->release($hubspotRetryTimestamp - time());
            return;
        }

        if ($xeroRetryTimestamp = Cache::get('xero-api-retry-timeout', null)) {
            if ($xeroRetryTimestamp - time() > 43100) {
                $xeroRetryTimestamp = time() + 43100;
            }
            Log::notice("CreateSyncXeroCompany@handle - Xero API rate limit activated, retrying in ".$xeroRetryTimestamp - time()." seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "contactId"=>$this->event->objectId]);
            $this->release($xeroRetryTimestamp - time());
            $data = ['message' => "CreateSyncXeroCompany@handle - Xero API rate limit activated, retrying in ".$xeroRetryTimestamp - time()." seconds"];
            foreach (['mitchell@upstreamtech.io', 'ella@upstreamtech.io'] as $recipient) {
                Mail::to($recipient)->send(new XeroEmailTimeOut($data));
            }
            return;
        }
        
        try {

            $companies = $hubspotIntegration->getContactCompanyAssociations($this->event->objectId);
            if (!$companies->getResults()) {
                Log::info("CreateSyncXeroContact@handle - HubSpot contact not associated with any company", ["event"=> $event, "integration"=>$integration]);
                return;
            }

            $companyId = $companies->getResults()[0]->getId();
            if (!$companyId) {
                Log::warning("CreateSyncXeroContact@handle - HubSpot company ID not found", ["event"=> $event, "integration"=>$integration, "companyId"=>$companyId, "contactId"=>$this->event->objectId]);
                return;
            }

            $hubspotCompany = $hubspotIntegration->getCompany($companyId);
            $xeroContact = null;
            $contact = null;

            if (!$contact = Contact::where('hubspot_company_id', $companyId)->first()) {
                try {
                    $xeroContact = $integration->getContactByName($hubspotCompany->getProperties()['name']);

                    Log::info("CreateSyncXeroContact@handle - Existing Xero contact found for ".$hubspotCompany->getProperties()['name'].", syncing with ".$xeroContact->getContactId(), ["event"=> $event, "integration"=>$integration, "companyId"=>$companyId, "contactId"=>$this->event->objectId]);
                    $contact = Contact::create(['xero_contact_id' => $xeroContact->getContactId(), 'hubspot_company_id' => $companyId]);
                } catch(ContactNotFoundException $e) {
                    Log::info("CreateSyncXeroContact@handle - Xero contact not found for ".$hubspotCompany->getProperties()['name'].", creating a new contact", ["event"=> $event, "integration"=>$integration, "companyId"=>$companyId, "contactId"=>$this->event->objectId]);
                    $xeroContact = $integration->createContact($hubspotCompany->getProperties()['name']);
                    $contact = Contact::create(['xero_contact_id' => $xeroContact->getContactId(), 'hubspot_company_id' => $companyId]);
                }
            } else {
                Log::info("CreateSyncXeroContact@handle - HubSpot company is already in sync with Xero contact ".$contact->xero_contact_id, ["event"=> $event, "integration"=>$integration, "companyId"=>$companyId, "contactId"=>$this->event->objectId]);
                $xeroContact = $integration->getContactById($contact->xero_contact_id);
            }

            $hsContacts = $hubspotIntegration->getCompanyContactAssociations($companyId);
            $contactRecords = [];
            if ($hsContacts->getResults()) {
                $contactIds = array_map(function($contact) {
                    return $contact->getId();
                }, $hsContacts->getResults());
    
                $contactRecords = $hubspotIntegration->getBatchContacts($contactIds);
            }

            if (!$contactRecords) {
                Log::info("CreateSyncXeroContact@handle - Xero contacts not found for company - $companyId", ["event"=> $event, "integration"=>$hubspotIntegration, "companyId"=>$companyId, "contactId"=>$this->event->objectId]);
                return;
            }

            $secondaryContacts = [];

            Log::info("CreateSyncXeroContact@handle - Mapping associated contacts for company", ["event"=> $event, "integration"=>$integration, "companyId"=>$companyId, "contactId"=>$this->event->objectId]);
            foreach($contactRecords->getResults() as $contactRecord) {
                if ($contactRecord->getProperties()['contact_type'] && $contactRecord->getProperties()['contact_type'] == 'Account Contact') {
                    $xeroContact->setEmailAddress($contactRecord->getProperties()['email']);
                    $xeroContact->setFirstName($contactRecord->getProperties()['firstname']);
                    $xeroContact->setLastName($contactRecord->getProperties()['lastname']);
                } else {
                    $secondaryContact = new ContactPerson();
                    $secondaryContact->setFirstName($contactRecord->getProperties()['firstname']);
                    $secondaryContact->setLastName($contactRecord->getProperties()['lastname']);
                    $secondaryContact->setEmailAddress($contactRecord->getProperties()['email']);
                    $secondaryContact->setIncludeInEmails($contactRecord->getProperties()['include_xero_emails']);
                    $secondaryContacts[] = $secondaryContact;
                }
            }

            if ($xeroContact) {
                if ($xeroContact->getEmailAddress()) {
                    // Contact persons can only be set if the primary contact has an email address
                    $xeroContact->setContactPersons($secondaryContacts);
                } else {
                    Log::notice("CreateSyncXeroContact@handle - Xero Contact ".$xeroContact->getContactId() ." does not have an email address, we cannot assign secondary contacts", ["event"=> $event, "integration"=>$integration, "companyId"=>$companyId, "contactId"=>$this->event->objectId]);
                }
                $integration->updateContact($xeroContact);
                Log::info("CreateSyncXeroContact@handle - Contact ".$xeroContact->getContactId() . " successfully updated", ["event"=> $event, "integration"=>$integration, "companyId"=>$companyId, "contactId"=>$this->event->objectId]);
                return true;
            } else {
                Log::error("CreateSyncXeroContact@handle - Xero contact is null, was not created or linked with Xero for company ".$companyId, ["event"=> $event, "integration"=>$integration, "companyId"=>$companyId, "contactId"=>$this->event->objectId]);
                \Sentry\captureMessage("CreateSyncXeroContact@handle - Xero contact is null, was not created or linked with Xero for company ".$companyId);
                return true;
            }

        } catch (\XeroAPI\XeroPHP\ApiException $e) {

            if ($e->getCode() === 429) {
                $responseHeaders = $e->getResponseHeaders();
                $retryAfter = $responseHeaders['Retry-After'][0];
                if ($retryAfter > 43100) {
                    $retryAfter = 43100;
                }
                Log::notice("CreateSyncXeroContact@handle - Xero API rate limit exceeded, retrying in {$retryAfter} seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "companyId"=>$companyId, "contactId"=>$this->event->objectId]);
                Cache::put('xero-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);
                $this->release(($retryAfter));
                
                return;
            }

            \Sentry\captureException($e);
            Log::error("CreateSyncXeroContact@handle - Xero API exception - ".$e->getMessage(), ["event"=> $event, "integration"=>$integration, "companyId"=>$companyId, "contactId"=>$this->event->objectId, "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        } catch (\HubSpot\Client\Crm\Companies\ApiException $e) {
            if ($e->getCode() === 429) {
                $retryAfter = 10;
                Log::notice("CreateSyncXeroContact@handle - HubSpot API rate limit exceeded, retrying in {$retryAfter} seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "companyId"=>$companyId, "contactId"=>$this->event->objectId]);
                Cache::put('hubspot-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("CreateSyncXeroContact@handle - HubSpot API exception - ".$e->getMessage(), ["event"=> $event, "integration"=>$integration, "companyId"=>$companyId, "contactId"=>$this->event->objectId, "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        } catch (\HubSpot\Client\Crm\Contacts\ApiException $e) {
            if ($e->getCode() === 429) {
                $retryAfter = 10;
                Log::notice("CreateSyncXeroContact@handle - HubSpot API rate limit exceeded, retrying in {$retryAfter} seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "companyId"=>$companyId, "contactId"=>$this->event->objectId]);
                Cache::put('hubspot-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("CreateSyncXeroContact@handle - HubSpot API exception - ".$e->getMessage(), ["event"=> $event, "integration"=>$integration, "companyId"=>$companyId, "contactId"=>$this->event->objectId, "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        } catch (\Exception $e) {
            Log::error("CreateSyncXeroContact@handle - Something has gone wrong: ".$e->getMessage(), [
                "event"=> $event, "integration"=>$integration, "companyId"=>$companyId, "contactId"=>$this->event->objectId,
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                'user'=>'hubspot_webhooks'
            ]);
            \Sentry\captureException($e);
            return;
        }
    }
}
