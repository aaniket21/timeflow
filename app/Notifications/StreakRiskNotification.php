<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class StreakRiskNotification extends Notification
{
    use Queueable;

    public function __construct()
    {
    }

    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('Streak at Risk! 🔥')
            ->icon('/icons/icon-192.svg')
            ->body('You haven\'t logged any time today. Do a quick session to keep your streak alive!')
            ->action('Start Timer', '/timer');
    }
}
