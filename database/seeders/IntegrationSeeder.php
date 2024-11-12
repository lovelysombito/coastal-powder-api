<?php

namespace Database\Seeders;

use App\Models\Integration\HubSpot;
use App\Models\Integration\Xero;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IntegrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HubSpot::updateOrCreate(['platform' => 'HUBSPOT'], ['platform' => 'HUBSPOT', 'platform_install_url' => 'https://app.hubspot.com/oauth/authorize?client_id='.env('HUBSPOT_CLIENT_ID').'&redirect_uri='.env('HUBSPOT_REDIRECT_URL').'&scope=oauth%20tickets%20e-commerce%20crm.objects.contacts.read%20crm.objects.contacts.write%20crm.objects.companies.write%20crm.objects.companies.read%20crm.objects.deals.read%20crm.objects.deals.write%20crm.objects.line_items.read%20crm.objects.line_items.write']);
        Xero::updateOrCreate(['platform' => 'XERO'], ['platform' => 'XERO', 'platform_install_url'=>'https://login.xero.com/identity/connect/authorize?response_type=code&client_id='.env('XERO_CLIENT_ID').'&redirect_uri='.env('XERO_REDIRECT_URL').'&scope=offline_access openid profile email accounting.settings.read accounting.transactions accounting.contacts']);
    }
}
