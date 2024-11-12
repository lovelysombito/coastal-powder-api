<?php

namespace App\Jobs\Xero;

use App\Mail\XeroEmailTimeOut;
use App\Models\Deal;
use App\Models\Integration\HubSpot;
use App\Models\Integration\Xero;
use App\Models\Xero\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RemoveInvoice implements ShouldQueue
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
        Log::info("RemoveInvoice@handle - {$event->subscriptionType}", ["event"=> $event]);

        $integration = Xero::where(['platform' => 'XERO', 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("RemoveInvoice@handle - Xero integration is not connected", ["event"=> $event, "integration"=>$integration]);
            return;
        }

        $hubspotIntegration = HubSpot::where(['platform' => 'HUBSPOT', 'platform_account_id'=>$event->portalId, 'integration_status'=>'Connected'])->first();
        if (!$hubspotIntegration) {
            Log::warning("RemoveInvoice@handle - HubSpot integration is not connected for ".$event->portalId, ["event"=> $event, "integration"=>$integration]);
            return;
        }

        if ($hubspotRetryTimestamp = Cache::get('hubspot-api-retry-timeout', null)) {
            Log::notice("RemoveInvoice@handle - HubSpot API rate limit activated, retrying in ".$hubspotRetryTimestamp - time()." seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
            $this->release($hubspotRetryTimestamp - time());
            return;
        }

        if ($xeroRetryTimestamp = Cache::get('xero-api-retry-timeout', null)) {
            if ($xeroRetryTimestamp - time() > 43100) {
                $xeroRetryTimestamp = time() + 43100;
            }
            Log::notice("RemoveInvoice@handle - Xero API rate limit activated, retrying in ".$xeroRetryTimestamp - time()." seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
            $this->release($xeroRetryTimestamp - time());
            $data = ['message' => "RemoveInvoice@handle - Xero API rate limit activated, retrying in ".$xeroRetryTimestamp - time()." seconds"];
            foreach (['mitchell@upstreamtech.io', 'ella@upstreamtech.io'] as $recipient) {
                Mail::to($recipient)->send(new XeroEmailTimeOut($data));
            }
            return;
        }

        try {

            $deal = Deal::where('hs_deal_id', $event->objectId)->first();
            if (!$deal) {
                Log::info("RemoveInvoice@handle - Deal ".$event->objectId . " is not in sync", ["event"=> $event, "deal"=> $deal, "integration"=>$integration]);
                return;
            }

            $invoice = Invoice::where('hubspot_deal_id', $event->objectId)->first();
            if (!$invoice) {
                Log::info("RemoveInvoice@handle - Deal ".$event->objectId . " has no invoice in sync", ["event"=> $event, "deal"=> $deal, "integration"=>$integration]);
                return;
            }

            Log::info("RemoveInvoice@handle - Retrieve invoice ".$invoice->xero_invoice_id, ["event"=> $event, "deal"=> $deal, "integration"=>$integration]);
            $xeroInvoice = $integration->getInvoiceById($invoice->xero_invoice_id);

            if ($xeroInvoice->getStatus() === 'AUTHORISED') {
                $xeroInvoice->setStatus('VOIDED');
            } else {
                $xeroInvoice->setStatus('DELETED');
            }
            
            Log::info("RemoveInvoice@handle - Update invoice ".$invoice->xero_invoice_id, ["status"=>$xeroInvoice->getStatus(), "event"=> $event, "deal"=> $deal, "integration"=>$integration]);
            $integration->updateInvoice($xeroInvoice);
            
            return;

        } catch (\XeroAPI\XeroPHP\ApiException $e) {

            if ($e->getCode() === 429) {
                $responseHeaders = $e->getResponseHeaders();
                $retryAfter = $responseHeaders['Retry-After'][0];
                if ($retryAfter > 43100) {
                    $retryAfter = 43100;
                }
                Log::notice("RemoveInvoice@handle - Xero API rate limit exceeded, retrying in {$retryAfter} seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
                Cache::put('xero-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("RemoveInvoice@handle - Xero API exception - ".$e->getMessage(), ["event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId, "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        } catch (\Exception $e) {
            Log::error("RemoveInvoice@handle - Something has gone wrong: ".$e->getMessage(), [
                "event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId,
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                'user'=>'hubspot_webhooks'
            ]);
            \Sentry\captureException($e);
            return;
        }
    }
}
