<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            return response()->json(['status' => 'ok', 'db' => 'connected'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'db' => 'disconnected'], 503);
        }
    }
}
