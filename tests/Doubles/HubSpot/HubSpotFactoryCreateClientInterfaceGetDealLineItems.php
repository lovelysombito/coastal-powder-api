<?php

namespace Tests\Doubles\HubSpot;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use \HubSpot\Client\Auth\OAuth\Model\TokenResponseIF;

class HubSpotFactoryCreateClientInterfaceGetDealLineItems extends Client
{

    public function __construct()
    {
        $string = json_encode([
            'results' => [
                ['id' => '123','type' => 'deal_to_line_item'],
                ['id' => '124','type' => 'deal_to_line_item'],
                ['id' => '125','type' => 'deal_to_line_item'],
                ['id' => '126','type' => 'deal_to_line_item'],
            ]
        ]);
        $getDealLineItemAssociationsResponse = new Response(200, ['Content-Type' => 'application/json'], $string);

        $string = json_encode([
            'status' => 'COMPLETE',
            'results' => [
                [
                    'id' => '123',
                    'properties' => [
                        'colour' => 'Colour10',
                        'description' => 'description',
                        'hs_object_id' => '123',
                        'hs_position_on_quote' => 0,
                        'material' => 'Aluminium',
                        'name' => 'Product1',
                        'powder_coat_line' => 'Big Batch',
                        'price' => 10,
                        'product_id' => 'dde8d4e9-148c-49d9-b869-af4d773e3fba',
                        'quantity' => 1,
                        'treatment' => 'ST',
                        'unit_of_measurement' => 'measurement',
                    ],
                ],
                [
                    'id' => '124',
                    'properties' => [
                        'colour' => 'Colour10',
                        'description' => 'description',
                        'hs_object_id' => '124',
                        'hs_position_on_quote' => 0,
                        'material' => 'Aluminium',
                        'name' => 'Product1',
                        'powder_coat_line' => 'Big Batch',
                        'price' => 10,
                        'product_id' => 'dde8d4e9-148c-49d9-b869-af4d773e3fba',
                        'quantity' => 1,
                        'treatment' => 'ST',
                        'unit_of_measurement' => 'measurement',
                    ],
                ],
                [
                    'id' => '125',
                    'properties' => [
                        'colour' => 'Colour10',
                        'description' => 'description',
                        'hs_object_id' => '125',
                        'hs_position_on_quote' => 0,
                        'material' => 'Aluminium',
                        'name' => 'Product1',
                        'powder_coat_line' => 'Big Batch',
                        'price' => 10,
                        'product_id' => 'dde8d4e9-148c-49d9-b869-af4d773e3fba',
                        'quantity' => 1,
                        'treatment' => 'ST',
                        'unit_of_measurement' => 'measurement',
                    ],
                ],
                [
                    'id' => '127',
                    'properties' => [
                        'colour' => 'Colour10',
                        'description' => 'description',
                        'hs_object_id' => '127',
                        'hs_position_on_quote' => 0,
                        'material' => 'Aluminium',
                        'name' => 'Product1',
                        'powder_coat_line' => 'Big Batch',
                        'price' => 10,
                        'product_id' => 'dde8d4e9-148c-49d9-b869-af4d773e3fba',
                        'quantity' => 1,
                        'treatment' => 'ST',
                        'unit_of_measurement' => 'measurement',
                    ]
                ],
            ]
        ]);
        $getBatchLineItemResponse = new Response(200, ['Content-Type' => 'application/json'], $string);

        $mock = new MockHandler([$getDealLineItemAssociationsResponse, $getBatchLineItemResponse]);

        $handlerStack = HandlerStack::create($mock);

        parent::__construct(['handler' => $handlerStack]);
    }
}
