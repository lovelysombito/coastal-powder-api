<?php

namespace Tests\Feature\Webhooks;

use App\Models\Integration;
use App\Models\Integration\HubSpot;
use App\Jobs\HubSpot\UpdateDealDescription;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Doubles\HubSpot\HubSpotFactoryCreateClientInterfaceUpdateDealDescription;
use Tests\TestCase;

class HubSpotTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_hubspot_webhook_succeeded_if_integration_isnt_connected()
    {
        $this->seed();

        $response = $this->withoutMiddleware('hubspot-webhook')->postJson('/api/webhooks/hubspot', [
            [
                'eventId' => '123456',
                'subscriptionId' => '123456',
                'subscriptionType' => 'deal.propertyChange',
                'portalId' => '123456',
                'appId' => '123456',
                'occurredAt' => '1660829806646',
                'attemptNumber' => 0,
                'objectId' => '123456',
                'propertyName' => 'dealstage',
                'propertyValue' => '15930848',
                'changeSource' => 'CRM_UI',
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_hubspot_webhook_doesnt_trigger_if_portal_not_connected()
    {
        Queue::fake();

        $this->seed();
        $hubspot = Integration::where(['platform' => 'HUBSPOT'])->first();

        $portalId = 123456;
        $userId = '123456789';
        $hubspot->update(['platform_account_id' => $portalId, 'integration_status' => 'Connected', 'platform_user_id' => $userId]);

        $response = $this->withoutMiddleware('hubspot-webhook')->postJson('/api/webhooks/hubspot', [
            [
                'eventId' => '123456',
                'subscriptionId' => '123456',
                'subscriptionType' => 'deal.propertyChange',
                'portalId' => 1234567,
                'appId' => '123456',
                'occurredAt' => '1660829806646',
                'attemptNumber' => 0,
                'objectId' => '123456',
                'propertyName' => 'description',
                'propertyValue' => '15930848',
                'changeSource' => 'CRM_UI',
            ]
        ]);

        Queue::assertNothingPushed();
        $response->assertStatus(200);
    }

    public function test_hubspot_webhook_triggers_multiple_deal_description_updates_with_multiple_events() {
        Queue::fake();

        $this->seed();
        $hubspot = Integration::where(['platform' => 'HUBSPOT'])->first();

        $portalId = 123456;
        $userId = '123456789';
        $hubspot = Integration::where(['platform' => 'HUBSPOT'])->first();
        $hubspot->update(['platform_account_id' => $portalId, 'integration_status' => 'Connected', 'platform_user_id' => $userId, 'platform_access_token' => 'token']);
        $hubspot->platform_access_token_expires_in = time() + 50000;
        $hubspot->save();


        $response = $this->withoutMiddleware('hubspot-webhook')->postJson('/api/webhooks/hubspot', [
            [
                'eventId' => '123456',
                'subscriptionId' => '123456',
                'subscriptionType' => 'deal.propertyChange',
                'portalId' => 123456,
                'appId' => '123456',
                'occurredAt' => '1660829806646',
                'attemptNumber' => 0,
                'objectId' => '123456',
                'propertyName' => 'description',
                'propertyValue' => 'sales order description',
                'changeSource' => 'CRM_UI',
            ],
            [
                'eventId' => '123456',
                'subscriptionId' => '123456',
                'subscriptionType' => 'deal.propertyChange',
                'portalId' => 123456,
                'appId' => '123456',
                'occurredAt' => '1660829806646',
                'attemptNumber' => 0,
                'objectId' => '123456',
                'propertyName' => 'xero_invoice_number',
                'propertyValue' => 'INV-0001',
                'changeSource' => 'CRM_UI',
            ],
            [
                'eventId' => '123456',
                'subscriptionId' => '123456',
                'subscriptionType' => 'deal.propertyChange',
                'portalId' => 123456,
                'appId' => '123456',
                'occurredAt' => '1660829806646',
                'attemptNumber' => 0,
                'objectId' => '123456',
                'propertyName' => 'dealstage',
                'propertyValue' => '15930849',
                'changeSource' => 'CRM_UI',
            ],
            [
                'eventId' => '123456',
                'subscriptionId' => '123456',
                'subscriptionType' => 'deal.propertyChange',
                'portalId' => 123456,
                'appId' => '123456',
                'occurredAt' => '1660829806646',
                'attemptNumber' => 0,
                'objectId' => '123456',
                'propertyName' => 'amount',
                'propertyValue' => '90.00',
                'changeSource' => 'CRM_UI',
            ]
        ]);

        Queue::assertPushed(UpdateDealDescription::class, 4);
        $response->assertStatus(200);
    }

    public function test_hubspot_webhook_updates_deal_description()
    {
        $this->seed();
        $hubspot = Integration::where(['platform' => 'HUBSPOT'])->first();

        $portalId = 123456;
        $userId = '123456789';
        $hubspot = Integration::where(['platform' => 'HUBSPOT'])->first();
        $hubspot->update(['platform_account_id' => $portalId, 'integration_status' => 'Connected', 'platform_user_id' => $userId, 'platform_access_token' => 'token']);
        $hubspot->platform_access_token_expires_in = time() + 50000;
        $hubspot->save();

        /** @var Object $hsClient */
        $hsClient = Mockery::mock(HubSpotFactoryCreateClientInterfaceUpdateDealDescription::class);
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
        $hsClient->shouldReceive('send')->times(16)->andReturn($getDealResponse);

        HubSpot::setClientInterface($hsClient);


        $response = $this->withoutMiddleware('hubspot-webhook')->postJson('/api/webhooks/hubspot', [
            [
                'eventId' => '123456',
                'subscriptionId' => '123456',
                'subscriptionType' => 'deal.propertyChange',
                'portalId' => 123456,
                'appId' => '123456',
                'occurredAt' => '1660829806646',
                'attemptNumber' => 0,
                'objectId' => '123456',
                'propertyName' => 'description',
                'propertyValue' => 'sales order description',
                'changeSource' => 'CRM_UI',
            ],
            [
                'eventId' => '123456',
                'subscriptionId' => '123456',
                'subscriptionType' => 'deal.propertyChange',
                'portalId' => 123456,
                'appId' => '123456',
                'occurredAt' => '1660829806646',
                'attemptNumber' => 0,
                'objectId' => '123456',
                'propertyName' => 'xero_invoice_number',
                'propertyValue' => 'INV-0001',
                'changeSource' => 'CRM_UI',
            ],
            [
                'eventId' => '123456',
                'subscriptionId' => '123456',
                'subscriptionType' => 'deal.propertyChange',
                'portalId' => 123456,
                'appId' => '123456',
                'occurredAt' => '1660829806646',
                'attemptNumber' => 0,
                'objectId' => '123456',
                'propertyName' => 'dealstage',
                'propertyValue' => '15930849',
                'changeSource' => 'CRM_UI',
            ],
            [
                'eventId' => '123456',
                'subscriptionId' => '123456',
                'subscriptionType' => 'deal.propertyChange',
                'portalId' => 123456,
                'appId' => '123456',
                'occurredAt' => '1660829806646',
                'attemptNumber' => 0,
                'objectId' => '123456',
                'propertyName' => 'amount',
                'propertyValue' => '90.00',
                'changeSource' => 'CRM_UI',
            ]
        ]);

        $response->assertStatus(200);
    }
}
