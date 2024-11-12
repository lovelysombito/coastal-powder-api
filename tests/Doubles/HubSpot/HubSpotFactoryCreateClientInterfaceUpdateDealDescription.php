<?php

namespace Tests\Doubles\HubSpot;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class HubSpotFactoryCreateClientInterfaceUpdateDealDescription extends Client
{

    public function __construct()
    {
        $string = json_encode([
            'id' => '123456',
            'properties' => [
                'client_job_no_' => '',
                'createdate' => '2021-05-20T01:58:33.193Z',
                'customer_delivery_docket' => '',
                'dealname' => 'Mitchell',
                'delivery_address' =>  '',
                'drop_off_zone' =>  '',
                'due_date' =>  '',
                'hs_lastmodifieddate' => '2022-08-18T14:09:28.600Z',
                'hs_object_id' => 5302358425,
                'hs_priority' =>  '',
                'job_colours' =>  '',
                'paid_upfront' =>  '',
                'picking_slip' =>  '',
                'po_link' =>  '',
                'po_number' =>  '',
                'xero_invoice_due_date' =>  '',
                'xero_invoice_number' =>  '',
                'xero_invoice_status' =>  '',
                'xero_invoice_total_amount_due' =>  '',
                'xero_invoice_total_amount_overdue' =>  '',
                'xero_invoice_total_amount_paid' =>  '',
            ]
        ]);
        $getDealResponse = new Response(200, ['Content-Type' => 'application/json'], $string);

        $mock = new MockHandler([$getDealResponse]);

        $handlerStack = HandlerStack::create($mock);

        parent::__construct(['handler' => $handlerStack]);
    }
}
