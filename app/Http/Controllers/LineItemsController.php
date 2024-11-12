<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LineItems;
use App\Helpers\ResponseHelper;
use App\Models\JobScheduling;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class LineItemsController extends Controller
{
    public function updateLineStatus(Request $request, $line)
    {
        Log::info("LineItemsController@updateLineStatus", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {
            $lineDetail = LineItems::where('line_item_id', $line)->first();

            if (!$lineDetail) {
                return ResponseHelper::errorResponse('Cannot update status. Incorrect line id.', config('constant.status_code.not_found'));
            }
            $params = json_decode($request->getContent());

            $lineStatus = '';
            foreach ($params as $key => $value) {
                if ($key == 'line_item_status') {
                    $lineStatus = $value;
                }
                $lineDetail->{$key} = $value;
            }

            $lineDetail->save();

            $job_id = $lineDetail->job_id;
            $jDetail = JobScheduling::with(['lines'])->where('job_id', $job_id)->first();

            $PassCount = 0;
            $readyCount = 0;
            $inprogressCount = 0;
            $completeCount = 0;
            $errorCount = 0;
            $waitCount = 0;

            $readyStatus = 'Ready';
            $inProgressStatus = 'In progress';
            $completeStatus = 'Complete';
            $errorStatus = 'Error | Redo';
            $waitingQc = 'Awaiting QC';

            $lineInProgress = 'in progress';
            $lineReady = 'ready';
            $lineComplete = 'complete';
            $lineError = 'error | redo';
            $lineWaiting = 'waiting';

            $cheminprogresscount = 0;
            $chemreadycount = 0;
            $chemcompletecount = 0;
            $chemerrorcount = 0;
            $chemwaitingcount = 0;

            $treatmentinprogresscount = 0;
            $treatmentreadycount = 0;
            $treatmentcompletecount = 0;
            $treatmenterrorcount = 0;
            $treatmentwaitingcount = 0;

            $burninprogresscount = 0;
            $burnreadycount = 0;
            $burncompletecount = 0;
            $burnerrorcount = 0;
            $burnwaitingcount = 0;

            $blastinprogresscount = 0;
            $blastreadycount = 0;
            $blastcompletecount = 0;
            $blasterrorcount = 0;
            $blastwaitingcount = 0;

            $powderinprogresscount = 0;
            $powderreadycount = 0;
            $powdercompletecount = 0;
            $powdererrorcount = 0;
            $powderwaitingcount = 0;

            $lineCount = 0;
            if ($jDetail['lines']) {
                foreach ($jDetail['lines'] as $line) {
                    if ($line) {
                        $lineCount++;
                        if ($line->line_item_status == $lineStatus) {
                            $PassCount++;
                        }
                        //Ready
                        if ($line->chem_status == $lineReady && $line->treatment_status == $lineReady && $line->burn_status == $lineReady && $line->blast_status == $lineReady && $line->powder_status == $lineReady) {
                            $readyCount++;
                        }
                        //In Progress
                        if ($line->chem_status == $lineInProgress && $line->treatment_status == $lineInProgress && $line->burn_status == $lineInProgress && $line->blast_status == $lineInProgress && $line->powder_status == $lineInProgress) {
                            $inprogressCount++;
                        }
                        //Complete
                        if ($line->chem_status == $lineComplete && $line->treatment_status == $lineComplete && $line->burn_status == $lineComplete && $line->blast_status == $lineComplete && $line->powder_status == $lineComplete) {
                            $completeCount++;
                        }
                        //Error | Redo
                        if ($line->chem_status == $lineError && $line->treatment_status == $lineError && $line->burn_status == $lineError && $line->blast_status == $lineError && $line->powder_status == $lineError) {
                            $errorCount++;
                        }
                        //waiting
                        if ($line->chem_status == $lineWaiting && $line->treatment_status == $lineWaiting && $line->burn_status == $lineWaiting && $line->blast_status == $lineWaiting && $line->powder_status == $lineWaiting) {
                            $waitCount++;
                        }

                        //$cheminprogresscount
                        if($line->chem_status == $lineInProgress){
                            $cheminprogresscount++;
                        }
                        //$chemreadycount
                        if($line->chem_status == $lineReady){
                            $chemreadycount++;
                        }
                        //$chemcompletecount
                        if($line->chem_status == $lineComplete){
                            $chemcompletecount++;
                        }
                        //$chemerrorcount
                        if($line->chem_status == $lineError){
                            $chemerrorcount++;
                        }
                        //$chemwaitingcount
                        if($line->chem_status == $lineWaiting){
                            $chemwaitingcount++;
                        }

                        //$treatmentinprogresscount
                        if($line->treatment_status == $lineInProgress){
                            $treatmentinprogresscount++;
                        }
                        //$treatmentreadycount
                        if($line->treatment_status == $lineReady){
                            $treatmentreadycount++;
                        }
                        //$treatmentcompletecount
                        if($line->treatment_status == $lineComplete){
                            $treatmentcompletecount++;
                        }
                        //$treatmenterrorcount
                        if($line->treatment_status == $lineError){
                            $treatmenterrorcount++;
                        }
                        //$treatmentwaitingcount
                        if($line->treatment_status == $lineWaiting){
                            $treatmentwaitingcount++;
                        }

                        //$burninprogresscount
                        if($line->burn_status == $lineInProgress){
                            $burninprogresscount++;
                        }
                        //$burnreadycount
                        if($line->burn_status == $lineReady){
                            $burnreadycount++;
                        }
                        //$burncompletecount
                        if($line->burn_status == $lineComplete){
                            $burncompletecount++;
                        }
                        //$burnerrorcount
                        if($line->burn_status == $lineError){
                            $burnerrorcount++;
                        }
                        //$burnwaitingcount
                        if($line->burn_status == $lineWaiting){
                            $burnwaitingcount++;
                        }

                        //$blastinprogresscount
                        if($line->blast_status == $lineInProgress){
                            $blastinprogresscount++;
                        }
                        //$blastreadycount
                        if($line->blast_status == $lineReady){
                            $blastreadycount++;
                        }
                        //$blastcompletecount
                        if($line->blast_status == $lineComplete){
                            $blastcompletecount++;
                        }
                        //$blasterrorcount
                        if($line->blast_status == $lineError){
                            $blasterrorcount++;
                        }
                        //$blastwaitingcount
                        if($line->blast_status == $lineWaiting){
                            $blastwaitingcount++;
                        }

                        //$powderinprogresscount
                        if($line->powder_status == $lineInProgress){
                            $powderinprogresscount++;
                        }
                        //$powderreadycount
                        if($line->powder_status == $lineReady){
                            $powderreadycount++;
                        }
                        //$powdercompletecount
                        if($line->powder_status == $lineComplete){
                            $powdercompletecount++;
                        }
                        //$powdererrorcount
                        if($line->powder_status == $lineError){
                            $powdererrorcount++;
                        }
                        //$powderwaitingcount
                        if($line->powder_status == $lineWaiting){
                            $powderwaitingcount++;
                        }    
                    }
                }
            }

            if ($lineCount > 0  && $PassCount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['job_status' => $lineStatus]);
            }


            //Ready
            if ($lineCount > 0  && $readyCount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['job_status' => $readyStatus, 'chem_status' => $lineReady, 'treatment_status' => $lineReady, 'burn_status' => $lineReady, 'blast_status' => $lineReady, 'powder_status' => $lineReady]);
            }

            //In Progress
            if ($lineCount > 0  && $inprogressCount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['job_status' => $inProgressStatus, 'chem_status' => $lineInProgress, 'treatment_status' => $lineInProgress, 'burn_status' => $lineInProgress, 'blast_status' => $lineInProgress, 'powder_status' => $lineInProgress]);
            }
            //Complete
            if ($lineCount > 0  && $completeCount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['job_status' => $completeStatus, 'chem_status' => $lineComplete, 'treatment_status' => $lineComplete, 'burn_status' => $lineComplete, 'blast_status' => $lineComplete, 'powder_status' => $lineComplete]);
            }
            //Error | Redo
            if ($lineCount > 0  &&  $errorCount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['job_status' => $errorStatus, 'chem_status' => $lineError, 'treatment_status' => $lineError, 'burn_status' => $lineError, 'blast_status' => $lineError, 'powder_status' => $lineError]);
            }
            //Waiting
            if ($lineCount > 0  &&  $waitCount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['job_status' => $waitingQc, 'chem_status' => $lineWaiting, 'treatment_status' => $lineWaiting, 'burn_status' => $lineWaiting, 'blast_status' => $lineWaiting, 'powder_status' => $lineWaiting]);
            }
            
            if ($lineCount > 0  &&  $powderinprogresscount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['powder_status' => $lineInProgress]);
            }
            if ($lineCount > 0  &&  $powderreadycount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['powder_status' => $lineReady]);
            }
            if ($lineCount > 0  &&  $powdercompletecount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['powder_status' => $lineComplete]);
            }
            if ($lineCount > 0  &&  $powdererrorcount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['powder_status' => $lineError]);
            }
            if ($lineCount > 0  &&  $powderwaitingcount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['powder_status' => $lineWaiting]);
            }

            if ($lineCount > 0  &&  $cheminprogresscount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['chem_status' => $lineInProgress]);
            }
            if ($lineCount > 0  &&  $chemreadycount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['chem_status' => $lineReady]);
            }
            if ($lineCount > 0  &&  $chemcompletecount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['chem_status' => $lineComplete]);
            }
            if ($lineCount > 0  &&  $chemerrorcount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['chem_status' => $lineError]);
            }
            if ($lineCount > 0  &&  $chemwaitingcount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['chem_status' => $lineWaiting]);
            }

            if ($lineCount > 0  &&  $treatmentinprogresscount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['treatment_status' => $lineInProgress]);
            }
            if ($lineCount > 0  &&  $treatmentreadycount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['treatment_status' => $lineReady]);
            }
            if ($lineCount > 0  &&  $treatmentcompletecount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['treatment_status' => $lineComplete]);
            }
            if ($lineCount > 0  &&  $treatmenterrorcount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['treatment_status' => $lineError]);
            }
            if ($lineCount > 0  &&  $treatmentwaitingcount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['treatment_status' => $lineWaiting]);
            }

            if ($lineCount > 0  &&  $burninprogresscount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['burn_status' => $lineInProgress]);
            }
            if ($lineCount > 0  &&  $burnreadycount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['burn_status' => $lineReady]);
            }
            if ($lineCount > 0  &&  $burncompletecount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['burn_status' => $lineComplete]);
            }
            if ($lineCount > 0  &&  $burnerrorcount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['burn_status' => $lineError]);
            }
            if ($lineCount > 0  &&  $burnwaitingcount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['burn_status' => $lineWaiting]);
            }

            if ($lineCount > 0  &&  $blastinprogresscount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['blast_status' => $lineInProgress]);
            }
            if ($lineCount > 0  &&  $blastreadycount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['blast_status' => $lineReady]);
            }
            if ($lineCount > 0  &&  $blastcompletecount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['blast_status' => $lineComplete]);
            }
            if ($lineCount > 0  &&  $blasterrorcount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['blast_status' => $lineError]);
            }
            if ($lineCount > 0  &&  $blastwaitingcount == $lineCount) {
                DB::table('job_scheduling')
                    ->where('job_id', $job_id)
                    ->update(['blast_status' => $lineWaiting]);
            }

            return ResponseHelper::responseMessage(config('constant.status_code.success'), '', 'line updated');
        
        } catch (Exception $e) {
            Log::error("LineItemsController@updateLineStatus - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }
}
