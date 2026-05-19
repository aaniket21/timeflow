<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(203);
if (!$user) {
    echo "No user 203\n";
    exit;
}
$s = App\Models\TimeSession::create(['user_id'=>203, 'started_at'=>now(), 'type'=>'timer']);
$req = \App\Http\Requests\StopSessionRequest::create('/api/sessions/'.$s->id.'/stop', 'POST', ['notes' => 'Testing notes']);
$req->setUserResolver(fn() => $user);

$controller = app(App\Http\Controllers\SessionController::class);
$response = $controller->stop($req, $s->id);

echo "Response status: " . $response->getStatusCode() . "\n";
$s->refresh();
echo "Notes in DB: " . $s->notes . "\n";
