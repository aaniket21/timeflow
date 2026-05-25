<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\TimeSession;
use App\Helpers\TimeHelper;
use App\Notifications\StreakRiskNotification;
use Carbon\Carbon;

class SendStreakRiskNotifications extends Command
{
    protected $signature = 'timeflow:streak-risk';
    protected $description = 'Send push notifications to users at risk of losing their streak (runs hourly)';

    public function handle()
    {
        // Get users who have notifications enabled
        $users = User::where('notifications_enabled', true)
            ->whereNotNull('timezone')
            ->where('streak_current', '>', 0)
            ->get();

        foreach ($users as $user) {
            $localTime = Carbon::now($user->timezone);
            
            // Only notify at 20:xx (8 PM) local time
            if ($localTime->hour === 20) {
                // Check if they have logged a session today (using UTC bounds for their local today)
                $bounds = TimeHelper::todayBoundsUtc($user);
                
                $hasSession = TimeSession::where('user_id', $user->id)
                    ->whereBetween('started_at', [$bounds['start'], $bounds['end']])
                    ->exists();

                if (!$hasSession) {
                    $user->notify(new StreakRiskNotification());
                }
            }
        }

        $this->info('Streak risk notifications processed.');
    }
}
