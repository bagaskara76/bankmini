<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerApiAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'customer') {
            return response()->json([
                'message' => 'Akses hanya tersedia untuk nasabah.',
            ], 403);
        }

        if (! Customer::where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Data nasabah tidak ditemukan.',
            ], 404);
        }

        return $next($request);
    }
}
