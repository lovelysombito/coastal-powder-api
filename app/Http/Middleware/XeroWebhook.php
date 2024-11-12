<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XeroWebhook
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
        $computedSignatureKey = base64_encode(
            hash_hmac('sha256', $request->getContent(), env('XERO_WEBHOOK_KEY'), true)
        );
        $xeroSignatureKey = $request->header('x-xero-signature');
        if (!hash_equals($computedSignatureKey, $xeroSignatureKey)) {
            Log::warning('WebhookController@handleXeroWebhook - x-xero-signature mismatch ',  ["req"=>['ip' => $request->ip(), 'user'=>'xero_webhooks']]);
            return response('',401);
        }

        return $next($request);
    }
}
