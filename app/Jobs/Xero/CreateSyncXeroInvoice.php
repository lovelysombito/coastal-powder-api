<?php

namespace App\Jobs\Xero;

use App\Exceptions\Xero\ContactNotFoundException;
use App\Mail\XeroEmailTimeOut;
use App\Models\Deal;
use App\Models\Integration\HubSpot;
use App\Models\Integration\Xero;
use App\Models\Xero\Contact;
use App\Models\Xero\Invoice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use XeroAPI\XeroPHP\Models\Accounting\Invoice as XeroInvoice;
use XeroAPI\XeroPHP\Models\Accounting\Invoices;
use XeroAPI\XeroPHP\Models\Accounting\LineItem;
use XeroAPI\XeroPHP\Models\Accounting\LineAmountTypes;

class CreateSyncXeroInvoice implements ShouldQueue
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
        Log::info("CreateSyncXeroInvoice@handle - {$event->subscriptionType}", ["event"=> $event]);


        $integration = Xero::where(['platform' => 'XERO', 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("CreateSyncXeroInvoice@handle - Xero integration is not connected", ["event"=> $event, "integration"=>$integration]);
            return;
        }

        $hubspotIntegration = HubSpot::where(['platform' => 'HUBSPOT', 'platform_account_id'=>$event->portalId, 'integration_status'=>'Connected'])->first();
        if (!$hubspotIntegration) {
            Log::warning("CreateSyncXeroInvoice@handle - HubSpot integration is not connected for ".$event->portalId, ["event"=> $event, "integration"=>$integration]);
            return;
        }

        if ($hubspotRetryTimestamp = Cache::get('hubspot-api-retry-timeout', null)) {
            Log::notice("CreateSyncXeroInvoice@handle - HubSpot API rate limit activated, retrying in ".$hubspotRetryTimestamp - time()." seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
            $this->release($hubspotRetryTimestamp - time());
            return;
        }

        if ($xeroRetryTimestamp = Cache::get('xero-api-retry-timeout', null)) {
            if ($xeroRetryTimestamp - time() > 43100) {
                $xeroRetryTimestamp = time() + 43100;
            }
            Log::notice("CreateSyncXeroInvoice@handle - Xero API rate limit activated, retrying in ".$xeroRetryTimestamp - time()." seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
            $this->release($xeroRetryTimestamp - time());
            $data = ['message' => "CreateSyncXeroInvoice@handle - Xero API rate limit activated, retrying in ".$xeroRetryTimestamp - time()." seconds"];
            foreach (['mitchell@upstreamtech.io', 'ella@upstreamtech.io'] as $recipient) {
                Mail::to($recipient)->send(new XeroEmailTimeOut($data));
            }
            return;
        }

        try {

            $deal = Deal::where('hs_deal_id', $event->objectId)->first();
            if (!$deal) {
                Log::info("CreateSyncXeroInvoice@handle - Deal ".$event->objectId . " is not in sync", ["event"=> $event, "integration"=>$integration]);
                return;
            }

            $hubspotDeal = $hubspotIntegration->getDeal($this->event->objectId);
            if (!$hubspotDeal) {
                Log::error("CreateSyncXeroInvoice@handle - HubSpot deal not found for id ".$event->objectId, ["event"=> $event, "integration"=>$integration]);
                return;
            }

            $companies = $hubspotIntegration->getDealCompanyAssociations($this->event->objectId);
            $hubspotCompany = null;
            if ($companies->getResults()) {
                $companyId = $companies->getResults()[0]->getId();
                $hubspotCompany = $hubspotIntegration->getCompany($companyId);

            } else {
                Log::debug("CreateSyncXeroInvoice@handle - HubSpot company not found for id ".$event->objectId, ["event"=> $event, "integration"=>$integration]);
                // TODO Alert user that no company is associated with this deal
                return;
            }

            $contacts = $hubspotIntegration->getDealContactAssociations($this->event->objectId);
            $contactRecords = [];

            if ($contacts->getResults()) {
                $contactIds = array_map(function($contact) {
                    return $contact->getId();
                }, $contacts->getResults());

                $contactRecords = $hubspotIntegration->getBatchContacts($contactIds);
            }
            
            $names = '';
            if ($contactRecords && $contactRecords->getResults()) {
                $contactNames = [];
                foreach($contactRecords->getResults() as $key => $contact) {
                    $name = "";
                    if (isset($contact->getProperties()['firstname'])) {
                        $name = $contact->getProperties()['firstname'];
                    }
                    if (isset($contact->getProperties()['lastname'])) {
                        $name = $name . " " .$contact->getProperties()['lastname'];
                    }
                    if (!$name) {
                        $name = $contact->getProperties()['email'];
                    }
                    array_push($contactNames, $name);
                }

                $names = implode('|', $contactNames);
                if ($names) {
                    $names = " | " . $names;
                }
            }

            $items = $hubspotIntegration->getDealLineItemAssociations($event->objectId);
            $items = array_map(function($item) {
                return $item->getId();
            }, $items->getResults());

            $lineItems = HubSpot::mapLineItemsToArrayProperties($hubspotIntegration->getBatchLineItems($items));
            usort($lineItems, function ($a, $b) {
                return $a['hs_position_on_quote'] <=> $b['hs_position_on_quote'];
            });
            
            $invoiceLineItems = [];
            foreach($lineItems as $lineitem) {
                $xeroLineItem = new LineItem();
                $xeroLineItem->setDescription($lineitem['product'] . " ". $lineitem['description'] . " " . $lineitem['unit_of_measurement'] . ' ' . $lineitem['colour']);
                $xeroLineItem->setQuantity($lineitem['quantity'] ? $lineitem['quantity'] : 1);
                $xeroLineItem->setUnitAmount($lineitem['price'] ? $lineitem['price'] : 0);
                $xeroLineItem->setAccountCode(env('XERO_ACCOUNT_CODE'));
                $invoiceLineItems[] = $xeroLineItem;
            }

            $reference = implode(' | ', [$hubspotDeal->getProperties()['po_number'], $hubspotDeal->getProperties()['client_job_no_']]) . $names;

            $xeroContact = null;
            $contact = null;


            if (!$contact = Contact::where('hubspot_company_id', $this->event->objectId)->first()) {
                try {
                    $xeroContact = $integration->getContactByName($hubspotCompany->getProperties()['name']);

                    Log::info("CreateSyncXeroInvoice@handle - Existing Xero contact found for ".$hubspotCompany->getProperties()['name'].", syncing with ".$xeroContact->getContactId(), ["event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
                    if (!Contact::where('xero_contact_id', $xeroContact->getContactId())->first()) {
                        $contact = Contact::create(['xero_contact_id' => $xeroContact->getContactId(), 'hubspot_company_id' => $this->event->objectId]);
                    } else {
                        Log::info("CreateSyncXeroInvoice@handle - ".$hubspotCompany->getProperties()['name'].", is already in sync with Xero", ["event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
                    }
                } catch(ContactNotFoundException $e) {
                    Log::info("CreateSyncXeroInvoice@handle - Xero contact not found for ".$hubspotCompany->getProperties()['name'].", creating a new contact", ["event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
                    $xeroContact = $integration->createContact($hubspotCompany->getProperties()['name']);
                    $contact = Contact::create(['xero_contact_id' => $xeroContact->getContactId(), 'hubspot_company_id' => $this->event->objectId]);
                }
            } else {
                Log::info("CreateSyncXeroInvoice@handle - HubSpot company is already in sync with Xero contact ".$contact->xero_contact_id, ["event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
                $xeroContact = $integration->getContactById($contact->xero_contact_id);

                $xeroContact->setName($hubspotCompany->getProperties()['name']);

                $integration->updateContact($xeroContact);
                Log::info("CreateSyncXeroInvoice@handle - Contact ".$xeroContact->getContactId() . " successfully updated", ["event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);

            }

            if ($invoice = Invoice::where('hubspot_deal_id', $event->objectId)->first()) {
                Log::info("CreateSyncXeroInvoice@handle - HubSpot deal is already in sync with Xero invoice ".$invoice->xero_invoice_id, ["event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);

                // Log::notice("CreateSyncXeroInvoice@handle - Sleep to restrict duplicate line items", ["event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
                $xeroInvoice = $integration->getInvoiceById($invoice->xero_invoice_id);

                if ($xeroInvoice->getStatus() === 'AUTHORISED' || $xeroInvoice->getStatus() === 'PAID') {
                    Log::info("CreateSyncXeroInvoice@handle - Xero invoice ".$invoice->xero_invoice_id." is already authorised, unable to edit", ["event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
                    return;
                }

                $xeroInvoice->setContact($xeroContact);
                $xeroInvoice->setLineItems($invoiceLineItems);
                $xeroInvoice->setReference($reference);
                $xeroInvoice->setLineAmountTypes(LineAmountTypes::EXCLUSIVE);

                $xeroInvoice = $integration->updateInvoice($xeroInvoice);

                Log::info("CreateSyncXeroInvoice@handle - Invoice ".$xeroInvoice->getInvoiceId() . " updated for deal ".$event->objectId, ["event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId, "lineitems"=>$invoiceLineItems]);
                $res = $hubspotIntegration->updateDeal($this->event->objectId, ['dealname'=>$xeroInvoice->getInvoiceNumber() .' | '. $hubspotCompany->getProperties()['name'] . $names]);

            } else {
                Log::info("CreateSyncXeroInvoice@handle - Creating a new invoice for deal ".$event->objectId, ["event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
                $xeroInvoice = new XeroInvoice();
                $xeroInvoice->setContact($xeroContact);
                $xeroInvoice->setLineItems($invoiceLineItems);
                $xeroInvoice->setReference($reference);
                $xeroInvoice->setLineAmountTypes(LineAmountTypes::EXCLUSIVE);
                $xeroInvoice->setType(XeroInvoice::TYPE_ACCREC);
                $xeroInvoice->setDate(Carbon::now()->toDateString());
                
                $invoices = new Invoices();
                $invoices->setInvoices([$xeroInvoice]);

                $invoice = $integration->createInvoices($invoices)[0];
                
                Invoice::create(['xero_invoice_id' => $invoice->getInvoiceId(), 'hubspot_deal_id' => $this->event->objectId]);
                Log::info("CreateSyncXeroInvoice@handle - Invoice ".$invoice->getInvoiceId() . " created for deal ".$event->objectId, ["event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId, "lineitems"=>$invoiceLineItems]);
                $res = $hubspotIntegration->updateDeal($this->event->objectId, ['dealname'=>$invoice->getInvoiceNumber() .' | '. $hubspotCompany->getProperties()['name'] . $names]);
            }
        } catch (\XeroAPI\XeroPHP\ApiException $e) {

            if ($e->getCode() === 429) {
                $responseHeaders = $e->getResponseHeaders();
                $retryAfter = $responseHeaders['Retry-After'][0];
                if ($retryAfter > 43100) {
                    $retryAfter = 43100;
                }
                Log::notice("CreateSyncXeroInvoice@handle - Xero API rate limit exceeded, retrying in {$retryAfter} seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
                Cache::put('xero-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("CreateSyncXeroInvoice@handle - Xero API exception - ".$e->getMessage(), ["event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId, "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        } catch (\HubSpot\Client\Crm\Companies\ApiException | \HubSpot\Client\Crm\Contacts\ApiException | \HubSpot\Client\Crm\Deals\ApiException | \HubSpot\Client\Crm\LineItems\ApiException $e) {
            if ($e->getCode() === 429) {
                $retryAfter = 10;
                Log::notice("CreateSyncXeroInvoice@handle - HubSpot API rate limit exceeded, retrying in {$retryAfter} seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId]);
                Cache::put('hubspot-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("CreateSyncXeroInvoice@handle - HubSpot API exception - ".$e->getMessage(), ["event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId, "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        
        } catch (\Exception $e) {
            Log::error("CreateSyncXeroInvoice@handle - Something has gone wrong: ".$e->getMessage(), [
                "event"=> $event, "integration"=>$integration, "dealId"=>$this->event->objectId,
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                'user'=>'hubspot_webhooks'
            ]);
            \Sentry\captureException($e);
            return;
        }
    }
}
