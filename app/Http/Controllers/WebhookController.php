<?php

namespace App\Http\Controllers;

use App\Jobs\HubSpot\CreateOrUpdateDealNameAndEmail;
use App\Models\Integration\HubSpot;
use App\Models\Integration\Xero;
use App\Models\Deal;
use App\Jobs\HubSpot\CreateUniqueDealName;
use App\Jobs\HubSpot\RemoveJobs;
use App\Jobs\HubSpot\SyncDealJobs;
use App\Jobs\HubSpot\UpdateDealAccountHold;
use App\Jobs\HubSpot\UpdateDealDescription;
use App\Jobs\HubSpot\UpdateJobPrefixOnDealNameChange;
use App\Jobs\Xero\CreateSyncXeroCompany;
use App\Jobs\Xero\CreateSyncXeroContact;
use App\Jobs\Xero\CreateSyncXeroInvoice;
use App\Jobs\Xero\DispatchEvents;
use App\Jobs\Xero\RemoveInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    const CONTACT_CREATION = 'contact.creation';
    const CONTACT_PROPERTY_CHANGE = 'contact.propertyChange';
    const CONTACT_DELETION = 'contact.deletion';
    const DEAL_CREATION = 'deal.creation';
    const DEAL_PROPERTY_CHANGE = 'deal.propertyChange';
    const DEAL_DELETION = 'deal.deletion';
    const COMPANY_CREATION = 'company.creation';
    const COMPANY_PROTERTY_CHANGE = 'company.propertyChange';

    public function handleHubspotWebhook(Request $request) {
        Log::info("WebhookController@handleHubspotWebhook", ["req"=>['ip' => $request->ip(), 'user'=>'hubspot_webhooks']]);
        try {
            $events = json_decode($request->getContent());
            foreach ($events as $event) {

                $integration = HubSpot::where(['platform' => 'HUBSPOT', 'platform_account_id'=>$event->portalId, 'integration_status'=>'Connected'])->first();
                if (!$integration) {
                    Log::warning("WebhookController@handleHubspotWebhook - HubSpot integration is not connected for ".$event->portalId, ["event"=> $event, "req"=>['ip' => $request->ip(), 'user'=>'hubspot_webhooks']]);
                    continue;
                }

                Log::info("WebhookController@handleHubspotWebhook - {$event->subscriptionType}", ["event"=> $event, "req"=>['ip' => $request->ip(), 'user'=>'hubspot_webhooks']]);

                switch ($event->subscriptionType) {
                    case self::CONTACT_CREATION:
                        CreateOrUpdateDealNameAndEmail::dispatch($event);
                        CreateSyncXeroContact::dispatch($event);
                        break;
                    case self::CONTACT_DELETION:
                        break;
                    case self::CONTACT_PROPERTY_CHANGE:
                        if ($event->propertyName === 'email' || $event->propertyName === 'firstname' || $event->propertyName === 'lastname') {
                            CreateOrUpdateDealNameAndEmail::dispatch($event);
                        }
                        CreateOrUpdateDealNameAndEmail::dispatch($event);
                        CreateSyncXeroContact::dispatch($event);
                        break;
                    case self::DEAL_CREATION:
                        CreateUniqueDealName::dispatch($event);
                        break;
                    case self::DEAL_DELETION:
                        Bus::chain([
                            new RemoveInvoice($event),
                            new RemoveJobs($event)
                        ])->dispatch();
                        break;
                    case self::DEAL_PROPERTY_CHANGE:
                        if ($event->propertyName === "dealstage" && $event->propertyValue === env('SALES_ORDERED_DELETED_STAGE', '15930848')) {
                            RemoveInvoice::dispatch($event);
                            break;
                        } else if ($event->propertyName === "dealstage" && (Deal::where('hs_deal_id', $event->objectId)->exists() || $event->propertyValue === env('SALES_ORDER_STAGE_ID'))) {
                            Bus::chain([
                                new SyncDealJobs($event),
                                new CreateSyncXeroInvoice($event)
                            ])->dispatch();
                        } else if (($event->propertyName === "dealstage" && $event->propertyValue === env('SALES_ORDER_STAGE_ID')) || Deal::where('hs_deal_id', $event->objectId)->exists()) {
                            //Check if the deal is already synced or if it has just been moved to the Sales Order stage
                            SyncDealJobs::dispatch($event);
                        }

                        if ($event->propertyName === "dealname" && Deal::where('hs_deal_id', $event->objectId)->exists()) {
                            UpdateJobPrefixOnDealNameChange::dispatch($event);
                        }

                        if ($event->propertyName === "account_hold" && Deal::where('hs_deal_id', $event->objectId)->exists()) {
                            UpdateDealAccountHold::dispatch($event);
                        }

                        UpdateDealDescription::dispatch($event);
                        break;
                    case self::COMPANY_CREATION:
                        CreateSyncXeroCompany::dispatch($event);
                        break;
                    case self::COMPANY_PROTERTY_CHANGE:
                        CreateSyncXeroCompany::dispatch($event);
                        break;
                    default:
                        break;
                }

            }
        } catch(\Exception $e) {
            Log::error("WebhookController@handleHubspotWebhook - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>'hubspot_webhooks'],
            ]);
            \Sentry\captureException($e);
            return response()->json([], 400);
        }
    }

    public function handleXeroWebhook(Request $request) {
        Log::info("WebhookController@handleXeroWebhook - process", ["req"=>['ip' => $request->ip(), 'user'=>'xero_webhooks']]);

        $integration = Xero::where(['platform' => 'XERO', 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("WebhookController@handleXeroWebhook - Xero integration is not connected", ["req"=>['ip' => $request->ip(), 'user'=>'xero_webhooks']]);
            return response('');
        }

        DispatchEvents::dispatch($request->events);

        return response('');
    }
}
