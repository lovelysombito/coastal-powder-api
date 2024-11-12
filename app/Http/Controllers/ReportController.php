<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Jobs\Exports\GenerateReport as ExportsGenerateReport;
use App\Models\Job;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function generateReport(Request $request) {
        try {
            $dateFrom = $request->reportStartDate;
            $dateTo = $request->reportEndDate;

            $jobs = Job::whereBetween('powder_date', [$dateFrom, $dateTo])->with('lineItems')->get();
            ExportsGenerateReport::dispatch($jobs, $request->user());
        } catch (Exception $e) {
            Log::error("ReportController@generateReport - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);  
            return ResponseHelper::errorResponse($e->getMessage(), config('constant.status_code.bad_request'));
        }
    }
}
