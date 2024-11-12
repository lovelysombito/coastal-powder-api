<?php

namespace App\Models\Integration;

use App\Exceptions\HubSpot\UnauthorisedException;
use App\Models\Integration;
use App\Models\Products;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use HubSpot\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HubSpot extends Integration
{
    use HasFactory, SoftDeletes;

    protected $SCOPES = 'crm.objects.companies.read crm.objects.contacts.read crm.objects.deals.read crm.objects.line_items.read crm.objects.deals.write crm.objects.owners.read contacts oauth tickets e-commerce';
    protected $factory;

    private static ?ClientInterface $clientInterface = null;

    public static function setClientInterface(?ClientInterface $clientInterface = null)
    {
        self::$clientInterface = $clientInterface;
    }

    public function initialise() {
        if ($this->integration_status === "Connected") {
            $this->refreshHubspotAcessToken();
        }

        $this->factory = Factory::createWithAccessToken($this->platform_access_token, self::$clientInterface);
    }

    public function refreshHubspotAcessToken() {
        if (empty($this->platform_access_token)) {
            Log::warning('Hubspot.refreshAndGetAccessToken - HubSpot has not been authorised');
            throw new UnauthorisedException("HubSpot has not been authorised");
        }

        // If token expire then generate new and  update into database
        if (time() > $this->platform_access_token_expires_in) {
            Log::info('Hubspot.refreshAndGetAccessToken - Refresh access token', ["integration"=>$this]);
            $tokens = Factory::create()->auth()->oAuth()->tokensApi()->createToken(
                'refresh_token',
                null,
                env('HUBSPOT_REDIRECT_URL'),
                env('HUBSPOT_CLIENT_ID'),
                env('HUBSPOT_CLIENT_SECRET'),
                $this->platform_refresh_token
            );
            Log::info('Hubspot.refreshAndGetAccessToken - Update tokens', ["integration"=>$this]);
            
            $this->platform_access_token = $tokens->getAccessToken();
            $this->platform_refresh_token = $tokens->getRefreshToken();
            $this->platform_access_token_expires_in = time() + ($tokens->getExpiresIn() * 0.95);
            $this->save();
        }
    }

    public function getResourceOwner($accessToken) {
        $response = Http::get("https://api.hubapi.com/oauth/v1/access-tokens/{$accessToken}");
        return $response->object();
    }

    /**
     * HubSpot Company Requests
     * 
     */

    public function getCompany($companyId) {
        $this->initialise();

        $company = $this->factory->crm()->companies()->basicApi()->getById($companyId);

        return $company;
    }

    public function getCompanyContactAssociations($companyId) {
        $this->initialise();

        $companyAssociations = $this->factory->crm()->companies()->associationsApi()->getAll($companyId, self::CONTACTS);

        return $companyAssociations;
    }

    /**
     * HubSpot Contact Requests
     */

    public function getBatchContacts($ids) {
        $this->initialise();

        $contactIds = array_map(function($id) {
            return new \HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectId(["id"=>$id]);
        }, $ids);

        $BatchReadInputSimplePublicObjectId = new \HubSpot\Client\Crm\Contacts\Model\BatchReadInputSimplePublicObjectId(['properties' => ["firstname", "lastname", "email", "contact_type", "include_xero_emails"], 'inputs' => $contactIds]);

        $contacts = $this->factory->crm()->contacts()->batchApi()->read($BatchReadInputSimplePublicObjectId);
        return $contacts;
    }

    public function getContact($id) {
        $this->initialise();

        $contact = $this->factory->crm()->contacts()->basicApi()->getById($id, [

        ]);
        return $contact;
    }

    public function getContactCompanyAssociations($contactId) {
        $this->initialise();

        $contactAssociations = $this->factory->crm()->contacts()->associationsApi()->getAll($contactId, self::COMPANIES);

        return $contactAssociations;
    }

    public function getContactDealAssociations($contactId) {
        $this->initialise();

        $contactAssociations = $this->factory->crm()->contacts()->associationsApi()->getAll($contactId, self::DEALS);

        return $contactAssociations;
    }

     /**
      * HubSpot Deal Requests

      */
    public function getDeal($dealId) {
        $this->initialise();

        $deal = $this->factory->crm()->deals()->basicApi()->getById($dealId, [
            "client_job_no_",
            "customer_delivery_docket",
            "delivery_address",
            "drop_off_zone",
            "due_date",
            "paid_upfront",
            "picking_slip",
            "po_link",
            "po_number",
            "hs_priority",
            "xero_invoice_number",
            "xero_invoice_status",
            "xero_invoice_due_date",
            "xero_invoice_total_amount_due",
            "xero_invoice_total_amount_overdue",
            "xero_invoice_total_amount_paid",
            "job_colours",
            "dealname",
            "dealstage"
        ]);

        return $deal;
    }

    public function updateDeal($dealId, $deal) {
        $this->initialise();

        $SimplePublicObjectInput = new \HubSpot\Client\Crm\Deals\Model\SimplePublicObjectInput(["properties" => $deal]);
        $deal = $this->factory->crm()->deals()->basicApi()->update($dealId, $SimplePublicObjectInput);

        return $deal;
    }

    public function getDealCompanyAssociations($dealId) {
        $this->initialise();

        $dealAssociations = $this->factory->crm()->deals()->associationsApi()->getAll($dealId, self::COMPANIES);

        return $dealAssociations;
    }

    public function getDealContactAssociations($dealId) {
        $this->initialise();

        $dealAssociations = $this->factory->crm()->deals()->associationsApi()->getAll($dealId, self::CONTACTS);

        return $dealAssociations;
    }

    public function getDealLineItemAssociations($dealId) {
        $this->initialise();

        $dealAssociations = $this->factory->crm()->deals()->associationsApi()->getAll($dealId, self::LINEITEMS);
        return $dealAssociations;
    }

    public function getBatchLineItems($ids) {
        $this->initialise();

        $lineitemIds = array_map(function($id) {
            return new \HubSpot\Client\Crm\LineItems\Model\SimplePublicObjectId(["id"=>$id]);
        }, $ids);

        $BatchReadInputSimplePublicObjectId = new \HubSpot\Client\Crm\LineItems\Model\BatchReadInputSimplePublicObjectId(['properties' => [
            'product_id',
            'name',
            'price',
            'quantity',
            'description',
            'colour',
            'material',
            'treatment',
            'powder_coat_line',
            'unit_of_measurement',
            'hs_position_on_quote'
        ], 'inputs' => $lineitemIds]);

        $lineitems = $this->factory->crm()->lineItems()->batchApi()->read($BatchReadInputSimplePublicObjectId);
        return $lineitems;
    }

    public function createBatchLineItems($dealId, $lineItems) {
        $this->initialise();

        $lineitemIds = array_map(function($lineitem) {
            return new \HubSpot\Client\Crm\LineItems\Model\SimplePublicObjectInput(["properties"=>$lineitem]);
        }, $lineItems);

        $BatchCreateInputSimplePublicObjectId = new \HubSpot\Client\Crm\LineItems\Model\BatchInputSimplePublicObjectInput(['inputs' => $lineitemIds]);
        $lineitems = $this->factory->crm()->lineItems()->batchApi()->create($BatchCreateInputSimplePublicObjectId);
        


        $associations = array_map(function($lineitem) use ($dealId) {
            return new \HubSpot\Client\Crm\Associations\Model\PublicAssociation([
                "from" => $dealId,
                "to" => $lineitem->getId(),
                "type" => "DEAL_TO_LINE_ITEM",
            ]);
        }, $lineitems->getResults());

        $BatchInputPublicAssociation = new \HubSpot\Client\Crm\Associations\Model\BatchInputPublicAssociation(['inputs' => $associations]);
        $response = $this->factory->crm()->associations()->batchApi()->create("DEAL", "LINE_ITEM", $BatchInputPublicAssociation);

        return $response;
    }

    public function updateBatchLineItems($lineItems) {
        $this->initialise();
        
        $lineitemIds = array_map(function($lineitem) {
            return new \HubSpot\Client\Crm\LineItems\Model\SimplePublicObjectBatchInput(["id" => $lineitem['line_item_id'], "properties"=>$lineitem]);
        }, $lineItems);

        $BatchUpdateInputSimplePublicObjectId = new \HubSpot\Client\Crm\LineItems\Model\BatchInputSimplePublicObjectBatchInput(['inputs' => $lineitemIds]);
        $lineitems = $this->factory->crm()->lineItems()->batchApi()->update($BatchUpdateInputSimplePublicObjectId);
        
        return $lineitems;
    }

    public function updateLineItem($lineItemId, $lineitem) {
        $this->initialise();

        $SimplePublicObjectInput = new \HubSpot\Client\Crm\LineItems\Model\SimplePublicObjectInput(["properties" => $lineitem]);
        $lineitem = $this->factory->crm()->lineItems()->basicApi()->update($lineItemId, $SimplePublicObjectInput);

        return $lineitem;
    }

    public function deleteBatchLineItems(Array $ids) {
        $this->initialise();

        $lineitemIds = array_map(function($id) {
            return new \HubSpot\Client\Crm\LineItems\Model\SimplePublicObjectId(["id"=>$id]);
        }, $ids);

        $BatchReadInputSimplePublicObjectId = new \HubSpot\Client\Crm\LineItems\Model\BatchInputSimplePublicObjectId(['inputs' => array_values($lineitemIds)]); // Not 100% sure why array_values is required here, it seems odd but it works. Maybe something to review in the future

        $lineitems = $this->factory->crm()->lineItems()->batchApi()->archive($BatchReadInputSimplePublicObjectId);
        
        return $lineitems;
    }

    // Ticket functions

    public function createTicket($ticket) {
        $this->initialise();

        $SimplePublicObjectInput = new \HubSpot\Client\Crm\Tickets\Model\SimplePublicObjectInput(["properties" => $ticket]);
        $ticket = $this->factory->crm()->tickets()->basicApi()->create($SimplePublicObjectInput);

        return $ticket;
    }

    public function updateTicket($ticketId, $ticket) {
        $this->initialise();

        $SimplePublicObjectInput = new \HubSpot\Client\Crm\Tickets\Model\SimplePublicObjectInput(["properties" => $ticket]);
        $ticket = $this->factory->crm()->tickets()->basicApi()->update($ticketId, $SimplePublicObjectInput);

        return $ticket;
    }

    public function deleteTicket($ticketId) {
        $this->initialise();

        $ticket = $this->factory->crm()->tickets()->basicApi()->archive($ticketId);

        return $ticket;
    }

    public function associateTicketToDeal($ticketId, $dealId) {
        $association = new \HubSpot\Client\Crm\Associations\Model\PublicAssociation([
            "from" => $dealId,
            "to" => $ticketId,
            "type" => "DEAL_TO_TICKET",
        ]);

        $BatchInputPublicAssociation = new \HubSpot\Client\Crm\Associations\Model\BatchInputPublicAssociation(['inputs' => [$association]]);
        $response = $this->factory->crm()->associations()->batchApi()->create("DEAL", "TICKET", $BatchInputPublicAssociation);

        return $response;
    }

    // Generic helper functions
    public static function mapLineItemsToArrayProperties($lineItems) {
        return array_map(function($item) {
            return [
                'product_id' => $item->getProperties()['product_id'],
                'description' => (isset($item->getProperties()['description'])) ? $item->getProperties()['description'] : $item->getProperties()['name'],
                'product' => $item->getProperties()['name'],
                'quantity' => (isset($item->getProperties()['quantity'])) ? $item->getProperties()['quantity'] : null,
                'price' => (isset($item->getProperties()['price'])) ? $item->getProperties()['price'] : null,
                'line_item_id' => $item->getProperties()['hs_object_id'],
                'colour' => (isset($item->getProperties()['colour'])) ? $item->getProperties()['colour'] : null,
                'unit_of_measurement' => (isset($item->getProperties()['unit_of_measurement'])) ? $item->getProperties()['unit_of_measurement'] : null,
                'hs_position_on_quote' => (isset($item->getProperties()['hs_position_on_quote'])) ? $item->getProperties()['hs_position_on_quote'] : null,
                'material' => (isset($item->getProperties()['material'])) ? $item->getProperties()['material'] : null,
                'treatment' => (isset($item->getProperties()['treatment'])) ? $item->getProperties()['treatment'] : null,
                'coating_line' => (isset($item->getProperties()['powder_coat_line'])) ? $item->getProperties()['powder_coat_line'] : null,
            ];
        }, $lineItems->getResults());
    }

    public static function castDealToArrayProperties($deal, $clientName = '') {
        return [
            'po_number' => isset($deal->getProperties()['po_number']) ? $deal->getProperties()['po_number'] : null,
            'client_job_number' => isset($deal->getProperties()['client_job_number_']) ? $deal->getProperties()['client_job_number'] : null,
            'promised_date' => isset($deal->getProperties()['due_date']) ? $deal->getProperties()['due_date'] : null,
            'priority' => isset($deal->getProperties()['hs_priority']) ? $deal->getProperties()['hs_priority'] : null,
            'collection' => isset($deal->getProperties()['collection']) ? $deal->getProperties()['collection'] : null,
            'collection_instructions' => isset($deal->getProperties()['collection_instructions']) ? $deal->getProperties()['collection_instructions'] : null,
            'labelled' => isset($deal->getProperties()['labelled']) ? $deal->getProperties()['labelled'] : null,
            'invoice_number' => isset($deal->getProperties()['xero_invoice_number']) ? $deal->getProperties()['xero_invoice_number'] : null,
            'hs_deal_stage' => isset($deal->getProperties()['dealstage']) ? $deal->getProperties()['dealstage'] : null,
            'xero_invoice_status' => isset($deal->getProperties()['xero_invoice_status']) ? $deal->getProperties()['xero_invoice_status'] : null,
            'delivery_address' => isset($deal->getProperties()['delivery_address']) ? $deal->getProperties()['delivery_address'] : null,
            'dropoff_zone' => isset($deal->getProperties()['drop_off_zone']) ? $deal->getProperties()['drop_off_zone'] : null,
            'file_link' => isset($deal->getProperties()['po_link']) ? $deal->getProperties()['po_link'] : null,
            'deal_name' => isset($deal->getProperties()['dealname']) ? $deal->getProperties()['dealname'] : null,
            'client_name' => $clientName,
        ];
    }

    public static function castLineItemToJobProperties($item) {
        $product = Products::where('product_name', $item['product'])->first();
        return [
            'description' => (isset($item['description'])) ? $item['description'] : $item['product'],
            'name' => $item['product'],
            'quantity' => (isset($item['quantity'])) ? $item['quantity'] : null,
            'price' => (isset($item['price'])) ? $item['price'] : null,
            'hs_deal_lineitem_id' => $item['line_item_id'],
            'measurement' => (isset($item['unit_of_measurement'])) ? $item['unit_of_measurement'] : null,
            'position' => (isset($item['hs_position_on_quote'])) ? $item['hs_position_on_quote'] : 0,
            'colour' => (isset($item['colour'])) ? $item['colour'] : null,
            'product_id' => $product ? $product->product_id : null,
        ];
    }

    public static function castJobToTicketProperties($job) {
        return [
            'hs_pipeline' => env('TICKET_PIPELINE'),
            'hs_pipeline_stage' => self::mapJobStatusToTicketStage($job),
            'hubspot_owner_id' => env('HUBSPOT_OWNER_ID'), 
            'subject' => $job->job_title,
        ];
    }

    public static function castLineItemToLineItemProperties($lineitem) {

        $coatingLine = 'None';
        switch ($lineitem->job->powder_bay) {
            case 'big batch':
                $coatingLine = 'Big Batch';
                break;
            case 'small batch':
                $coatingLine = 'Small Batch';
                break;
            case 'main line':
                $coatingLine = 'Main Line';
                break;
        }

        return [
            'description' => $lineitem->description,
            'product' => $lineitem->name,
            'quantity' => $lineitem->quantity,
            'price' => $lineitem->price,
            'unit_of_measurement' => $lineitem->measurement,
            'hs_position_on_quote' => $lineitem->position,
            'colour' => $lineitem->colour,
            'material' => $lineitem->job->material,
            'treatment' => $lineitem->job->treatment,
            'powder_coat_line' => $coatingLine,
        ];
    }

    public static function castDealToHSDealProperties($deal) {
        $properties = [
            'pipeline' => env('DEAL_PIPELINE', 'default'),
        ];
        if (self::mapDealStatusToDealStage($deal->deal_status)) {
            $properties['dealstage'] = self::mapDealStatusToDealStage($deal->deal_status);
        }

        return $properties;
    }

    public static function mapJobStatusToTicketStage($job) {
        if ($job->trashed()) {
            return env('TICKET_REMOVED_STAGE');
        }

        if ($job->is_eror_redo === 'yes') {
            return env('TICKET_ERROR_REDO_STAGE');
        }

        switch($job->job_status) {
            case 'Ready':
                return env('TICKET_READY_FOR_SCHEDULING_STAGE');
                break;
            case 'In Progress':
                return env('TICKET_IN_PROGRESS_STAGE');
                break;
            case 'Awaiting QC':
                return env('TICKET_AWAITING_QC_STAGE');
                break;
            case 'Awaiting QC Passed':
            case 'QC Passed':
                return env('TICKET_QC_PASSED_STAGE');
                break;
            case 'Dispatched':
            case 'Complete':
                return env('TICKET_DISPATCHED_STAGE');
                break;
            case 'Partially Shipped':
                return env('TICKET_PARTIALLY_SHIPPED_STAGE');
                break;
            default:
                return env('TICKET_IN_PROGRESS_STAGE');
        }


    }

    public static function mapDealStatusToDealStage($status) {
        switch ($status) {
            case 'ready_for_dispatch':
                return env('DEAL_READY_TO_BE_INVOICED_STAGE');
            case 'complete':
                return env('DEAL_DISPATCHED_STAGE');
        }
    }


    public function initialiseSchedulingProperties() {
        $this->initialise();

        try {
            // Create new Xero group of properties
            $PropertyGroupCreate = new \HubSpot\Client\Crm\Properties\Model\PropertyGroupCreate(['name' => "productinformation", 'label' => "Product Information"]);
            $apiResponse = $this->factory->crm()->properties()->groupsApi()->create("product", $PropertyGroupCreate);
        } catch (\HubSpot\Client\Crm\Properties\ApiException $e) {
            // If the group already exists, ignore the error
            if ($e->getCode() != 409) {
                throw $e;
            }
        }

        //Ensure all required product properties are created
        $productProperties = new \HubSpot\Client\Crm\Properties\Model\BatchInputPropertyCreate(['inputs' => 
            [
                [
                    "groupName"=>"productinformation",
                    "hidden"=>false,
                    "label"=>"Colour",
                    "name"=>"colour",
                    "type"=>"string",
                    "fieldType"=>"text",
                ],
                [
                    "groupName"=>"productinformation",
                    "hidden"=>false,
                    "label"=>"Unit Of Measure",
                    "name"=>"unit_of_measurement",
                    "type"=>"string",
                    "fieldType"=>"text",
                ],
                [
                    "groupName"=>"productinformation",
                    "hidden"=>true,
                    "label"=>"Product ID",
                    "name"=>"product_id",
                    "type"=>"string",
                    "fieldType"=>"text",
                ],
                [
                    "groupName"=>"productinformation",
                    "hidden"=>false,
                    "label"=>"Material",
                    "name"=>"material",
                    "type"=>"enumeration",
                    "fieldType"=>"select",
                    "options" => [
                        [
                            "label" => "Steel",
                            "value" => "steel",
                            "hidden" => false,
                        ],
                        [
                            "label" => "Aluminium",
                            "value" => "aluminium",
                            "hidden" => false,
                        ],
                    ]
                ],
                [
                    "groupName"=>"productinformation",
                    "hidden"=>false,
                    "label"=>"Powder Coat Line",
                    "name"=>"powder_coat_line",
                    "type"=>"enumeration",
                    "fieldType"=>"select",
                    "options" => [
                        [
                            "label" => "Small Batch",
                            "value" => "Small Batch",
                            "hidden" => false,
                        ],
                        [
                            "label" => "Big Batch",
                            "value" => "Big Batch",
                            "hidden" => false,
                        ],
                        [
                            "label" => "Main Line",
                            "value" => "Main Line",
                            "hidden" => false,
                        ],
                        [
                            "label" => "No Line",
                            "value" => "No Line",
                            "hidden" => false,
                        ],
                        [
                            "label" => "None",
                            "value" => "None",
                            "hidden" => false,
                        ]
                    ]
                        ],
                [
                    "groupName"=>"productinformation",
                    "hidden"=>false,
                    "label"=>"Treatment",
                    "name"=>"treatment",
                    "type"=>"enumeration",
                    "fieldType"=>"select",
                    "options" => [
                        [
                            "label" => "S",
                            "value" => "S",
                            "hidden" => false,
                        ],
                        [
                            "label" => "ST",
                            "value" => "ST",
                            "hidden" => false,
                        ],
                        [
                            "label" => "STC",
                            "value" => "STC",
                            "hidden" => false,
                        ],
                        [
                            "label" => "STPC",
                            "value" => "STPC",
                            "hidden" => false,
                        ],
                        [
                            "label" => "SBTPC",
                            "value" => "SBTPC",
                            "hidden" => false,
                        ],
                        [
                            "label" => "T",
                            "value" => "T",
                            "hidden" => false,
                        ],
                        [
                            "label" => "TC",
                            "value" => "TC",
                            "hidden" => false,
                        ],
                        [
                            "label" => "TP",
                            "value" => "TP",
                            "hidden" => false,
                        ],
                        [
                            "label" => "TPC",
                            "value" => "TPC",
                            "hidden" => false,
                        ],
                        [
                            "label" => "C",
                            "value" => "C",
                            "hidden" => false,
                        ],
                        [
                            "label" => "F",
                            "value" => "F",
                            "hidden" => false,
                        ],
                        [
                            "label" => "FB",
                            "value" => "FB",
                            "hidden" => false,
                        ],
                        [
                            "label" => "FBP",
                            "value" => "FBP",
                            "hidden" => false,
                        ],
                        [
                            "label" => "FBPC",
                            "value" => "FBPC",
                            "hidden" => false,
                        ],
                        [
                            "label" => "B",
                            "value" => "B",
                            "hidden" => false,
                        ],
                        [
                            "label" => "BPC",
                            "value" => "BPC",
                            "hidden" => false,
                        ],
                        [
                            "label" => "BC",
                            "value" => "BC",
                            "hidden" => false,
                        ],
                        [
                            "label" => "BP",
                            "value" => "BP",
                            "hidden" => false,
                        ]
                    ]
                ]
            ]
        ]);
        $apiResponse = $this->factory->crm()->properties()->batchApi()->create("products", $productProperties);

        try {
            // Create new Xero group of properties
            $PropertyGroupCreate = new \HubSpot\Client\Crm\Properties\Model\PropertyGroupCreate(['name' => "xero", 'label' => "Xero"]);
            $apiResponse = $this->factory->crm()->properties()->groupsApi()->create("deals", $PropertyGroupCreate);
        } catch (\HubSpot\Client\Crm\Properties\ApiException $e) {
            // If the group already exists, ignore the error
            if ($e->getCode() != 409) {
                throw $e;
            }
        }

        //Ensure all required deal properties are created
        $dealProperties = new \HubSpot\Client\Crm\Properties\Model\BatchInputPropertyCreate(['inputs' => 
            [
                [
                    "groupName"=>"dealinformation",
                    "hidden"=>false,
                    "label"=>"Client Job No.",
                    "name"=>"client_job_no_",
                    "type"=>"string",
                    "fieldType"=>"text",
                ],
                [
                    "groupName"=>"dealinformation",
                    "hidden"=>false,
                    "label"=>"Customer Delivery Docket",
                    "name"=>"customer_delivery_docket",
                    "type"=>"string",
                    "fieldType"=>"text",
                ],
                [
                    "groupName"=>"dealinformation",
                    "hidden"=>false,
                    "label"=>"Delivery Address",
                    "name"=>"delivery_address",
                    "type"=>"string",
                    "fieldType"=>"text",
                ],
                [
                    "groupName"=>"dealinformation",
                    "hidden"=>false,
                    "label"=>"Drop off Zone",
                    "name"=>"drop_off_zone",
                    "type"=>"string",
                    "fieldType"=>"text",
                ],
                [
                    "groupName"=>"dealinformation",
                    "hidden"=>false,
                    "label"=>"Due Date",
                    "name"=>"due_date",
                    "type"=>"date",
                    "fieldType"=>"date",
                ],
                [
                    "groupName"=>"dealinformation",
                    "hidden"=>false,
                    "label"=>"Paid Upfront",
                    "name"=>"paid_upfront",
                    "type"=>"enumeration",
                    "fieldType"=>"select",
                    "options" => [
                        [
                            "label" => "Yes",
                            "value" => "Yes",
                            "hidden" => false,
                        ],
                        [
                            "label" => "No",
                            "value" => "No",
                            "hidden" => false,
                        ],
                    ]
                ],

                [
                    "groupName"=>"dealinformation",
                    "hidden"=>false,
                    "label"=>"Picking Slip",
                    "name"=>"picking_slip",
                    "type"=>"string",
                    "fieldType"=>"text",
                ],
                [
                    "groupName"=>"dealinformation",
                    "hidden"=>false,
                    "label"=>"Link",
                    "name"=>"po_link",
                    "type"=>"string",
                    "fieldType"=>"text",
                ],
                [
                    "groupName"=>"dealinformation",
                    "hidden"=>false,
                    "label"=>"PO Number",
                    "name"=>"po_number",
                    "type"=>"string",
                    "fieldType"=>"text",
                ],
                [
                    "groupName"=>"dealinformation",
                    "hidden"=>false,
                    "label"=>"Job Colours",
                    "name"=>"job_colours",
                    "type"=>"string",
                    "fieldType"=>"text",
                ],


                // Xero properties
                [ 
                    "groupName"=>"xero",
                    "hidden"=>false,
                    "label"=>"Xero Invoice Number",
                    "name"=>"xero_invoice_number",
                    "type"=>"string",
                    "fieldType"=>"text",
                ],
                [ 
                    "groupName"=>"xero",
                    "hidden"=>false,
                    "label"=>"Xero Invoice Status",
                    "name"=>"xero_invoice_status",
                    "type"=>"enumeration",
                    "fieldType"=>"select",
                    "options" => [
                        [
                            "label" => "Draft",
                            "value" => "DRAFT",
                            "hidden" => false,
                        ],
                        [
                            "label" => "Submitted",
                            "value" => "SUBMITTED",
                            "hidden" => false,
                        ],
                        [
                            "label" => "Approved",
                            "value" => "APPROVED",
                            "hidden" => false,
                        ],
                        [
                            "label" => "Authorised",
                            "value" => "AUTHORISED",
                            "hidden" => false,
                        ],
                        [
                            "label" => "Deleted",
                            "value" => "DELETED",
                            "hidden" => false,
                        ],
                        [
                            "label" => "Voided",
                            "value" => "VOIDED",
                            "hidden" => false,
                        ],
                        [
                            "label" => "Paid",
                            "value" => "PAID",
                            "hidden" => false,
                        ],
                        [
                            "label" => "Partially Paid",
                            "value" => "PARTIALL_PAID",
                            "hidden" => false,
                        ],
                    ]
                ],
                [ 
                    "groupName"=>"xero",
                    "hidden"=>false,
                    "label"=>"Xero Invoice Due Date",
                    "name"=>"xero_invoice_due_date",
                    "type"=>"date",
                    "fieldType"=>"date",
                ],
                [ 
                    "groupName"=>"xero",
                    "hidden"=>false,
                    "label"=>"Xero Invoice Total Amount Due",
                    "name"=>"xero_invoice_total_amount_due",
                    "type"=>"number",
                    "fieldType"=>"number",
                ],
                [ 
                    "groupName"=>"xero",
                    "hidden"=>false,
                    "label"=>"Xero Invoice Total Amount Overdue",
                    "name"=>"xero_invoice_total_amount_overdue",
                    "type"=>"number",
                    "fieldType"=>"number",
                ],
                [ 
                    "groupName"=>"xero",
                    "hidden"=>false,
                    "label"=>"Xero Invoice Total Amount Paid",
                    "name"=>"xero_invoice_total_amount_paid",
                    "type"=>"number",
                    "fieldType"=>"number",
                ],
            ]
        ]);
        $apiResponse = $this->factory->crm()->properties()->batchApi()->create("deals", $dealProperties);

        //Ensure all required contact properties are created
        $contactProperties = new \HubSpot\Client\Crm\Properties\Model\BatchInputPropertyCreate(['inputs' => 
            [
                [
                    "groupName"=>"contactinformation",
                    "hidden"=>false,
                    "label"=>"Include Xero Emails",
                    "name"=>"include_xero_emails",
                    "type"=>"enumeration",
                    "fieldType"=>"select",
                    "options" => [
                        [
                            "label" => "Yes",
                            "value" => "Yes",
                            "hidden" => false,
                        ],
                        [
                            "label" => "No",
                            "value" => "No",
                            "hidden" => false,
                        ],
                    ]
                ],
                [
                    "groupName"=>"contactinformation",
                    "hidden"=>false,
                    "label"=>"Contact Type",
                    "name"=>"contact_type",
                    "type"=>"enumeration",
                    "fieldType"=>"select",
                    "options" => [
                        [
                            "label" => "Account Contact",
                            "value" => "Account Contact",
                            "hidden" => false,
                        ],
                        [
                            "label" => "Purchases Contact",
                            "value" => "Purchases Contact",
                            "hidden" => false,
                        ],
                        [
                            "label" => "Administration Contact",
                            "value" => "Administration Contact",
                            "hidden" => false,
                        ],
                    ]
                ],
                [
                    "groupName"=>"contactinformation",
                    "hidden"=>false,
                    "label"=>"Include Xero Emails",
                    "name"=>"include_xero_emails",
                    "type"=>"enumeration",
                    "fieldType"=>"select",
                    "options" => [
                        [
                            "label" => "Yes",
                            "value" => "Yes",
                            "hidden" => false,
                        ],
                        [
                            "label" => "No",
                            "value" => "No",
                            "hidden" => false,
                        ],
                    ]
                ],
            ]
        ]);
        $apiResponse = $this->factory->crm()->properties()->batchApi()->create("contacts", $contactProperties);
    }

    public static function castJobToNoteProperties($note) {
        return [
            "hs_timestamp" => Carbon::now('UTC')->getPreciseTimestamp(3),
            "hs_note_body" => $note['comments'],
            "hubspot_owner_id" => env('HUBSPOT_OWNER_ID'),
            "hs_attachment_ids" => $note['hsObjectId'] ?? null
        ];
    }

    public function createNote($note) {
        $this->initialise();

        $SimplePublicObjectInput = new \HubSpot\Client\Crm\Objects\Notes\Model\SimplePublicObjectInput(["properties" => $note]);
        $ticket = $this->factory->crm()->objects()->notes()->basicApi()->create($SimplePublicObjectInput);

        return $ticket;
    }

    public function updateNote($commentId, $note) {
        $this->initialise();

        $SimplePublicObjectInput = new \HubSpot\Client\Crm\Objects\Notes\Model\SimplePublicObjectInput(["properties" => $note]);
        $ticket = $this->factory->crm()->objects()->notes()->basicApi()->update($commentId, $SimplePublicObjectInput);

        return $ticket;
    }

    public function associateNoteToDeal($noteId, $dealId) {
        $association = new \HubSpot\Client\Crm\Associations\Model\PublicAssociation([
            "from" => $dealId,
            "to" => $noteId,
            "type" => "DEAL_TO_NOTE",
        ]);

        $BatchInputPublicAssociation = new \HubSpot\Client\Crm\Associations\Model\BatchInputPublicAssociation(['inputs' => [$association]]);
        $response = $this->factory->crm()->associations()->batchApi()->create("DEAL", "NOTE", $BatchInputPublicAssociation);

        return $response;
    }

    public function getDealTicketAssociations($dealId) {
        $this->initialise();

        $dealAssociations = $this->factory->crm()->deals()->associationsApi()->getAll($dealId, self::TICKETS);

        return $dealAssociations;
    }

    public function associateNoteToTicket($noteId, $ticket) {
        $association = new \HubSpot\Client\Crm\Associations\Model\PublicAssociation([
            "from" => $ticket,
            "to" => $noteId,
            "type" => "TICKET_TO_NOTE",
        ]);

        $BatchInputPublicAssociation = new \HubSpot\Client\Crm\Associations\Model\BatchInputPublicAssociation(['inputs' => [$association]]);
        $response = $this->factory->crm()->associations()->batchApi()->create("TICKET", "NOTE", $BatchInputPublicAssociation);

        return $response;
    }
}