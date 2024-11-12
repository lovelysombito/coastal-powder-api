<?php

namespace App\Console\Commands\Scripts;

use App\Jobs\Xero\UpdateHubSpotInvoiceStatus;
use App\Models\Integration\Xero;
use App\Models\Xero\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TriggerUpdateHubSpotDealFromInvoiceUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xero:trigger-update-hubspot-deal-from-invoice {datefrom}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieved invoices updated within a certain date range and dispatches them to update HubSpot deals';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        Log::info("TriggerUpdateHubSpotDealFromInvoiceUpdates@handle");

        $integration = Xero::where(['platform' => 'XERO', 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("Xero/DispatchEvents@handle - Xero integration is not connected", ['user'=>'xero_webhooks']);
            return;
        }

        Log::info("TriggerUpdateHubSpotDealFromInvoiceUpdates@handle - Retrieve invoices");
        $invoices = $integration->getInvoicesModifiedSince(new \DateTime($this->argument('datefrom')));
        $now = new \DateTime();
        foreach($invoices as $invoice) {
            Log::info("TriggerUpdateHubSpotDealFromInvoiceUpdates@handle - Dispatching invoice: ".$invoice->getInvoiceNumber()." - ".$invoice->getInvoiceId());

            $event = [
                "resourceUrl" => "https://api.xero.com/api.xro/2.0/Invoices/".$invoice->getInvoiceId(),
                "resourceId" => $invoice->getInvoiceId(),
                "tenantId" => $integration->platform_account_id,
                "tenantType" => "ORGANISATION",
                "eventCategory" => "INVOICE",
                "eventType" => "COMMAND_UPDATE",
                "eventDateUtc" => $now->format('Y-m-d H:i:s'),
            ];

            UpdateHubSpotInvoiceStatus::dispatch($event);
        }

        return 0;
    }
}
