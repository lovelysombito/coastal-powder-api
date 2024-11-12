<?php

namespace App\Helpers;

use App\Models\FailedJob;
use Carbon\Carbon;
use DateTimeZone;
use App\Models\Job;
use App\Models\LineItems;

class JobHelper
{
    public static function setUpdateDayScheduleData($job, $msg)
    {
        return [
            'message' => $msg,
            'job_id'                   => $job->job_id,
            'chem_priority' => $job->chem_priority,
            'burn_priority' => $job->burn_priority,
            'treatment_priority' => $job->treatment_priority,
            'blast_priority' => $job->blast_priority,
            'powder_priority' => $job->powder_priority,
            'chem_bay'      => $job->chem_bay,
            'burn_bay'    => $job->burn_bay,
            'treatment_bay' => $job->treatment_bay,
            'blast_bay'           => $job->blast_bay,
            'powder_bay'       => $job->powder_bay,
            'chem_date'         => $job->chem_date,
            'end_chem_date'         => $job->end_chem_date,
            'burn_date'      => $job->burn_date,
            'end_burn_date'      => $job->end_burn_date,
            'treatment_date'        => $job->treatment_date,
            'end_treatment_date'        => $job->end_treatment_date,
            'blast_date' => $job->blast_date,
            'end_blast_date' => $job->end_blast_date,
            'powder_date'    => $job->powder_date,
            'end_powder_date'    => $job->end_powder_date,
            'chem_status'           => $job->chem_status,
            'burn_status'       => $job->burn_status,
            'treatment_status'           => $job->treatment_status,
            'blast_status'         => $job->blast_status,
            'powder_status'        => $job->powder_status,
        ];
    }

    public static function has_duplicate($array)
    {
        $counts = array_count_values($array);
        return array_filter($array, function ($value) use ($counts) {
            return $counts[$value] > 1;
        });
    }

    public static function sortingJob($data,$bay)
    {
        usort($data, function ($a, $b) use ($bay) {
            if ($a[$bay.'_date'] == $b[$bay.'_date']) {
                return $a[$bay.'_priority'] - $b[$bay.'_priority'];
            }
            return strcmp($a[$bay.'_date'], $b[$bay.'_date']);
        });
        return $data;
    }

    public static function nextDay($day){
        $nextDayCount = 1;
        $i = 1;
        do {
            $temp = Carbon::now()->addDays($nextDayCount)->format('Y-m-d');
            if (!in_array(date('l', strtotime($temp)), ["Sunday"]) && !in_array($temp, config('constant.holidayList'))) {
                $i++;
            }
            $nextDayCount++;
        } while ($i < $day);

        $colVal = Carbon::now()->addDays($nextDayCount - 1)->format('Y-m-d');
        return $colVal;
    }

    public static function getJobs($request){
        if (!isset($request->start_date) && !isset($request->end_date) && !$request->start_date && !$request->end_date  ) {
            $request->start_date = Carbon::now(new DateTimeZone('Australia/Brisbane'))->format('Y-m-d');
            $request->end_date = Carbon::now(new DateTimeZone('Australia/Brisbane'))->addDays(14)->format('Y-m-d');
        } else if (!isset($request->end_date) && !$request->end_date ) {
            $request->end_date = Carbon::createFromFormat('Y-m-d', $request->start_date, new DateTimeZone('Australia/Brisbane'))->addDays(14)->format('Y-m-d');
        } else if (!isset($request->start_date) && $request->start_date) {
            $request->start_date = Carbon::now(new DateTimeZone('Australia/Brisbane'))->format('Y-m-d');
        }

        $due_date = null;
        if($request->overdue_only && $request->overdue_only == 'true'){
            $due_date = Carbon::now(new DateTimeZone('Australia/Brisbane'))->format('Y-m-d');
        }

        $jobs = Job::getJobs([
            'scheduled_start_date' => $request->start_date,
            'scheduled_end_date'=>$request->end_date,
            'colour'=>$request->colour,
            'status'=>$request->status,
            'material'=>$request->material,
            'treatment'=>$request->treatment,
            'due_date' => $due_date,
            'client_name'=>$request->client_name,
            'po_number'=>$request->po_number,
            'invoice_number'=>$request->invoice_number,
        ])->get();

        return $jobs;
    }

    public static function getJobsBayReport($request){
        $jobs = Job::getJobsBayReport([
            'status'=>$request->status,
        ])->get();

        return $jobs;
    }

    public static function getJobsBayWithDateReport($request){
        $jobs = Job::getJobsBayWithDatesReport([
            'start_date' => $request->filterStartDate,
            'end_date' => $request->filterEndDate
        ])->get();

        return $jobs;
    }

