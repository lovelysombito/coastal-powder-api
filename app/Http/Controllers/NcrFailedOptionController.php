<?php

namespace App\Http\Controllers;

use App\Events\JobEvent;
use App\Models\NcrFailedOption;
use Exception;
use Illuminate\Http\Request;
use App\Http\Requests\FailedOptionRequest;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Log;

class NcrFailedOptionController extends Controller
{
    protected $model;
    public function __construct(){
        $this->model = new NcrFailedOption();
    }

    public function getFailedOption(Request $request) {
        Log::info("NcrFailedOptionController@getFailedOption", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $optionList = $this->model::paginate(config('constant.paginate.failed_option'));

            if ($optionList->total() == 0) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'option List');
            }

            return ResponseHelper::responseMessage(config('constant.status_code.success'), $optionList, 'option List');
        
        } catch (Exception $e) {
            Log::error("NcrFailedOptionController@getFailedOption - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }

    public function addFailedOption(FailedOptionRequest $request) {
        Log::info("NcrFailedOptionController@addFailedOption", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        
        try {
            
            $params = json_decode($request->getContent());

            $option = $this->model::create([
                'ncr_failed' => $params->ncr_failed
            ]);
            
            event(new JobEvent('ncr'));
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $option, 'Option added.');
        
        } catch (Exception $e) {
            Log::error("NcrFailedOptionController@addFailedOption - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }
    
    public function editFailedOption(Request $request,$optionId) {
        Log::info("NcrFailedOptionController@editFailedOption", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        
        try {
            $params = json_decode($request->getContent());
            $failedOption = $this->model::find($optionId);
            if (!$failedOption)
                return ResponseHelper::errorResponse('Cannot update option. Invalid ncr_failed_id.', config('constant.status_code.bad_request'));

            
            $failedOption->ncr_failed = $params->ncr_failed;
            $failedOption->save();
            
            event(new JobEvent('ncr'));
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $failedOption, 'option updated.');
        
        } catch (Exception $e) {
            Log::error("NcrFailedOptionController@editFailedOption - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }

    public function deleteFailedOption(Request $request, $optionId)
    {
        Log::info("NcrFailedOptionController@deleteFailedOption", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $failedOption = $this->model::find($optionId);
            if (!$failedOption)
                return ResponseHelper::errorResponse('option delete invalid.', config('constant.status_code.bad_request'));

            $failedOption->delete();

            event(new JobEvent('ncr'));
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $failedOption, 'option deleted.');
        } catch (Exception $e) {
            Log::error("NcrFailedOptionController@deleteFailedOption - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }
}
