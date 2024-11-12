<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class JobSchedulingTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    // public function test_user_can_retrieve_scheduled_table_overview()
    // {
        // print_r("Seed");
        // $this->seed();
        // print_r("Seed example");
        // $user = User::where('email', 'support@upstreamtech.io')->first();

        // $response = $this->actingAs($user)->withHeaders([
        //     'referer' => env('SPA_URL'),
        //     'origin' => env('SPA_URL'),
        // ])->getJson('/api/jobs/scheduled/table/overview');

        // $response->dump();

        // $response
        //     ->assertStatus(200)
        //     ->assertJsonStructure([
        //         'data' => 
        //             'current_page',
        //             'data' => [
        //             '*' => [
        //                 'job_id',
        //                 'updated_at',
        //                 "hs_ticket_id",
        //                 "invoice_number",
        //                 "job_status",
        //                 "amount",
        //                 "promised_date",
        //                 "client_name",
        //                 "po_number",
        //                 "colour",
        //                 "material",
        //                 "chem_bay",
        //                 "treatment_bay",
        //                 "burn_bay",
        //                 "blast_bay",
        //                 "powder_bay",
        //                 "chem_date",
        //                 "treatment_date",
        //                 "burn_date",
        //                 "blast_date",
        //                 "powder_date",
        //                 "chem_status",
        //                 "treatment_status",
        //                 "burn_status",
        //                 "blast_status",
        //                 "powder_status",
        //                 "chem_completed",
        //                 "treatment_completed",
        //                 "burn_completed",
        //                 "blast_completed",
        //                 "powder_completed",
        //                 "chem_bay_required",
        //                 "treatment_bay_required",
        //                 "burn_bay_required",
        //                 "blast_bay_required",
        //                 "powder_bay_required",
        //                 "job_comments" => [
        //                     '*' => [
        //                         "comment_id",
        //                         "parent_id",
        //                         "firstname",
        //                         "lastname",
        //                         "invoice_number",
        //                         "job_id",
        //                         "comment",
        //                     ]
        //                 ],
        //                 "lines" => [
        //                     '*' => [
        //                         "line_item_id",
        //                         "product_name",
        //                         "file_link",
        //                         "colour_name",
        //                         "signature",
        //                         "quantity",
        //                         "line_item_status",
        //                         "created_at",
        //                         "updated_at",
        //                         "deleted_at",
        //                         "number_dispatched",
        //                         "material",
        //                         "chem_bay",
        //                         "treatment_bay",
        //                         "burn_bay",
        //                         "blast_bay",
        //                         "powder_bay",
        //                         "chem_date",
        //                         "treatment_date",
        //                         "burn_date",
        //                         "blast_date",
        //                         "powder_date",
        //                         "chem_status",
        //                         "treatment_status",
        //                         "burn_status",
        //                         "blast_status",
        //                         "powder_status",
        //                         "chem_completed",
        //                         "treatment_completed",
        //                         "burn_completed",
        //                         "blast_completed",
        //                         "powder_completed",
        //                     ],
        //                 ],
        //             ]
        //         ]
        //     ]);
    // }
}
