<?php

namespace App\Jobs\HubSpot;

use App\Models\Integration\HubSpot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CreateUniqueDealName implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $event;
    public $tries = 0;
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
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

        Log::info("CreateUniqueDealName@handle - {$event->subscriptionType}", ["event"=> $event]);

        $integration = HubSpot::where(['platform' => 'HUBSPOT', 'platform_account_id'=>$event->portalId, 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("CreateUniqueDealName@handle - HubSpot integration is not connected for ".$event->portalId, ["event"=> $event, "integration"=>$integration]);
            return;
        }

        if ($hubspotRetryTimestamp = Cache::get('hubspot-api-retry-timeout', null)) {
            Log::notice("CreateUniqueDealName@handle - HubSpot API rate limit activated, retrying in ".$hubspotRetryTimestamp - time()." seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "companyId"=>$this->event->objectId]);
            $this->release($hubspotRetryTimestamp - time());
            return;
        }

        try {

            $hubspotDeal = $integration->getDeal($this->event->objectId);
            if (!$hubspotDeal) {
                Log::error("CreateUniqueDealName@handle - HubSpot deal not found for id ".$event->objectId, ["event"=> $event, "integration"=>$integration]);
                return;
            }

            $companies = $integration->getDealCompanyAssociations($this->event->objectId);
            $hubspotCompanyName = "";
            if ($companies->getResults()) {
                $companyId = $companies->getResults()[0]->getId();
                $hubspotCompany = $integration->getCompany($companyId);
                $hubspotCompanyName = ' | '.$hubspotCompany->getProperties()['name'];

            } else {
                Log::debug("CreateUniqueDealName@handle - HubSpot company not found for id ".$event->objectId, ["event"=> $event, "integration"=>$integration]);
            }

            $contacts = $integration->getDealContactAssociations($this->event->objectId);
            $contactRecords = [];

            if ($contacts->getResults()) {
                $contactIds = array_map(function($contact) {
                    return $contact->getId();
                }, $contacts->getResults());

                $contactRecords = $integration->getBatchContacts($contactIds);
            }
            
            $names = '';
            if ($contactRecords && $contactRecords->getResults()) {
                $contactNames = [];
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

                $names = implode('|', $contactNames);
                if ($names) {
                    $names = " | " . $names;
                }
            }

            $dealPrefix = '';
            if ($hubspotDeal->getProperties()['dealstage'] === env('HUBSPOT_QUOTE_STAGE')) {
                $dealPrefix = 'QUOTE';
            } else if ($hubspotDeal->getProperties()['dealstage'] === env('HUBSPOT_DO_STAGE')) {
                $dealPrefix = 'DO';
            }

            $res = $integration->updateDeal($this->event->objectId, ['dealname'=>$dealPrefix."-".$this->event->objectId . $hubspotCompanyName . $names]);

            return true;
        } catch (\HubSpot\Client\Crm\Companies\ApiException $e) {
            if ($e->getCode() === 429) {
                $retryAfter = 10;
                Log::notice("CreateUniqueDealName@handle - HubSpot API rate limit exceeded, retrying in {$retryAfter} seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "companyId"=>$this->event->objectId]);
                Cache::put('hubspot-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("CreateUniqueDealName@handle - HubSpot API exception - ".$e->getMessage(), ["event"=> $event, "integration"=>$integration, "companyId"=>$this->event->objectId, "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        } catch (\Exception $e) {
            Log::error("CreateUniqueDealName@handle - Something has gone wrong: ".$e->getMessage(), [
                "event"=> $event, "integration"=>$integration, "companyId"=>$this->event->objectId,
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                'user'=>'hubspot_webhooks'
            ]);
            \Sentry\captureException($e);
            return;
        }
    }
}
