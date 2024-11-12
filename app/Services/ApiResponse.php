<?php

namespace App\Services;

class ApiResponse
{
    public function responseJson($data, $message, $status)
    {
        if (is_null($data)) {
            return response()->json(
                [
                    'message' => $message,
                    'status' => $status
                ]
            );
        }
        
        return response()->json(
            [
                'data' => $data,
                'message' => $message,
                'status' => $status
            ]
        );
    }
}
