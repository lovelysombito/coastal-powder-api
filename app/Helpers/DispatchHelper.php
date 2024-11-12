<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class DispatchHelper
{
    public static function setData($deal)
    {
        $arr =  [
            'deal_id' => $deal->deal_id,
            'deal_name' => $deal->deal_name,
            'po_number' => $deal->po_number,
            'promised_date' => $deal->promised_date,
            'priority' => $deal->priority,
            'collection' => $deal->collection,
            'collection_instructions' => $deal->collection_instructions,
            'collection_location' => $deal->collection_location,
            'invoice_number' => $deal->invoice_number,
            'hs_deal_stage' => $deal->hs_deal_stage,
            'delivery_address' => $deal->delivery_address,
            'dropoff_zone' => $deal->dropoff_zone,
            'client_name' => $deal->client_name,
            'file_link' => $deal->file_link,
            'signature' => $deal->jobSignature ?? null,
            'job_status' => $deal->jobStatus,
            'job_ids' => $deal->job_ids,
            'lines' => $deal->lines,
            'name' => $deal->name,
            'email' => $deal->email,
            'account_hold' => $deal->account_hold,
            'jobs' => $deal->jobs
        ];

        return $arr;
    }

    public static function setPackingData($value) {
        $arr = [
            'id' => $value->packing_slip_id,
            'name' => $value->packing_slip_name,
            'link' => $value->packing_slip_file
        ];

        return $arr;
    }
}
