<?php

namespace App\Models\Integration;

use App\Exceptions\Xero\ContactNotFoundException;
use App\Exceptions\Xero\MultipleContactsFoundException;
use App\Exceptions\Xero\UnauthorisedException;
use App\Models\Integration;
use App\Models\User;
use GuzzleHttp\ClientInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use XeroAPI\XeroPHP\Api\IdentityApi;
use XeroAPI\XeroPHP\Api\AccountingApi;
use XeroAPI\XeroPHP\ApiException;
use XeroAPI\XeroPHP\Configuration;
use XeroAPI\XeroPHP\JWTClaims;
use XeroAPI\XeroPHP\Models\Accounting\Contact;
use XeroAPI\XeroPHP\Models\Accounting\Contacts;
use XeroAPI\XeroPHP\Models\Accounting\Invoice;
use XeroAPI\XeroPHP\Models\Accounting\Invoices;

class Xero extends Integration
{
    use HasFactory, SoftDeletes;

    protected $SCOPES = 'offline_access openid profile email accounting.settings.read accounting.transactions accounting.contacts';
    protected $identityApi, $accountingApi;

    private static ?ClientInterface $clientInterface = null;

    public static function setClientInterface(?ClientInterface $clientInterface = null)
    {
        self::$clientInterface = $clientInterface;
    }

    public function initialise() {
        if ($this->integration_status === "Connected") {
            $this->refreshXeroAcessToken();
        }

        $config = Configuration::getDefaultConfiguration(self::$clientInterface)->setAccessToken($this->platform_access_token);
        $this->identityApi = new IdentityApi(self::$clientInterface, $config);
        $this->accountingApi = new AccountingApi(self::$clientInterface, $config);
    }

    public function refreshXeroAcessToken() {
        if (empty($this->platform_access_token)) {
            Log::warning('Xero.refreshXeroAcessToken - Xero has not been authorised');
            throw new UnauthorisedException("Xero has not been authorised");
        }

        // If token expire then generate new and  update into database
        if (time() > $this->platform_access_token_expires_in) {
            Log::info('Xero.refreshXeroAcessToken - Refresh access token', ["integration"=>$this]);
            $response = Http::asForm()->withHeaders([
                'authorization' => 'Basic ' . base64_encode(env('XERO_CLIENT_ID') . ':' . env('XERO_CLIENT_SECRET')),
            ])->post('https://identity.xero.com/connect/token', [
                'refresh_token' => $this->platform_refresh_token,
                'grant_type' => 'refresh_token',
            ])->json();
            Log::info('Xero.refreshXeroAcessToken - Update tokens', ["integration"=>$this]);
            
            $this->platform_access_token = $response['access_token'];
            $this->platform_refresh_token = $response['refresh_token'];
            $this->platform_access_token_expires_in = time() + ($response['expires_in'] * 0.95);
            $this->save();
        }
    }

