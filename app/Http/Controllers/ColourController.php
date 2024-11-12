<?php

namespace App\Http\Controllers;

use App\Events\JobEvent;
use App\Models\Colours;
use Exception;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Log;

class ColourController extends Controller
{
    public function getAllColours(Request $request) {
        Log::info("ColourController@getAllColours", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        
        try {
            $colours = Colours::all();
            if (count($colours) == 0) {
                return response()->json([
                    'status' => 'Bad Request',
                    'code' => 200,
                    'message' => []
                ], 200);
            }

            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'message' => $colours
            ]);
        } catch (Exception $e) {
            Log::error("ColourController@generateQrCodeLabels - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.not_found'));
        }
    }

    public function addColour(Request $request) {
        Log::info("ColourController@addColour", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        
        try {
            $request->validate([
                'name' => 'string|required',
                'low_weight' => 'numeric',
                'weight' => 'numeric',
            ]);


            $name = $request->name;
            $lowWeight = $request->low_weight ?? 0;
            $weight = $request->weight ?? 0;
            
            $colour = Colours::where('name', $name)->first();
            if ($colour) {
                return ResponseHelper::errorResponse('Same Colour already exist.', config('constant.status_code.not_found'));
            }

            $newColour = new Colours([
                'name' => $name,
                'low_weight' => $lowWeight,
                'weight' => $weight,
            ]);
    
            $newColour->save();
            event(new JobEvent('colour'));
            return response()->json([
                'message' => 'Colour has successfully been added.'
            ]);
        } catch (Exception $e) {
            Log::error("ColourController@addColour - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
         
            return response()->json([
                'status' => 'Bad Request',
                'code' => config('constant.status_code.not_found'),
                'message' => 'Colour add invalid.',
            ], config('constant.status_code.not_found'));
        }
    }

    public function editColour(Request $request, $colourId) {
        Log::info("ColourController@editColour", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        
        try {
            if (!isset($request->name) || !isset($request->low_weight) || !isset($request->weight))
                throw new Exception('Some fields are missing');

            $name = $request->name;
            $low_weight = $request->low_weight;
            $weight = $request->weight;

            $colour = Colours::find($colourId);
            if (!$colour) 
                throw new Exception('Colour ID invalid. Not found!');

            $colour->name = $name;
            $colour->low_weight = $low_weight;
            $colour->weight = $weight;
            $colour->save();

            event(new JobEvent('colour'));
            return response()->json([
                'message' => 'Colour has successfully been edited.'
            ], 200);
        } catch (Exception $e) {
            Log::error("ColourController@editColour - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
         
            return response()->json([
                'status' => 'Bad Request',
                'code' => config('constant.status_code.not_found'),
                'message' => 'Colour edit invalid.',
            ], config('constant.status_code.not_found'));
        
        }
    }

    public function deleteColour(Request $request, $colourId) {
        Log::info("ColourController@deleteColour", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {
            
            $colour = Colours::find($colourId);
            if (!$colour) 
                throw new Exception('Colour delete invalid.');

            $colour->delete();
            
            event(new JobEvent('colour'));
            return response()->json([
                'message' => 'Colour has successfully been deleted.'
            ], 200);
                
        } catch (Exception $e) {
            Log::error("ColourController@deleteColour - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
         
            return response()->json([
                'status' => 'Bad Request',
                'code' => config('constant.status_code.not_found'),
                'message' => 'Colour delete invalid.',
            ], config('constant.status_code.not_found'));
        
        }
    }

    public function changeWeight(Request $request, $colourId) {
        Log::info("ColourController@changeWeight", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            if (!isset($request->change_weight) || !isset($colourId))
                throw new Exception('Some fields are missing');

            $change_weight = $request->change_weight;
            $colour = Colours::find($colourId);
            
            if (!$colour) 
                throw new Exception('Colour edit invalid.');
            
            $newWeight = $colour->weight - $change_weight;
            if($newWeight < 0){
                $colour->weight = 0;
            }else{
                $colour->weight = $newWeight;
            }
            $colour->save();

            return response()->json([
                'message' => 'Colour weight has successfully been edited.'
            ], 200);
        } catch (Exception $e) {
            Log::error("ColourController@deleteColour - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
         
            return response()->json([
                'status' => 'Bad Request',
                'code' => config('constant.status_code.not_found'),
                'message' => 'Powder weight edit invalid.',
            ], config('constant.status_code.not_found'));
        
        }
    }
}
