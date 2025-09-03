<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetLinksMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('.well-known/assetlinks.json')) {
            return response()->json($this->getAssetLinks())
                ->header('Content-Type', 'application/json')
                ->header('Access-Control-Allow-Origin', '*');
        }

        return $next($request);
    }

    private function getAssetLinks()
    {
        return [
            [
                'relation' => ['delegate_permission/common.handle_all_urls'],
                'target' => [
                    'namespace' => 'android_app',
                    'package_name' => config('app.android_package_name'),
                    'sha256_cert_fingerprints' => config('app.android_cert_fingerprints')
                ]
            ]
        ];
    }
}
