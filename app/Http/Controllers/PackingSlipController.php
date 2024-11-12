<?php

namespace App\Http\Controllers;

use App\Helpers\DispatchHelper;
use App\Helpers\ResponseHelper;
use App\Http\Traits\ImageUploadTrait;
use App\Mail\SendArchiveEmail;
use App\Mail\SendPackingSlipMail;
use App\Models\PackingSlip;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use PDF;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PackingSlipController extends Controller
{
use ImageUploadTrait;

    public function getPackingSlip(Request $request, $id) {
        Log::info("PackingSlipController@getPackingSlip", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {
            $packingSlipDatas = PackingSlip::where('deal_id', $id)->get();

            if(!$packingSlipDatas) {
                return ResponseHelper::errorResponse('Packing Slip not found', config('constant.status_code.not_found'));
            }

            $packingSlipDatas->transform(function ($value) use ($request) {
                return DispatchHelper::setPackingData($value, $request->user());
            });

            if (!empty($packingSlipDatas)) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), $packingSlipDatas, 'Packing Slip List');
            } else {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'Packing Slip List');
            }
        } catch (Exception $e) {
            Log::error("PackingSlipController@getPackingSlip - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }

    public function downloadPackingSlip(Request $request) {
        Log::info("PackingSlipController@downloadPackingSlip", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        
        try {
            $data = PackingSlip::find($request->id);

            if (!$data) {
                throw new Exception('Invalid PDF file');
            }

            $dealId = $request->deal_id;

            $pdfData = [
                'api_url' => env('APP_URL', null),
                'status' => $data->packing_slip_data['status'],
                'line_items' => $data->packing_slip_data['line_items'],
                'signature' => $data->packing_slip_signature_file,
                'customer_name' => $data->packing_slip_customer_name ?? 'N/A',
                'customer_email' => $data->packing_slip_data['customer_email'] ?? 'N/A'
            ];
            
            $date = new DateTime();
            $pdf = PDF::loadView('pdfs/packing_slip', $pdfData);
            return $pdf->download(($date->format('Y_m_d_H_i_s') .'_'.$dealId . '_packing_slip.pdf').'.pdf');
        } catch (Exception $e) {
            return $e->getMessage();
            Log::error("PackingSlipController@downloadPackingSlip - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e); 
            return ResponseHelper::errorResponse(config('constant.error_message'), config('constant.status_code.bad_request'));
        }
    }

    public function emailPackingSlip(Request $request) {
        $user = $request->user();
        try {
            $data = PackingSlip::find($request->id);

            if (!$data) {
                throw new Exception('Invalid PDF file');
            }

            $pdfData = [
                'api_url' => env('APP_URL', null),
                'file' => $data->packing_slip_file,
                'status' => $data->packing_slip_data['status'],
                'deal_name' => $data->name,
                'line_items' => $data->packing_slip_data['line_items'],
                'signature' => $data->packing_slip_signature_file
            ];

            Mail::to($user->email)->send(new SendArchiveEmail($pdfData));

            return ResponseHelper::responseMessage(config('constant.status_code.success'), null, 'Packing slip email sent');

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
