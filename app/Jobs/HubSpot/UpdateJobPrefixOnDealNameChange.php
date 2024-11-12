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

class UpdateJobPrefixOnDealNameChange implements ShouldQueue
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

        Log::info("UpdateJobPrefixOnDealNameChange@handle - {$event->subscriptionType}", ["event"=> $event]);

        $integration = HubSpot::where(['platform' => 'HUBSPOT', 'platform_account_id'=>$event->portalId, 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("UpdateJobPrefixOnDealNameChange@handle - HubSpot integration is not connected for ".$event->portalId, ["event"=> $event, "integration"=>$integration]);
            return;
        }

        if ($hubspotRetryTimestamp = Cache::get('hubspot-api-retry-timeout', null)) {
            Log::notice("UpdateJobPrefixOnDealNameChange@handle - HubSpot API rate limit activated, retrying in ".$hubspotRetryTimestamp - time()." seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
            $this->release($hubspotRetryTimestamp - time());
            return;
        }

        try {

            $hubspotDeal = $integration->getDeal($this->event->objectId);
            if (!$hubspotDeal) {
                Log::error("UpdateJobPrefixOnDealNameChange@handle - HubSpot deal not found for id ".$event->objectId, ["event"=> $event, "integration"=>$integration]);
                return;
            }

            $dealName = $hubspotDeal->getProperties()['dealname'];

            $deal = Deal::where(['hs_deal_id' => $this->event->objectId])->first();
            
            foreach($deal->jobs as $job) {
                $job->update(['job_prefix' => $dealName]);
            }

            Log::warning("UpdateJobPrefixOnDealNameChange@handle - Linked job prefixes have been successfully updated", ["event"=> $event, "integration"=>$integration]);

            return;
        } catch (\HubSpot\Client\Crm\Companies\ApiException | \HubSpot\Client\Crm\Contacts\ApiException | \HubSpot\Client\Crm\Deals\ApiException | \HubSpot\Client\Crm\LineItems\ApiException $e) {
            if ($e->getCode() === 429) {
                $retryAfter = 10;
                Log::notice("UpdateJobPrefixOnDealNameChange@handle - HubSpot API rate limit exceeded, retrying in {$retryAfter} seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
                Cache::put('hubspot-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("UpdateJobPrefixOnDealNameChange@handle - HubSpot API exception - ".$e->getMessage(), ["event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId, "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        } catch (\Exception $e) {
            Log::error("UpdateJobPrefixOnDealNameChange@handle - Something has gone wrong: ".$e->getMessage(), [
                "event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId,
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                'user'=>'hubspot_webhooks'
            ]);
            \Sentry\captureException($e);
            return;
        }
    }
}
