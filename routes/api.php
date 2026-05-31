<?php

use App\Models\Hosting;
use App\Models\Organization;
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

Route::get('site-list', function (Request $request) {
    return Site::query()
        ->when($request->has('status'), fn ($query) => $query->where('status', $request->input('status')))
        ->get(['id', 'domain', 'status']);
});

Route::get('hosting-list/{organization:ulid?}', function (Request $request, ?Organization $organization) {
    $hostings = Hosting::when($organization, fn ($query) => $query->whereBelongsTo($organization))
        ->select(['id', 'domain'])
        ->with(['sites' => fn ($query) => $query->select(['id', 'hosting_id', 'domain'])])
        ->withCount('sites')
        ->get()
        ->map(function (Hosting $hosting) {
            $data = $hosting->toArray();
            $data['sites'] = $hosting->sites->pluck('domain');

            return $data;
        });

    return response()->json($hostings, 200, [], JSON_PRETTY_PRINT);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
