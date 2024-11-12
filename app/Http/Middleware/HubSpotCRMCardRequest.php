<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HubSpotCRMCardRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        $signature = $request->header('crmcard-signature');

        try {
            $data = JWT::decode($signature, new Key(env('APP_KEY'), 'HS256'));
            
            return $next($request);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            Log::warning("HubSpotCRMCardRequest@handle - Invalid signature provided", ["req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards']]);
            return response()->json(['message'=>'Invalid request'], 401);
        } catch (\Firebase\JWT\ExpiredException $e) {
            Log::warning("HubSpotCRMCardRequest@handle - Expired verification token provided", ["req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards']]);
            return response()->json(['message'=>'Invalid request'], 401);
        } catch (\UnexpectedValueException $e) {
            Log::warning("HubSpotCRMCardRequest@handle - ".$e->getMessage(), ["req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards']]);
            return response()->json(['message'=>'Invalid request'], 401);
        } catch (Exception $e) {
            Log::error("HubSpotCRMCardRequest@handle - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards'],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }
}
