<?php

namespace App\Jobs\HubSpot;

use App\Models\Deal;
use App\Models\Integration\HubSpot;
use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RemoveJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $event;
    public $maxExceptions = 3;

    public function __construct($event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $event = $this->event;

        Log::info("RemoveJobs@handle - {$event->subscriptionType}", ["event"=> $event]);

        $integration = HubSpot::where(['platform' => 'HUBSPOT', 'platform_account_id'=>$event->portalId, 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("RemoveJobs@handle - HubSpot integration is not connected for ".$event->portalId, ["event"=> $event, "integration"=>$integration]);
            return;
        }

        try {

            $deal = Deal::where('hs_deal_id', $this->event->objectId)->first();
            if (!$deal) {
                Log::info("RemoveJobs@handle - Deal ".$event->objectId . " is not in sync", ["event"=> $event, "deal"=> $deal, "integration"=>$integration]);
                return;
            }

            Log::info("RemoveJobs@handle - Remove jobs and line items for deal ".$event->objectId, ["event"=> $event, "deal"=> $deal, "integration"=>$integration]);
            foreach($deal->jobs as $job) {
                foreach ($job->lines as $line) {
                    Log::info("RemoveJobs@handle - Remove jobs line ".$line->line_item_id, ["event"=> $event, "deal"=> $deal, "integration"=>$integration]);
                    $line->delete();
                }
                Log::info("RemoveJobs@handle - Remove job".$job->job_id, ["event"=> $event, "deal"=> $deal, "integration"=>$integration]);
                $job->delete();
            }

            Log::info("RemoveJobs@handle - Removing HubSpot Sync for deal ".$deal->deal_id . " with HS Deal: ".$this->event->objectId, ["event"=> $event, "deal"=> $deal, "integration"=>$integration]);
            $deal->delete();

            return;
        } catch (\Exception $e) {
            Log::error("RemoveJobs@handle - Something has gone wrong: ".$e->getMessage(), [
                "event"=> $event, "integration"=>$integration, "deal"=> $deal,
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                'user'=>'hubspot_webhooks'
            ]);
            \Sentry\captureException($e);
            return;
        }
    }
}
