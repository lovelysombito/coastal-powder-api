<?php

namespace Tests\Doubles\HubSpot;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use \HubSpot\Client\Auth\OAuth\Model\TokenResponseIF;

class HubSpotFactoryCreateClientInterfaceHubspotCallback extends Client
{

    public function __construct()
    {

        $string = json_encode([
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
            'expires_in' => '3600',
            'token_type' => 'Bearer',
            'scope' => 'scope',
        ]);
        $obtainAccessTokenResponse = new Response(200, ['Content-Type' => 'application/json'], $string);

        $mock = new MockHandler([$obtainAccessTokenResponse]);

        $handlerStack = HandlerStack::create($mock);

        parent::__construct(['handler' => $handlerStack]);
    }
}
