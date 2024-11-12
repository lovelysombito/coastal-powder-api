<?php

namespace App\Http\Middleware;

use App\Models\Integration;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HubSpotCRMCard
{
    public function handle(Request $request, Closure $next)
    {

        if (!$request->portalId) {
            Log::warning("HubspotCRMCardkMiddleware: No portal id provided", ["req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards']]);
            return response()->json(['message' => 'Invalid request'], 400);
        }

        if (!$request->associatedObjectId) {
            Log::warning("HubspotCRMCardkMiddleware: No object id provided", ["req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards']]);
            return response()->json(['message' => 'Invalid request'], 400);
        }

        if (!Integration::where(['platform' => 'hubspot', 'platform_account_id' => $request->portalId, 'integration_status' => 'Connected'])->exists()) {
            Log::warning("HubspotCRMCardkMiddleware: No hubspot integration found for portal id", ["req"=>['ip' => $request->ip(), 'user'=>'hubspot_crm_cards']]);
            return response()->json(['message' => 'Invalid request'], 400);
        }

        return $next($request);
    }
}
