<?php

namespace App\Listeners;

use App\Events\JobRemoved;
use App\Models\Integration\HubSpot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RemoveJobTicket implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 0;
    public $maxExceptions = 3;


    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function retryUntil() {
        return now()->addHours(18);
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\JobRemoved  $event
     * @return void
     */
    public function handle(JobRemoved $event)
    {
        $job = $event->job;

        Log::info("RemoveJobTicket@handle - {$job->job_id}", ["job"=> $event->job]);

        $integration = HubSpot::where(['platform' => 'HUBSPOT', 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("RemoveJobTicket@handle - HubSpot integration is not connected for ".$event->portalId, ["job"=> $event->job, "integration"=>$integration]);
            return;
        }

        if ($hubspotRetryTimestamp = Cache::get('hubspot-api-retry-timeout', null)) {
            Log::notice("RemoveJobTicket@handle - HubSpot API rate limit activated, retrying in ".$hubspotRetryTimestamp - time()." seconds", ["integration"=>$integration, "job"=> $event->job]);
            $this->release($hubspotRetryTimestamp - time());
            return;
        }

        try {

            $ticket = $integration->deleteTicket($job->hs_ticket_id);

            Log::info("RemoveJobTicket@handle - Deleted ticket {$job->hs_ticket_id} for job {$job->job_id}", ["job"=> $event->job]);
        } catch (\HubSpot\Client\Crm\Tickets\ApiException $e) {
            if ($e->getCode() === 429) {
                $retryAfter = 10;
                Log::notice("RemoveJobTicket@handle - HubSpot API rate limit exceeded, retrying in {$retryAfter} seconds", ["integration"=>$integration, "job"=> $event->job]);
                Cache::put('hubspot-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("RemoveJobTicket@handle - HubSpot API exception - ".$e->getMessage(), ["integration"=>$integration, "job"=> $event->job, "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        } catch (\Exception $e) {
            Log::error("RemoveJobTicket@handle - Something has gone wrong: ".$e->getMessage(), [
                "integration"=>$integration, "job"=> $event->job,
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                'user'=>'hubspot_webhooks'
            ]);
            \Sentry\captureException($e);
            return;
        }
    }
}
