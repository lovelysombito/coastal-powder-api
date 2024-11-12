<?php

namespace App\Jobs\HubSpot;

use App\Models\Comment;
use App\Models\Deal;
use App\Models\Integration\HubSpot;
use App\Models\JobScheduling;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AddDealNoteReply implements ShouldQueue
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
        $data = $this->event;

        Log::info("AddDealNoteReply@handle - ", ["event"=> $data]);

        $integration = HubSpot::where(['platform' => 'HUBSPOT', 'connected_user_id'=>$data['userId'], 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("AddDealNoteReply@handle - HubSpot integration is not connected for ".$data['userId'], ["event"=> $data, "integration"=>$integration]);
            return;
        }

        if ($hubspotRetryTimestamp = Cache::get('hubspot-api-retry-timeout', null)) {
            Log::notice("AddDealNoteReply@handle - HubSpot API rate limit activated, retrying in ".$hubspotRetryTimestamp - time()." seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $data, "integration"=>$integration]);
            $this->release($hubspotRetryTimestamp - time());
            return;
        }

        try {

            $integration = HubSpot::where(['platform' => 'HUBSPOT', 'connected_user_id'=>$data['userId'], 'integration_status'=>'Connected'])->first();
            $job = JobScheduling::where('job_id', $data['objectId'])->first();

            $deal = Deal::find($job->deal_id);
            $hubspotDeal = $integration->getDeal($deal->hs_deal_id);
            if (!$hubspotDeal) {
                Log::error("AddDealNoteReply@handle - HubSpot deal not found for id ".$data['userId'], ["event"=> $data, "integration"=>$integration]);
                return;
            }

            $comment = Comment::find($data['parentId']);
            Log::info("commentId - $comment->hs_object_id");
            if ($comment) {
                $note = $integration->updateNote($comment->hs_object_id, ['hs_note_body' => $data['comments']]);
                $comment->update(['hs_object_id' => $note['id']]);
            }

            Log::warning("AddDealNoteReply@handle - Linked job prefixes have been successfully updated", ["event"=> $data, "integration"=>$integration]);

            return;
        } catch (\HubSpot\Client\Crm\Companies\ApiException | \HubSpot\Client\Crm\Contacts\ApiException | \HubSpot\Client\Crm\Deals\ApiException | \HubSpot\Client\Crm\LineItems\ApiException $e) {
            if ($e->getCode() === 429) {
                $retryAfter = 10;
                Log::notice("AddDealNoteReply@handle - HubSpot API rate limit exceeded, retrying in {$retryAfter} seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $data, "integration"=>$integration]);
                Cache::put('hubspot-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("AddDealNoteReply@handle - HubSpot API exception - ".$e->getMessage(), ["event"=> $data, "integration"=>$integration, "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        } catch (\Exception $e) {
            Log::error("AddDealNoteReply@handle - Something has gone wrong: ".$e->getMessage(), [
                "event"=> $data, "integration"=>$integration,
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                'user'=>'hubspot_webhooks'
            ]);
            \Sentry\captureException($e);
            return;
        }
    
    }
}
