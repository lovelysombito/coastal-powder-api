<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Exception;
use Illuminate\Http\Request;
use App\Http\Requests\HolidayRequest;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Log;

class HolidayController extends Controller
{
    public function getAllHolidays(Request $request) {
        Log::info("HolidayController@getAllHolidays", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {
            $holidayList = Holiday::all();

            if (count($holidayList) == 0) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'Holiday List');
            }

            return ResponseHelper::responseMessage(config('constant.status_code.success'), $holidayList, 'Holiday List');
        } catch (Exception $e) {
            Log::error("HolidayController@getAllHolidays - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }

    public function addHoliday(HolidayRequest $request) {
        Log::info("HolidayController@addHoliday", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {
            
            $params = json_decode($request->getContent());

            $holiday = Holiday::create([
                'date' => $params->date,
                'holiday' => $params->holiday
            ]);
            
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $holiday, 'Holiday added.');
        
        } catch (Exception $e) {
            Log::error("HolidayController@addHoliday - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }
    
    public function editHoliday(Request $request,$holidayId) {
        Log::info("HolidayController@editHoliday", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {
            $params = json_decode($request->getContent());
            $holiday = Holiday::find($holidayId);
            if (!$holiday)
                return ResponseHelper::errorResponse('Cannot update holiday. Invalid holiday Id.', config('constant.status_code.bad_request'));

            if (isset($params->date)){
                $holidayDate = Holiday::where('date',$params->date)
                ->where('holiday_id', '!=' ,$holidayId)
                ->get();
                if (count($holidayDate) > 0) {
                    return ResponseHelper::errorResponse('This date already mentioned as holiday.', config('constant.status_code.bad_request'));
                 }
                $holiday->date = $params->date;
            }
            if (isset($params->holiday)) {
                $holiday->holiday = $params->holiday;
            }
            $holiday->save();
            
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $holiday, 'Holiday updated.');
        
        } catch (Exception $e) {
            Log::error("HolidayController@editHoliday - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }

    public function deleteHoliday(Request $request, $holidayId)
    {
        Log::info("HolidayController@deleteHoliday", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {

            $holiday = Holiday::find($holidayId);
            if (!$holiday)
                return ResponseHelper::errorResponse('Holiday delete invalid.', config('constant.status_code.bad_request'));

            $holiday->delete();
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $holiday, 'Holiday deleted.');
        } catch (Exception $e) {
            Log::error("HolidayController@deleteHoliday - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }
}
