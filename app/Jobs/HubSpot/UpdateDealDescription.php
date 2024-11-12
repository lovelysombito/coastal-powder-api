<?php

namespace App\Jobs\HubSpot;

use App\Models\Deal;
use App\Models\Integration\HubSpot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdateDealDescription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $event;
    public $tries = 0;
    public $maxExceptions = 3;

    public function __construct($event)
    {
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

        Log::info("UpdateDealDescription@handle - {$event->subscriptionType}", ["event"=> $event]);

        $integration = HubSpot::where(['platform' => 'HUBSPOT', 'platform_account_id'=>$event->portalId, 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("UpdateDealDescription@handle - HubSpot integration is not connected for ".$event->portalId, ["event"=> $event, "integration"=>$integration]);
            return;
        }

        if ($hubspotRetryTimestamp = Cache::get('hubspot-api-retry-timeout', null)) {
            Log::notice("UpdateDealDescription@handlee - HubSpot API rate limit activated, retrying in ".$hubspotRetryTimestamp - time()." seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
            $this->release($hubspotRetryTimestamp - time());
            return;
        }

        try {

            $hubspotDeal = $integration->getDeal($this->event->objectId);
            if (!$hubspotDeal) {
                Log::error("UpdateDealDescription@handle - HubSpot deal not found for id ".$event->objectId, ["event"=> $event, "integration"=>$integration]);
                return;
            }

            $coNumber = isset($hubspotDeal->getProperties()['client_job_number_']) ? $hubspotDeal->getProperties()['client_job_number'] : '';
            $poNumber = isset($hubspotDeal->getProperties()['po_number']) ? $hubspotDeal->getProperties()['po_number'] : '';
            $invNumber = isset($hubspotDeal->getProperties()['xero_invoice_number']) ? $hubspotDeal->getProperties()['xero_invoice_number'] : '';
            $colours = isset($hubspotDeal->getProperties()['job_colours']) ? $hubspotDeal->getProperties()['job_colours'] : '';

            $companies = $integration->getDealCompanyAssociations($this->event->objectId);
            $hubspotCompanyName = "";
            if ($companies->getResults()) {
                $companyId = $companies->getResults()[0]->getId();
                $hubspotCompany = $integration->getCompany($companyId);
                $hubspotCompanyName = ' | '.$hubspotCompany->getProperties()['name'];

            }

            $contacts = $integration->getDealContactAssociations($this->event->objectId);
            $contactRecords = [];

            if ($contacts->getResults()) {
                $contactIds = array_map(function($contact) {
                    return $contact->getId();
                }, $contacts->getResults());

                $contactRecords = $integration->getBatchContacts($contactIds);
            }
            
            $contactNames = [];
            if ($contactRecords && $contactRecords->getResults()) {
                foreach($contactRecords->getResults() as $key => $contact) {
                    $name = "";
                    if (isset($contact->getProperties()['firstname'])) {
                        $name = $contact->getProperties()['firstname'];
                    }
                    if (isset($contact->getProperties()['lastname'])) {
                        $name = $name . " " .$contact->getProperties()['lastname'];
                    }
                    if (!$name) {
                        $name = $contact->getProperties()['email'];
                    }
                    array_push($contactNames, $name);
                }
            }

            $names = implode('|', $contactNames);
            if ($names) {
                $names = " | " . $names;
            }

            $description = $coNumber . ' ' . $poNumber . ' ' . $invNumber . ' ' . $hubspotCompanyName . ' ' . implode(' ', $contactNames) . ' ' . $colours;

            $integration->updateDeal($this->event->objectId, ['description' => $description]);

            if ($deal = Deal::where('hs_deal_id', $this->event->objectId)->first())
            foreach($deal->jobs as $job) {
                if ($job->hs_ticket_id) {
                    $integration->updateTicket($job->hs_ticket_id, ['content' => $description]);
                }
            }

            return true;
        } catch (\HubSpot\Client\Crm\Companies\ApiException | \HubSpot\Client\Crm\Contacts\ApiException | \HubSpot\Client\Crm\Deals\ApiException | \HubSpot\Client\Crm\LineItems\ApiException $e) {
            if ($e->getCode() === 429) {
                $retryAfter = 10;
                Log::notice("UpdateDealDescription@handle - HubSpot API rate limit exceeded, retrying in {$retryAfter} seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
                Cache::put('hubspot-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("UpdateDealDescription@handle - HubSpot API exception - ".$e->getMessage(), ["event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId, "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        } catch (\Exception $e) {
            Log::error("UpdateDealDescription@handle - Something has gone wrong: ".$e->getMessage(), [
                "event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId,
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                'user'=>'hubspot_webhooks'
            ]);
            \Sentry\captureException($e);
            return;
        }
    }
}
