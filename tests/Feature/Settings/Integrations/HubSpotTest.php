<?php

namespace Tests\Feature\Settings\Integrations;

use App\Http\Controllers\IntegrationController;
use App\Models\Integration;
use App\Models\Integration\HubSpot;
use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\Doubles\HubSpot\HubSpotFactoryCreateClientInterfaceGetDealLineItems;
use Tests\Doubles\HubSpot\HubSpotFactoryCreateClientInterfaceHubspotCallback;
use Tests\Doubles\HubSpot\HubSpotFactoryCreateClientInterfaceInitialiseHubSpotProperties;
use Tests\TestCase;

class HubSpotTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_administrator_can_authorise_hubspot()
    {
        $this->seed();

        /** @var User $user */
        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        /** @var Object $client */
        $client = Mockery::mock(HubSpotFactoryCreateClientInterfaceHubspotCallback::class);
        $string = json_encode([
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
            'expires_in' => '3600',
            'token_type' => 'Bearer',
            'scope' => 'scope',
        ]);
        $response = new Response(200, ['Content-Type' => 'application/json'], $string);
        $client->shouldReceive('send')->once()->andReturn($response);

        IntegrationController::setClientInterface($client);

        /** @var Object $hsClient */
        $hsClient = \Mockery::mock(HubSpotFactoryCreateClientInterfaceInitialiseHubSpotProperties::class);
        $string = json_encode([
            'status' => 'COMPLETE',
            'results' => [],
            'num_errors' => 0,
            'errors' => [],
        ]);
        $response = new Response(200, ['Content-Type' => 'application/json'], $string);
        $hsClient->shouldReceive('send')->times(5)->andReturn($response);

        HubSpot::setClientInterface($hsClient);

        Http::preventStrayRequests();
        Http::fake([
            'api.hubapi.com/oauth/v1/access-tokens/*' => Http::response([
                "token" => "string",
                "user" => "string",
                "hub_domain" => "string",
                "scopes" => [
                    "string"
                ],
                "scope_to_scope_group_pks" => [
                    0
                ],
                "trial_scopes" => [
                    "string"
                ],
                "trial_scope_to_scope_group_pks" => [
                    0
                ],
                "hub_id" => 0,
                "app_id" => 0,
                "expires_in" => 0,
                "user_id" => 0,
                "token_type" => "string"
            ], 200),
        ]);

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/integrations/hubspot/callback', [
            "code" => "code",
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                "message" => "Successfully connected to HubSpot"
            ]);
    }

    public function test_authorisation_fails_with_invalid_code()
    {
        $this->seed();

        /** @var User $user */
        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        IntegrationController::setClientInterface(null);

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/integrations/hubspot/callback', [
            "code" => "code",
        ]);

        $response
            ->assertStatus(400)
            ->assertExactJson([
                "message" => "Invalid authorisisation code, please try again"
            ]);
    }

    public function test_hubspot_authorisation_fails_when_integration_unavailable()
    {
        $this->seed();

        /** @var User $user */
        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $hubspot = Integration::where(['platform' => 'HUBSPOT'])->first();

        $hubspot->delete();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/integrations/hubspot/callback', [
            "code" => "code",
        ]);

        $response
            ->assertStatus(400)
            ->assertExactJson([
                "message" => "The HubSpot integration is currently unavailable"
            ]);
    }

    public function test_adminstrator_can_disconnect_hubspot_integration()
    {   
        $this->seed();

        /** @var User $user */
        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();
        
        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->deleteJson('/api/integrations/hubspot');

        $response
            ->assertStatus(200)
            ->assertJson([
                "message" => "Successfully disconnected HubSpot"
            ]);
    }

    public function test_deal_line_item_editor_card_crm_is_viewable() {
        $this->seed();

        $hubspot = Integration::where(['platform' => 'HUBSPOT'])->first();

        $portalId = 123456;
        $associatedObjectId = 654321;
        $userId = '123456789';
        $hubspot->update(['platform_account_id' => $portalId, 'integration_status' => 'Connected', 'platform_user_id' => $userId]);

        $uri = env('APP_URL').'/api/hubspot/crm-cards/deal/line-item-editor?portalId=' . $portalId . '&associatedObjectId=' . $associatedObjectId;

        $response = $this->withoutMiddleware('hubspot-webhook')->getJson($uri);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'primaryAction' => [
                    'type',
                    'label',
                    'uri',
                    'width',
                    'height',
                ]
            ]);
    }

    public function test_deal_line_item_editor_card_crm_is_unavailable_when_integration_isnt_active() {
        $this->seed();

        $portalId = 123456;
        $associatedObjectId = 654321;
        $uri = env('APP_URL').'/api/hubspot/crm-cards/deal/line-item-editor?portalId=' . $portalId . '&associatedObjectId=' . $associatedObjectId;

        $response = $this->withoutMiddleware('hubspot-webhook')->getJson($uri);

        $response
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'The HubSpot integration is currently unavailable'
            ]);
    }

    public function test_get_deal_line_items() {
        $this->seed();

        $portalId = 123456;
        $userId = '123456789';
        $hubspot = Integration::where(['platform' => 'HUBSPOT'])->first();
        $hubspot->update(['platform_account_id' => $portalId, 'integration_status' => 'Connected', 'platform_user_id' => $userId, 'platform_access_token' => 'token']);
        $hubspot->platform_access_token_expires_in = time() + 50000;
        $hubspot->save();

        /** @var Object $hsClient */
        $hsClient = Mockery::mock(HubSpotFactoryCreateClientInterfaceGetDealLineItems::class);
        $string = json_encode([
            'results' => [
                ['id' => '123','type' => 'deal_to_line_item'],
                ['id' => '124','type' => 'deal_to_line_item'],
                ['id' => '125','type' => 'deal_to_line_item'],
                ['id' => '126','type' => 'deal_to_line_item'],
            ]
        ]);
        $getAssociationsResponse = new Response(200, ['Content-Type' => 'application/json'], $string);

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
                        'price' => "10",
                        'product_id' => 'dde8d4e9-148c-49d9-b869-af4d773e3fba',
                        'quantity' => "1",
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
                        'hs_position_on_quote' => "0",
                        'material' => 'Aluminium',
                        'name' => 'Product1',
                        'powder_coat_line' => 'Big Batch',
                        'price' => '10',
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
                        'price' => '10',
                        'product_id' => 'dde8d4e9-148c-49d9-b869-af4d773e3fba',
                        'quantity' => '1',
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
                        'hs_position_on_quote' => '0',
                        'material' => 'Aluminium',
                        'name' => 'Product1',
                        'powder_coat_line' => 'Big Batch',
                        'price' => '10',
                        'product_id' => 'dde8d4e9-148c-49d9-b869-af4d773e3fba',
                        'quantity' => '1',
                        'treatment' => 'ST',
                        'unit_of_measurement' => 'measurement',
                    ]
                ],
            ]
        ]);
        $getLineItemsResponse = new Response(200, ['Content-Type' => 'application/json'], $string);

        $hsClient->shouldReceive('send')->times(1)->andReturn($getAssociationsResponse);
        $hsClient->shouldReceive('send')->times(1)->andReturn($getLineItemsResponse);


        HubSpot::setClientInterface($hsClient);

        $dealId = 123456;
        $response = $this->withoutMiddleware('hubspot-crm-card-request')->withHeaders(['crmcard-user-id'=>$userId])->getJson('/api/hubspot/crm-cards/deals/'.$dealId.'/line-items');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'length',
                'lineitems' => [
                    '*' => [
                        'colour',
                        'description',
                        'line_item_id',
                        'hs_position_on_quote',
                        'material',
                        'product',
                        'coating_line',
                        'price',
                        'product_id',
                        'quantity',
                        'treatment',
                        'unit_of_measurement',
                    ]
                ]
            ])
            ->assertJson([
                "length" => 4,
            ]);
    }

    public function test_retrieve_products_for_line_item_editor() {
        $this->seed();

        $response = $this->withoutMiddleware('hubspot-crm-card-request')->getJson('/api/hubspot/crm-cards/products');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'products' => [
                    '*' => [
                        'product_id',
                        'product_name',
                        'description',
                        'price',
                    ]
                ]
            ]);
    }

    public function test_retrieve_colours_for_line_item_editor() {
        $this->seed();

        $response = $this->withoutMiddleware('hubspot-crm-card-request')->getJson('/api/hubspot/crm-cards/colours');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'colours' => [
                    '*' => [
                        'colour_id',
                        'name'
                    ]
                ]
            ]);
    }
}
