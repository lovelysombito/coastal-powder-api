<?php

namespace App\Http\Controllers;

use App\Events\JobEvent;
use Illuminate\Http\Request;
use App\Models\Treatments;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\TreatmentRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Material;
use App\Models\MaterialTreatment;
use Exception;

class TreatmentController extends Controller
{
    protected $model;
    protected $materialModel;
    protected $materialTreatmentModel;

    protected $jobTable='job_scheduling';
    public function __construct(){
        $this->model = new Treatments();
        $this->materialModel = new Material();
        $this->materialTreatmentModel = new MaterialTreatment();
    }
    public function getAllTreatment(Request $request) {
        Log::info("TreatmentController@getAllTreatment", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {
            if($request->material && $request->material == 'steel'){
                $treatmentList = $this->model::with('materials')->whereHas('materials', function($q) {
                    $q->where('material', 'steel');
                })->orderBy('treatment')->paginate(config('constant.pagination.treatment'));
            } else if($request->material && $request->material == 'aluminium'){
                $treatmentList = $this->model::with('materials')->whereHas('materials', function($q) {
                    $q->where('material', 'aluminium');
                })->orderBy('treatment')->paginate(config('constant.pagination.treatment'));
            } else {
                $treatmentList = $this->model::with('materials')->orderBy('treatment')->paginate(config('constant.pagination.treatment'));
            }

            if (count($treatmentList) == 0) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'Treatment List');
            }

            return ResponseHelper::responseMessageWithoutData(config('constant.status_code.success'), $treatmentList);
        } catch (Exception $e) {
            Log::error("TreatmentController@getAllTreatment - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }

    public function addTreatment(TreatmentRequest $request) {
        Log::info("TreatmentController@addTreatment", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {

            $materialId = $this->materialModel::where('material',$request->material)->first()->material_id;
            
            $treatment = $this->model::create([
                'treatment' => $request->treatment
            ]);

            $materialTreatment = $this->materialTreatmentModel::create([
                'treatment_id' => $treatment->treatment_id,
                'material_id' => $materialId
            ]);

            
            $treatmentNew = $this->model::with('materials')->where('treatment_id',$treatment->treatment_id)->orderBy('treatment')->paginate(config('constant.pagination.treatment'));
            event(new JobEvent('treatment'));

            return ResponseHelper::responseMessage(config('constant.status_code.success'), $treatmentNew, 'Treatment added.');
        
        } catch (Exception $e) {
            Log::error("TreatmentController@addTreatment - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }
    
    public function editTreatment(TreatmentRequest $request,$treatmentId) {
        Log::info("TreatmentController@editTreatment", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {
            $params = json_decode($request->getContent());
            $treatment = $this->model::find($treatmentId);
            if (!$treatment)
                return ResponseHelper::errorResponse('Cannot update treatment. Invalid treatment Id.', config('constant.status_code.bad_request'));

            if (isset($params->treatment)){
                $treatment->treatment = $params->treatment;
            }

            $materialId = $this->materialModel::where('material',$request->material)->first()->material_id;

            if($this->model::where('treatment_id',$treatmentId)->where('treatment',$params->treatment)->count() != 1 && $this->model::where('treatment',$params->treatment)->count() == 1){
                return ResponseHelper::errorResponse('Cannot update treatment. treatment already taken', config('constant.status_code.bad_request'));
            }    
            $treatment->save();

            if(!$this->materialTreatmentModel::where('treatment_id',$treatment->treatment_id)->where('material_id',$materialId)->first()){
                $materialTreatment = $this->materialTreatmentModel::create([
                    'treatment_id' => $treatment->treatment_id,
                    'material_id' => $materialId
                ]);
            }

            event(new JobEvent('treatment'));
            return ResponseHelper::responseMessage(config('constant.status_code.success'), '', 'Treatment updated.');
        } catch (Exception $e) {
            Log::error("TreatmentController@editTreatment - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }

    public function deleteTreatment(Request $request, $treatmentId)
    {
        Log::info("TreatmentController@deleteTreatment", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {

            $treatment = $this->model::find($treatmentId);
            if (!$treatment)
                return ResponseHelper::errorResponse('Treatment delete invalid.', config('constant.status_code.bad_request'));

            $treatment->delete();
            event(new JobEvent('treatment'));
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $treatment, 'Treatment deleted.');
        } catch (Exception $e) {
            Log::error("TreatmentController@deleteTreatment - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }
}
