<?php

namespace Tests\Doubles\HubSpot;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use \HubSpot\Client\Auth\OAuth\Model\TokenResponseIF;

class HubSpotFactoryCreateClientInterfaceInitialiseHubSpotProperties extends Client
{

    public function __construct()
    {

        $response = json_encode([
            'status' => 'COMPLETE',
            'results' => [],
            'num_errors' => 0,
            'errors' => [],
        ]);
        $createPropertyResponse = new Response(200, ['Content-Type' => 'application/json'], $response);

        $mock = new MockHandler([$createPropertyResponse]);

        $handlerStack = HandlerStack::create($mock);

        parent::__construct(['handler' => $handlerStack]);
    }
}