    public static function getFailedJobsBayWithDateReport($request){
        $jobs = FailedJob::getJobsBayWithDatesReport([
            'status'=>$request->status,
            'start_date' => $request->filterStartDate,
            'end_date' => $request->filterEndDate
        ])->get();

        return $jobs;
    }

    public static function lineUpdate($lines,$bay,$status)
    {
        foreach ($lines as $k => $line) {
            $line->{$bay} = $status;
            $line->save();
        }
    }

    public static function checkAwaitingQC($jobDetail,$lines)
    {
        $completeStatus = 'complete';
        $updateAwaiting = false;
        if($jobDetail->chem_bay_required == 'yes'){
            if($jobDetail->chem_status==$completeStatus){
                $updateAwaiting = true;
            } else {
                $updateAwaiting = false;
            }
        } 
        if($jobDetail->burn_bay_required == 'yes'){
            if($jobDetail->burn_status==$completeStatus){
                $updateAwaiting = true;
            } else {
                $updateAwaiting = false;
            }
        }
        if($jobDetail->treatment_bay_required == 'yes'){
            if($jobDetail->treatment_status==$completeStatus){
                $updateAwaiting = true;
            } else {
                $updateAwaiting = false;
            }
        }
        if($jobDetail->blast_bay_required == 'yes'){
            $updateAwaiting = true;
            if($jobDetail->blast_status==$completeStatus){
                $updateAwaiting = true;
            } else {
                $updateAwaiting = false;
            }
        }
        if($jobDetail->powder_bay_required == 'yes'){
            if($jobDetail->powder_status==$completeStatus){
                $updateAwaiting = true;
            } else {
                $updateAwaiting = false;
            }
        }

        if($updateAwaiting){
            $jobDetail->job_status = 'Awaiting QC';
            foreach ($lines as $k => $line) {
                $line->line_item_status = 'Awaiting QC';
                $line->save();
            }
        }
    }
    
    public static function sortingJobDashboard($data,$bay)
    {
        if($bay=='ready'){
            usort($data, function ($a, $b) use ($bay) {
                return strnatcasecmp($a['job_title'], $b['job_title']);
            });
            return $data;
        } else {
            usort($data, function ($a, $b) use ($bay) {
                if ($a[$bay.'_date'] == $b[$bay.'_date']) {
                    return strnatcasecmp($a['job_title'], $b['job_title']);
                }
                return strcmp($a[$bay.'_date'], $b[$bay.'_date']);
            });
            return $data;
        }
    }

    public static function lineJobStatus($jobDetail,$job,$key,$value)
    {
        $lines = LineItems::where('job_id', $job)->get();
        if ($key == 'job_status') {
            foreach ($lines as $k => $line) {
                $line->line_item_status = $value;
                $line->save();
            }
            $jobDetail->{$key} = $value;
            $jobDetail->save();
        }
    }

    public static function detailSet($detail,$bay,$status){
        if($detail->{$bay.'_bay_required'} == 'yes'){
            $detail->{$bay.'_status'} = $status;
        }
        return $detail;
    }

    
    public static function lineSet($detail,$bay,$status){
        $detail->{$bay.'_status'} = $status;
        return $detail;
    }

