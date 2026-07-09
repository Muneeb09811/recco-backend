<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// ==================== DEBUG ROUTE ====================
Route::get('/debug', function () {
    return response()->json([
        'DB_HOST' => env('DB_HOST'),
        'DB_PORT' => env('DB_PORT'),
        'DB_DATABASE' => env('DB_DATABASE'),
        'DB_USERNAME' => env('DB_USERNAME'),
        'DB_PASSWORD' => env('DB_PASSWORD') ? '***set***' : '***not set***',
    ]);
});

// ==================== FIX DB ROUTE ====================
Route::get('/fix-db/{pass}', function ($pass) {
    if ($pass !== 'RECCO2024FIX') {
        return response('Unauthorized', 401);
    }
    
    try {
        // Check connection
        DB::connection()->getPdo();
        
        return response()->json([
            'success' => true,
            'message' => 'Database connected successfully!',
            'tables' => DB::select("SHOW TABLES"),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'db_host' => env('DB_HOST'),
            'db_port' => env('DB_PORT'),
        ], 500);
    }
});

// ==================== ARTISAN ROUTE ====================
Route::get('/artisan/{command}/{pass}', function ($command, $pass) {
    if ($pass !== 'RECCO2024FIX') {
        return response('Unauthorized', 401);
    }
    
    $allowed = ['migrate', 'db:seed', 'config:clear', 'cache:clear', 'install:api'];
    
    if (!in_array($command, $allowed)) {
        return response('Command not allowed', 403);
    }
    
    try {
        $exitCode = Artisan::call($command, [
            '--force' => true,
            '--class' => request()->get('class'),
        ]);
        
        return response()->json([
            'success' => $exitCode === 0,
            'output' => Artisan::output(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

// ==================== HOME ====================
Route::get('/', function () {
    return response()->json([
        'app' => 'Recco API',
        'status' => 'running',
    ]);
});