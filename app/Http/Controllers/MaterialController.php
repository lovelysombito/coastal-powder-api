<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Material;
use App\Models\MaterialTreatment;
use App\Models\Treatments;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MaterialController extends Controller
{
    protected $model;
    protected $materialModel;
    protected $materialTreatmentModel;
    public function __construct(){
        $this->model = new Treatments();
        $this->materialModel = new Material();
        $this->materialTreatmentModel = new MaterialTreatment();
    }
    public function getAllMaterial(Request $request) {
        Log::info("MaterialController@getAllMaterial", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {
            $materialList = $this->materialModel->all();
            if (count($materialList) == 0) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'Material List');
            }

            return ResponseHelper::responseMessageWithoutData(config('constant.status_code.success'), $materialList);
        } catch (Exception $e) {
            Log::error("MaterialController@getAllMaterial - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }
}
