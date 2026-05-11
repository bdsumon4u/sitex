<?php

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/get-service-id/{domain}', function (string $domain) {
    $site = Site::query()
        ->with(['organization' => function ($query) {
            $query->select('id', 'service_id');
        }])
        ->where('domain', $domain)
        ->first(['id', 'service_id', 'organization_id']);

    if ($site) {
        return response()->json([
            'service_id' => $site->service_id ?? $site->organization?->service_id,
        ]);
    }

    return response()->json([
        'message' => 'Site not found',
    ], 404);
});

Route::middleware('throttle:20,5')->get('sites', function (Request $request) {
    return Site::query()
        ->when($request->has('status'), fn ($query) => $query->where('status', $request->input('status')))
        ->get(['id', 'domain', 'status']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
