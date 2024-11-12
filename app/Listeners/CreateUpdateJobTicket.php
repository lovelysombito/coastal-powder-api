<?php

namespace App\Listeners;

use App\Events\JobSaved;
use App\Models\Integration\HubSpot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CreateUpdateJobTicket implements ShouldQueue
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
     * @param  \App\Events\JobSaved  $event
     * @return void
     */
    public function handle(JobSaved $event)
    {

        $job = $event->job;

        Log::info("CreateUpdateJobTicket@handle - {$job->job_id}", ["job"=> $event->job]);

        $integration = HubSpot::where(['platform' => 'HUBSPOT', 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("CreateUpdateJobTicket@handle - HubSpot integration is not connected", ["job"=> $event->job, "integration"=>$integration]);
            return;
        }

        if ($hubspotRetryTimestamp = Cache::get('hubspot-api-retry-timeout', null)) {
            Log::notice("CreateUpdateJobTicket@handle - HubSpot API rate limit activated, retrying in ".$hubspotRetryTimestamp - time()." seconds", ["integration"=>$integration, "job"=> $event->job]);
            $this->release($hubspotRetryTimestamp - time());
            return;
        }

        try {

            // Is the ticket already created? Or do we need to create one
            if ($job->hs_ticket_id) {
                $ticket = $integration->updateTicket($job->hs_ticket_id, HubSpot::castJobToTicketProperties($job));
                Log::info("CreateUpdateJobTicket@handle - Updated ticket for job {$job->job_id}", ["job"=> $event->job]);
            } else {
                $ticket = $integration->createTicket(HubSpot::castJobToTicketProperties($job));
                $job->hs_ticket_id = $ticket->getId();
                $job->saveQuietly();

                $res = $integration->associateTicketToDeal($ticket->getId(), $job->deal->hs_deal_id);

                Log::info("CreateUpdateJobTicket@handle - Created ticket {$job->hs_ticket_id} for job {$job->job_id}", ["job"=> $event->job]);
            }
        } catch (\HubSpot\Client\Crm\Tickets\ApiException $e) {
            if ($e->getCode() === 429) {
                $retryAfter = 10;
                Log::notice("CreateUpdateJobTicket@handle - HubSpot API rate limit exceeded, retrying in {$retryAfter} seconds", ["integration"=>$integration, "job"=> $event->job]);
                Cache::put('hubspot-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("CreateUpdateJobTicket@handle - HubSpot API exception - ".$e->getMessage(), ["integration"=>$integration, "job"=> $event->job, "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        } catch (\Exception $e) {
            Log::error("CreateUpdateJobTicket@handle - Something has gone wrong: ".$e->getMessage(), [
                "integration"=>$integration, "job"=> $event->job,
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                'user'=>'hubspot_webhooks'
            ]);
            \Sentry\captureException($e);
            return;
        }
    }
}
