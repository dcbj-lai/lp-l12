<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictFacilitiesViewer
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->hasRole('facility.viewer')) {
            if ($request->is('/') || $request->routeIs('dashboard')) {
                return redirect()->route('resources.reservations.index');
            }

            // No Livewire updates or API calls: viewers use a server-rendered read-only page.
            abort_unless(
                ($request->isMethod('GET') && $request->routeIs('resources.reservations.index', 'resources.reservations.floor-plan'))
                || ($request->isMethod('POST') && $request->routeIs('logout')),
                403,
                'This account can only view facilities reservations for approval or approved.'
            );
        }

        return $next($request);
    }
}
