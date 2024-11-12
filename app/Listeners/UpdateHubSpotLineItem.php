<?php

namespace App\Listeners;

use App\Events\LineItemUpdated;
use App\Models\Integration\HubSpot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdateHubSpotLineItem implements ShouldQueue
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
     * @param  object  $event
     * @return void
     */
    public function handle(LineItemUpdated $event)
    {
        $lineitem = $event->lineitem;

        Log::info("UpdateHubSpotLineItem@handle - {$lineitem->line_item_id}", ["lineitem"=> $event->lineitem]);

        $integration = HubSpot::where(['platform' => 'HUBSPOT', 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("UpdateHubSpotLineItem@handle - HubSpot integration is not connected", ["lineitem"=> $event->lineitem, "integration"=>$integration]);
            return;
        }

        if ($hubspotRetryTimestamp = Cache::get('hubspot-api-retry-timeout', null)) {
            Log::notice("UpdateHubSpotLineItem@handle - HubSpot API rate limit activated, retrying in ".$hubspotRetryTimestamp - time()." seconds", ["integration"=>$integration, "lineitem"=> $event->lineitem]);
            $this->release($hubspotRetryTimestamp - time());
            return;
        }

        try {

            $integration->updateLineItem($lineitem->hs_deal_lineitem_id, HubSpot::castLineItemToLineItemProperties($lineitem));

        } catch (\HubSpot\Client\Crm\LineItems\ApiException $e) {
            if ($e->getCode() === 429) {
                $retryAfter = 10;
                Log::notice("UpdateHubSpotLineItem@handle - HubSpot API rate limit exceeded, retrying in {$retryAfter} seconds", ["integration"=>$integration, "lineitem"=> $event->lineitem]);
                Cache::put('hubspot-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("UpdateHubSpotLineItem@handle- HubSpot API exception - ".$e->getMessage(), ["integration"=>$integration, "lineitem"=> $event->lineitem, "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        } catch (\Exception $e) {
            Log::error("UpdateHubSpotLineItem@handle - Something has gone wrong: ".$e->getMessage(), [
                "integration"=>$integration, "lineitem"=> $event->lineitem,
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                'user'=>'hubspot_webhooks'
            ]);
            \Sentry\captureException($e);
            return;
        }
    }
}
