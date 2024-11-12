<?php

namespace App\Http\Controllers;

use App\Events\JobEvent;
use App\Helpers\JobScheduleHelper;
use App\Helpers\ResponseHelper;
use App\Http\Requests\QCRequest;
use App\Http\Traits\ImageUploadTrait;
use App\Models\Deal;
use App\Models\FailedJob;
use App\Models\FailedLineItems;
use App\Models\Integration\HubSpot;
use App\Models\Job;
use App\Models\JobScheduling;
use App\Models\LineItems;
use App\Models\NonConformanceReport;
use App\Models\QualityControl;
use App\Services\ApiResponse;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Mail\SendQCMail;
use Illuminate\Support\Facades\Mail;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\Uuid;
use App\Http\Requests\QCLineRequest;
use App\Models\NcrFailedOption;
use Illuminate\Support\Facades\Auth;
use App\Models\Treatments;

class QualityControlController extends Controller
{
    use ImageUploadTrait;

    public function __construct()
    {
            $this->apiResponse = new ApiResponse;
    }

    public function getAllQualityControl(Request $request)
    {
        Log::info("QualityControlController@getAllQualityControl", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $jobList = JobScheduling::getQcJob()->paginate(config('constant.pagination.job'));
            foreach ($jobList as $job) {
                $lineQc = array();
                if ($job['lines']) {
                    foreach ($job['lines'] as $line) {
                        //line QC
                        if (QualityControl::where('object_id', $line->line_item_id)->where('object_type', 'LINE_ITEM')->first()) {
                            array_push($lineQc, QualityControl::where('object_id', $line->line_item_id)->where('object_type', 'LINE_ITEM')->first());
                        }
                    }
                }
                //JobQC
                $jobQc = QualityControl::where('object_id', $job->job_id)->where('object_type', 'JOB')->get();
                foreach ($jobQc as $key => $qc) {
                    //merge job & line QC
                    array_push($lineQc, $qc);
                }
                $job->qc = $lineQc;
            }
            $jobList->getCollection()->transform(function ($value) {
                return JobScheduleHelper::setQCData($value);
            });

            if ($jobList->total() > 0) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), $jobList, 'Quality control jobs list.');
            } else {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'No quality control jobs found.');
            }
        } catch (Exception $e) {
            Log::error("QualityControlController@getAllQualityControl - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }

    public function updateQCJobStatus(QCRequest $request, $jobId)
    {
        Log::info("QualityControllController@updateQCJobStatus - process", [["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]]);
        try {

            $jobDetail = Job::where('job_id', $jobId)->first();

            if (!$jobDetail) {
                Log::warning("QualityControllController@updateQCJobStatus - Job not found for id: {$jobId}", [["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]]);
                return ResponseHelper::errorResponse('Cannot update Qc status.', config('constant.status_code.not_found'));
            }
            $id = $jobDetail->job_id;
            $dealId = $jobDetail->deal_id;

            $params = $request->all();
            $path = 'QCimg/' .  Carbon::now()->timestamp . '-' . $id;

            $original_image_url = '';
            if (isset($params['signature'])) {
                $original_image_url = $this->saveImage($params['signature'], $path);
            }

            $original_photo_url = '';
            if (isset($params['photo'])) {
                $original_photo_url = $this->saveImage($params['photo'], $path);
            }

            //create new job based on the failed job
            if (isset($params['qc_status']) && $params['qc_status'] === 'failed') {

                $comment = NcrFailedOption::where('ncr_failed_id',$params['ncr_failed_id'])->first();
                Log::info("QualityControllController@updateQCJobStatus - Job {$jobId} has failed", ["job" => $jobDetail, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]]);

                $ncr = NonConformanceReport::create([
                    'initial_job_id' => $jobDetail->job_id,
                    'user_id'=>Auth::user()->user_id,
                    'ncr_id' => Uuid::uuid4(),
                    'comments'=>$comment->ncr_failed,
                    'ncr_failed_id' => $params['ncr_failed_id'],
                    'photo' => $original_photo_url,
                ]);

                Log::info("QualityControllController@updateQCJobStatus - Create NCR", ["job" => $jobDetail, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]]);
                
                $failedJob = FailedJob::create([
                    'ncr_id' => $ncr->ncr_id,
                    'deal_id' => $jobDetail->deal_id,
                    'job_number' => $jobDetail->job_number ? $jobDetail->job_number : '',
                    'job_prefix' => $jobDetail->job_prefix ? $jobDetail->job_prefix : '',
                    'colour' => $jobDetail->colour,
                    'hs_ticket_id' => $jobDetail->hs_ticket_id ? $jobDetail->hs_ticket_id : '',
                    'priority' => $jobDetail->priority,
                    'treatment' => $jobDetail->treatment,
                    'material' => $jobDetail->material,
                    'chem_priority' => $jobDetail->chem_priority,
                    'treatment_priority' => $jobDetail->treatment_priority,
                    'burn_priority' => $jobDetail->burn_priority,
                    'blast_priority' => $jobDetail->blast_priority,
                    'powder_priority' => $jobDetail->powder_priority,
                    'chem_bay_required' => $jobDetail->chem_bay_required,
                    'chem_status' => $jobDetail->chem_status,
                    'chem_contractor_return_date' => $jobDetail->chem_contractor_return_date,
                    'chem_completed' => $jobDetail->chem_completed,
                    'chem_date' => $jobDetail->chem_date,
                    'treatment_bay_required' => $jobDetail->treatment_bay_required,
                    'treatment_status' => $jobDetail->treatment_status,
                    'treatment_contractor_return_date' => $jobDetail->treatment_contractor_return_date,
                    'treatment_completed' => $jobDetail->treatment_completed,
                    'treatment_date' => $jobDetail->treatment_date,
                    'burn_bay_required' => $jobDetail->burn_bay_required,
                    'burn_status' => $jobDetail->burn_status,
                    'burn_contractor_return_date' => $jobDetail->burn_contractor_return_date,
                    'burn_completed' => $jobDetail->burn_completed,
                    'burn_date' => $jobDetail->burn_date,
                    'blast_bay_required' => $jobDetail->blast_bay_required,
                    'blast_status' => $jobDetail->blast_status,
                    'blast_contractor_return_date' => $jobDetail->blast_contractor_return_date,
                    'blast_completed' => $jobDetail->blast_completed,
                    'blast_date' => $jobDetail->blast_date,
                    'powder_bay_required' => $jobDetail->powder_bay_required,
                    'powder_status' => $jobDetail->powder_status,
                    'powder_completed' => $jobDetail->powder_completed,
                    'powder_date' => $jobDetail->powder_date,
                ]);

                $frontURL = config('constant.front_url');

                // send mail for failed job
                $data = [
                    'content'=>'This is an automated email from the Sync - Coastal Powder Coating & Blasting Application. A job has failed QC Testing and is now in NCR. The Redone job link below will show the new job in the Sync Application. Please see image attached.',
                    'subject'=>'NCR - '.($failedJob->is_error_redo === "yes" ? "ERROR | REDO - ":"") . ($failedJob->deals ? ($failedJob->deals->invoice_number ? $failedJob->deals->invoice_number. ' ' . $failedJob->job_number : $failedJob->job_prefix . ' ' . $failedJob->job_number ) : $failedJob->job_prefix . ' ' . $failedJob->job_number),
                    'job'=>($failedJob->is_error_redo === "yes" ? "ERROR | REDO - ":"") . ($failedJob->deals ? ($failedJob->deals->invoice_number ? $failedJob->deals->invoice_number. ' ' . $failedJob->job_number : $failedJob->job_prefix . ' ' . $failedJob->job_number ) : $failedJob->job_prefix . ' ' . $failedJob->job_number),
                    're_done_job'=>($jobDetail->is_error_redo === "yes" ? "ERROR | REDO - ":"") . ($jobDetail->deals ? ($jobDetail->deals->invoice_number ? $jobDetail->deals->invoice_number. ' ' . $jobDetail->job_number : $jobDetail->job_prefix . ' ' . $jobDetail->job_number ) : $jobDetail->job_prefix . ' ' . $jobDetail->job_number),
                    'link'=>$frontURL.'schedule/burn?id='.$jobDetail->job_id.'&selected=true#'.$jobDetail->job_id,
                    'object_id' => $id,
                    'object_type'=>'JOB',
                    'comment'=>$comment->ncr_failed,
                    'image' => $original_photo_url,
                    'api_url' => env('APP_URL', null)
                ];

                Mail::to(env('ADMIN_MAIL'))->send(new SendQCMail($data));
                Log::info("QualityControllController@updateQCJobStatus - Duplicated Failed Job", ["job" => $jobDetail, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]]);
                $failedLineItems = [];
                if(isset($params['selected_line_item_ids'])) {
                    //explode it to become array
                    $selectedLineItemIds = explode(',', $params['selected_line_item_ids']);
                    foreach ($selectedLineItemIds as $passedLineItemId) {
                        $passedLineItem = LineItems::where('line_item_id', $passedLineItemId)->first();
                        if($passedLineItem) {
                            $failedLineItems[] = $passedLineItem->line_item_id;
                            $failedLineItem = FailedLineItems::create([
                                'ncr_id' => $ncr->ncr_id,
                                'product_id' => $passedLineItem->product_id,
                                'failed_job_id' => $failedJob->failed_job_id,
                                'deal_id' => $passedLineItem->deal_id,
                                'measurement' => $passedLineItem->measurement,
                                'colour' => $passedLineItem->colour,
                                'chem_bay' => $passedLineItem->chem_bay,
                                'treatment_bay' => $passedLineItem->treatment_bay,
                                'burn_bay' => $passedLineItem->burn_bay,
                                'blast_bay' => $passedLineItem->blast_bay,
                                'powder_bay' => $passedLineItem->powder_bay,
                                'chem_date' => $passedLineItem->chem_date,
                                'treatment_date' => $passedLineItem->treatment_date,
                                'burn_date' => $passedLineItem->burn_date,
                                'blast_date' => $passedLineItem->blast_date,
                                'powder_date' => $passedLineItem->powder_date,
                                'description' => $passedLineItem->description,
                                'position' => $passedLineItem->position,
                                'quantity' => $passedLineItem->quantity,
                                'name' => $passedLineItem->name,
                                'price' => $passedLineItem->price,
                                'chem_status' => $passedLineItem->chem_status,
                                'treatment_status' => $passedLineItem->treatment_status,
                                'burn_status' => $passedLineItem->burn_status,
                                'blast_status' => $passedLineItem->blast_status,
                                'powder_status' => $passedLineItem->powder_status,
                                'chem_completed' => $passedLineItem->chem_completed,
                                'treatment_completed' => $passedLineItem->treatment_completed,
                                'burn_completed' => $passedLineItem->burn_completed,
                                'blast_completed' => $passedLineItem->blast_completed,
                                'powder_completed' => $passedLineItem->powder_completed,
                            ]);
                        }
                    }
                }

                Log::info("QualityControllController@updateQCJobStatus - Duplicated line items", ["job" => $jobDetail, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]]);

                $partialJobCompleted = false;
                $movedLineItems = [];
                foreach($jobDetail->lineItems as $lineitem) {
                    if(!in_array($lineitem->line_item_id, $failedLineItems)) {
                        $partialJobCompleted = true;
                        $movedLineItems[] = $lineitem;
                    }
                }

                if($partialJobCompleted) {
                    Log::info("QualityControllController@updateQCJobStatus - Job {$jobId} is partially complete", ["job" => $jobDetail, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]]);
                    $newId = null;
                    do {
                        $newId = Uuid::uuid4()->toString();
                    } while (Job::where('job_id', $newId)->exists());

                    $newJob = $jobDetail->replicate([
                        'job_id',
                        'hs_ticket_id',
                    ]);
                    $newJob->job_id = $newId;
                    $newJob->job_status = 'Awaiting QC Passed';
                    $newJob->job_prefix = $newJob->job_prefix . " - Partial";
                    $newJob->save();

                    foreach($movedLineItems as $lineitem) {
                        $lineitem->job_id = $newJob->job_id;
                        $lineitem->line_item_status = 'QC Passed';
                        $lineitem->save();
                    }
                    
                    //we need this right now cause the object_id is references to line_item table only, it should be both for job and lineitem or nothing
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;');


                    $data = QualityControl::create([
                        'object_id' => $newId,
                        'object_type' => 'JOB',
                        'qc_status' => 'passed',
                        'signature' => $original_image_url,
                        'user_id'=>Auth::user()->user_id,
                        'ncr_failed_id' => isset($params['ncr_failed_id']) ? $params['ncr_failed_id'] : '',
                    ]);
                }

                $process = $params['process'];

                $jobDetail->fresh();

                $process = $params['process'];

                $jobDetail->is_error_redo = 'yes';
                $jobDetail->job_status = 'Ready';
                $jobDetail->treatment = $process;
                $jobDetail->hs_ticket_id = null;
                $jobDetail->save();

                $treatmentBays =[];
                $treatmentBays = str_split($jobDetail->treatment);
                $bayValues = [];
                $lineitemBayValues = [];
                $bayValues['chem_bay_required'] = 'no';
                $bayValues['chem_date'] = null;
                $bayValues['chem_completed'] = null;
                $bayValues['chem_priority'] = -1;
                $bayValues['chem_status'] = null;
                $bayValues['treatment_bay_required'] = 'no';
                $bayValues['treatment_date'] = null;
                $bayValues['treatment_completed'] = null;
                $bayValues['treatment_priority'] = -1;
                $bayValues['treatment_status'] = null;
                $bayValues['burn_bay_required'] = 'no';
                $bayValues['burn_date'] = null;
                $bayValues['burn_completed'] = null;
                $bayValues['burn_priority'] = -1;
                $bayValues['burn_status'] = null;
                $bayValues['blast_bay_required'] = 'no';
                $bayValues['blast_date'] = null;
                $bayValues['blast_completed'] = null;
                $bayValues['blast_priority'] = -1;
                $bayValues['blast_status'] = null;
                $bayValues['powder_bay_required'] = 'no';
                $bayValues['powder_date'] = null;
                $bayValues['powder_completed'] = null;
                $bayValues['powder_priority'] = -1;
                $bayValues['powder_status'] = null;

                $lineitemBayValues['chem_status'] = 'na';
                $lineitemBayValues['chem_bay'] = 'na';
                $lineitemBayValues['chem_date'] = null;
                $lineitemBayValues['chem_completed'] = null;
                $lineitemBayValues['treatment_status'] = 'na';
                $lineitemBayValues['treatment_bay'] = 'na';
                $lineitemBayValues['treatment_date'] = null;
                $lineitemBayValues['treatment_completed'] = null;
                $lineitemBayValues['burn_status'] = 'na';
                $lineitemBayValues['burn_bay'] = 'na';
                $lineitemBayValues['burn_date'] = null;
                $lineitemBayValues['burn_completed'] = null;
                $lineitemBayValues['blast_status'] = 'na';
                $lineitemBayValues['blast_bay'] = 'na';
                $lineitemBayValues['blast_date'] = null;
                $lineitemBayValues['blast_completed'] = null;
                $lineitemBayValues['powder_status'] = 'na';
                $lineitemBayValues['powder_bay'] = 'na';
                $lineitemBayValues['powder_date'] = null;
                $lineitemBayValues['powder_completed'] = null;
                $lineitemBayValues['line_item_status'] = 'Ready';


                foreach($treatmentBays as $i => $bayCode) {
                    $stageStatus = 'waiting';
                    if ($i == 0) {
                        $stageStatus = 'ready';
                    }
                    switch($bayCode) {
                        case 'S':
                            $bayValues['chem_bay_required'] = 'yes';
                            $bayValues['chem_status'] = $stageStatus;
                            $lineitemBayValues['chem_status'] = $stageStatus;
                            break;
                        case 'T':
                            $bayValues['treatment_bay_required'] = 'yes';
                            $bayValues['treatment_status'] = $stageStatus;
                            $lineitemBayValues['treatment_status'] = $stageStatus;

                            break;
                        case 'F':
                            $bayValues['burn_bay_required'] = 'yes';
                            $bayValues['burn_status'] = $stageStatus;
                            $lineitemBayValues['burn_status'] = $stageStatus;
                            break;
                        case 'B':
                            $bayValues['blast_bay_required'] = 'yes';
                            $bayValues['blast_status'] = $stageStatus;
                            $lineitemBayValues['blast_status'] = $stageStatus;
                            break;
                        case 'C':
                        case 'P':
                            $bayValues['powder_bay_required'] = 'yes';
                            $bayValues['powder_status'] = $stageStatus;
                            $lineitemBayValues['powder_status'] = $stageStatus;
                            break;
                    }
                }

                Log::info("QualityControllController@updateQCJobStatus - Reset error|redo job statuses", ["job" => $jobDetail, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]]);

                $jobDetail->update($bayValues);
                foreach($jobDetail->lineItems as $lineitem) {
                    $lineitem->update($lineitemBayValues);
                }

            } else {
                //we need this right now cause the object_id is references to line_item table only, it should be both for job and lineitem or nothing
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                Log::info("QualityControllController@updateQCJobStatus - Job {$jobId} has passed qc", ["job" => $jobDetail, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]]);

                $data = QualityControl::create([
                    'object_id' => $id,
                    'object_type' => 'JOB',
                    'qc_status' => $params['qc_status'],
                    'ncr_failed_id' => isset($params['ncr_failed_id']) ? $params['ncr_failed_id'] : '',
                    'photo' => $original_photo_url,
                    'user_id'=>Auth::user()->user_id,
                    'signature' => $original_image_url
                ]);

                LineItems::where('job_id', $id)->update(['line_item_status' => 'QC Passed']);

                $allQCPassed = true;
                foreach($jobDetail->deal->jobs as $job) {
                    if ($jobDetail->job_id == $job->job_id) {
                        continue;
                    }

                    if ($job->job_status !== 'QC Passed' && $job->job_status !== 'Awaiting QC Passed') {
                        $allQCPassed = false;
                    }
                }

                if ($allQCPassed) {
                    Log::info("QualityControllController@updateQCJobStatus - All jobs for {$jobDetail->deal->deal_id} have passed QC", ["job" => $jobDetail, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]]);
                    $jobDetail->update(['job_status' => 'QC Passed']);
                    foreach($jobDetail->deal->jobs as $job) {
                        $job->update(['job_status' => 'QC Passed']);
                    }

                    $deal = $jobDetail->deal;

                    $integration = HubSpot::where(['platform' => 'HUBSPOT', 'integration_status'=>'Connected'])->first();
                    if (!$integration) {
                        Log::warning("QualityControllController@updateQCJobStatus - HubSpot integration is not connected", ["deal"=> $deal, "integration" => $integration, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()?->user_id]]]);
                    } else {
                        $deal->deal_status = 'ready_for_dispatch';
                        $deal->save();
                        Log::info("QualityControllController@updateQCJobStatus - Update deal {$deal->deal_id} stage to ready to be invoiced", ["properties"=>HubSpot::castDealToHSDealProperties($deal), "deal"=> $deal, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()?->user_id]]]);
                        $integration->updateDeal($deal->hs_deal_id, HubSpot::castDealToHSDealProperties($deal));
                    }

                } else {
                    Log::info("QualityControllController@updateQCJobStatus - {$jobId} is awaiting sister jobs to be QC Passed", ["job" => $jobDetail, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]]);
                    $jobDetail->update(['job_status' => 'Awaiting QC Passed']);
                }
            }   

            event(new JobEvent('qc'));
            return response()->json(['message' => 'Successfully '. $params['qc_status'] .' the job.'], 200);

        } catch (Exception $e) {
            return $e->getMessage();
            Log::error("QualityControllController@updateQCJobStatus - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->id, 'params'=>$params],
            ]);
            \Sentry\captureException($e);
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }

    public function updateQCLineStatus(QCLineRequest $request, $lineId)
    {
        Log::info("QualityController@updateQCLineStatus", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $lineDetail = LineItems::where('line_item_id', $lineId)->first();

            if (!$lineDetail) {
                return ResponseHelper::errorResponse('Cannot update qc status.', config('constant.status_code.not_found'));
            }
            $id = $lineDetail->line_item_id;

            do {
                //generate a random string using Laravel's str_random helper
                $dId = Str::random(16);
            } //check if the token already exists and if it does, try again
            while (QualityControl::where('qc_id', $dId)->first());

            $params = $request->all();
            $path = 'QCimg/' .  Carbon::now()->timestamp . '-' . $id;

            $original_image_url = '';
            if (isset($params['signature'])) {
                $original_image_url = $this->saveImage($params['signature'], $path);
            }


            $original_photo_url = '';
            if (isset($params['photo'])) {
                $original_photo_url = $this->saveImage($params['photo'], $path);
            }

            //transaction
            DB::transaction(function () use ($id, $params, $lineDetail, $original_image_url, $dId, $original_photo_url) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');


                DB::table('quality_controls')->insert([
                    'qc_id' => $dId,
                    'object_id' => $id,
                    'object_type' => 'LINE_ITEM',
                    'qc_status' => $params['qc_status'],
                    'ncr_failed_id' => $params['ncr_failed_id'],
                    'photo' => $original_photo_url,
                    'user_id'=>Auth::user()->user_id,
                    'signature' => $original_image_url
                ]);

                if (isset($params['qc_status']) && $params['qc_status'] == 'failed') {
                    DB::table('line_items')
                        ->where('line_item_id', $id)
                        ->update(['line_item_status' => 'Awaiting QC']);
                    $comment = NcrFailedOption::where('ncr_failed_id',$params['ncr_failed_id'])->first();

                    $data = [
                        'comment'=>$comment->ncr_failed,
                        'object_id' => $id,
                        'object_type'=>'LINE_ITEM',
                        'image' => $original_photo_url,//"https://i.ibb.co/L9XmyC8/1.jpg",
                        'api_url' => env('APP_URL', null)
                    ];
                    Mail::to(env('ADMIN_MAIL'))->send(new SendQCMail($data));
                } else {
                    DB::table('line_items')
                        ->where('line_item_id', $id)
                        ->update(['line_item_status' => 'QC Passed']);
            }
            });


            $job_id = $lineDetail->job_id;
            $jDetail = JobScheduling::UpdateJobStatus($job_id)->first();

            $PassCount = 0;
            $lineCount = 0;
            if ($jDetail['lines']) {
                foreach ($jDetail['lines'] as $line) {
                    if ($line) {
                        $lineCount++;
                        if ($line->line_item_status == 'QC Passed') {
                            $PassCount++;
                        }
                    }
                }
            }

            if ($lineCount > 0  && $PassCount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['job_status' => 'QC Passed']);
            }

            if ($lineCount > 0  && $PassCount != $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['job_status' => 'Awaiting QC']);
            }
            $Detail = JobScheduling::UpdateJobStatus($job_id)->first();

            $detail = JobScheduleHelper::setUpdateJobStatusData($Detail);

            event(new JobEvent('qc'));
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $detail, 'Line status updated');
        } catch (Exception $e) {
            Log::error("QualityController@updateQCLineStatus - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }

    public function getQCPassedJobs(Request $request)
    {
        Log::info("QualityController@getQCPassedJobs", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {

            $jobList = Job::with(['lines', 'deal'])->where('job_status', 'Awaiting QC Passed')->orWhere('job_status', 'QC Passed')->paginate(config('constant.pagination.dispatch'));

            return $jobList;
            // foreach ($jobList as $job) {
            //     $lineQc = array();

            //     $job->amount = 0;
            //     if ($job['lines']) {
            //         foreach ($job['lines'] as $line) {
            //             if ($line->line_product) {
            //                 $job->amount += $line->line_product->price;
            //             }
            //             //line QC
            //             if (QualityControl::where('object_id', $line->line_item_id)->where('object_type', 'LINE_ITEM')->first()) {
            //                 array_push($lineQc, QualityControl::where('object_id', $line->line_item_id)->where('object_type', 'LINE_ITEM')->first());
            //             }
            //         }
            //     }
            //     //JobQC
            //     $jobQc = QualityControl::where('object_id', $job->job_id)->where('object_type', 'JOB')->get();
            //     foreach ($jobQc as $key => $qc) {
            //         //merge job & line QC
            //         array_push($lineQc, $qc);
            //     }
            //     $job->qc = $lineQc;
            // }
            // $jobList->getCollection()->transform(function ($value) {
            //     return JobScheduleHelper::setQCData($value);
            // });

            // return ResponseHelper::responseMessage(config('constant.status_code.success'), $jobList, 'Quality control jobs list.');

        } catch (Exception $e) {
            Log::error('QualityControlController@getQCPassedJobs - Error '. $e->getMessage(), [
                'trace' => $e->getTrace(),
                'message' => $e->getMessage()
            ]);
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.not_found'));
        }
    }

    public function getQCPendingJobs(Request $request)
    {
        Log::info("QualityController@getQCPendingJobs", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $jobList = JobScheduling::getQcJob()->paginate(config('constant.pagination.job'));
            foreach ($jobList as $job) {
                $lineQc = array();
                if ($job['lines']) {
                    foreach ($job['lines'] as $line) {
                        //line QC
                        if (QualityControl::where('object_id', $line->line_item_id)->where('object_type', 'LINE_ITEM')->first()) {
                            array_push($lineQc, QualityControl::where('object_id', $line->line_item_id)->where('object_type', 'LINE_ITEM')->first());
                        }
                    }
                }
                //JobQC
                $jobQc = QualityControl::where('object_id', $job->job_id)->where('object_type', 'JOB')->get();
                foreach ($jobQc as $key => $qc) {
                    //merge job & line QC
                    array_push($lineQc, $qc);
                }
                $job->qc = $lineQc;
            }
            $jobList->getCollection()->transform(function ($value) {
                return JobScheduleHelper::setQCData($value);
            });

            if ($jobList->total() > 0) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), $jobList, 'Quality control jobs list.');
            } else {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'No quality control jobs found.');
            }
        } catch (Exception $e) {
            Log::error("QualityControlController@getQCPendingJobs - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }
}
