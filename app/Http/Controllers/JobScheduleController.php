<?php

namespace App\Http\Controllers;

use App\Events\JobEvent;
use App\Helpers\DispatchHelper;
use App\Helpers\JobHelper;
use App\Helpers\JobScheduleHelper;
use App\Helpers\ResponseHelper;
use App\Http\Requests\EditJobRequest;
use App\Http\Requests\GetJobRequest;
use App\Http\Requests\JobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Models\Deal;
use App\Models\Dispatch;
use App\Models\LineItems;
use App\Models\Job;
use App\Models\User;
use PDF;
use Carbon\Carbon;
use DateTimeZone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Treatments;

class JobScheduleController extends Controller
{
    /**
     * Get Jobs Overview Table
     */
    public function getJobs(GetJobRequest $request)
    {
        Log::info("JobScheduleController@getJobs", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
        $jobList = Job::getOutStandingJobs($request->start_date,$request->end_date)->paginate(config('constant.pagination.job'));

        foreach ($jobList as $job) {
            $job->amount = 0;
            if ($job['lines']) {
                foreach ($job['lines'] as $line) {
                    $job->amount += $line->quantity * $line->price;
                }
            }
        }

        $jobList->getCollection()->transform(function ($value) use ($request) {
            return JobScheduleHelper::setData($value, $request->user());
        });

        if ($jobList->total() > 0) {
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $jobList, 'Job List');
        } else {
            return ResponseHelper::errorResponse('No jobs found.', config('constant.status_code.success'));
        }
        } catch (Exception $e) {
            Log::error("JobScheduleController@getJobs - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.not_found'));
        }
    }

    public function getJobsDashboard(JobRequest $request) {
        try{
            Log::info("JobScheduleController@getJobsDashboard", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
                
        $jobs = JobHelper::getJobs($request);

        $dashboardDates = [];
        foreach($jobs as $jobKey => $job) {
            if ($job->chem_bay_required === 'yes' && ( $job->chem_date >= $request->start_date && $job->chem_date <= $request->end_date)) {
                $job->bay = 'Chem';
                if ($job->end_chem_date !== null) {
                    $dates = $this->getRangeDates($job->chem_date, $job->end_chem_date);
                    foreach ($dates as $key => $date) {
                        $dashboardDates[$date][] = clone $job;
                        $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'chem');

                    }
                } else {
                    $dashboardDates[$job->chem_date][] = clone $job;
                    $dashboardDates[$job->chem_date] = JobHelper::sortingJobDashboard($dashboardDates[$job->chem_date],'chem');
                }

            } else {
                if ($job->end_chem_date !== null) {
                    $job->bay = 'Chem';
                    $dates = $this->getRangeDates($job->chem_date, $job->end_chem_date);
                    foreach ($dates as $key => $date) {
                        if ($job->chem_bay_required === 'yes' && ( $date >= $request->start_date && $date <= $request->end_date)) {
                            $dashboardDates[$date][] = clone $job;
                            $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'chem');
                        }
                    }
                }
            }

            if ($job->treatment_bay_required === 'yes' && ( $job->treatment_date >= $request->start_date && $job->treatment_date <= $request->end_date)) {
                $job->bay = 'Treatment';
                if ($job->end_treatment_date !== null) {
                    $dates = $this->getRangeDates($job->treatment_date, $job->end_treatment_date);
                    foreach ($dates as $key => $date) {
                        $dashboardDates[$date][] = clone $job;
                        $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'treatment');
                    }
                } else {
                    $dashboardDates[$job->treatment_date][] = clone $job;
                    $dashboardDates[$job->treatment_date] = JobHelper::sortingJobDashboard($dashboardDates[$job->treatment_date],'treatment');
                }
            } else {
                if ($job->end_treatment_date !== null) {
                    $job->bay = 'Treatment';
                    $dates = $this->getRangeDates($job->treatment_date, $job->end_treatment_date);
                    foreach ($dates as $key => $date) {
                        if ($job->treatment_bay_required === 'yes' && ( $date >= $request->start_date && $date <= $request->end_date)) {
                            $dashboardDates[$date][] = clone $job;
                            $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'treatment');
                        }
                    }
                }
            }

