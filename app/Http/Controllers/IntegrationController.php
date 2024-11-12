<?php

namespace App\Http\Controllers;

use App\Jobs\HubSpot\SyncDealLineItems;
use App\Jobs\Xero\CreateSyncXeroInvoice;
use App\Models\Colours;
use App\Models\Deal;
use App\Models\Integration;
use App\Models\Job;
use App\Models\LineItems;
use App\Models\Integration\HubSpot;
use App\Models\Integration\Xero;
use App\Models\Products;
use App\Models\Xero\Contact;
use App\Models\Xero\Invoice;
use Carbon\Carbon;
use Exception;
use Firebase\JWT\JWT;
use GuzzleHttp\ClientInterface;
use HubSpot\Client\Auth\OAuth\ApiException;
use HubSpot\Factory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Expr\Cast\Object_;

class IntegrationController extends Controller
{

    private static ?ClientInterface $clientInterface = null;


    public static function setClientInterface(?ClientInterface $clientInterface = null)
    {
        self::$clientInterface = $clientInterface;
    }
    
    public function getAllIntegration(Request $request) {
        try {
            $integrations = Integration::with('user')->get();

            if (count($integrations) < 1) {
                return response()->json(['message'=>'No integrations currently available'], 400);
            }

            return response()->json([$integrations], 200);
        } catch (Exception $e) {
            Log::error("IntegrationController@getAllIntegegration - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }

    public function hubspotCallback(Request $request) {
        Log::info("IntegrationController@hubspotCallback", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        $request->validate([
            'code' => 'required|string',
        ]);
            
        $code = $request->code;

        if(!$code) {
            Log::warning("IntegrationController@hubspotCallback - No code provided in the request", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
            return response('Invalid request', 401);
        }

        if ($request->query('error')) {
            Log::error("IntegrationController@hubspotCallback - HubSpot Auth Error - ".$request->query('error'), ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
            return response()->json(['message'=>'Invalid request'], 400);
        }

        $integration = HubSpot::where(['platform' => 'HUBSPOT'])->first();
        if (!$integration) {
            Log::warning("IntegrationController@hubspotCallback - HubSpot integegration is unavailable", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
            return response()->json(['message'=>'The HubSpot integration is currently unavailable'], 400);
        }

        try {

            $tokens = Factory::create(self::$clientInterface)->auth()->oAuth()->tokensApi()->createToken(
                'authorization_code',
                $code,
                env('HUBSPOT_REDIRECT_URL'),
                env('HUBSPOT_CLIENT_ID'),
                env('HUBSPOT_CLIENT_SECRET')
            );

            $owner_details = $integration->getResourceOwner($tokens->getAccessToken());

            $integration->platform_account_id = $owner_details->hub_id;
            $integration->platform_access_token = $tokens->getAccessToken();
            $integration->platform_refresh_token = $tokens->getRefreshToken();
            $integration->platform_user_id = $owner_details->user_id;
            $integration->platform_access_token_expires_in = time() + ($tokens->getExpiresIn() * 0.95);
            $integration->connected_user_id = $request->user()->user_id;
            $integration->integration_status = "Connected";

            $integration->save();

            $integration->initialiseSchedulingProperties();

            $integrations = Integration::with('user')->get();

            Log::info("IntegrationController@hubspotCallback - Successfully connected to HubSpot", ["integration" => ["id"=>$integration->integration_id], "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
            return response()->json(['message'=>'Successfully connected to HubSpot', "integrations"=>$integrations], 200);
        } catch (ApiException $e) {

            $response = json_decode($e->getResponseBody());
            
            if ($response->status === "BAD_AUTH_CODE") {
                Log::warning("IntegrationController@hubspotCallback - HubSpot API Exception - Invalid authorisation code provided", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
                return response()->json(['message'=>'Invalid authorisisation code, please try again'], 400);
            }

            Log::error("IntegrationController@hubspotCallback - HubSpot API Exception: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        } catch (Exception $e) { 
            Log::error("IntegrationController@hubspotCallback - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }


    public function removeHubSpotIntegration(Request $request) {

        Log::info("IntegrationController@removeHubSpotIntegration", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $integration = HubSpot::where('platform', 'HUBSPOT')->first();
            if (!$integration) {
                Log::warning("IntegrationController@removeHubSpotIntegration - HubSpot integegration is unavailable", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
                return response()->json(['message'=>'The HubSpot integration is currently unavailable'], 400);
            }

            $integration->integration_status = "Awaiting Connection";
            $integration->connected_user_id = null;
            $integration->platform_user_id = null;
            $integration->platform_access_token = null;
            $integration->platform_refresh_token = null;
            $integration->platform_account_id = null;
            $integration->save();

            Log::info("IntegrationController@removeHubSpotIntegration - Successfully disconnected HubSpot Integration", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

            $integrations = Integration::with('user')->get();
            return response()->json(['message'=>'Successfully disconnected HubSpot', "integrations"=>$integrations], 200);
        } catch (Exception $e) { 
            Log::error("IntegrationController@removeHubSpotIntegration - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }

    public function getDealLineItemEditorCrmCard(Request $request) {
        try {

            $integration = Integration::where(['platform' => 'HUBSPOT', 'platform_account_id' => $request->portalId, 'integration_status' => 'Connected'])->first();
            if (!$integration) {
                Log::warning("IntegrationController@getDealLineItemEditorCrmCard - HubSpot integegration is unavailable", ["req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards']]);
                return response()->json(['message'=>'The HubSpot integration is currently unavailable'], 400);
            }

            $secret = env('APP_KEY');
            $payload = [
                'userId' => $integration->platform_user_id,
                'accountId' => $integration->platform_account_id,
            ];

            $payload['exp'] = time() + (60 * 60);

            if (!isset($payload['iat'])) {
                $payload['iat'] = time();
            }

            $signature = JWT::encode($payload, $secret, 'HS256');

            $url = env('APP_URL')."/crm-cards/deals/line-item-editor?dealId={$request->associatedObjectId}&userId={$integration->platform_user_id}&signature={$signature}";

            return response()->json([
                "primaryAction" => [
                    "type" => "IFRAME",
                    "width" => 1500,
                    "height" => 1500,
                    "uri" => $url,
                    "label" => "Edit Items"
                ]
            ]);
        } catch (Exception $e) { 
            Log::error("IntegrationController@getDealLineItemEditorCrmCard - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards'],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }

    public function viewCRMCardLineItemEditor(Request $request) {
        if (!$request->userId) {
            Log::warning("IntegrationController@viewCRMCardLineItemEditor - No userId provided", ["req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards']]);
            return response()->json(['message' => 'Invalid request'], 400);
        }

        if (!$request->signature) {
            Log::warning("IntegrationController@viewCRMCardLineItemEditor - No signature provided", ["req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards']]);
            return response()->json(['message' => 'Invalid request'], 400);
        }

        return view('crm-cards.deal-line-item-editor');
    }

    public function getDealLineItems(Request $request, $dealId) {
        try {
            $integration = HubSpot::where(['platform' => 'HUBSPOT', 'platform_user_id' => $request->header('crmcard-user-id'), 'integration_status' => 'Connected'])->first();
            if (!$integration) {
                Log::warning("IntegrationController@getDealLineItems - HubSpot integegration is unavailable", ["req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards']]);
                return response()->json(['message'=>'The HubSpot integration is currently unavailable'], 400);
            }

            $items = $integration->getDealLineItemAssociations($dealId);
            $items = array_map(function($item) {
                return $item->getId();
            }, $items->getResults());

            $lineItems = HubSpot::mapLineItemsToArrayProperties($integration->getBatchLineItems($items));

            return response()->json(['lineitems' => $lineItems, 'length' => count($lineItems)], 200);
        } catch (Exception $e) { 
            Log::error("IntegrationController@getDealLineItems - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards'],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }

    public function getProducts(Request $request) {
        try {
            $products = Products::all();

            return response()->json(['products' => $products], 200);
        } catch (Exception $e) { 
            Log::error("IntegrationController@getProducts - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards'],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }

    public function getColours(Request $request) {
        try {
            $colours = Colours::all();

            return response()->json(['colours' => $colours], 200);
        } catch (Exception $e) { 
            Log::error("IntegrationController@getColours - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards'],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }

    public function updateDealLineItems(Request $request, $dealId) {
        $request->validate([
            'lineitems' => 'present|array',
        ]);

        $lineItems = $request->lineitems;

        // TODO

        try {
            $integration = HubSpot::where(['platform' => 'HUBSPOT', 'platform_user_id' => $request->header('crmcard-user-id'), 'integration_status' => 'Connected'])->first();
            
            // First, we must check the existing HS line items and remove any that no longer exist
            $hsLineItems = $integration->getDealLineItemAssociations($dealId);
            $hsLineItems = array_map(function($item) {
                return $item->getId();
            }, $hsLineItems->getResults());

            $removeItems = array_diff($hsLineItems, array_column($lineItems, 'line_item_id'));

            if ($removeItems) {
                // Removed deleted line items from the deal
                $integration->deleteBatchLineItems($removeItems);

                LineItems::whereIn('hs_deal_lineitem_id', $removeItems)->delete();
            }
            
            $existingLineItems = [];
            $newLineItems = [];

            $dealAmount = 0;
            $colours = [];

            foreach($lineItems as $lineItem) {

                $formattedLineItem = [
                    'name' => $lineItem['product'],
                    'product_id' => $lineItem['product_id'],
                    'hs_position_on_quote' => $lineItem['hs_position_on_quote'],
                    'description' => $lineItem['description'],
                    'quantity' => $lineItem['quantity'],
                    'price' => $lineItem['price'],
                    'colour' => $lineItem['colour'],
                    'unit_of_measurement' => $lineItem['unit_of_measurement'],
                    'material' => $lineItem['material'],
                    'treatment' => $lineItem['treatment'],
                    'powder_coat_line' => $lineItem['coating_line'],
                ];

                if (isset($lineItem['line_item_id'])) {
                    $formattedLineItem['line_item_id'] = $lineItem['line_item_id'];
                    $existingLineItems[] = $formattedLineItem;
                } else {
                    $newLineItems[] = $formattedLineItem;
                }

                // Collect information for deal description/properties
                try {
                    $dealAmount = $dealAmount + (floatval($lineItem['quantity']) * floatval($lineItem['price']));
                } catch (Exception $e) {} // Exception is incase quantity or price is not a number or null just in case
                if (!in_array($lineItem['colour'], $colours)) {
                    array_push($colours, $lineItem['colour']);
                }
            }

            if ($newLineItems) {
                $integration->createBatchLineItems($dealId, $newLineItems);
            }

            if ($existingLineItems) {
                $integration->updateBatchLineItems($existingLineItems);
            }

            $integration->updateDeal($dealId, [
                'job_colours' => implode(' ', $colours),
                'amount' => $dealAmount,
            ]);

            if (Deal::where('hs_deal_id', $dealId)->exists()) {
                SyncDealLineItems::dispatch($dealId);
                $hsEvent = new \stdClass;
                $hsEvent->subscriptionType = "lineitemeditor.updated";
                $hsEvent->portalId = $integration->platform_account_id;
                $hsEvent->objectId = $dealId;
                CreateSyncXeroInvoice::dispatch($hsEvent);
            }

            return response()->json(['message' => 'Line items successfully updated'], 200);
        } catch (Exception $e) { 
            Log::error("IntegrationController@updateDealLineItems - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards'],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }

    public function getTicketJobStatusCrmCard(Request $request) {
        try {
            $integration = Integration::where(['platform' => 'HUBSPOT', 'platform_account_id' => $request->portalId, 'integration_status' => 'Connected'])->first();
            if (!$integration) {
                Log::warning("IntegrationController@getCompanyXeroInvoicesCrmCard - HubSpot integegration is unavailable", ["req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards']]);
                return response()->json(['message'=>'The HubSpot integration is currently unavailable'], 400);
            }

            $job = Job::where('hs_ticket_id', $request->associatedObjectId)->first();
            if (!$job) {
                $response = [
                    "objectId" => $request->associatedObjectId,
                    "title" => 'No connected job found',
                ];
                return response()->json(['results'=>[$response]], 200);
            }

            $response = [
                "objectId" => $request->associatedObjectId,
                "title" => $job->job_prefix.' | '.$job->job_number,
                "chemstrip_status" => ucwords($job->chem_status) ?? '',
                "chemstrip_required" => $job->chem_bay_required ?? 'no',
                "chemstrip_date" => $job->chem_date ?? '1990-01-01',
                "treatment_status" => ucwords($job->treatment_status) ?? '',
                "treatment_required" => $job->treatment_bay_required ?? 'no',
                "treatment_date" => $job->treatment_date ?? '1990-01-01',
                "burn_status" => ucwords($job->burn_status) ?? '',
                "burn_required" => $job->burn_bay_required ?? 'no',
                "burn_date" => $job->burn_date ?? '1990-01-01',
                "blast_status" => ucwords($job->blast_status) ?? '',
                "blast_required" => ucwords($job->blast_bay) ?? 'no',
                "blast_date" => $job->blast_date ?? '1990-01-01',
                "powder_status" => ucwords($job->powder_status) ?? '',
                "powder_required" => ucwords($job->powder_bay_required) ?? 'no',
                "powder_date" => $job->powder_date ?? '1990-01-01',
            ];

            return response()->json(['results'=>[$response]]);

        } catch (Exception $e) { 
            Log::error("IntegrationController@getTicketJobStatusCrmCard - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards'],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }

    public function getCompanyXeroInvoicesCrmCard(Request $request) {
        try {
            $integration = Integration::where(['platform' => 'HUBSPOT', 'platform_account_id' => $request->portalId, 'integration_status' => 'Connected'])->first();
            if (!$integration) {
                Log::warning("IntegrationController@getCompanyXeroInvoicesCrmCard - HubSpot integegration is unavailable", ["req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards']]);
                return response()->json(['message'=>'The HubSpot integration is currently unavailable'], 400);
            }

            $company = Contact::where('hubspot_company_id', $request->associatedObjectId)->first();
            if (!$company) {
                $response = [
                    "objectId" => $request->associatedObjectId,
                    "title" => 'No connected company found',
                ];
                return response()->json(['results'=>[$response]], 200);
            }

            $xeroIntegration = Xero::where(['platform' => 'XERO', 'integration_status' => 'Connected'])->first();
            if (!$xeroIntegration) {
                Log::warning("IntegrationController@getCompanyXeroInvoicesCrmCard - Xero integegration is unavailable", ["req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards']]);
                return response()->json(['message'=>'The Xero integration is currently unavailable'], 400);
            }

            $xeroContact = $xeroIntegration->getContactById($company->xero_contact_id);
            $response = [];
            $contact = [
                "objectId" => $request->associatedObjectId,
                "title" => $xeroContact->getName(),
                'link' => "https://go.xero.com/Contacts/View/".$xeroContact->getContactId(),
            ];
            array_push($response, $contact);

            $invoices = $xeroIntegration->getInvoicesByContactId($company->xero_contact_id);
            foreach($invoices->getInvoices() as $i => $invoice) {
                $status = $invoice->getStatus();
                $dueDate = '1900-01-01';
                try {
                    $dueDate = new Carbon($invoice->getDueDateAsDate());
                    if ($invoice->getAmountDue() > 0 && $dueDate->isPast()) {
                        $status = 'OVERDUE';
                    }
                    $dueDate = $dueDate->toDateString();
                } catch (Exception $e) {}
                $link = "https://go.xero.com/AccountsReceivable/View.aspx?invoiceID=";
                if ($status == 'DRAFT') {
                    $link = "https://go.xero.com/AccountsReceivable/Edit.aspx?invoiceID=";
                }
                $invoiceResponse = [
                    "objectId" => $i,
                    "title" => $invoice->getInvoiceNumber(),
                    'link' => $link . $invoice->getInvoiceId(),
                    'invoice_status' => $status,
                    'due_date' => $dueDate,
                    'total' => [
                        'value'=> $invoice->getTotal(),
                        'currencyCode' => $invoice->getCurrencyCode(),
                    ],
                    'amount_paid' => [
                        'value'=> $invoice->getAmountPaid(),
                        'currencyCode' => $invoice->getCurrencyCode(),
                    ],
                ];

                if ($i <= 3) {
                    array_push($response, $invoiceResponse);
                }
            }

            return response()->json(['results'=>$response, 'totalCount'=>$i+1, 'allItemsLink'=>"https://go.xero.com/Contacts/View/".$xeroContact->getContactId(), 'itemLabel'=>'All Invoices']);

        } catch (Exception $e) { 
            Log::error("IntegrationController@getCompanyXeroInvoicesCrmCard - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards'],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }

    public function getDealTicketXeroInvoiceCrmCard(Request $request) {
        try {
            $integration = Integration::where(['platform' => 'HUBSPOT', 'platform_account_id' => $request->portalId, 'integration_status' => 'Connected'])->first();
            if (!$integration) {
                Log::warning("IntegrationController@getDealTicketXeroInvoiceCrmCard - HubSpot integegration is unavailable", ["req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards']]);
                return response()->json(['message'=>'The HubSpot integration is currently unavailable'], 400);
            }

            $invoice = null;
            if ($request->associatedObjectType === 'TICKET') {
                $job = Job::where('hs_ticket_id', $request->associatedObjectId)->first();
                if (!$job) {
                    $response = [
                        "objectId" => $request->associatedObjectId,
                        "title" => 'Ticket is not associated with Sync',
                    ];
                    return response()->json(['results'=>[$response]], 200);
                }

                $invoice = Invoice::where('hubspot_deal_id', $job->deal->hs_deal_id)->first();
            } else {
                $invoice = Invoice::where('hubspot_deal_id', $request->associatedObjectId)->first();
            }

            if (!$invoice) {
                $response = [
                    "objectId" => $request->associatedObjectId,
                    "title" => 'No synced invoice has been found',
                ];
                return response()->json(['results'=>[$response]], 200);
            }

            $xeroIntegration = Xero::where(['platform' => 'XERO', 'integration_status' => 'Connected'])->first();
            if (!$xeroIntegration) {
                Log::warning("IntegrationController@getDealTicketXeroInvoiceCrmCard - Xero integegration is unavailable", ["req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards']]);
                return response()->json(['message'=>'The Xero integration is currently unavailable'], 400);
            }

            $xeroInvoice = $xeroIntegration->getInvoiceById($invoice->xero_invoice_id);

            $status = $xeroInvoice->getStatus();
            $dueDate = '1900-01-01';
            try {
                $dueDate = new Carbon($xeroInvoice->getDueDateAsDate());
                if ($xeroInvoice->getAmountDue() > 0 && $dueDate->isPast()) {
                    $status = 'OVERDUE';
                }
                $dueDate = $dueDate->toDateString();
            } catch (Exception $e) {}
            $link = "https://go.xero.com/AccountsReceivable/View.aspx?invoiceID=";
            if ($status == 'DRAFT') {
                $link = "https://go.xero.com/AccountsReceivable/Edit.aspx?invoiceID=";
            }
            $response = [[
                "objectId" => 1,
                "title" => $xeroInvoice->getInvoiceNumber(),
                'link' => $link . $xeroInvoice->getInvoiceId(),
                'invoice_status' => $status,
                'due_date' => $dueDate,
                'total' => [
                    'value'=> $xeroInvoice->getTotal(),
                    'currencyCode' => $xeroInvoice->getCurrencyCode(),
                ],
                'amount_paid' => [
                    'value'=> $xeroInvoice->getAmountPaid(),
                    'currencyCode' => $xeroInvoice->getCurrencyCode(),
                ],
            ]];

            return response()->json(['results'=>$response]);

        } catch (Exception $e) { 
            Log::error("IntegrationController@getDealTicketXeroInvoiceCrmCard - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards'],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }

    }


    // Xero functions
    public function xeroCallback(Request $request) {
        Log::info("IntegrationController@xeroCallback", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        $request->validate([
            'code' => 'required|string',
        ]);
            
        $code = $request->code;

        if(!$code) {
            Log::warning("IntegrationController@xeroCallback - No code provided in the request", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
            return response('Invalid request', 401);
        }

        if ($request->query('error')) {
            Log::error("IntegrationController@xeroCallback - Xero Auth Error - ".$request->query('error'), ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
            return response()->json(['message'=>'Invalid request'], 400);
        }

        $integration = Xero::where(['platform' => 'XERO'])->first();
        if (!$integration) {
            Log::warning("IntegrationController@xeroCallback - Xero integegration is unavailable", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
            return response()->json(['message'=>'The Xero integration is currently unavailable'], 400);
        }

        try {

            $integration->getAccessToken($code, $request->user());

            $integrations = Integration::with('user')->get();

            Log::info("IntegrationController@xeroCallback - Successfully connected to Xero", ["integration" => ["id"=>$integration->integration_id], "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
            return response()->json(['message'=>'Successfully connected to Xero', "integrations"=>$integrations], 200);
        } catch (RequestException $e) {
            
            $response = $e->response;
            if ($response->status() === 400) {
                Log::warning("IntegrationController@xeroCallback - Xero API Exception - Invalid authorisation code provided", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
                return response()->json(['message'=>'Invalid authorisisation code, please try again'], 400);
            }

            $response = json_decode($e->getResponseBody());
            
            Log::error("IntegrationController@xeroCallback - Xero API Exception: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        } catch (Exception $e) { 
            Log::error("IntegrationController@xeroCallback; - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }

    public function removeXeroIntegration(Request $request) {

        Log::info("IntegrationController@removeXeroIntegration", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $integration = HubSpot::where('platform', 'XERO')->first();
            if (!$integration) {
                Log::warning("IntegrationController@removeXeroIntegration - Xero integration is unavailable", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
                return response()->json(['message'=>'The Xero integration is currently unavailable'], 400);
            }

            $integration->integration_status = "Awaiting Connection";
            $integration->connected_user_id = null;
            $integration->platform_user_id = null;
            $integration->platform_access_token = null;
            $integration->platform_refresh_token = null;
            $integration->platform_account_id = null;
            $integration->save();

            Log::info("IntegrationController@removeXeroIntegration - Successfully disconnected Xero Integration", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

            $integrations = Integration::with('user')->get();
            return response()->json(['message'=>'Successfully disconnected Xero', "integrations"=>$integrations], 200);
        } catch (Exception $e) { 
            Log::error("IntegrationController@removeXeroIntegration - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }
}