    public function getAccessToken($code, User $user) {
        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Basic ' . base64_encode(env('XERO_CLIENT_ID') . ':' . env('XERO_CLIENT_SECRET')),
        ])->post('https://identity.xero.com/connect/token', [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => env('XERO_REDIRECT_URL'),
        ]);

        if (!$response->ok()) {
            Log::info('Xero.getAcessToken - An error occured retrieving the access token', ["integration"=>$this, 'user' => $user->user_id]);
            // throw new \XeroAPI\XeroPHP\ApiException('Invalid request');
            $response->throw();
        }

        $this->platform_access_token = $response['access_token'];
        $this->platform_refresh_token = $response['refresh_token'];
        $this->platform_access_token_expires_in = time() + ($response['expires_in'] * 0.95);
        $this->integration_status = "Connected";
        $this->connected_user_id = $user->user_id;
        $this->save();

        $this->initialise();

        $jwtClaims = new JWTClaims();
        $decodedAccessToken = $jwtClaims->decodeAccessToken($response['access_token']);

        $tenantId = $this->getTenantId($decodedAccessToken->getAuthenticationEventId());

        $this->platform_user_id = $decodedAccessToken->getXeroUserId();
        $this->platform_account_id = $tenantId;
        
        $this->save();
    }

    public function getTenantId($authEventId = null) {
        $connections = $this->identityApi->getConnections($authEventId);
        if (!isset($connections[0])) {
            
            $connections = $this->identityApi->getConnections();
            return $connections[0]->getTenantId();
        }
        return $connections[0]->getTenantId();
    }

    public function getContactByName($name) {
        $this->initialise();

        // if (Cache::has('xero_contacts')) {
        //     Log::info('Xero.getContactByName - Retrieving contacts from cache', ["integration"=>$this]);
        //     $contacts = Cache::get('xero_contacts');
        // } else {
        //     $contacts = $this->accountingApi->getContacts($this->platform_account_id);
        //     Cache::put('xero_contacts', $contacts, now()->addMinutes(60));
        // }
        $contacts = $this->accountingApi->getContacts($this->platform_account_id);

        foreach($contacts as $contact) {
            if (strtolower($contact->getName()) == strtolower($name)) {
                return $contact;
            }
        }

        throw new ContactNotFoundException("No contact found with the name $name");
    }

    public function getContactById($contactId) {
        $this->initialise();

        if (Cache::has('xero_contact_'.$contactId)) {
            Log::info('Xero.getContactById - Retrieving contact from cache', ["integration"=>$this, 'contactId' => $contactId]);
            $contact = [Cache::get('xero_contact_'.$contactId)];
        } else {
            $contact = $this->accountingApi->getContact($this->platform_account_id, $contactId);
            Cache::put('xero_contact_'.$contactId, $contact, now()->addMinutes(60));
        }

        if(count($contact) == 0) {
            throw new ContactNotFoundException("No contact is found with contact id: " , $contactId);
        }
        if(count($contact) > 1) {
            throw new MultipleContactsFoundException("Multiple contacts found with contact id: " , $contactId);
        }
        return $contact[0];
    }

    public function createContact(string $companyName, string $firstName=null, string $lastName=null, string $email=null) {
        $this->initialise();

        $contact = new Contact;
        $contact->setName($companyName)->setFirstName($firstName)->setLastName($lastName)->setEmailAddress($email);

        $contacts = new Contacts();
        $contacts->setContacts([$contact]);

        $xeroContacts = $this->accountingApi->createContacts($this->platform_account_id, $contacts, true);
        $newContact = $xeroContacts->getContacts()[0];
        if ($newContact->getValidationErrors()) {
            throw new ApiException($newContact->getValidationErrors()->getMessage());
        }

        Cache::put('xero_contact_'.$newContact->getContactId(), $newContact, now()->addMinutes(60));

        return $newContact;
    }

    public function updateContact(Contact $contact) {
        $this->initialise();

        if (Cache::has('xero_contact_'.$contact->getContactId())) {
            $existingContact = Cache::get('xero_contact_'.$contact->getContactId());
            if ($existingContact == $contact) {
                Log::info('Xero.updateContact - Contact is in cache and already up to date', ["integration"=>$this, 'contactId' => $contact->getContactId()]);
                return $existingContact;
            }
        }

        $updatedContact = $this->accountingApi->updateContact($this->platform_account_id, $contact->getContactID(), $contact)[0];
        if ($updatedContact->getValidationErrors()) {
            throw new ApiException($updatedContact->getValidationErrors()->getMessage());
        }

        Cache::put('xero_contact_'.$updatedContact->getContactId(), $updatedContact, now()->addMinutes(60));

        return $updatedContact;
    }

    public function getInvoiceById($invoiceId, $cache = true) {
        $this->initialise();

        if ($cache && Cache::has('xero_invoice_'.$invoiceId)) {
            Log::info('Xero.getInvoiceById - Retrieving invoice from cache', ["integration"=>$this, 'invoiceId' => $invoiceId]);
            $invoice = Cache::get('xero_invoice_'.$invoiceId);
            return $invoice;
        } else {
            $invoice = $this->accountingApi->getInvoice($this->platform_account_id, $invoiceId);
            if(count($invoice) == 0) {
                throw new ContactNotFoundException("No invoice is found with invoice id: " , $invoiceId);
            }
            if(count($invoice) > 1) {
                throw new MultipleContactsFoundException("Multiple invoices found with invoice id: ". $invoiceId);
            }
            Log::info('Xero.getInvoiceById - Storing invoice to cache '.$invoiceId, ["integration"=>$this, 'invoiceId' => $invoiceId]);
            Cache::put('xero_invoice_'.$invoiceId, $invoice[0], now()->addMinutes(60));
        }

        return $invoice[0];
    }

    public function getInvoicesByContactId($contactId) {
        $this->initialise();

        if (Cache::has('xero_invoices_contact_'.$contactId)) {
            Log::info('Xero.getInvoicesByContactId - Retrieving contact invoices from cache', ["integration"=>$this, 'contactId' => $contactId]);
            return Cache::get('xero_invoices_contact_'.$contactId);
        }

        $invoices = $this->accountingApi->getInvoices($this->platform_account_id, null, null, null, null, null, [$contactId], ["AUTHORISED","DRAFT","SUBMITTED","PAID"]);

        Cache::put('xero_invoices_contact_'.$contactId, $invoices, now()->addMinutes(60));

        return $invoices;
    }

    public function createInvoices(Invoices $invoice) {
        $this->initialise();

        $xeroInvoices = $this->accountingApi->createInvoices($this->platform_account_id, $invoice, true);


        foreach($xeroInvoices as $invoice) {
            Cache::put('xero_invoice_'.$invoice->getInvoiceId(), $invoice, now()->addMinutes(60));

            $contactInvoices = Cache::get('xero_invoices_contact_'.$invoice->getContact()->getContactId());
            $contactInvoices[] = $invoice;
            Cache::put('xero_invoices_contact_'.$invoice->getContact()->getContactId(), $contactInvoices, now()->addMinutes(60));
        }

        return $xeroInvoices->getInvoices();
    }

    public function getInvoicesModifiedSince($date) {
        $this->initialise();

        $invoices = $this->accountingApi->getInvoices($this->platform_account_id, $date);
        foreach($invoices as $invoice) {
            Cache::put('xero_invoice_'.$invoice->getInvoiceId(), $invoice, now()->addMinutes(60));
        }

        return $invoices;
    }

    public function updateInvoice(Invoice $invoice) {
        $this->initialise();

        if (Cache::has('xero_invoice_'.$invoice->getInvoiceId())) {
            $existingInvoice = Cache::get('xero_invoice_'.$invoice->getInvoiceId());
            if ($this->compareInvoices($existingInvoice, $invoice)) {
                Log::info('Xero.updateInvoice - Invoice is in cache and already up to date', ["integration"=>$this, 'invoiceId' => $invoice->getInvoiceId()]);
                // No need to update
                return $invoice;
            }
        }

        $invoice->setSentToContact(null);
        
        $updatedInvoice = $this->accountingApi->updateInvoice($this->platform_account_id, $invoice->getInvoiceID(), $invoice);
        Cache::put('xero_invoice_'.$invoice->getInvoiceId(), $updatedInvoice[0], now()->addMinutes(60));

        return $updatedInvoice[0];

    }




    private function compareInvoices($invoice1, $invoice2) {

        if ($invoice1->getInvoiceID() != $invoice2->getInvoiceID()) {
            return false;
        }

        if ($invoice1->getContact()->getContactID() != $invoice2->getContact()->getContactID()) {
            return false;
        }

        if ($invoice1->getDueDate() != $invoice2->getDueDate()) {
            return false;
        }

        if ($invoice1->getLineAmountTypes() != $invoice2->getLineAmountTypes()) {
            return false;
        }

        if ($invoice1->getInvoiceNumber() != $invoice2->getInvoiceNumber()) {
            return false;
        }

        if ($invoice1->getReference() != $invoice2->getReference()) {
            return false;
        }

        if ($invoice1->getTotalTax() != $invoice2->getTotalTax()) {
            return false;
        }

        if ($invoice1->getTotal() != $invoice2->getTotal()) {
            return false;
        }

        if ($invoice1->getSubTotal() != $invoice2->getSubTotal()) {
            return false;
        }

        if ($invoice1->getAmountDue() != $invoice2->getAmountDue()) {
            return false;
        }

        if ($invoice1->getAmountPaid() != $invoice2->getAmountPaid()) {
            return false;
        }

        foreach ($invoice1->getLineItems() as $index => $lineItem) {
            if (!array_key_exists($index, $invoice2->getLineItems())) {
                return false;
            }

            $invoice2LineItem = $invoice2->getLineItems()[$index];
            if ($lineItem->getDescription() != $invoice2LineItem->getDescription()) {
                return false;
            }

            if ($lineItem->getQuantity() != $invoice2LineItem->getQuantity()) {
                return false;
            }

            if ($lineItem->getUnitAmount() != $invoice2LineItem->getUnitAmount()) {
                return false;
            }

            if ($lineItem->getAccountCode() != $invoice2LineItem->getAccountCode()) {
                return false;
            }
        }

        foreach ($invoice2->getLineItems() as $index => $lineItem) {

            if (!array_key_exists($index, $invoice1->getLineItems())) {
                return false;
            }

            $invoice1LineItem = $invoice1->getLineItems()[$index];
            if ($lineItem->getDescription() != $invoice1LineItem->getDescription()) {
                return false;
            }

            if ($lineItem->getQuantity() != $invoice1LineItem->getQuantity()) {
                return false;
            }

            if ($lineItem->getUnitAmount() != $invoice1LineItem->getUnitAmount()) {
                return false;
            }

            if ($lineItem->getAccountCode() != $invoice1LineItem->getAccountCode()) {
                return false;
            }
        }

        return true;
    }
}
