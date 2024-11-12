<?php

namespace App\Jobs\Xero;

use App\Mail\XeroEmailTimeOut;
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

class UpdateHubSpotInvoiceStatus implements ShouldQueue
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
        Log::info("UpdateHubSpotInvoiceStatusInvoice@handle - ".$event['resourceId'], ["event"=> $event]);

        $integration = Xero::where(['platform' => 'XERO', 'platform_account_id' => $event['tenantId'], 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("UpdateHubSpotInvoiceStatusInvoice@handle - Xero integration is not connected", ["event"=> $event, "integration"=>$integration]);
            return;
        }

        $hubspotIntegration = HubSpot::where(['platform' => 'HUBSPOT', 'integration_status'=>'Connected'])->first();
        if (!$hubspotIntegration) {
            Log::warning("UpdateHubSpotInvoiceStatusInvoice@handle - HubSpot integration is not connected", ["event"=> $event, "integration"=>$integration]);
            return;
        }

        if ($hubspotRetryTimestamp = Cache::get('hubspot-api-retry-timeout', null)) {
            Log::notice("UpdateHubSpotInvoiceStatusInvoice@handle - HubSpot API rate limit activated, retrying in ".$hubspotRetryTimestamp - time()." seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "invoiceId"=>$event['resourceId']]);
            $this->release($hubspotRetryTimestamp - time());
            return;
        }

        if ($xeroRetryTimestamp = Cache::get('xero-api-retry-timeout', null)) {
            if ($xeroRetryTimestamp - time() > 43100) {
                $xeroRetryTimestamp = time() + 43100;
            }
            Log::notice("UpdateHubSpotInvoiceStatusInvoice@handle - Xero API rate limit activated, retrying in ".$xeroRetryTimestamp - time()." seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "invoiceId"=>$event['resourceId']]);
            $this->release($xeroRetryTimestamp - time());
            $data = ['message' => "UpdateHubSpotInvoiceStatusInvoice@handle - Xero API rate limit activated, retrying in ".$xeroRetryTimestamp - time()." seconds"];
            foreach (['mitchell@upstreamtech.io', 'ella@upstreamtech.io'] as $recipient) {
                Mail::to($recipient)->send(new XeroEmailTimeOut($data));
            }
            return;
        }

        try {

            if (!$invoice = Invoice::where('xero_invoice_id', $event['resourceId'])->first()) {
                Log::info("UpdateHubSpotInvoiceStatusInvoice@handle - Invoice ".$event['resourceId'] . " is not in sync", ["event"=> $event, "integration"=>$integration, "invoiceId"=>$event['resourceId']]);
                return;
            }

            $xeroInvoice = $integration->getInvoiceById($invoice->xero_invoice_id);

            $status = $xeroInvoice->getStatus();
            $paid = 'Awaiting Payment';
            if ($xeroInvoice->getAmountDue() == 0 && $xeroInvoice->getStatus() != "DRAFT") {
                $paid = "Paid";
            } else if ($xeroInvoice->getAmountDue() < $xeroInvoice->getTotal()) {
                $paid = "Partially Paid";
                if ($xeroInvoice->getDueDate()) {
                    $dueDate = $xeroInvoice->getDueDate()->format('Y-m-d');
                    if ($dueDate < date('Y-m-d')) {
                        $paid = "Overdue | Partially Paid";
                    }
                }
            } else if ($xeroInvoice->getDueDate()) {
                $dueDate = $xeroInvoice->getDueDateAsDate()->format('Y-m-d');
                if ($dueDate < date('Y-m-d')) {
                    $paid = "Overdue";
                }
            }

            if ($status == 'DRAFT')  {
                $paid = "DRAFT";
            }

            Log::info("UpdateHubSpotInvoiceStatusInvoice@handle - Update linked deal {$invoice->hubspot_deal_id}", ["event"=> $event, "integration"=>$integration, "invoiceId"=>$event['resourceId']]);
            $hubspotIntegration->updateDeal($invoice->hubspot_deal_id, ['xero_invoice_number' => $xeroInvoice->getInvoiceNumber(), 'xero_invoice_status'=>$status, 'invoice_approved'=>$paid, 'xero_invoice_due_date' => $xeroInvoice->getDueDate() ? $xeroInvoice->getDueDateAsDate()->format('Y-m-d') : '', 'xero_invoice_total_amount_due' => $xeroInvoice->getAmountDue(), 'xero_invoice_total_amount_paid' => $xeroInvoice->getTotal() - $xeroInvoice->getAmountDue()]);
        } catch (\XeroAPI\XeroPHP\ApiException $e) {

            if ($e->getCode() === 429) {
                $responseHeaders = $e->getResponseHeaders();
                $retryAfter = $responseHeaders['Retry-After'][0];
                if ($retryAfter > 43100) {
                    $retryAfter = 43100;
                }
                Log::notice("UpdateHubSpotInvoiceStatusInvoice@handle - Xero API rate limit exceeded, retrying in {$retryAfter} seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "invoiceId"=>$event['resourceId']]);
                Cache::put('xero-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("UpdateHubSpotInvoiceStatusInvoice@handle - Xero API exception - ".$e->getMessage(), ["event"=> $event, "integration"=>$integration, "invoiceId"=>$event['resourceId'], "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        } catch (\HubSpot\Client\Crm\Deals\ApiException $e) {
            if ($e->getCode() === 429) {
                $retryAfter = 10;
                Log::notice("UpdateHubSpotInvoiceStatusInvoice@handle - HubSpot API rate limit exceeded, retrying in {$retryAfter} seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "invoiceId"=>$event['resourceId']]);
                Cache::put('hubspot-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("UpdateHubSpotInvoiceStatusInvoice@handle - HubSpot API exception - ".$e->getMessage(), ["event"=> $event, "integration"=>$integration, "invoiceId"=>$event['resourceId'], "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        
        } catch (\Exception $e) {
            Log::error("UpdateHubSpotInvoiceStatusInvoice@handle - Something has gone wrong: ".$e->getMessage(), [
                "event"=> $event, "integration"=>$integration, "invoiceId"=>$event['resourceId'],
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                'user'=>'hubspot_webhooks'
            ]);
            \Sentry\captureException($e);
            return;
        }

    }
}