    public static function jobsFormatter($jobs, $arrayJobs, $inProgressInBay, $currentValueInProgressInBay, $currentValueOfJobsInProgress, $request) {
        $currentValueOfCompletedJobs = 0;
        foreach($jobs as $job) {
            if ($job->job_status == $request->status) {
                $currentValueOfJobsInProgress += $job->amount;
                if ($request->bay == 'chem') {
                    if ($job->chem_bay_required === 'yes') {
                        $job->bay = 'Chem';
                        $inProgressInBay += 1;
                        $currentValueInProgressInBay += $job->amount;
                        $arrayJobs[] = clone $job;
                    }
                }
                if ($request->bay == 'burn') {
                    if ($job->burn_bay_required === 'yes') {
                        $job->bay = 'Burn';
                        $inProgressInBay += 1;
                        $currentValueInProgressInBay += $job->amount;
                        $arrayJobs[] = clone $job;
                    }

                }
                if ($request->bay == 'treatment') {
                    if ($job->treatment_bay_required === 'yes') {
                        $job->bay = 'Treatment';
                        $inProgressInBay += 1;
                        $currentValueInProgressInBay += $job->amount;
                        $arrayJobs[] = clone $job;
                    }
                    
                }
                if ($request->bay == 'blast') {
                    if ($job->blast_bay_required === 'yes') {
                        $job->bay = 'Blast';
                        $inProgressInBay += 1;
                        $currentValueInProgressInBay += $job->amount;
                        $arrayJobs[] = clone $job;
                    }
                    
                }
                if ($request->bay == 'powder-main-line') {
                    if ($job->powder_bay_required === 'yes'  && $job->powder_bay === 'main line') {
                        $job->bay = 'Main Line';
                        $inProgressInBay += 1;
                        $currentValueInProgressInBay += $job->amount;
                        $arrayJobs[] = clone $job;
                    }
                }
                if($request->bay == 'powder-small-batch') {
                    if ($job->powder_bay_required === 'yes' && $job->powder_bay === 'small batch') {
                        $job->bay = 'Small Batch';
                        $inProgressInBay += 1;
                        $currentValueInProgressInBay += $job->amount;
                        $arrayJobs[] = clone $job;
                    }
                }

                if($request->bay == 'powder-big-batch') {
                    if ($job->powder_bay_required === 'yes' && $job->powder_bay === 'big batch') {
                        $job->bay = 'Big Batch';
                        $inProgressInBay += 1;
                        $currentValueInProgressInBay += $job->amount;
                        $arrayJobs[] = clone $job;
                    }
                }
            }

            /** Get total value of last treatment completed jobs */
            if ($job->chem_bay_required == 'yes') {
                if ($job->chem_completed == null) {
                    continue;
                }
            }
            if ($job->burn_bay_required == 'yes') {
                if ($job->burn_completed == null) {
                    continue;
                }
            }
            if ($job->treatment_bay_required == 'yes') {
                if ($job->treatment_completed == null) {
                    continue;
                }
            }
            if ($job->blast_bay_required == 'yes') {
                if ($job->blast_completed == null) {
                    continue;
                }
            }
            if ($job->powder_bay_required == 'yes' && $job->powder_bay == 'main line') {
                if ($job->powder_completed == null) {
                    continue;
                }
            }

            if ($job->powder_bay_required == 'yes' && $job->powder_bay == 'small batch') {
                if ($job->powder_completed == null) {
                    continue;
                }
            }
            if ($job->powder_bay_required == 'yes' && $job->powder_bay == 'big batch') {
                if ($job->powder_completed == null) {
                    continue;
                }
            }

            $currentValueOfCompletedJobs += $job->amount;

        }

        $array = [
            'lists' => $arrayJobs,
            'number_of_bay' => $inProgressInBay,
            'current_value_of_bay' => $currentValueInProgressInBay,
            'current_value_of_jobs' => $currentValueOfJobsInProgress,
            'current_value_of_completed_jobs' => $currentValueOfCompletedJobs
        ];

        return $array;
    }

    public static function failedJobsFormatter($jobs, $arrayJobs, $numberOfFailedJobs, $currentValueOfFailedJobs, $request) {
        foreach($jobs as $job) {
            $currentValueOfFailedJobs += $job->amount;

            if ($request->bay == 'chem') {
                if ($job->chem_bay_required === 'yes') {
                    $job->bay = 'Chem';
                    $arrayJobs[] = clone $job;
                }
            }
            if ($request->bay == 'burn') {
                if ($job->burn_bay_required === 'yes') {
                    $job->bay = 'Burn';
                    $arrayJobs[] = clone $job;
                }

            }
            if ($request->bay == 'treatment') {
                if ($job->treatment_bay_required === 'yes') {
                    $job->bay = 'Treatment';
                    $arrayJobs[] = clone $job;
                }
                
            }
            if ($request->bay == 'blast') {
                if ($job->blast_bay_required === 'yes') {
                    $job->bay = 'Blast';
                    $arrayJobs[] = clone $job;
                }
                
            }
            if ($request->bay == 'powder-main-line') {
                if ($job->powder_bay_required === 'yes'  && $job->powder_bay === 'main line') {
                    $job->bay = 'Main Line';
                    $arrayJobs[] = clone $job;
                }
            }
            if($request->bay == 'powder-small-batch') {
                if ($job->powder_bay_required === 'yes' && $job->powder_bay === 'small batch') {
                    $job->bay = 'Small Batch';
                    $arrayJobs[] = clone $job;
                }
            }

            if($request->bay == 'powder-big-batch') {
                if ($job->powder_bay_required === 'yes' && $job->powder_bay === 'big batch') {
                    $job->bay = 'Big Batch';
                    $arrayJobs[] = clone $job;
                }
            }

        }
        $numberOfFailedJobs = count($jobs);
        $array = [
            'lists' => $arrayJobs,
            'current_value_of_failed_jobs' => $currentValueOfFailedJobs,
            'number_of_failed_jobs' => $numberOfFailedJobs
        ];

        return $array;
    }
}