            if ($job->burn_bay_required === 'yes' && ( $job->burn_date >= $request->start_date && $job->burn_date <= $request->end_date)) {
                $job->bay = 'Burn';
                if ($job->end_burn_date !== null) {
                    $dates = $this->getRangeDates($job->burn_date, $job->end_burn_date);
                    foreach ($dates as $key => $date) {
                        $dashboardDates[$date][] = clone $job;
                        $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'burn');
                    }
                } else {
                    $dashboardDates[$job->burn_date][] = clone $job;
                    $dashboardDates[$job->burn_date] = JobHelper::sortingJobDashboard($dashboardDates[$job->burn_date],'burn');
                }
            } else {
                if ($job->end_burn_date !== null) {
                    $job->bay = 'Burn';
                    $dates = $this->getRangeDates($job->burn_date, $job->end_burn_date);
                    foreach ($dates as $key => $date) {
                        if ($job->burn_bay_required === 'yes' && ( $date >= $request->start_date && $date <= $request->end_date)) {
                            $dashboardDates[$date][] = clone $job;
                            $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'burn');
                        }
                    }
                }
            }

            if ($job->blast_bay_required === 'yes' && ( $job->blast_date >= $request->start_date && $job->blast_date <= $request->end_date)) {
                $job->bay = 'Blast';
                if ($job->end_blast_date !== null) {
                    $dates = $this->getRangeDates($job->blast_date, $job->end_blast_date);
                    foreach ($dates as $key => $date) {
                        $dashboardDates[$date][] = clone $job;
                        $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'blast');
                    }
                } else {
                    $dashboardDates[$job->blast_date][] = clone $job;
                    $dashboardDates[$job->blast_date] = JobHelper::sortingJobDashboard($dashboardDates[$job->blast_date],'blast');
                }
            } else {
                if ($job->end_blast_date !== null) {
                    $job->bay = 'Blast';
                    $dates = $this->getRangeDates($job->blast_date, $job->end_blast_date);
                    foreach ($dates as $key => $date) {
                        if ($job->blast_bay_required === 'yes' && ( $date >= $request->start_date && $date <= $request->end_date)) {
                            $dashboardDates[$date][] = clone $job;
                            $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'blast');
                        }
                    }
                }
            }

            if ($job->powder_bay_required === 'yes' && ( $job->powder_date >= $request->start_date && $job->powder_date <= $request->end_date)) {
                if ($job->powder_bay === 'main line') {
                    $job->bay = 'Main Line';
                    if ($job->end_powder_date !== null) {
                        $dates = $this->getRangeDates($job->powder_date, $job->end_powder_date);
                        foreach ($dates as $key => $date) {
                            $dashboardDates[$date][] = clone $job;
                            $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'powder');
                        }
                    } else {
                        $dashboardDates[$job->powder_date][] = clone $job;
                        $dashboardDates[$job->powder_date] = JobHelper::sortingJobDashboard($dashboardDates[$job->powder_date],'powder');
                    }
                } else if ($job->powder_bay === 'big batch') {
                    $job->bay = 'Big Batch';
                    if ($job->end_powder_date !== null) {
                        $dates = $this->getRangeDates($job->powder_date, $job->end_powder_date);
                        foreach ($dates as $key => $date) {
                            $dashboardDates[$date][] = clone $job;
                            $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'powder');
                        }
                    } else {
                        $dashboardDates[$job->powder_date][] = clone $job;
                        $dashboardDates[$job->powder_date] = JobHelper::sortingJobDashboard($dashboardDates[$job->powder_date],'powder');
                    }
                } else if ($job->powder_bay === 'small batch') {
                    $job->bay = 'Small Batch';
                    if ($job->end_powder_date !== null) {
                        $dates = $this->getRangeDates($job->powder_date, $job->end_powder_date);
                        foreach ($dates as $key => $date) {
                            $dashboardDates[$date][] = clone $job;
                            $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'powder');
                        }
                    } else {
                        $dashboardDates[$job->powder_date][] = clone $job;
                        $dashboardDates[$job->powder_date] = JobHelper::sortingJobDashboard($dashboardDates[$job->powder_date],'powder');
                    }
                }
            } else {
                if ($job->powder_bay === 'main line') {
                    if ($job->end_powder_date !== null) {
                        $job->bay = 'Main Line';
                        $dates = $this->getRangeDates($job->powder_date, $job->end_powder_date);
                        foreach ($dates as $key => $date) {
                            if ($job->powder_bay_required === 'yes' && ( $date >= $request->start_date && $date <= $request->end_date)) {
                                $dashboardDates[$date][] = clone $job;
                                $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'powder');
                            }
                        }
                    }
                } else if ($job->powder_bay === 'big batch') {
                    if ($job->end_powder_date !== null) {
                        $job->bay = 'Big Batch';
                        $dates = $this->getRangeDates($job->powder_date, $job->end_powder_date);
                        foreach ($dates as $key => $date) {
                            if ($job->powder_bay_required === 'yes' && ( $date >= $request->start_date && $date <= $request->end_date)) {
                                $dashboardDates[$date][] = clone $job;
                                $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'powder');
                            }
                        }
                    }
                } else if ($job->powder_bay === 'small batch') {
                    if ($job->end_powder_date !== null) {
                        $job->bay = 'Small Batch';
                        $dates = $this->getRangeDates($job->powder_date, $job->end_powder_date);
                        foreach ($dates as $key => $date) {
                            if ($job->powder_bay_required === 'yes' && ( $date >= $request->start_date && $date <= $request->end_date)) {
                                $dashboardDates[$date][] = clone $job;
                                $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'powder');
                            }
                        }
                    }
                }
            }

            if (($job->chem_date === null && $job->chem_bay_required === 'yes') || ($job->treatment_date === null && $job->treatment_bay_required === 'yes') || ($job->burn_date === null && $job->burn_bay_required === 'yes') || ($job->blast_date === null && $job->blast_bay_required === 'yes') || ($job->powder_date === null && $job->powder_bay_required === 'yes')) {
                $job->bay = 'Ready for Schedule';
                $dashboardDates['ready'][] = clone $job;
                $dashboardDates['ready'] = JobHelper::sortingJobDashboard($dashboardDates['ready'],'ready');
            }
        }


        return response()->json(['jobs' => $dashboardDates]);
        }  catch (Exception $e) {
            Log::error("JobScheduleController@getJobsDashboard - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.not_found'));
        }
    }

    public function getJobsOverview(JobRequest $request) {
        Log::info("JobScheduleController@getJobsOverview", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
        $jobs = JobHelper::getJobs($request);

        $readyJob = [];
        $chemJob = [];
        $burnJob = [];
        $treatmentJob = [];
        $blastJob = [];
        $mainJob = [];
        $bigJob = [];
        $smallJob = [];
        
        foreach($jobs as $job) {
            if ($job->chem_bay_required === 'yes' && ( $job->chem_date >= $request->start_date && $job->chem_date <= $request->end_date)) {
                $job->bay = 'chem';
                $chemJob[] = clone $job;
            } else {
                if ($job->end_chem_date !== null) {
                    $job->bay = 'chem';
                    $dates = $this->getRangeDates($job->chem_date, $job->end_chem_date);
                    foreach ($dates as $key => $date) {
                        if ($job->chem_bay_required === 'yes' && ( $date >= $request->start_date && $date <= $request->end_date)) {
                            $chemJob[] = clone $job;
                            break;
                        }
                    }
                }
            }
            
            if ($job->treatment_bay_required === 'yes' && ( $job->treatment_date >= $request->start_date && $job->treatment_date <= $request->end_date)) {
                $job->bay = 'treatment';
                $treatmentJob[] = clone $job;
            } else {
                if ($job->end_treatment_date !== null) {
                    $job->bay = 'treatment';
                    $dates = $this->getRangeDates($job->treatment_date, $job->end_treatment_date);
                    foreach ($dates as $key => $date) {
                        if ($job->treatment_bay_required === 'yes' && ( $date >= $request->start_date && $date <= $request->end_date)) {
                            $treatmentJob[] = clone $job;
                            break;
                        }
                    }
                }
            }

            if ($job->burn_bay_required === 'yes' && ( $job->burn_date >= $request->start_date && $job->burn_date <= $request->end_date)) {
                $job->bay = 'burn';
                $burnJob[] = clone $job;
            } else {
                if ($job->end_burn_date !== null) {
                    $job->bay = 'burn';
                    $dates = $this->getRangeDates($job->burn_date, $job->end_burn_date);
                    foreach ($dates as $key => $date) {
                        if ($job->burn_bay_required === 'yes' && ( $date >= $request->start_date && $date <= $request->end_date)) {
                            $burnJob[] = clone $job;
                            break;
                        }
                    }
                }
            }

            if ($job->blast_bay_required === 'yes' && ( $job->blast_date >= $request->start_date && $job->blast_date <= $request->end_date)) {
                $job->bay = 'blast';
                $blastJob[] = clone $job;
            } else {
                if ($job->end_blast_date !== null) {
                    $job->bay = 'blast';
                    $dates = $this->getRangeDates($job->blast_date, $job->end_blast_date);
                    foreach ($dates as $key => $date) {
                        if ($job->blast_bay_required === 'yes' && ( $date >= $request->start_date && $date <= $request->end_date)) {
                            $blastJob[] = clone $job;
                            break;
                        }
                    }
                }
            }
            
            if ($job->powder_bay_required === 'yes' && ( $job->powder_date >= $request->start_date && $job->powder_date <= $request->end_date)) {
                if ($job->powder_bay === 'main line') {
                    $job->bay = 'main line';
                    $mainJob[] = clone $job;
                } else if ($job->powder_bay === 'big batch') {
                    $job->bay = 'big batch';
                    $bigJob[] = clone $job;
                } else if ($job->powder_bay === 'small batch') {
                    $job->bay = 'small batch';
                    $smallJob[] = clone $job;
                }
            } else {
                if ($job->powder_bay === 'main line') {
                    $job->bay = 'main line';
                    if ($job->end_powder_date !== null) {
                        $dates = $this->getRangeDates($job->powder_date, $job->end_powder_date);
                        foreach ($dates as $key => $date) {
                            if ($job->powder_bay_required === 'yes' && ( $date >= $request->start_date && $date <= $request->end_date)) {
                                $mainJob[] = clone $job;
                                break;
                            }
                        }
                    }
                } else if ($job->powder_bay === 'big batch') {
                    $job->bay = 'big batch';
                    if ($job->end_powder_date !== null) {
                        $dates = $this->getRangeDates($job->powder_date, $job->end_powder_date);
                        foreach ($dates as $key => $date) {
                            if ($job->powder_bay_required === 'yes' && ( $date >= $request->start_date && $date <= $request->end_date)) {
                                $bigJob[] = clone $job;
                                break;
                            }
                        }
                    }
                } else if ($job->powder_bay === 'small batch') {
                    $job->bay = 'small batch';
                    if ($job->end_powder_date !== null) {
                        $dates = $this->getRangeDates($job->powder_date, $job->end_powder_date);
                        foreach ($dates as $key => $date) {
                            if ($job->powder_bay_required === 'yes' && ( $date >= $request->start_date && $date <= $request->end_date)) {
                                $smallJob[] = clone $job;
                                break;
                            }
                        }
                    }
                }
            }

            if (($job->chem_date === null && $job->chem_bay_required === 'yes') || ($job->treatment_date === null && $job->treatment_bay_required === 'yes') || ($job->burn_date === null && $job->burn_bay_required === 'yes') || ($job->blast_date === null && $job->blast_bay_required === 'yes') || ($job->powder_date === null && $job->powder_bay_required === 'yes')) {
                $job->bay = 'ready';
                $job->check_due_date = isset($job->deal) ? $job->deal->promised_date : null; 
                $readyJob[] = clone $job;
            }
            
        }
        
        //Ready
        $readyJob = JobHelper::sortingJobDashboard($readyJob,'ready');

        //Chem
        $chemJob = JobHelper::sortingJobDashboard($chemJob,'chem');
        
        //treatment
        $treatmentJob = JobHelper::sortingJobDashboard($treatmentJob,'treatment');
        
        //burn
        $burnJob = JobHelper::sortingJobDashboard($burnJob,'burn');

        
        //blast
        $blastJob = JobHelper::sortingJobDashboard($blastJob,'blast');
        
        //powder
        $mainJob = JobHelper::sortingJobDashboard($mainJob,'powder');
        $bigJob = JobHelper::sortingJobDashboard($bigJob,'powder');
        $smallJob = JobHelper::sortingJobDashboard($smallJob,'powder');

        $overviewBays = [];
        if($readyJob)
            $overviewBays['ready'] = $readyJob;
        if($chemJob)
            $overviewBays['chem'] = $chemJob;
        if($treatmentJob)
            $overviewBays['treatment'] = $treatmentJob;
        if($burnJob)
            $overviewBays['burn'] = $burnJob;
        if($blastJob)
            $overviewBays['blast'] = $blastJob;
        if($mainJob)
            $overviewBays['main line'] = $mainJob;
        if($bigJob)
            $overviewBays['big batch'] = $bigJob;
        if($smallJob)
            $overviewBays['small batch'] = $smallJob;

        return response()->json(['jobs' => $overviewBays]);
        } catch (Exception $e) {
            return $e->getMessage();
            Log::error("JobScheduleController@getJobsOverview - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.not_found'));
        }
    }

    /**
     * Get Jobs By Bay
     */
    public function getJobByBay(GetJobRequest $request, $bay)
    {
        Log::info("JobScheduleController@getJobByBay", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $jobList = Job::getJobByBay($bay,$request->start_date,$request->end_date)->paginate(config('constant.pagination.job'));

            foreach ($jobList as $job) {
                $job->amount = 0;
                if ($job['lines']) {
                    foreach ($job['lines'] as $line) {
                        $job->amount += $line->quantity * $line->price;
                    }
                }
            }

            $jobList->getCollection()->transform(function ($value) use ($request) {
                return JobScheduleHelper::setData($value, $request->user());
            });
            if ($jobList->total() > 0) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), $jobList, 'Job List');
            } else {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'Job List');
            }
        } catch (Exception $e) {
            Log::error("JobScheduleController@getJobByBay - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.not_found'));
        }
    }

    /**
     * Get Jobs By Bay Kanban
     */
    public function getJobByBayKanban(GetJobRequest $request, $bay)
    {
        Log::info("JobScheduleController@getJobByBayKanban", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            //Ready
            $colVal = config('constant.ready_to_schedule');
            $columnVal = config('constant.ready');
            $ansArr = [];

            $jobList = Job::getJobByBayKanbanDate($bay, $colVal, $request->user(),$request->start_date,$request->end_date)->get();
            if ($jobList) {
                foreach ($jobList as $job) {
                    $job->amount = 0;
                    if ($job['lines']) {
                        foreach ($job['lines'] as $line) {
                            $job->amount += $line->quantity * $line->price;
                        }
                    }
                }
                $jobList->transform(function ($value) use ($columnVal, $request) {
                    return JobScheduleHelper::setDataDateKanbanBay($value, $columnVal, $request->user());
                });
                if (count($jobList) > 0) {
                array_push($ansArr, $jobList);
            }
            }


            //Today
            $colVal = Carbon::now()->format('Y-m-d');
            $columnVal = config('constant.today');
            $today = Carbon::now()->format('Y-m-d');

            if (!in_array(date('l', strtotime($today)), ["Sunday"]) && !in_array($today, config('constant.holidayList'))) {
                $jobList = Job::getJobByBayKanbanDate($bay, $colVal, $request->user(),$request->start_date,$request->end_date)->get();
                if ($jobList) {
                    foreach ($jobList as $job) {
                        $job->amount = 0;
                        if ($job['lines']) {
                            foreach ($job['lines'] as $line) {
                                $job->amount += $line->quantity * $line->price;
                            }
                        }
                    }
                        $jobList->transform(function ($value) use ($columnVal, $request) {
                            return JobScheduleHelper::setDataDateKanbanBay($value, $columnVal, $request->user());
                        });

                        if (count($jobList) > 0) {
                        array_push($ansArr, $jobList);
                    }
                }
            }

            //tomorrow and after that
            for ($loop = 2; $loop <= 1000; $loop++) {
                $nextDayCount = 1;
                $i = 1;
                do {
                    $temp = Carbon::now()->addDays($nextDayCount)->format('Y-m-d');
                    if (!in_array(date('l', strtotime($temp)), ["Sunday"]) && !in_array($temp, config('constant.holidayList'))) {
                        $i++;
                    }

                    $nextDayCount++;
                } while ($i < $loop);

                $colVal = Carbon::now()->addDays($nextDayCount - 1)->format('Y-m-d');
                $columnVal = $colVal;

                if ($colVal == Carbon::now()->addDays(1)->format('Y-m-d')) {
                    $columnVal = config('constant.tomorrow');
                }

                $jobList = Job::getJobByBayKanbanDate($bay, $colVal, $request->user(),$request->start_date,$request->end_date)->get();

                if (count($jobList) > 0) {
                    foreach ($jobList as $job) {
                        $job->amount = 0;
                        if ($job['lines']) {
                            foreach ($job['lines'] as $line) {
                                $job->amount += $line->quantity * $line->price;
                            }
                        }
                    }
                    $jobList->transform(function ($value) use ($columnVal, $request) {
                        return JobScheduleHelper::setDataDateKanbanBay($value, $columnVal, $request->user());
            });

                    if (count($jobList) > 0) {
                    array_push($ansArr, $jobList);
                    }
                } else {
                    $cnt = 0;
                    if ($bay == 'chem') {
                        $cnt = Job::select('job_id')->whereDate('chem_date', '>', $colVal)->count();
                    } else if ($bay == 'treatment') {
                        $cnt = Job::select('job_id')->whereDate('treatment_date', '>', $colVal)->count();
                    } else if ($bay == 'burn') {
                        $cnt = Job::select('job_id')->whereDate('burn_date', '>', $colVal)->count();
                    } else if ($bay == 'blast') {
                        $cnt = Job::select('job_id')->whereDate('blast_date', '>', $colVal)->count();
                    } else if ($bay == 'powder-big-batch') {
                        $cnt = Job::select('job_id')->whereDate('powder_date', '>', $colVal)->where('powder_bay', '=', 'big batch')->count();
                    } else if ($bay == 'powder-small-batch') {
                        $cnt = Job::select('job_id')->whereDate('powder_date', '>', $colVal)->where('powder_bay', '=', 'small batch')->count();
                    } else if ($bay == 'powder-main-line') {
                        $cnt = Job::select('job_id')->whereDate('powder_date', '>', $colVal)->where('powder_bay', '=', 'main line')->count();
                    }
                    if ($cnt == 0)
                        break;
                }
            }



            if (count($ansArr) > 0) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), $ansArr, 'Job List');
            } else {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'Job List');
            }
        } catch (Exception $e) {
            Log::error("JobScheduleController@getJobByBayKanban - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
         
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.not_found'));
        }
    }

    /**
     * Get Jobs Overview date kanban
     */
    public function getJobsOverviewDateKanban(GetJobRequest $request)
    {
        Log::info("JobScheduleController@getJobsOverviewDateKanban", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            //Ready to schedule
            $colVal = config('constant.ready_to_schedule');
            $columnVal = config('constant.ready_to_schedule');
            $ansArr = [];
            $jobList = Job::getJobsOverviewDateKanban($colVal, $request->user(),$request->start_date,$request->end_date)->get();
            if ($jobList) {
                foreach ($jobList as $job) {
                    $job->amount = 0;
                    if ($job['lines']) {
                        foreach ($job['lines'] as $line) {
                            $job->amount += $line->quantity * $line->price;
                        }
                    }
                }
                $jobList->transform(function ($value) use ($request) {
                    return JobScheduleHelper::setDataOverviewDateKanban($value, $request->user());
                });
                array_push($ansArr, [$columnVal => $jobList]);
            }



            //Today
            $colVal = Carbon::now()->format('Y-m-d');
            $columnVal = config('constant.today');
            $today = Carbon::now()->format('Y-m-d');

            if (!in_array(date('l', strtotime($today)), ["Sunday"]) && !in_array($today, config('constant.holidayList'))) {
                $jobList = Job::getJobsOverviewDateKanban($colVal, $request->user(),$request->start_date,$request->end_date)->get();
                if ($jobList) {
                    foreach ($jobList as $job) {
                        $job->amount = 0;
                        if ($job['lines']) {
                            foreach ($job['lines'] as $line) {
                                $job->amount += $line->quantity * $line->price;
                            }
                        }
                    }
                    $jobList->transform(function ($value) use ($request) {
                        return JobScheduleHelper::setDataOverviewDateKanban($value, $request->user());
                    });
                    array_push($ansArr, [$columnVal => $jobList]);
                }
            }



            //tomorrow and after that
            for ($loop = 2; $loop <= 1000; $loop++) {
                $nextDayCount = 1;
                $i = 1;
                do {
                    $temp = Carbon::now()->addDays($nextDayCount)->format('Y-m-d');
                    if (!in_array(date('l', strtotime($temp)), ["Sunday"]) && !in_array($temp, config('constant.holidayList'))) {
                        $i++;
                    }

                    $nextDayCount++;
                } while ($i < $loop);

                $colVal = Carbon::now()->addDays($nextDayCount - 1)->format('Y-m-d');
                $columnVal = $colVal;

                if ($colVal == Carbon::now()->addDays(1)->format('Y-m-d')) {
                    $columnVal = config('constant.tomorrow');
                }

                $jobList = Job::getJobsOverviewDateKanban($colVal, $request->user(),$request->start_date,$request->end_date)->get();

                if (count($jobList) > 0) {
                    foreach ($jobList as $job) {
                        $job->amount = 0;
                        if ($job['lines']) {
                            foreach ($job['lines'] as $line) {
                                $job->amount += $line->quantity * $line->price;
                            }
                        }
                    }

                    $jobList->transform(function ($value) use ($request) {
                        return JobScheduleHelper::setDataOverviewDateKanban($value, $request->user());
                    });
                    array_push($ansArr, [$columnVal => $jobList]);
                } else {
                    break;
                }
            }


            if (count($ansArr) > 0) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), $ansArr, 'Job List');
            } else {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'Job List');
            }
        } catch (Exception $e) {
            Log::error("JobScheduleController@getJobsOverviewDateKanban - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.not_found'));
        }
    }

    public function updateJobDetail(Request $request, $job)
    {
        Log::info("JobScheduleController@updateJobDetail", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $jobDetail = Job::where('job_id', $job)->first();

            if (!$jobDetail) {
                return ResponseHelper::errorResponse('Cannot update bay date. Incorrect job id.', config('constant.status_code.not_found'));
            }
            $params = json_decode($request->getContent());

            $msg = 'Cannot update. Cannot change the scheduled bay date on or before previous bay date.';
            $ans = JobHelper::setUpdateDayScheduleData($jobDetail, $msg);

            $status = 'ready';
            $waitingStatus = 'waiting';
            $completeStatus = 'complete';

            $currentDate = date('Y-m-d');
            if (isset($params->chem_date) || isset($params->treatment_date) || isset($params->burn_date) || isset($params->blast_date) || isset($params->powder_date) || isset($params->end_chem_date) || isset($params->end_treatment_date) || isset($params->end_burn_date) || isset($params->end_blast_date) || isset($params->end_powder_date)) {
                $jobReadyCnt = 0;
                foreach ($params as $key => $value) {
                    if($value){
                    $jobDetail->{$key} = $value;
                    } else {
                        $jobDetail->{$key} = null;
                    }
                    if ($key == 'chem_date') {
                        if ($value && $value <= $currentDate) {
                            $jobDetail->chem_status = $completeStatus;
                        } else {
                        $jobDetail->chem_status = $status;
                            $jobReadyCnt++;
                        }
                    }
                    if ($key == 'burn_date') {
                        if ($value && $value <= $currentDate) {
                            $jobDetail->burn_status = $completeStatus;
                        } else {
                            if($value == ''){
                                $jobDetail->burn_status = $status;
                                $jobReadyCnt++;
                            } else {
                            if ($jobReadyCnt > 0) {
                                $jobDetail->burn_status = $waitingStatus;
                            } else {
                                $jobDetail->burn_status = $status;
                                $jobReadyCnt++;
                            }
                        }
                    }
                    }
                    if ($key == 'treatment_date') {
                        if ($value && $value <= $currentDate) {
                            $jobDetail->treatment_status = $completeStatus;
                        } else {
                            if($value == ''){
                                $jobDetail->treatment_status = $status;
                                $jobReadyCnt++;
                            } else {
                            if ($jobReadyCnt > 0) {
                                $jobDetail->treatment_status = $waitingStatus;
                            } else {
                        $jobDetail->treatment_status = $status;
                                $jobReadyCnt++;
                            }
                            }
                    }
                    }
                    if ($key == 'blast_date') {
                        if ($value && $value <= $currentDate) {
                            $jobDetail->blast_status = $completeStatus;
                        } else {
                            if($value == ''){
                                $jobDetail->blast_status = $status;
                                $jobReadyCnt++;
                            } else {
                            if ($jobReadyCnt > 0) {
                                $jobDetail->blast_status = $waitingStatus;
                            } else {
                        $jobDetail->blast_status = $status;
                                $jobReadyCnt++;
                            }
                        }
                    }
                    }
                    if ($key == 'powder_date') {
                        if ($value && $value <= $currentDate) {
                            $jobDetail->powder_status = $completeStatus;
                        } else {
                            if($value == ''){
                                $jobDetail->powder_status = $status;
                                $jobReadyCnt++;
                            } else {
                            if ($jobReadyCnt > 0) {
                                $jobDetail->powder_status = $waitingStatus;
                            } else {
                        $jobDetail->powder_status = $status;
                                $jobReadyCnt++;
                            }
                        }
                    }
                }
                }
                $jobDetail->job_status = 'ready';
                $jobDetail->save();

                $lines = LineItems::where('job_id', $job)->get();

                if ($key!='end_chem_date' && $key!='end_treatment_date' && $key!='end_burn_date' && $key!='end_blast_date' && $key!='end_powder_date') {

                foreach ($lines as $key => $line) {
                    $lineReadyCnt = 0;
                    foreach ($params as $key => $value) {
                        if($value){
                        $line->{$key} = $value;
                        } else {
                            $line->{$key} = null;
                        }
                        if ($key == 'chem_date') {
                            if ($value && $value <= $currentDate) {
                                $line->chem_status = $completeStatus;
                            } else {
                            $line->chem_status = $status;
                                $lineReadyCnt++;
                            }
                        }
                        if ($key == 'treatment_date') {
                            if ($value && $value <= $currentDate) {
                                $line->treatment_status = $completeStatus;
                            } else {
                                if($value == ''){
                                    $line->treatment_status = $status;
                                    $lineReadyCnt++;
                                } else {
                                if ($lineReadyCnt > 0) {
                                    $line->treatment_status = $waitingStatus;
                                } else {
                            $line->treatment_status = $status;
                                    $lineReadyCnt++;
                                }
                            }
                        }
                        }
                        if ($key == 'burn_date') {
                            if ($value && $value <= $currentDate) {
                                $line->burn_status = $completeStatus;
                            } else {
                                if($value == ''){
                                    $line->burn_status = $status;
                                        $lineReadyCnt++;
                                } else {
                                if ($lineReadyCnt > 0) {
                                    $line->burn_status = $waitingStatus;
                                } else {
                            $line->burn_status = $status;
                                    $lineReadyCnt++;
                                }
                            }
                        }
                        }
                        if ($key == 'blast_date') {
                            if ($value && $value <= $currentDate) {
                                $line->blast_status = $completeStatus;
                            } else {
                                if($value == ''){
                                    $line->blast_status = $status;
                                    $lineReadyCnt++;
                                } else {
                                if ($lineReadyCnt > 0) {
                                    $line->blast_status = $waitingStatus;
                                } else {
                            $line->blast_status = $status;
                                    $lineReadyCnt++;
                                }
                            }
                        }
                        }
                        if ($key == 'powder_date') {
                            if ($value && $value <= $currentDate) {
                                $line->powder_status = $completeStatus;
                            } else {
                                if($value == ''){
                                    $line->powder_status = $status;
                                    $lineReadyCnt++;
                                } else {
                                if ($lineReadyCnt > 0) {
                                    $line->powder_status = $waitingStatus;
                                } else {
                            $line->powder_status = $status;
                                    $lineReadyCnt++;
                                }
                            }
                        }
                        }
                        $line->save();
                    }
                }
                }    
    
                //Event call
                event(new JobEvent("job event call"));
                
                $msg = 'Successfully updated.';
                $ans = JobHelper::setUpdateDayScheduleData($jobDetail, $msg);
                return ResponseHelper::responseMessageWithoutData(config('constant.status_code.success'), $ans);
            } else {
                return ResponseHelper::errorResponse('Add proper parameter', config('constant.status_code.bad_request'));
            }
        } catch (Exception $e) {
            Log::error("JobScheduleController@updateJobDetail - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
         
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }

    public function editJob(EditJobRequest $request, $jobId) {
        Log::info("JobScheduleController@editJob", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $job = Job::find($jobId);
            if(!$job) {
                return ResponseHelper::errorResponse('Job not found', config('constant.status_code.not_found'));
            }

            $bayCodes = [];
         
            $bayCodes = str_split($request->treatment);

            $bayValues = (object) [];

            $bayValues->chem_bay_required = 'no';
            $bayValues->treatment_bay_required = 'no';
            $bayValues->burn_bay_required = 'no';
            $bayValues->blast_bay_required = 'no';
            $bayValues->powder_bay_required = 'no';
            $bayValues->chem_status = 'na';
            $bayValues->treatment_status = 'na';
            $bayValues->burn_status = 'na';
            $bayValues->blast_status = 'na';
            $bayValues->powder_status = 'na';
            $bayValues->powder_bay = 'na';


            $lineitemBayValues = (object) [];

            $lineitemBayValues->chem_status = 'na';
            $lineitemBayValues->treatment_status = 'na';
            $lineitemBayValues->burn_status = 'na';
            $lineitemBayValues->blast_status = 'na';
            $lineitemBayValues->powder_status = 'na';
            $lineitemBayValues->powder_bay = 'na';

            $stageStatus = "waiting";
            $prevStageStatus = "complete";

            foreach($bayCodes as $i => $bayCode) {

                if ($i == 0) {
                    $stageStatus = 'ready';
                }

                switch($bayCode) {
                    case 'S':
                        $bayValues->chem_bay_required = 'yes';

                        if(!$job->chem_status || $job->chem_status == 'na') {
                            $bayValues->chem_status = $stageStatus;
                            $lineitemBayValues->chem_status = $stageStatus;

                            if($stageStatus == 'ready') {
                                $stageStatus = 'waiting';
                            }
                        } else {
                            if($prevStageStatus == 'complete') {
                                if($job->chem_status == 'complete') {
                                    $bayValues->chem_status = $job->chem_status;
                                    $lineitemBayValues->chem_status = $job->chem_status;
                                    $stageStatus = 'ready';
                                } else {
                                    $bayValues->chem_status = $stageStatus;
                                    $lineitemBayValues->chem_status = $stageStatus;
                                    $stageStatus = 'waiting';
                                }
                            } else {
                                $bayValues->chem_status = $stageStatus;
                                $lineitemBayValues->chem_status = $stageStatus;
                            }

                        }

                        $prevStageStatus = $bayValues->chem_status;

                        break;
                    case 'T':
                        $bayValues->treatment_bay_required = 'yes';

                        if(!$job->treatment_status || $job->treatment_status == 'na') {
                            $bayValues->treatment_status = $stageStatus;
                            $lineitemBayValues->treatment_status = $stageStatus;
                            if($stageStatus == 'ready') {
                                $stageStatus = 'waiting';
                            }
                        } else {

                            if($prevStageStatus == 'complete') {
                                if($job->treatment_status == 'complete') {
                                    $bayValues->treatment_status = $job->treatment_status;
                                    $lineitemBayValues->treatment_status = $job->treatment_status;
                                    $stageStatus = 'ready';
                                } else {
                                    $bayValues->treatment_status = $stageStatus;
                                    $lineitemBayValues->treatment_status = $stageStatus;
                                    $stageStatus = 'waiting';
                                }
                            } else {
                                $bayValues->treatment_status = $stageStatus;
                                $lineitemBayValues->treatment_status = $stageStatus;
                                $stageStatus = 'waiting';
                            }

                        }

                        $prevStageStatus = $bayValues->treatment_status;

                        break;
                    case 'F':
                        $bayValues->burn_bay_required = 'yes';
                        if(!$job->burn_status || $job->burn_status == 'na') {
                            $bayValues->burn_status = $stageStatus;
                            $lineitemBayValues->burn_status = $stageStatus;
                            if($stageStatus == 'ready') {
                                $stageStatus = 'waiting';
                            }
                        } else {
                            if($prevStageStatus == 'complete') {
                                if($job->burn_status == 'complete') {
                                    $bayValues->burn_status = $job->burn_status;
                                    $lineitemBayValues->burn_status = $job->burn_status;
                                    $stageStatus = 'ready';
                                } else {
                                    $bayValues->burn_status = $stageStatus;
                                    $lineitemBayValues->burn_status = $stageStatus;
                                    $stageStatus = 'waiting';
                                }
                            } else {
                                $bayValues->burn_status = $stageStatus;
                                $lineitemBayValues->burn_status = $stageStatus;
                            }

                        }

                        $prevStageStatus = $bayValues->burn_status;

                        break;
                    case 'B':
                        $bayValues->blast_bay_required = 'yes';
                        if(!$job->blast_status || $job->blast_status == 'na') {
                            $bayValues->blast_status = $stageStatus;
                            $lineitemBayValues->blast_status = $stageStatus;
                            if($stageStatus == 'ready') {
                                $stageStatus = 'waiting';
                            }
                        } else {

                            if($prevStageStatus == 'complete') {
                                if($job->blast_status == 'complete') {
                                    $bayValues->blast_status = $job->blast_status;
                                    $lineitemBayValues->blast_status = $job->blast_status;
                                    $stageStatus = 'ready';
                                } else {
                                    $bayValues->blast_status = $stageStatus;
                                    $lineitemBayValues->blast_status = $stageStatus;
                                    $stageStatus = 'waiting';
                                }
                            } else {
                                $bayValues->blast_status = $stageStatus;
                                $lineitemBayValues->blast_status = $stageStatus;
                            }

                        }

                        $prevStageStatus = $bayValues->blast_status;

                        break;
                    case 'C':
                    case 'P':
                        $bayValues->powder_bay_required = 'yes';

                        if($request->powder_bay) {
                            $lineitemBayValues->powder_bay = $request->powder_bay;
                            $bayValues->powder_bay = $request->powder_bay;
                        }

                        if(!$job->powder_status || $job->powder_status == 'na') {
                            $bayValues->powder_status = $stageStatus;
                            $lineitemBayValues->powder_status = $stageStatus;
                            if($stageStatus == 'ready') {
                                $stageStatus = 'waiting';
                            }
                        } else {
                            if($prevStageStatus == 'complete') {
                                if($job->powder_status == 'complete') {
                                    $bayValues->powder_status = $job->powder_status;
                                    $lineitemBayValues->powder_status = $job->powder_status;
                                    $stageStatus = 'ready';
                                } else {
                                    $bayValues->powder_status = $stageStatus;
                                    $lineitemBayValues->powder_status = $stageStatus;
                                    $stageStatus = 'waiting';
                                }
                            } else {
                                $bayValues->powder_status = $stageStatus;
                                $lineitemBayValues->powder_status = $stageStatus;
                                $stageStatus = 'waiting';
                            }

                        }

                        $prevStageStatus = $bayValues->powder_status;

                        break;
                }
            }

            if($request->treatment) {
                $bayValues->treatment = $request->treatment;
            }

            
            if($request->material) {
                $bayValues->material = $request->material;
            }

            if($request->colour) {
                $bayValues->colour = $request->colour;
                $lineitemBayValues->colour = $request->colour;
            }
            
            if($request->due_date) {
                $deal = $job->deal;
                if($deal) {
                    $deal->promised_date = $request->due_date;
                    $deal->save();
                }
            }

            $job->update((array) $bayValues);


            foreach ($job->lineItems as $lineItem) {
                $lineItem->update((array) $lineitemBayValues);
            }

            event(new JobEvent("job event call"));
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $job, 'Job updated successfully');

        } catch (Exception $e) {
            Log::error("JobScheduleController@EditJob - Error {$e->getMessage()}", [
                'trace' => $e->getTrace(),
                'message' => $e->getMessage()
            ]);
            \Sentry\captureException($e); 
         
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }

    public function updateJobStatus(UpdateJobRequest $request, $job)
    {
        Log::info("JobScheduleController@updateJobStatus", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $jobDetail = Job::where('job_id', $job)->first();
            if (!$jobDetail) {
                return ResponseHelper::errorResponse('Cannot update status. Incorrect job id.', config('constant.status_code.not_found'));
            }
            $params = json_decode($request->getContent());

            $treatmentBays = [];
            if($jobDetail->treatment){
                $treatmentBays = str_split($jobDetail->treatment);
            }
            $waitingStatus = 'waiting';
            $readyStatus = 'ready';
            $completeStatus = 'complete';

            $currentDate = date('Y-m-d');


            foreach ($params as $key => $value) {
                $lines = LineItems::where('job_id', $job)->get();
                if ($key == 'job_status') {
                    foreach ($lines as $k => $line) {
                        $line->line_item_status = $value;
                        $line->save();
                    }
                }

                if ($key === 'Main Line_status' || $key === 'Big Batch_status' || $key === 'Small Batch_status') {
                    $key = 'powder_status';
                }

                $jobDetail->{$key} = $value;

                

                
                $completedKey = null;
                if ($value === 'complete') {
                    if ($key === "chem_status" || $key === 'Chem_status') {
                        $completedKey = "chem_completed";
                    } else if ($key === 'Treatment_status' || $key === 'treatment_status') {
                        $completedKey = 'treatment_completed';
                    }else if ($key === 'blast_status' || $key === 'Blast_status') {
                        $completedKey = 'blast_completed';
                    } else if ($key === 'burn_status' || $key === 'Burn_status') {
                        $completedKey = 'burn_completed';
                    } else if ($key === 'powder_status' || $key === 'Powder_status') {
                        $key = 'powder_status';
                        $completedKey = 'powder_completed';
                    }

                    $jobDetail->{$completedKey} = $currentDate;
                    $jobDetail->save();
                }
                

                if ($key == 'chem_status' || $key == 'Chem_status' || $key == 'treatment_status' || $key == 'Treatment_status' || $key == 'burn_status' || $key == 'Burn_status' || $key == 'blast_status' || $key == 'Blast_status' || $key == 'powder_status' || $key == 'chem_bay' || $key == 'treatment_bay' || $key == 'burn_bay' || $key == 'blast_bay' || $key == 'powder_bay'|| $key === 'Main Line_status' || $key === 'Big Batch_status' || $key === 'Small Batch_status') {
                    foreach ($lines as $k => $line) {
                        $line->{$key} = $value;
                        if ($completedKey) {
                            $line->{$completedKey} = $currentDate;
                        }
                        $line->save();
                    }


                    if ($key == 'chem_status' && $value == 'complete') {
                        $chemTreatmentIndex = array_search('C', $treatmentBays);
                        if (!array_key_exists($chemTreatmentIndex + 1, $treatmentBays)) {
                            $jobDetail->job_status = 'Awaiting QC';
                            continue;
                        }
                        $nextTreatment = $treatmentBays[$chemTreatmentIndex + 1];
                        if ($nextTreatment === 'T' && $jobDetail->treatment_status === 'waiting' ) {
                            $jobDetail->treatment_status = 'ready';
                        } else if ($nextTreatment === 'F' && $jobDetail->burn_status === 'waiting' ) {
                            $jobDetail->burn_status = 'ready';
                        } else if ($nextTreatment === 'B' && $jobDetail->blast_status === 'waiting' ) {
                            $jobDetail->blast_status = 'ready';
                        } else if ($nextTreatment === 'C' || $nextTreatment === 'P' && $jobDetail->powder_status === 'waiting' ) {
                            $jobDetail->powder_status = 'ready';
                        }
                    } else if ($key == 'treatment_status' && $value == 'complete') {
                        $treatmentTreatmentIndex = array_search('T', $treatmentBays);
                        if (!array_key_exists($treatmentTreatmentIndex + 1, $treatmentBays)) {
                            $jobDetail->job_status = 'Awaiting QC';
                            continue;
                        }
                        $nextTreatment = $treatmentBays[$treatmentTreatmentIndex + 1];
                        if ($nextTreatment === 'F' && $jobDetail->burn_status === 'waiting' ) {
                            $jobDetail->burn_status = 'ready';
                        } else if ($nextTreatment === 'B' && $jobDetail->blast_status === 'waiting' ) {
                            $jobDetail->blast_status = 'ready';
                        } else if ($nextTreatment === 'C' || $nextTreatment === 'P' && $jobDetail->powder_status === 'waiting' ) {
                            $jobDetail->powder_status = 'ready';
                        }
                    } else if ($key == 'burn_status' && $value == 'complete') {
                        $burnTreatmentIndex = array_search('F', $treatmentBays);
                        if (!array_key_exists($burnTreatmentIndex + 1, $treatmentBays)) {
                            $jobDetail->job_status = 'Awaiting QC';
                            continue;
                        }
                        $nextTreatment = $treatmentBays[$burnTreatmentIndex + 1];
                        if ($nextTreatment === 'B' && $jobDetail->blast_status === 'waiting' ) {
                            $jobDetail->blast_status = 'ready';
                        } else if ($nextTreatment === 'C' || $nextTreatment === 'P' && $jobDetail->powder_status === 'waiting' ) {
                            $jobDetail->powder_status = 'ready';
                        }
                    } else if ($key == 'blast_status' && $value == 'complete') {
                        $blastTreatmentIndex = array_search('B', $treatmentBays);
                        if (!array_key_exists($blastTreatmentIndex + 1, $treatmentBays)) {
                            $jobDetail->job_status = 'Awaiting QC';
                            continue;
                        }
                        $nextTreatment = $treatmentBays[$blastTreatmentIndex + 1];
                        if ($nextTreatment === 'C' || $nextTreatment === 'P' && $jobDetail->powder_status === 'waiting' ) {
                            $jobDetail->powder_status = 'ready';
                        }
                    } else if ($key == 'powder_status' && $value == 'complete') {
                        $jobDetail->job_status = 'Awaiting QC';
                    }
                }

            }

            foreach ($params as $key => $value) {
                $lines = LineItems::where('job_id', $job)->get();
                if ($key == 'job_status') {
                    foreach ($lines as $k => $line) {
                        $line->line_item_status = $value;
                        $line->save();
                    }
                }

                if ($key === 'Main Line_status' || $key === 'Big Batch_status' || $key === 'Small Batch_status') {
                    $key = 'powder_status';
                }

                if($key == 'chem_date' || $key == 'treatment_date' || $key == 'burn_date' || $key == 'blast_date' || $key == 'powder_date'){
                    if($key == 'chem_date'){
                        $jobDetail->chem_date = $value;

                        foreach ($lines as $k => $line) {
                            $line->chem_date = $value;
                            $line->save();
                        }

                        $jobDetail->save();
                    } else if($key == 'burn_date'){
                        $jobDetail->burn_date = $value;

                        foreach ($lines as $k => $line) {
                            $line->burn_date = $value;
                            $line->save();
                        }

                        $jobDetail->save();
                    } else if($key == 'treatment_date'){
                        $jobDetail->treatment_date = $value;

                        foreach ($lines as $k => $line) {
                            $line->treatment_date = $value;
                            $line->save();
                        }

                        $jobDetail->save();
                    } else if($key == 'blast_date'){
                        $jobDetail->blast_date = $value;

                        foreach ($lines as $k => $line) {
                            $line->blast_date = $value;
                            $line->save();
                        }

                        $jobDetail->save();
                    } else if($key == 'powder_date'){
                        $jobDetail->powder_date = $value;
                        
                        foreach ($lines as $k => $line) {
                            $line->powder_date = $value;
                            $line->save();
                        }
                        
                        $jobDetail->save();
                    }
                }

                if($key == 'chem_status' || $key == 'Chem_status' || $key == 'treatment_status' || $key == 'Treatment_status' || $key == 'burn_status' || $key == 'Burn_status' || $key == 'blast_status' || $key == 'Blast_status' || $key == 'powder_status'){
                    if($key == 'chem_status' || $key == 'Chem_status'){
                        foreach ($lines as $k => $line) {
                            $line->chem_status = $value;
                            $line->save();
                        }
                        $jobDetail->chem_status = $value;
                
                        if($jobDetail->chem_status == $completeStatus) {
                            $cnt = 0; 
                            if($jobDetail->burn_bay_required == 'yes'){
                                $jobDetail->burn_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'burn_status',$readyStatus);
                                $cnt++;
                            }
                            if($jobDetail->treatment_bay_required == 'yes' && $cnt<1){                        
                                $jobDetail->treatment_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'treatment_status',$readyStatus);
                                $cnt++;
                            }
                            if($jobDetail->blast_bay_required == 'yes' && $cnt<1){
                                $jobDetail->blast_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'blast_status',$readyStatus);
                                $cnt++;
                            }
                            if($jobDetail->powder_bay_required == 'yes' && $cnt<1){
                                $jobDetail->powder_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'powder_status',$readyStatus);
                            }
                            $jobDetail->save();
                        }
                    } else if($key == 'burn_status' || $key == 'Burn_status'){
                        foreach ($lines as $k => $line) {
                            $line->burn_status = $value;
                            $line->save();
                        }

                        $jobDetail->burn_status = $value;

                        if($jobDetail->burn_status == $completeStatus) {
                            $cnt = 0; 
                            if($jobDetail->treatment_bay_required == 'yes'){
                                $jobDetail->treatment_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'treatment_status',$readyStatus);
                                $cnt++;
                            }
                            if($jobDetail->blast_bay_required == 'yes' && $cnt<1){
                                $jobDetail->blast_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'blast_status',$readyStatus);
                                $cnt++;
                            }
                            if($jobDetail->powder_bay_required == 'yes' && $cnt<1){
                                $jobDetail->powder_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'powder_status',$readyStatus);
                            }
                        }
                        $jobDetail->save();
                    } else if($key == 'treatment_status' || $key == 'Treatment_status'){
                        foreach ($lines as $k => $line) {
                            $line->treatment_status = $value;
                            $line->save();
                        }
                        $jobDetail->treatment_status = $value;

                        if($jobDetail->treatment_status == $completeStatus) {
                            $cnt = 0; 
                            if($jobDetail->blast_bay_required == 'yes'){
                                $jobDetail->blast_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'blast_status',$readyStatus);
                                $cnt++;
                            }
                            if($jobDetail->powder_bay_required == 'yes' && $cnt<1){
                                $jobDetail->powder_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'powder_status',$readyStatus);
                            }
                        }
                        $jobDetail->save();
                    } else if($key == 'blast_status' || $key == 'Blast_status'){
                        foreach ($lines as $k => $line) {
                            $line->blast_status = $value;
                            $line->save();
                        }
                        $jobDetail->blast_status = $value;
                        
                        if($jobDetail->blast_status == $completeStatus) {
                            $cnt = 0; 
                            if($jobDetail->powder_bay_required == 'yes'){
                                $jobDetail->powder_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'powder_status',$readyStatus);
                            }
                            $jobDetail->save();
                        }
                    } else if($key == 'powder_status'){
                        foreach ($lines as $k => $line) {
                            $line->powder_status = $value;
                            $line->save();
                        }
                         
                        if($jobDetail->powder_bay_required == 'yes'){
                            $jobDetail->powder_status = $readyStatus;
                            JobHelper::lineUpdate($lines,'powder_status',$readyStatus);
                        }
                        
                        if($value){
                            $jobDetail->powder_status = $value;
                        }
                        $jobDetail->save();
                    }
                }
                
                if($jobDetail->chem_status == $completeStatus && $jobDetail->treatment_status == $completeStatus && $jobDetail->burn_status == $completeStatus && $jobDetail->blast_status == $completeStatus && $jobDetail->powder_status == $completeStatus){
                        $jobDetail->job_status = 'Awaiting QC';
                    foreach ($lines as $k => $line) {
                        $line->line_item_status = 'Awaiting QC';
                        $line->save();
                    }
                }
                
                if($jobDetail->chem_status == $completeStatus && $jobDetail->treatment_status == $completeStatus && $jobDetail->burn_status == $completeStatus && $jobDetail->blast_status == $completeStatus && $jobDetail->powder_status == $completeStatus){
                    $jobDetail->job_status = 'Awaiting QC';
                }

                if($jobDetail->chem_status != $completeStatus || $jobDetail->treatment_status != $completeStatus || $jobDetail->burn_status != $completeStatus || $jobDetail->blast_status != $completeStatus || $jobDetail->powder_status != $completeStatus){
                    $jobDetail->job_status = 'in progress';
                    foreach ($lines as $k => $line) {
                        $line->line_item_status = 'in progress';
                        $line->save();
                    }
                }

                JobHelper::checkAwaitingQC($jobDetail,$lines);
            }

            $jobDetail->save();
            //Event call
            event(new JobEvent("job event call"));
            if ($jobDetail->job_status === 'Awaiting QC') {
                $lines = LineItems::where('job_id', $job)->get();
                foreach ($lines as $k => $line) {
                    $line->line_item_status = 'Awaiting QC';
                    $line->save();
                }
            }
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $jobDetail, 'job detail updated');
        } catch (Exception $e) {
            Log::error("JobScheduleController@updateJobStatus - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);  
            return ResponseHelper::errorResponse($e->getMessage(), config('constant.status_code.bad_request'));
        }
    }

    public function generateQrCodeLabels(Request $request, $job_id)
    {
        Log::info("JobScheduleController@generateQrCodeLabels", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try{
        $job = Job::where('job_id', $job_id)->with(['deals'])->first();
        if(!$job){
            return ResponseHelper::errorResponse('Cannot find Job. Incorrect job id.', config('constant.status_code.not_found'));
        }

        if(!$job->deals){
            return ResponseHelper::errorResponse('Job '.$job_id.' doesnt have associated deals');
        }

        $company_name = $job->deals->client_name;
        $po_number = $job->deals->po_number;
        $inv_number = $job->deals->invoice_number;
        $colour = $job->colour;
        $client_on_hold = $job->deals->client_on_hold;
        $payment_terms = $job->deals->payment_terms;

        $data = [
            "company_name" => $company_name,
            "po_number" => $po_number,
            "inv_number" => $inv_number,
            "colour" => $colour,
            "client_on_hold" => $client_on_hold,
            "payment_terms" => $payment_terms,
            "qr_code" => base64_encode(QrCode::size(192)->generate(getenv('UX_URL').'/jobs/'.$job_id.''))
        ];

        $pdf = PDF::loadView('labels.qr-code-labels', $data);
        return $pdf->download(($inv_number ? $inv_number : 'job_label').'.pdf');
        } catch (Exception $e) {
            Log::error("JobScheduleController@generateQrCodeLabels - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.not_found'));
        }
    }

    public function handleJobQRCodeRedirection(Request $request, $jobId) {
        Log::info("JobScheduleController@handleJobQRCodeRedirection", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try{
        $job = Job::where('job_id', $jobId)->with(['deals'])->first();
        if(!$job){
            return ResponseHelper::errorResponse('Cannot find Job. Incorrect job id.', config('constant.status_code.not_found'));
        }

        $redirect = 'dashboard';
        if ($job->job_status === 'Awaiting QC') {
            $redirect = 'qc';
        } else if ($job->job_status === 'QC Passed') {
            $redirect = 'dispatch';
        } else if ($job->job_status === 'Ready') {
            $bay = '';
            if($job->treatment){
                $treatmentBays = str_split($job->treatment);
            $bay = $treatmentBays[0];
            }
            if ($bay === 'C' || $bay === 'P') {
                if ($job->powder_bay === 'big batch') {
                    $redirect = 'powder/powder-big-batch';
                } else if ($job->powder_bay === 'small batch') {
                    $redirect = 'powder/powder-small-batch';
                } else if ($job->powder_bay === 'main line') {
                    $redirect = 'powder/powder-main-line';
                }
            } else if ($bay === 'S') {
                $redirect = 'schedule/chem';
            } else if ($bay === 'B') {
                $redirect = 'schedule/blast';
            } else if ($bay === 'F') {
                $redirect = 'schedule/burn';
            } else if ($bay === 'T') {
                $redirect = 'schedule/treatment';
            }
        } else if ($job->job_status === 'In Progress') {
            $treatmentBays = [];
            if($job->treatment){
                $treatmentBays = str_split($job->treatment);
            }
            foreach($treatmentBays as $bay) {
                if ($bay === 'C' || $bay === 'P' && $job->powder_status !== 'complete') {
                    if ($job->powder_bay === 'big batch') {
                        $redirect = 'powder/powder-big-batch';
                    } else if ($job->powder_bay === 'small batch') {
                        $redirect = 'powder/powder-small-batch';
                    } else if ($job->powder_bay === 'main line') {
                        $redirect = 'powder/powder-main-line';
                    }
                    break;
                } else if ($bay === 'S' && $job->chem_status !== 'complete') {
                    $redirect = 'schedule/chem';
                    break;
                } else if ($bay === 'B' && $job->blast_status !== 'complete') {
                    $redirect = 'schedule/blast';
                    break;
                } else if ($bay === 'F' && $job->burn_status !== 'complete') {
                    $redirect = 'schedule/burn';
                    break;
                } else if ($bay === 'T' && $job->treatment_status !== 'complete') {
                    $redirect = 'schedule/treatment';
                    break;
                }
            }
        }

        if($job->deal){
            $archiveList = Deal::with(['jobs', 'jobs.lines', 'jobs.lines.line_product', 'jobs.quality_control'])
                ->where('deal_id', $job->deal->deal_id)
                ->where('deal_status', 'fully_dispatched')
                ->whereDoesntHave('jobs', function($query) {
                    return $query->where('job_status','<>', 'Complete');
                })->get();

            if(count($archiveList) > 0){
                $redirect = 'archive';
            }    
        }

        return response()->json(['message' => 'Successfully retrieved the job', 'redirect' => $redirect]);
        } catch (Exception $e) {
            Log::error("JobScheduleController@handleJobQRCodeRedirection - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.not_found'));
        }    
    }

    public function getAllJobsOverviewKanban(Request $req) {
        Log::info("JobScheduleController@getAllJobsOverviewKanban", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        
        try {
        $jobList = Job::getAllKanbanJobs($req->user())->get();

        foreach ($jobList as $job) {
            $job->amount = 0;
            if ($job['lines']) {
                foreach ($job['lines'] as $line) {
                    $job->amount += $line->quantity * $line->price;
                }
            }

            // if ($job->chem_date == $req->schedule_date) {
            //     $job->column = 'chem';
            // } else if ($job->treatment_date == $req->schedule_date) {
            //     $job->column = 'treatment';
            // } else if ($job->burn_date == $req->schedule_date) {
            //     $job->column = 'burn';
            // } else if ($job->blast_date == $req->schedule_date) {
            //     $job->column = 'blast';
            // } else if ($job->powder_date == $req->schedule_date) {
            //     $job->column = 'powder';
            // }
        }

            $jobList->transform(function ($value) use ($req) {
            return JobScheduleHelper::setData($value, $req->user());
        });

                // foreach ($jobList as $job) {
                //     $job->amount = 0;
                //     if ($job['lines']) {
                //         foreach ($job['lines'] as $line) {
                //             $job->amount += $line->quantity * $line->price;
                //         }
                //     }

                //     // if ($job->chem_date == $req->schedule_date) {
                //     //     $job->column = 'chem';
                //     // } else if ($job->treatment_date == $req->schedule_date) {
                //     //     $job->column = 'treatment';
                //     // } else if ($job->burn_date == $req->schedule_date) {
                //     //     $job->column = 'burn';
                //     // } else if ($job->blast_date == $req->schedule_date) {
                //     //     $job->column = 'blast';
                //     // } else if ($job->powder_date == $req->schedule_date) {
                //     //     $job->column = 'powder';
                //     // }
                // }

                // $jobList->transform(function ($value) use ($req) {
                //     return JobScheduleHelper::setData($value, $req->user());
                // });

                // $chemArr = [];
                // $treatArr = [];
                // $burnArr = [];
                // $blastArr = [];
                // $powderArr = [];

                // foreach ($jobList as $job) {
                //     if ($job['chem_bay_required'] == 'yes') {
                //         array_push($chemArr, $job);
                //     }
                //     if ($job['treatment_bay_required'] == 'yes') {
                //         array_push($treatArr, $job);
                //     }
                //     if ($job['burn_bay_required'] == 'yes') {
                //         array_push($burnArr, $job);
                //     }
                //     if ($job['blast_bay_required'] == 'yes') {
                //         array_push($blastArr, $job);
                //     }
                //     if ($job['powder_bay_required'] == 'yes') {
                //         array_push($powderArr, $job);
                //     }
                // }
                // // $jobArr = [$chemArr, $treatArr, $burnArr, $blastArr, $powderArr];
                // $jobArr = [];
                // array_push($jobArr, $chemArr);
                // array_push($jobArr, $treatArr);
                // array_push($jobArr, $burnArr);
                // array_push($jobArr, $blastArr);
                // array_push($jobArr, $powderArr);


                // if (count($jobArr) > 0) {
                //     return ResponseHelper::responseMessage(config('constant.status_code.success'), $jobArr, 'Job List');
                // } else {
                // return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'Job List');
                // }


            $chemArr = [];
            $treatArr = [];
            $burnArr = [];
            $blastArr = [];
            $powderArr = [];

            foreach ($jobList as $job) {
                if ($job['chem_bay_required'] === 'yes') {
                    $job['column'] = 'chem';
                    array_push($chemArr, $job);
                }
                if ($job['treatment_bay_required'] === 'yes') {
                    $job['column'] = 'treatment';
                    array_push($treatArr, $job);
                }

                if ($job['burn_bay_required'] === 'yes') {
                    $job['column'] = 'burn';
                    array_push($burnArr, $job);
                }

                if ($job['blast_bay_required'] === 'yes') {
                    $job['column'] = 'blast';
                    array_push($blastArr, $job);
                }

                if ($job['powder_bay_required'] === 'yes') {
                    $job['column'] = 'powder';
                    array_push($powderArr, $job);
                }
            }
            $jobArr = [$chemArr, $treatArr, $burnArr, $blastArr, $powderArr];
            if (count($jobArr) > 0) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), $jobArr, 'Job List');
        } else {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'Job List');
        }
        } catch (Exception $e) {
            Log::error("JobScheduleController@generateQrCodeLabels - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);  
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.not_found'));
        }
    }

    public function archiveJob(Request $request){
        Log::info("JobScheduleController@archiveJob", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        
        try {
            $archiveList = Deal::with(['jobs', 'jobs.lines', 'jobs.lines.line_product', 'jobs.quality_control'])->where('deal_status', 'fully_dispatched')->whereDoesntHave('jobs', function($query) {
                return $query->where('job_status','<>', 'Complete');
            })->paginate(config('constant.pagination.dispatch'));

            foreach ($archiveList as $dealKey => $deal) {
                $newLineArray = [];
                $newJobArray = [];
                $jobSignature = '';
                $jobStatus = '';
                foreach ($deal->jobs as $jobKey => $job) {
                    $deal->jobStatus = $job->job_status;
                    array_push($newJobArray, $job->job_id);
                    $deal->jobSignature = Dispatch::where('object_id', $job->job_id)->where('object_type', 'JOB')->first();
                    $user = User::find($job->quality_control->user_id);
                    foreach ($job->lines as $lineKey => $line) {
                        $line->signature = Dispatch::where('object_id', $line->line_item_id)->where('object_type', 'LINE_ITEM')->first()->signature ?? null;
                        $line->number_remaining = max($line->quantity - $line->number_dispatched, 0);
                        if ($user) {
                            $line->first_name = $user->firstname;
                            $line->last_name = $user->lastname;
                        }
                        array_push($newLineArray, $line);
                    }
                }

                $deal->job_ids = $newJobArray;
                $deal->lines = $newLineArray;
            }

            $archiveList->getCollection()->transform(function ($value) use ($request) {
                return DispatchHelper::setData($value, $request->user());
            });

            if ($archiveList->total() > 0) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), $archiveList, 'Archive List');
            } else {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'Archive List');
            }
        } catch (Exception $e) {
            Log::error("JobScheduleController@archiveJob - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }

    public function overrideQc(Request $request,$job)
    {
        Log::info("JobScheduleController@overrideQc", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        
        try {
            $jobDetail = Job::where('job_id', $job)->first();

            if (!$jobDetail) {
                return ResponseHelper::errorResponse('Cannot update status. Incorrect job id.', config('constant.status_code.not_found'));
            }
            $params = json_decode($request->getContent());

            $required = 'yes';
            $completeStatus = 'complete';

            foreach ($params as $key => $value) {
                //for line_items

                if ($key == 'chem_date' || $key == 'treatment_date' || $key == 'burn_date' || $key == 'blast_date' || $key == 'powder_date') {
                    $lines = LineItems::where('job_id', $job)->get();
                    foreach ($lines as $k => $line) {
                        $line->line_item_status = 'Awaiting QC';
                        $line->{$key} = $value;
                        if($key == 'chem_date'){
                            $line->chem_status = $completeStatus;
                        }
                        if($key == 'burn_date'){
                            $line->burn_status = $completeStatus;
                        }
                        if($key == 'treatment_date'){
                            $line->treatment_status = $completeStatus;
                        }
                        if($key == 'blast_date'){
                            $line->blast_status = $completeStatus;
                        }
                        if($key == 'powder_date'){
                            $line->powder_status = $completeStatus;
                        }
                        $line->save();
                    }
                }    
                if($key == 'chem_date'){
                    $jobDetail->chem_bay_required = $required;
                    $jobDetail->chem_status = $completeStatus;
                }
                if($key == 'burn_date'){
                    $jobDetail->burn_bay_required = $required;    
                    $jobDetail->burn_status = $completeStatus;
                }
                if($key == 'treatment_date'){
                    $jobDetail->treatment_bay_required = $required;    
                    $jobDetail->treatment_status = $completeStatus;
                }
                if($key == 'blast_date'){
                    $jobDetail->blast_bay_required = $required;    
                    $jobDetail->blast_status = $completeStatus;
                }
                if($key == 'powder_date'){
                    $jobDetail->powder_bay_required = $required;
                    $jobDetail->powder_status = $completeStatus;
                }
                $jobDetail->{$key} = $value;
            }
            
            $jobDetail->job_status = 'Awaiting QC';
            $jobDetail->save();

            event(new JobEvent("job event call"));
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $jobDetail, 'job detail updated');
        } catch (Exception $e) {
            Log::error("JobScheduleController@overrideQc - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
         
            return ResponseHelper::errorResponse($e->getMessage(), config('constant.status_code.bad_request'));
        }
    }
        
    public function getJobsBay(JobRequest $request) {
        Log::info("JobScheduleController@getJobsBay", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try{
        $jobs = JobHelper::getJobs($request);

        $dashboardDates = [];
        foreach($jobs as $job) {
            if ($request->bay == 'chem') {
                if (($job->chem_date === null && $job->chem_bay_required === 'yes')) {
                    $job->bay = 'Ready for Schedule';
                    $dashboardDates['ready'][] = clone $job;
                    $dashboardDates['ready'] = JobHelper::sortingJobDashboard($dashboardDates['ready'],'ready');
                }

                if ($job->end_chem_date !== null) {
                    $dates = $this->getRangeDates($job->chem_date, $job->end_chem_date);
                    foreach ($dates as $key => $date) {
                        if ($job->chem_bay_required === 'yes' && ( $date >= $request->start_date && $job->chem_date <= $request->end_date)) {
                                $job->bay = 'Chem';
                                $dashboardDates[$date][] = clone $job;
                                $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'chem');
                        }
                    }
                } else {
                    if ($job->chem_bay_required === 'yes' && ( $job->chem_date >= $request->start_date && $job->chem_date <= $request->end_date)) {
                        $job->bay = 'Chem';
                        $dashboardDates[$job->chem_date][] = clone $job;
                        $dashboardDates[$job->chem_date] = JobHelper::sortingJobDashboard($dashboardDates[$job->chem_date],'chem');
                        
                    }
                }
            }
            if ($request->bay == 'burn') {
                if (($job->burn_date === null && $job->burn_bay_required === 'yes')) {
                    $job->bay = 'Ready for Schedule';
                    $dashboardDates['ready'][] = clone $job;
                    $dashboardDates['ready'] = JobHelper::sortingJobDashboard($dashboardDates['ready'],'ready');
                }

                if ($job->end_burn_date !== null) {
                    $dates = $this->getRangeDates($job->burn_date, $job->end_burn_date);
                    foreach ($dates as $key => $date) {
                        if ($job->burn_bay_required === 'yes' && ( $date >= $request->start_date && $job->burn_date <= $request->end_date)) {
                            $job->bay = 'Burn';
                            $dashboardDates[$date][] = clone $job;
                            $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'burn');
                        }
                    }
                } else {
                    if ($job->burn_bay_required === 'yes' && ( $job->burn_date >= $request->start_date && $job->burn_date <= $request->end_date)) {
                        $job->bay = 'Burn';
                        $dashboardDates[$job->burn_date][] = clone $job;
                        $dashboardDates[$job->burn_date] = JobHelper::sortingJobDashboard($dashboardDates[$job->burn_date],'burn');
                    }
                }

            }
            if ($request->bay == 'treatment') {
                if (($job->treatment_date === null && $job->treatment_bay_required === 'yes')) {
                    $job->bay = 'Ready for Schedule';
                    $dashboardDates['ready'][] = clone $job;
                    $dashboardDates['ready'] = JobHelper::sortingJobDashboard($dashboardDates['ready'],'ready');
                }
                if ($job->end_treatment_date !== null) {
                    $dates = $this->getRangeDates($job->treatment_date, $job->end_treatment_date);
                    foreach ($dates as $key => $date) {
                        if ($job->treatment_bay_required === 'yes' && ( $date >= $request->start_date && $job->treatment_date <= $request->end_date)) {
                            $job->bay = 'Treatment';
                            $dashboardDates[$date][] = clone $job;
                            $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'treatment');
                        }
                    }
                } else {
                    if ($job->treatment_bay_required === 'yes' && ( $job->treatment_date >= $request->start_date && $job->treatment_date <= $request->end_date)) {
                        $job->bay = 'Treatment';
                        $dashboardDates[$job->treatment_date][] = clone $job;
                        $dashboardDates[$job->treatment_date] = JobHelper::sortingJobDashboard($dashboardDates[$job->treatment_date],'treatment');
                    }
                }
                
            }
            if ($request->bay == 'blast') {
                if (($job->blast_date === null && $job->blast_bay_required === 'yes')) {
                    $job->bay = 'Ready for Schedule';
                    $dashboardDates['ready'][] = clone $job;
                    $dashboardDates['ready'] = JobHelper::sortingJobDashboard($dashboardDates['ready'],'ready');
                }

                if ($job->end_blast_date !== null) {
                    $dates = $this->getRangeDates($job->blast_date, $job->end_blast_date);
                    foreach ($dates as $key => $date) {
                        if ($job->blast_bay_required === 'yes' && ( $date >= $request->start_date && $job->blast_date <= $request->end_date)) {
                            $job->bay = 'Blast';
                            $dashboardDates[$date][] = clone $job;
                            $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'blast');
                        }
                    }
                } else {
                    if ($job->blast_bay_required === 'yes' && ( $job->blast_date >= $request->start_date && $job->blast_date <= $request->end_date)) {
                        $job->bay = 'Blast';
                        $dashboardDates[$job->blast_date][] = clone $job;
                        $dashboardDates[$job->blast_date] = JobHelper::sortingJobDashboard($dashboardDates[$job->blast_date],'blast');
                    }
                }
                
            }
            if ($request->bay == 'powder-main-line') {
                if (($job->powder_date === null && $job->powder_bay_required === 'yes' && $job->powder_bay === 'main line')) {
                    $job->bay = 'Ready for Schedule';
                    $dashboardDates['ready'][] = clone $job;
                    $dashboardDates['ready'] = JobHelper::sortingJobDashboard($dashboardDates['ready'],'ready');
                }
                if ($job->end_powder_date !== null) {
                    $dates = $this->getRangeDates($job->powder_date, $job->end_powder_date);
                    foreach ($dates as $key => $date) {
                        if ($job->powder_bay_required === 'yes'  && $job->powder_bay === 'main line' && ( $date >= $request->start_date && $job->powder_date <= $request->end_date)) {
                            $job->bay = 'Main Line';
                            $dashboardDates[$date][] = clone $job;
                            $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'powder');
                        }
                    }
                } else {
                    if ($job->powder_bay_required === 'yes'  && $job->powder_bay === 'main line' && ( $job->powder_date >= $request->start_date && $job->powder_date <= $request->end_date)) {
                        $job->bay = 'Main Line';
                        $dashboardDates[$job->powder_date][] = clone $job;
                        $dashboardDates[$job->powder_date] = JobHelper::sortingJobDashboard($dashboardDates[$job->powder_date],'powder');
                    }
                }
            }
            if($request->bay == 'powder-small-batch') {
                if (($job->powder_date === null && $job->powder_bay_required === 'yes' && $job->powder_bay === 'small batch')) {
                    $job->bay = 'Ready for Schedule';
                    $dashboardDates['ready'][] = clone $job;
                    $dashboardDates['ready'] = JobHelper::sortingJobDashboard($dashboardDates['ready'],'ready');
                }

                if ($job->end_powder_date !== null) {
                    $dates = $this->getRangeDates($job->powder_date, $job->end_powder_date);
                    foreach ($dates as $key => $date) {
                        if ($job->powder_bay_required === 'yes' && $job->powder_bay === 'small batch' && ( $date >= $request->start_date && $job->powder_date <= $request->end_date)) {
                            $job->bay = 'Small Batch';
                            $dashboardDates[$date][] = clone $job;
                            $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'powder');
                        }
                    }
                } else {
                    if ($job->powder_bay_required === 'yes' && $job->powder_bay === 'small batch' && ( $job->powder_date >= $request->start_date && $job->powder_date <= $request->end_date)) {
                        $job->bay = 'Small Batch';
                        $dashboardDates[$job->powder_date][] = clone $job;
                        $dashboardDates[$job->powder_date] = JobHelper::sortingJobDashboard($dashboardDates[$job->powder_date],'powder');
                    }
                }
            }

            if($request->bay == 'powder-big-batch') {
                if (($job->powder_date === null && $job->powder_bay_required === 'yes' && $job->powder_bay === 'big batch')) {
                    $job->bay = 'Ready for Schedule';
                    $dashboardDates['ready'][] = clone $job;
                    $dashboardDates['ready'] = JobHelper::sortingJobDashboard($dashboardDates['ready'],'ready');
                }
                if ($job->end_powder_date !== null) {
                    $dates = $this->getRangeDates($job->powder_date, $job->end_powder_date);
                    foreach ($dates as $key => $date) {
                        if ($job->powder_bay_required === 'yes' && $job->powder_bay === 'big batch' && ( $date >= $request->start_date && $job->powder_date <= $request->end_date)) {
                            $job->bay = 'Big Batch';
                            $dashboardDates[$date][] = clone $job;
                            $dashboardDates[$date] = JobHelper::sortingJobDashboard($dashboardDates[$date],'powder');
                        }
                    }
                } else {
                    if ($job->powder_bay_required === 'yes' && $job->powder_bay === 'big batch' && ( $job->powder_date >= $request->start_date && $job->powder_date <= $request->end_date)) {
                        $job->bay = 'Big Batch';
                        $dashboardDates[$job->powder_date][] = clone $job;
                        $dashboardDates[$job->powder_date] = JobHelper::sortingJobDashboard($dashboardDates[$job->powder_date],'powder');
                    }
                }
                
            }
        }
        return response()->json(['jobs' => $dashboardDates]);
          } catch (Exception $e) {
            Log::error("JobScheduleController@getJobsBay - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.not_found'));
        }  
    }

    public function getRangeDates($startDate, $endDate)
    {
        $rangArray = [];
        $startDate = strtotime($startDate);
        $endDate = strtotime($endDate);
        for ($currentDate = $startDate; $currentDate <= $endDate; 
                                        $currentDate += (86400)) {
            $date = date('Y-m-d', $currentDate);
            $rangArray[] = $date;
        }
  
        return $rangArray;
    }

    public function updateJobLocation(Request $request, $jobId) {
        Log::info("JobScheduleController@updateJobLocation", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        
        try {

            $job = Job::find($jobId);

            if (!$job) {
                Log::error("JobScheduleController@updateJobLocation - Job {$jobId} not found", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
                return ResponseHelper::errorResponse('Cannot update location. Incorrect job id.', config('constant.status_code.not_found'));
            }

            if ($request->location_id == 'other') {
                $job->other_location = $request->location;
                $job->save();
                Log::info("JobScheduleController@updateJobLocation - Successfully updated 'other' location", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id], "job"=>$job, "location"=>$request->location]);
                return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'job location updated');
            }

            $job->location_id = $request->location_id;
            $job->save();
            Log::info("JobScheduleController@updateJobLocation - Successfully updated location", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id], "job"=>$job, "location"=>$request->location]);
            return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'job location updated');
        } catch (Exception $e) {
            Log::error("JobScheduleController@updateJobLocation - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.not_found'));
        }
    }

    public function updateJobPriority(UpdateJobRequest $request, $job)
    {
        Log::info("JobScheduleController@updateJobPriority", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $jobDetail = Job::where('job_id', $job)->first();

            if (!$jobDetail) {
                return ResponseHelper::errorResponse('Cannot update status. Incorrect job id.', config('constant.status_code.not_found'));
            }
            $params = json_decode($request->getContent());

            foreach ($params as $key => $value) {
                JobHelper::lineJobStatus($jobDetail,$job,$key,$value);
                if ($key == 'priority' ||$key == 'chem_priority' || $key == 'burn_priority' || $key == 'treatment_priority' || $key == 'blast_priority' || $key == 'powder_priority') {
                    $jobDetail->{$key} = $value;
                }
            }

            $jobDetail->save();
            //Event call
            event(new JobEvent("job event call"));

            return ResponseHelper::responseMessage(config('constant.status_code.success'), $jobDetail, 'job detail updated');
        } catch (Exception $e) {
            Log::error("JobScheduleController@updateJobPriority - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);  
            return ResponseHelper::errorResponse($e->getMessage(), config('constant.status_code.bad_request'));
        }
    }

    public function updateJobBayDate(UpdateJobRequest $request, $job)
    {
        Log::info("JobScheduleController@updateJobBayDate", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $jobDetail = Job::where('job_id', $job)->first();

            if (!$jobDetail) {
                return ResponseHelper::errorResponse('Cannot update status. Incorrect job id.', config('constant.status_code.not_found'));
            }
            $params = json_decode($request->getContent());

            $waitingStatus = 'waiting';
            $readyStatus = 'ready';
            $completeStatus = 'complete';
            
            foreach ($params as $key => $value) {
                JobHelper::lineJobStatus($jobDetail,$job,$key,$value);

                if($key == 'chem_date' || $key == 'treatment_date' || $key == 'burn_date' || $key == 'blast_date' || $key == 'powder_date'){
                    $lines = LineItems::where('job_id', $job)->get();
                    
                    if($key == 'chem_date'){
                        $jobDetail->chem_date = $value;
                        
                        $jobDetail->chem_status = $readyStatus;
                        JobHelper::detailSet($jobDetail,'burn',$waitingStatus);
                        JobHelper::detailSet($jobDetail,'treatment',$waitingStatus);
                        JobHelper::detailSet($jobDetail,'blast',$waitingStatus);
                        JobHelper::detailSet($jobDetail,'powder',$waitingStatus);
                        
                        foreach ($lines as $k => $line) {
                            $line->chem_date = $value;
                            
                            $line->chem_status = $readyStatus;
                            JobHelper::lineSet($line,'burn',$waitingStatus);
                            JobHelper::lineSet($line,'treatment',$waitingStatus);
                            JobHelper::lineSet($line,'blast',$waitingStatus);
                            JobHelper::lineSet($line,'powder',$waitingStatus);
                            $line->save();
                        }
                        $jobDetail->save();
                    } else if($key == 'burn_date'){
                        $jobDetail->burn_date = $value;
                        
                        $jobDetail->burn_status = $readyStatus;
                        
                        JobHelper::detailSet($jobDetail,'treatment',$waitingStatus);
                        JobHelper::detailSet($jobDetail,'blast',$waitingStatus);
                        JobHelper::detailSet($jobDetail,'powder',$waitingStatus);
                        
                        foreach ($lines as $k => $line) {
                            $line->burn_date = $value;

                            $line->burn_status = $readyStatus;
                            JobHelper::lineSet($line,'treatment',$waitingStatus);
                            JobHelper::lineSet($line,'blast',$waitingStatus);
                            JobHelper::lineSet($line,'powder',$waitingStatus);
                            $line->save();
                        }
                        $jobDetail->save();
                    } else if($key == 'treatment_date'){
                        $jobDetail->treatment_date = $value;

                        $jobDetail->treatment_status = $readyStatus;
                        JobHelper::detailSet($jobDetail,'blast',$waitingStatus);
                        JobHelper::detailSet($jobDetail,'powder',$waitingStatus);
                        
                        foreach ($lines as $k => $line) {
                            $line->treatment_date = $value;
                                                        
                            $line->treatment_status = $readyStatus;
                            JobHelper::lineSet($line,'blast',$waitingStatus);
                            JobHelper::lineSet($line,'powder',$waitingStatus);
                            $line->save();
                        }
                        $jobDetail->save();
                    } else if($key == 'blast_date'){
                        $jobDetail->blast_date = $value;
                        
                        $jobDetail->blast_status = $readyStatus;
                        JobHelper::detailSet($jobDetail,'powder',$waitingStatus);
                        

                        foreach ($lines as $k => $line) {
                            $line->blast_date = $value;

                            $line->blast_status = $readyStatus;
                            JobHelper::lineSet($line,'powder',$waitingStatus);
                            $line->save();
                        }
                        $jobDetail->save();
                    } else if($key == 'powder_date'){
                        $jobDetail->powder_date = $value;
                        
                        $jobDetail->powder_status = $readyStatus;

                        foreach ($lines as $k => $line) {
                            $line->powder_date = $value;

                            $line->powder_status = $readyStatus;
                            $line->save();
                        }
                        $jobDetail->save();
                    }

                }
                if($jobDetail->chem_status == $completeStatus && $jobDetail->treatment_status == $completeStatus && $jobDetail->burn_status == $completeStatus && $jobDetail->blast_status == $completeStatus && $jobDetail->powder_status == $completeStatus){
                        $jobDetail->job_status = 'Awaiting QC';
                    foreach ($lines as $k => $line) {
                        $line->line_item_status = 'Awaiting QC';
                        $line->save();
                    }
                }
                
                if($jobDetail->chem_status == $completeStatus && $jobDetail->treatment_status == $completeStatus && $jobDetail->burn_status == $completeStatus && $jobDetail->blast_status == $completeStatus && $jobDetail->powder_status == $completeStatus){
                    $jobDetail->job_status = 'Awaiting QC';
                }

                if($jobDetail->chem_status != $completeStatus || $jobDetail->treatment_status != $completeStatus || $jobDetail->burn_status != $completeStatus || $jobDetail->blast_status != $completeStatus || $jobDetail->powder_status != $completeStatus){
                    $jobDetail->job_status = 'in progress';
                    foreach ($lines as $k => $line) {
                        $line->line_item_status = 'in progress';
                        $line->save();
                    }
                }
            }


            $jobDetail->save();
            //Event call
            event(new JobEvent("job event call"));

            return ResponseHelper::responseMessage(config('constant.status_code.success'), $jobDetail, 'job detail updated');
        } catch (Exception $e) {
            Log::error("JobScheduleController@updateJobBayDate - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);  
            return ResponseHelper::errorResponse($e->getMessage(), config('constant.status_code.bad_request'));
        }
    }

    public function updateJobBayStatus(UpdateJobRequest $request, $job)
    {
        Log::info("JobScheduleController@updateJobBayStatus", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $jobDetail = Job::where('job_id', $job)->first();

            if (!$jobDetail) {
                return ResponseHelper::errorResponse('Cannot update status. Incorrect job id.', config('constant.status_code.not_found'));
            }
            $params = json_decode($request->getContent());
            
            $treatmentBays = [];
            if($jobDetail->treatment){
                $treatmentBays = str_split($jobDetail->treatment);
            }

            $waitingStatus = 'waiting';
            $readyStatus = 'ready';
            $completeStatus = 'complete';

            $currentDate = date('Y-m-d');

            foreach ($params as $key => $value) {
                JobHelper::lineJobStatus($jobDetail,$job,$key,$value);

                $lines = LineItems::where('job_id', $job)->get();

                if ($key === 'Main Line_status' || $key === 'Big Batch_status' || $key === 'Small Batch_status') {
                    $key = 'powder_status';
                }
                
                $completedKey = null;
                if ($value === 'complete') {
                    if ($key === "chem_status" || $key === 'Chem_status') {
                        $completedKey = "chem_completed";
                    } else if ($key === 'Treatment_status' || $key === 'treatment_status') {
                        $completedKey = 'treatment_completed';
                    }else if ($key === 'blast_status' || $key === 'Blast_status') {
                        $completedKey = 'blast_completed';
                    } else if ($key === 'burn_status' || $key === 'Burn_status') {
                        $completedKey = 'burn_completed';
                    } else if ($key === 'powder_status' || $key === 'Powder_status') {
                        $key = 'powder_status';
                        $completedKey = 'powder_completed';
                    }

                    if($completedKey){
                        $jobDetail->{$completedKey} = $currentDate;
                        $jobDetail->save();
                    }

                }
                

                if ($key == 'chem_status' || $key == 'Chem_status' || $key == 'treatment_status' || $key == 'Treatment_status' || $key == 'burn_status' || $key == 'Burn_status' || $key == 'blast_status' || $key == 'Blast_status' || $key == 'powder_status' || $key == 'chem_bay' || $key == 'treatment_bay' || $key == 'burn_bay' || $key == 'blast_bay' || $key == 'powder_bay'|| $key === 'Main Line_status' || $key === 'Big Batch_status' || $key === 'Small Batch_status') {
                    foreach ($lines as $k => $line) {
                        $line->{$key} = $value;
                        if ($completedKey) {
                            $line->{$completedKey} = $currentDate;
                        }
                        $line->save();
                    }

                    if ($key == 'chem_status' && $value == 'complete') {
                        $chemTreatmentIndex = array_search('C', $treatmentBays);
                        if (!array_key_exists($chemTreatmentIndex + 1, $treatmentBays)) {
                            $jobDetail->job_status = 'Awaiting QC';
                            continue;
                        }
                        $nextTreatment = $treatmentBays[$chemTreatmentIndex + 1];
                        if ($nextTreatment === 'T' && $jobDetail->treatment_status === 'waiting' ) {
                            $jobDetail->treatment_status = 'ready';
                        } else if ($nextTreatment === 'F' && $jobDetail->burn_status === 'waiting' ) {
                            $jobDetail->burn_status = 'ready';
                        } else if ($nextTreatment === 'B' && $jobDetail->blast_status === 'waiting' ) {
                            $jobDetail->blast_status = 'ready';
                        } else if ($nextTreatment === 'C' || $nextTreatment === 'P' && $jobDetail->powder_status === 'waiting' ) {
                            $jobDetail->powder_status = 'ready';
                        }
                    } else if ($key == 'treatment_status' && $value == 'complete') {
                        $treatmentTreatmentIndex = array_search('T', $treatmentBays);
                        if (!array_key_exists($treatmentTreatmentIndex + 1, $treatmentBays)) {
                            $jobDetail->job_status = 'Awaiting QC';
                            continue;
                        }
                        $nextTreatment = $treatmentBays[$treatmentTreatmentIndex + 1];
                        if ($nextTreatment === 'F' && $jobDetail->burn_status === 'waiting' ) {
                            $jobDetail->burn_status = 'ready';
                        } else if ($nextTreatment === 'B' && $jobDetail->blast_status === 'waiting' ) {
                            $jobDetail->blast_status = 'ready';
                        } else if ($nextTreatment === 'C' || $nextTreatment === 'P' && $jobDetail->powder_status === 'waiting' ) {
                            $jobDetail->powder_status = 'ready';
                        }
                    } else if ($key == 'burn_status' && $value == 'complete') {
                        $burnTreatmentIndex = array_search('F', $treatmentBays);
                        if (!array_key_exists($burnTreatmentIndex + 1, $treatmentBays)) {
                            $jobDetail->job_status = 'Awaiting QC';
                            continue;
                        }
                        $nextTreatment = $treatmentBays[$burnTreatmentIndex + 1];
                        if ($nextTreatment === 'B' && $jobDetail->blast_status === 'waiting' ) {
                            $jobDetail->blast_status = 'ready';
                        } else if ($nextTreatment === 'C' || $nextTreatment === 'P' && $jobDetail->powder_status === 'waiting' ) {
                            $jobDetail->powder_status = 'ready';
                        }
                    } else if ($key == 'blast_status' && $value == 'complete') {
                        $blastTreatmentIndex = array_search('B', $treatmentBays);
                        if (!array_key_exists($blastTreatmentIndex + 1, $treatmentBays)) {
                            $jobDetail->job_status = 'Awaiting QC';
                            continue;
                        }
                        $nextTreatment = $treatmentBays[$blastTreatmentIndex + 1];
                        if ($nextTreatment === 'C' || $nextTreatment === 'P' && $jobDetail->powder_status === 'waiting' ) {
                            $jobDetail->powder_status = 'ready';
                        }
                    } else if ($key == 'powder_status' && $value == 'complete') {
                        $jobDetail->job_status = 'Awaiting QC';
                    }
                }

            }

            foreach ($params as $key => $value) {
                $lines = LineItems::where('job_id', $job)->get();
                
                if ($key === 'Main Line_status' || $key === 'Big Batch_status' || $key === 'Small Batch_status') {
                    $key = 'powder_status';
                }

                if($key == 'chem_status' || $key == 'Chem_status' || $key == 'treatment_status' || $key == 'Treatment_status' || $key == 'burn_status' || $key == 'Burn_status' || $key == 'blast_status' || $key == 'Blast_status' || $key == 'powder_status'){
                    if($key == 'chem_status' || $key == 'Chem_status'){
                        foreach ($lines as $k => $line) {
                            $line->chem_status = $value;
                            $line->save();
                        }
                        $jobDetail->chem_status = $value;
                
                        if($jobDetail->chem_status == $completeStatus) {
                            $cnt = 0; 
                            if($jobDetail->burn_bay_required == 'yes'){
                                $jobDetail->burn_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'burn_status',$readyStatus);
                                $cnt++;
                            }
                            if($jobDetail->treatment_bay_required == 'yes' && $cnt<1){                        
                                $jobDetail->treatment_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'treatment_status',$readyStatus);
                                $cnt++;
                            }
                            if($jobDetail->blast_bay_required == 'yes' && $cnt<1){
                                $jobDetail->blast_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'blast_status',$readyStatus);
                                $cnt++;
                            }
                            if($jobDetail->powder_bay_required == 'yes' && $cnt<1){
                                $jobDetail->powder_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'powder_status',$readyStatus);
                            }
                            $jobDetail->save();
                        }
                    } else if($key == 'burn_status' || $key == 'Burn_status'){
                        foreach ($lines as $k => $line) {
                            $line->burn_status = $value;
                            $line->save();
                        }

                        $jobDetail->burn_status = $value;

                        if($jobDetail->burn_status == $completeStatus) {
                            $cnt = 0; 
                            if($jobDetail->treatment_bay_required == 'yes'){
                                $jobDetail->treatment_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'treatment_status',$readyStatus);
                                $cnt++;
                            }
                            if($jobDetail->blast_bay_required == 'yes' && $cnt<1){
                                $jobDetail->blast_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'blast_status',$readyStatus);
                                $cnt++;
                            }
                            if($jobDetail->powder_bay_required == 'yes' && $cnt<1){
                                $jobDetail->powder_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'powder_status',$readyStatus);
                            }
                        }
                        $jobDetail->save();
                    } else if($key == 'treatment_status' || $key == 'Treatment_status'){
                        foreach ($lines as $k => $line) {
                            $line->treatment_status = $value;
                            $line->save();
                        }
                        $jobDetail->treatment_status = $value;

                        if($jobDetail->treatment_status == $completeStatus) {
                            $cnt = 0; 
                            if($jobDetail->blast_bay_required == 'yes'){
                                $jobDetail->blast_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'blast_status',$readyStatus);
                                $cnt++;
                            }
                            if($jobDetail->powder_bay_required == 'yes' && $cnt<1){
                                $jobDetail->powder_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'powder_status',$readyStatus);
                            }
                        }
                        $jobDetail->save();
                    } else if($key == 'blast_status' || $key == 'Blast_status'){
                        foreach ($lines as $k => $line) {
                            $line->blast_status = $value;
                            $line->save();
                        }
                        $jobDetail->blast_status = $value;
                        
                        if($jobDetail->blast_status == $completeStatus) {
                            $cnt = 0; 
                            if($jobDetail->powder_bay_required == 'yes'){
                                $jobDetail->powder_status = $readyStatus;
                                JobHelper::lineUpdate($lines,'powder_status',$readyStatus);
                            }
                        }
                        $jobDetail->save();
                    } else if($key == 'powder_status'){
                        foreach ($lines as $k => $line) {
                            $line->powder_status = $value;
                            $line->save();
                        }
                         
                        if($jobDetail->powder_bay_required == 'yes'){
                            $jobDetail->powder_status = $readyStatus;
                            JobHelper::lineUpdate($lines,'powder_status',$readyStatus);
                        }
                        
                        if($value){
                            $jobDetail->powder_status = $value;
                        }
                        $jobDetail->save();
                    }
                }
                
                if($jobDetail->chem_status == $completeStatus && $jobDetail->treatment_status == $completeStatus && $jobDetail->burn_status == $completeStatus && $jobDetail->blast_status == $completeStatus && $jobDetail->powder_status == $completeStatus){
                    $jobDetail->job_status = 'Awaiting QC';
                    foreach ($lines as $k => $line) {
                        $line->line_item_status = 'Awaiting QC';
                        $line->save();
                    }
                }
                
                if($jobDetail->chem_status == $completeStatus && $jobDetail->treatment_status == $completeStatus && $jobDetail->burn_status == $completeStatus && $jobDetail->blast_status == $completeStatus && $jobDetail->powder_status == $completeStatus){
                    $jobDetail->job_status = 'Awaiting QC';
                }

                if($jobDetail->chem_status != $completeStatus || $jobDetail->treatment_status != $completeStatus || $jobDetail->burn_status != $completeStatus || $jobDetail->blast_status != $completeStatus || $jobDetail->powder_status != $completeStatus){
                    $jobDetail->job_status = 'in progress';
                    foreach ($lines as $k => $line) {
                        $line->line_item_status = 'in progress';
                        $line->save();
                    }
                }

                JobHelper::checkAwaitingQC($jobDetail,$lines);
            }

            $jobDetail->save();
            //Event call
            event(new JobEvent("job event call"));

            return ResponseHelper::responseMessage(config('constant.status_code.success'), $jobDetail, 'job detail updated');
        } catch (Exception $e) {
            Log::error("JobScheduleController@updateJobBayStatus - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);  
            return ResponseHelper::errorResponse($e->getMessage(), config('constant.status_code.bad_request'));
        }
    }

    public function getJobsBayReport(Request $request) {
        Log::info("JobScheduleController@getJobBayReport", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try{
        $arrayJobs = [];
        $numberOfJobs = 0;
        $currentValueOfBay = 0;
        $currentValueOfJobs = 0;
        $currentValueOfFailedJobs = 0;
        $numberOfFailedJobs = 0;

        if ($request->filterStartDate == null)
            return response()->json('Missing Date');

        $jobs = JobHelper::getJobsBayWithDateReport($request);
        $data = JobHelper::jobsFormatter($jobs, $arrayJobs, $numberOfJobs, $currentValueOfBay, $currentValueOfJobs, $request);

        $failedJobs = JobHelper::getFailedJobsBayWithDateReport($request);
        $failedData = JobHelper::failedJobsFormatter($failedJobs, $arrayJobs, $numberOfFailedJobs, $currentValueOfFailedJobs, $request);

        return response()->json([
            'jobs' => $data,
            'failed_jobs' => $failedData,
        ]);

        } catch (Exception $e) {
            return response()->json($e->getMessage());
        Log::error("JobScheduleController@getJobBayReport - Something has gone wrong: ".$e->getMessage(), [
            "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
            "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
        ]);
        \Sentry\captureException($e); 
        return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.not_found'));
        }  
    }

    

    
}
