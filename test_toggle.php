<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$habit = \App\Models\Goal::where('type', 'habit')->first();

if (!$habit) {
    echo "No habit found.\n";
    exit;
}

$user = \App\Models\User::find($habit->user_id);
$controller = app(\App\Http\Controllers\GoalController::class);

$request = \Illuminate\Http\Request::create('/api/habits/week?start=2026-05-18', 'GET');
$request->setUserResolver(function () use ($user) { return $user; });

$response = $controller->weekHabits($request);
echo json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT) . "\n";
