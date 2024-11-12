<?php

namespace App\Http\Controllers;

use App\Helpers\JobScheduleHelper;
use App\Helpers\ResponseHelper;
use App\Models\FailedJob;
use App\Models\NonConformanceReport;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NonConformanceReportController extends Controller
{
   public function getNonConformanceReports(Request $request) {
        Log::info("NonConformanceReportController@getNonConformanceReports", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $datas = FailedJob::with('ncr')->with('lines')->with('deals')->paginate((config('constant.pagination.job')));

            $datas->getCollection()->transform(function ($value) use ($request) {
                return JobScheduleHelper::setNCRData($value, $request->user());
            });

            if (!empty($datas)) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), $datas, 'NCR Lists');
            } else 
            {
                return ResponseHelper::errorResponse('No NCR found.', config('constant.status_code.not_found'));
            }
        } catch (Exception $e) {
            Log::error("NonConformanceReportController@getNonConformanceReports - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
   }

    public function downloadImage(Request $request, $id) {
        Log::info("NonConformanceReportController@downloadImage", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {
            $data = NonConformanceReport::find($id);
            $url = $data->photo;
            return file_get_contents($url);
        } catch (Exception $e) {
            Log::error("NonConformanceReportController@downloadImage - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }
}
