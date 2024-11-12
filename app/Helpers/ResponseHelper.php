<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

class ResponseHelper
{
    /**
     * @param $code
     * @param array $data
     * @param string $message
     * @param string $version
     *
     * @return JsonResponse|string
     * @todo: this really ought to only return a Response object, rather than sometimes a string and sometimes Response
     */
    public static function responseMessage($code, $data = [], $message = '')
    {
        $response = Response::json(['msg' => $message, 'data' => $data], $code);
        $response->header('Content-Type', 'text/json');
        return $response;
    }

    /**
     * @param array $errors
     * @param int $code
     *
     * @return JsonResponse|string
     */
    public static function errorResponse($errors = [], $code = 422)
    {
        $response = Response::json(['errors' => $errors], $code);
        $response->header('Content-Type', 'text/json');

        return $response;
    }

    public static function responseMessageWithoutData($code, $data = [])
    {
        $response = Response::json($data, $code);
        $response->header('Content-Type', 'text/json');
        return $response;
    }
}
