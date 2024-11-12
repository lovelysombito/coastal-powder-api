<?php

namespace App\Http\Controllers;

use App\Events\JobEvent;
use App\Helpers\DealHelper;
use Illuminate\Http\Request;
use App\Models\JobScheduling;
use App\Helpers\JobScheduleHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\ValidationHelper;
use App\Http\Requests\DispatchRequest;
use App\Http\Requests\LineDispatchRequest;
use App\Models\Dispatch;
use App\Models\Integration\HubSpot;
use App\Models\LineItems;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\ImageUploadTrait;
use App\Mail\SendPackingSlipMail;
use App\Models\Deal;
use Carbon\Carbon;
use DateTime;
// use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\DispatchHelper;
use App\Mail\SendDispatchPackingSlipMail;
use App\Models\PackingSlip;
use Illuminate\Support\Facades\Mail;
use PDF;

class DispatchController extends Controller
{
    use ImageUploadTrait;
    public function getJobDispatched(Request $request)
    {
        Log::info("DispatchController@getJobDispatched", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {
            $dealList = Deal::with(['jobs', 'jobs.lines', 'jobs.lines.line_product'])->whereDoesntHave('jobs', function($query) {
                return $query->where('job_status','<>', 'QC Passed')->where('job_status','<>','Partially Shipped');
            })->paginate(config('constant.pagination.dispatch'));

            foreach ($dealList as $dealKey => $deal) {
                $newLineArray = [];
                $newJobArray = [];
                $jobSignature = '';
                $jobStatus = '';
                foreach ($deal->jobs as $jobKey => $job) {
                    $deal->jobStatus = $job->job_status;
                    array_push($newJobArray, $job->job_id);
                    $deal->jobSignature = Dispatch::where('object_id', $job->job_id)->where('object_type', 'JOB')->first();
                    foreach ($job->lines as $lineKey => $line) {
                        $line->signature = Dispatch::where('object_id', $line->line_item_id)->where('object_type', 'LINE_ITEM')->first()->signature ?? null;
                        $line->number_remaining = $line->quantity < $line->number_dispatched ? 0 : round($line->quantity - $line->number_dispatched, 2);
                        array_push($newLineArray, $line);
                    }
                }
                $deal->job_ids = $newJobArray;
                $deal->lines = $newLineArray;
            }

            $dealList->getCollection()->transform(function ($value) use ($request) {
                return DispatchHelper::setData($value, $request->user());
            });

            if ($dealList->total() > 0) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), $dealList, 'Deal List');
            } else {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'Deal List');
            }
        } catch (Exception $e) {
            Log::error("DispatchController@getJobDispatched - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }

    public function UpdateJobDispatchStatus(DispatchRequest $request, $job)
    {
        Log::info("DispatchController@UpdateJobDispatchStatus", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {
            $jobDetail = JobScheduling::where('job_id', $job)->first();

            if (!$jobDetail) {
                return ResponseHelper::errorResponse('Cannot update dispatch status.', config('constant.status_code.not_found'));
            }
            $id = $jobDetail->job_id;

            if (Dispatch::where('object_id', $job)->first()) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), '', 'Already updated');
            }

            do {
                //generate a random string using Laravel's str_random helper
                $dId = Str::random(16);
            } //check if the token already exists and if it does, try again
            while (Dispatch::where('dispatch_id', $dId)->first());

            $params = $request->all();
            $path = 'img/' .  Carbon::now()->timestamp . '-' . $id;
            $deal_status = $request->status;

            $original_image_url = '';
            if (isset($params['signature'])) {
                $original_image_url = $this->saveImage($params['signature'], $path);
            }

            $dealDetail = Deal::where('deal_id', $jobDetail->deal_id)->first();
            if ($deal_status == 'fully_dispatched') {
                // return response()->json($params);
                DB::transaction(function () use ($id, $params, $original_image_url, $dId) {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                    DB::table('dispatch')->insert([
                        'dispatch_id' => $dId,
                        'object_id' => $id,
                        'object_type' => 'JOB',
                        'signature' => $original_image_url,
                        'dispatch_customer_name' => $params['customer_name'],
                        'dispatch_customer_email'=> $params['customer_email']
                    ]);

                    DB::table('line_items')
                        ->where('job_id', $id)
                        ->update(['line_item_status' => 'Dispatched']);

                    DB::table('job_scheduling')
                        ->where('job_id', $id)
                        ->update(['job_status' => 'Complete']);

                    if (isset($params['dispatch_status'])) {
                        if (is_bool($params['dispatch_status'])) {
                            DB::table('line_items')
                                ->where('job_id', $id)
                                ->update(['dispatch_status' => $params['dispatch_status']]);
                        }
                    }
                });

                $dealDetail->deal_status = $deal_status;
                $dealDetail->save();

                $integration = HubSpot::where(['platform' => 'HUBSPOT', 'integration_status'=>'Connected'])->first();
                if (!$integration) {
                    Log::warning("QualityControllController@updateQCJobStatus - HubSpot integration is not connected", ["deal"=> $dealDetail, "integration" => $integration, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()?->user_id]]]);
                } else {
                    Log::info("QualityControllController@updateQCJobStatus - Update deal {$dealDetail->deal_id} stage to picked up", ["deal"=> $dealDetail, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()?->user_id]]]);
                    $integration->updateDeal($dealDetail->hs_deal_id, HubSpot::castDealToHSDealProperties($dealDetail));

                    
                }
            }

            if ($deal_status == 'partially_dispatched') {
                $dealDetail->deal_status = $deal_status;
                $dealDetail->save();
            }
            
            
            $jDetail = JobScheduling::UpdateJobStatus($id)->first();
            $detail = JobScheduleHelper::setUpdateJobStatusData($jDetail);

            event(new JobEvent('dispatch'));
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $detail, 'Job status updated');
        } catch (Exception $e) {
            Log::error("DispatchController@UpdateJobDispatchStatus - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }


    public function UpdateLineDispatchStatus(LineDispatchRequest $request, $line)
    {
        Log::info("DispatchController@UpdateLineDispatchStatus", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {
            $lineDetail = LineItems::where('line_item_id', $line)->first();

            if (!$lineDetail) {
                return ResponseHelper::errorResponse('Cannot update dispatch status.', config('constant.status_code.not_found'));
            }
            $id = $lineDetail->line_item_id;

            $params = $request->all();
            $path = 'img/' .  Carbon::now()->timestamp . '-' . $id;
            $deal_status = $request->status;

            $original_image_url = '';
            if (isset($params['signature'])) {
                $original_image_url = $this->saveImage($params['signature'], $path);
            }
            $number_dispatched = $lineDetail->number_dispatched;
            $total = $number_dispatched + $params['number_items_collected'];
            $status = 'Partially Shipped';
            $quantity = $lineDetail->quantity;

            if ($quantity < $total) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), '', 'number_items_collected should be less than or equal to available quantity');
            }

            if ($quantity == $total) {
                $status = 'Dispatched';
            }

            do {
                //generate a random string using Laravel's str_random helper
                $dId = Str::random(16);
            } //check if the token already exists and if it does, try again
            while (Dispatch::where('dispatch_id', $dId)->first());

            return $deal_status;

            if ($deal_status == 'fully_dispatched') {
                //transaction
                DB::transaction(function () use ($id, $params, $lineDetail, $original_image_url, $dId, $status, $total) {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                    DB::table('line_items')
                        ->where('line_item_id', $id)
                        ->update(['line_item_status' => $status, 'number_dispatched' => $total]);

                    DB::table('dispatch')->insert([
                        'dispatch_id' => $dId,
                        'object_id' => $id,
                        'object_type' => 'LINE_ITEM',
                        'signature' => $original_image_url,
                        'dispatch_customer_name' => $params['customer_name'],
                        'dispatch_customer_email'=> $params['customer_email']
                    ]);

                    //update body param in line_items table
                    if (isset($params['dispatch_status'])) {
                        $lineDetail->dispatch_status = $params['dispatch_status'];
                        $lineDetail->save();
                    }
                });

                if ($deal_status == 'partially_dispatched') {
                    if ($lineDetail->quantity > 0) {
                        $lineDetail->quantity = $lineDetail->quantity - $params['number_items_collected'];
                        $lineDetail->save();
                    }
                }

                $lineDetail->number_dispatched = $lineDetail->number_dispatched + $params['number_items_collected'];
                $lineDetail->save();
            }

            if ($deal_status == 'partially_dispatched') {
                $lineDetail->number_dispatched = $lineDetail->number_dispatched + $params['number_items_collected'];
                $lineDetail->save();
            }

            $job_id = $lineDetail->job_id;
            $jDetail = JobScheduling::UpdateJobStatus($job_id)->first();

            $dispatchCount = 0;
            $lineCount = 0;
            if ($jDetail['lines']) {
                foreach ($jDetail['lines'] as $line) {
                    if ($line) {
                        $lineCount++;
                        if ($line->line_item_status == 'Dispatched') {
                            $dispatchCount++;
                        }
                    }
                }
            }

            if ($lineCount > 0  && $dispatchCount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['job_status' => 'Complete']);
            }

            if ($lineCount > 0  && $dispatchCount != $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['job_status' => 'Partially Shipped']);
            }
            $Detail = JobScheduling::UpdateJobStatus($job_id)->first();

            $detail = JobScheduleHelper::setUpdateJobStatusData($Detail);

            event(new JobEvent('dispatch'));
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $detail, 'Line status updated');
        } catch (Exception $e) {
            Log::error("DispatchController@UpdateLineDispatchStatus - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }

    public function PrintPackingSlip(Request $request, String $dealId) {
        Log::info("DispatchController@PrintPackingSlip", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $deal = Deal::find($dealId);
            if(!$deal) {
                return ResponseHelper::errorResponse('Deal not found', config('constant.status_code.not_found'));
            }

            $dealStatus = 'N/A';
            if ($deal->deal_status == 'partially_dispatched') {
                $dealStatus = 'Partially Dispatched';
            }

            if ($deal->deal_status == 'fully_dispatched') {
                $dealStatus = 'Fully Dispatched';
            }

            $data = [
                'api_url' => env('APP_URL', null),
                'status' => $dealStatus,
                'line_items' => json_decode($request->lineItems),
                'signature' => $request->signature,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email
            ];

            $pdf = PDF::loadView('pdfs/packing_slip', $data);
            $date = new DateTime();

            return $pdf->download(($date->format('Y_m_d_H_i_s') .'_'.$dealId . '_packing_slip.pdf').'.pdf');

        } catch (Exception $e) {
            return $e->getMessage();
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.error'));
        }
    }

    public function SendEmailPackingSlip(Request $request, String $dealId) {
        $user = $request->user();

        $deal = Deal::find($dealId);
        if(!$deal) {
            return ResponseHelper::errorResponse('Deal not found', config('constant.status_code.not_found'));
        }
        
        $data = [
            'file' => $request->pdf_path,
            'content'=>'This is an automated email from the Sync - Coastal Powder Coating & Blasting Application. Some items have been dispatched. Please see attached for more details.',
            'api_url' => env('APP_URL', null),
            'subject'=>"Dispatched - ".$deal->deal_name,
            'deal_name' => $deal->deal_name
        ];

        if($request->customer_email){
            Mail::to($request->customer_email)->send(new SendPackingSlipMail($data));
        }

        return ResponseHelper::responseMessage(config('constant.status_code.success'), null, 'Packing slip email sent');

    }

    public function UpdateBulkLineDispatchStatus(Request $request)
    {
        try {
            if (!isset($request->line_items)) {
                return ResponseHelper::errorResponse('Please select at least one line item to continue', config('constant.status_code.not_found'));
            }
    
            $decodedLineItems = json_decode($request->line_items);
            foreach ($decodedLineItems as $key => $lineItem) {
                $lineDetail = LineItems::where('line_item_id', $lineItem->id)->first();
    
                if (!$lineDetail) {
                    return ResponseHelper::errorResponse('Cannot update dispatch status.', config('constant.status_code.not_found'));
                }
                $id = $lineDetail->line_item_id;
                $params = $request->all();
                $path = 'img/' .  Carbon::now()->timestamp . '-' . $id;
                $deal_status = $request->status;
    
                $originalSignUrl = '';
                if (isset($params['signature'])) {
                    $originalSignUrl = $this->saveImage($params['signature'], $path);
                }
                
                $number_dispatched = $lineDetail->number_dispatched;
                $total = $number_dispatched + $lineItem->number_items_collected;
                $status = 'Partially Shipped';
                $quantity = $lineDetail->quantity;
    
                if ($quantity < $total) {
                    return ResponseHelper::responseMessage(config('constant.status_code.success'), '', 'number_items_collected should be less than or equal to available quantity');
                }
    
                if ($quantity == $total) {
                    $status = 'Dispatched';
                }
    
                do {
                    //generate a random string using Laravel's str_random helper
                    $dId = Str::random(16);
                } //check if the token already exists and if it does, try again
                while (Dispatch::where('dispatch_id', $dId)->first());
    
                if ($deal_status == 'fully_dispatched') {
                    //transaction
                    DB::transaction(function () use ($id, $params, $lineDetail, $originalSignUrl, $dId, $status, $total) {
                        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    
                        DB::table('line_items')
                            ->where('line_item_id', $id)
                            ->update(['line_item_status' => $status, 'number_dispatched' => $total]);
    
                        DB::table('dispatch')->insert([
                            'dispatch_id' => $dId,
                            'object_id' => $id,
                            'object_type' => 'LINE_ITEM',
                            'signature' => $originalSignUrl,
                            'dispatch_customer_name' => $params['customer_name'],
                            'dispatch_customer_email'=> $params['customer_email']
                        ]);
    
                        //update body param in line_items table
                        if (isset($params['dispatch_status'])) {
                            $lineDetail->dispatch_status = $params['dispatch_status'];
                            $lineDetail->save();
                        }
                    });
    
    
                    $lineDetail->number_dispatched = $lineDetail->number_dispatched + $lineItem->number_items_collected;
                    $lineDetail->save();
                }
    
                if ($deal_status == 'partially_dispatched') {
                    $lineDetail->number_dispatched = $lineDetail->number_dispatched + $lineItem->number_items_collected;
                    $lineDetail->save();
                }
    
                $job_id = $lineDetail->job_id;
                $jDetail = JobScheduling::UpdateJobStatus($job_id)->first();
    
                $dispatchCount = 0;
                $lineCount = 0;
                if ($jDetail['lines']) {
                    foreach ($jDetail['lines'] as $line) {
                        if ($line) {
                            $lineCount++;
                            if ($line->line_item_status == 'Dispatched') {
                                $dispatchCount++;
                            }
                        }
                    }
                }
    
                if ($deal_status == 'fully_dispatched') {
                    if ($lineCount > 0  && $dispatchCount == $lineCount) {
                        DB::table('job_scheduling')
                            ->where('job_id', $job_id)
                            ->update(['job_status' => 'Complete']);
                    }
                } else {
                    if ($lineCount > 0  && $dispatchCount != $lineCount) {
                        DB::table('job_scheduling')
                            ->where('job_id', $job_id)
                            ->update(['job_status' => 'Partially Shipped']);
                    }
                }
                
                $Detail = JobScheduling::UpdateJobStatus($job_id)->first();
                JobScheduleHelper::setUpdateJobStatusData($Detail);
              
            }

            $deal = Deal::find($request->deal_id);
            if(!$deal) {
                return ResponseHelper::errorResponse('Deal not found', config('constant.status_code.not_found'));
            }

            $pdfStatus = 'Partially Dispatched';

            if ($request->status == 'fully_dispatched') {
                $pdfStatus = 'Fully Dispatched';
            }

            $data = [
                'api_url' => env('APP_URL', null),
                'status' => $pdfStatus,
                'line_items' => json_decode($request->line_items),
                'signature' => $originalSignUrl,
                'customer_name' => $params['customer_name'],
                'customer_email' => $params['customer_email']
            ];
    
            $pdf = PDF::loadView('pdfs/packing_slip', $data);
            $date = new DateTime();
            $path = 'packing_slips/' .  $date->format('Y_m_d_H_i_s') .'_'.$request->deal_id . '_packing_slip.pdf';
            $fileName = $this->savePDF($pdf, $path);
    
            PackingSlip::create([
                'deal_id' => $request->deal_id,
                'packing_slip_name' => $date->format('Y_m_d_H_i_s') .'_'.$deal->deal_name,
                'packing_slip_file' => $fileName,
                'packing_slip_data' => $data,
                'packing_slip_signature_file' => $originalSignUrl,
                'packing_slip_customer_name' => $params['customer_name'] ?? 'N/A'
            ]);

            $data = [
                'fileName' => $fileName,
                'signature' => $originalSignUrl
            ];

            return $data;
        } catch (Exception $e) {
            Log::error("DispatchController@UpdateBulkLineDispatchStatus - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }

    }

    public function SendDispatchEmailPackingSlip(Request $request, String $dealId) {
        $user = $request->user();

        $deal = Deal::find($dealId);
        if(!$deal) {
            return ResponseHelper::errorResponse('Deal not found', config('constant.status_code.not_found'));
        }
        
        $data = [
            'file' => $request->pdf_path,
            'content'=>'This is an automated email from the Sync - Coastal Powder Coating & Blasting Application. Some items have been dispatched. Please see attached for more details.',
            'api_url' => env('APP_URL', null),
            'subject'=>"Dispatched - ".$deal->deal_name,
            'deal_name' => $deal->deal_name
        ];

        Mail::to($user->email)->send(new SendDispatchPackingSlipMail($data));
        return ResponseHelper::responseMessage(config('constant.status_code.success'), null, 'Packing slip email sent');

    }
}
