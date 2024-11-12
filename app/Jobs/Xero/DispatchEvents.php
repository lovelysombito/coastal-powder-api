<?php

namespace App\Jobs\Xero;

use App\Jobs\Xero\UpdateHubSpotInvoiceStatus;
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

class DispatchEvents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $events;

    public function __construct($events)
    {
        $this->events = $events;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Xero/DispatchEvents@handle", ['user'=>'xero_webhooks']);

        $integration = Xero::where(['platform' => 'XERO', 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("Xero/DispatchEvents@handle - Xero integration is not connected", ['user'=>'xero_webhooks']);
            return;
        }

        $events = $this->events;

        foreach ($events as $event) {
            if ($event['tenantId'] !== $integration->platform_account_id) {
                Log::info("Xero/DispatchEvents@handle - Xero is not connected to tenant ".$event['tenantId'], ["event"=> $event, "integration"=>$integration]);
                continue;
            }
            if($event['eventCategory'] === "INVOICE") {

                // When an invoice is updated, refresh the cached version
                if (Cache::has('xero_invoice_'.$event['resourceId'])) {
                    $integration->getInvoiceById($event['resourceId'], false);
                }

                if (Invoice::where('xero_invoice_id', $event['resourceId'])->first()) {
                    Log::info("Xero/DispatchEvents@handle - Invoice ".$event['resourceId'] . " dispatched to update HubSpot", ["event"=> $event, "integration"=>$integration]);
                    UpdateHubspotInvoiceStatus::dispatch($event);
                } else {
                    Log::info("Xero/DispatchEvents@handle - Invoice ".$event['resourceId'] . " is not in sync", ["event"=> $event, "integration"=>$integration]);
                }
            }
        }
    }
}
