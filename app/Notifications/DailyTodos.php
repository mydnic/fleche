<?php

namespace App\Notifications;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

/**
 * The list sent when the user's day starts, on every channel they turned on.
 */
class DailyTodos extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param Collection<int, Todo> $todos */
    public function __construct(public Collection $todos) {}

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return array_values(array_filter([
            $notifiable->notify_mail ? 'mail' : null,
            $notifiable->notify_telegram && $notifiable->telegram_chat_id ? TelegramChannel::class : null,
        ]));
    }

    public function toMail(User $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('🏹 Your todos for today')
            ->greeting("Morning {$notifiable->name}!");

        foreach ($this->todos as $todo) {
            $mail->line('• '.$todo->name);
        }

        return $mail->action('Open Fleche', url('/app'));
    }

    public function toTelegram(User $notifiable): TelegramMessage
    {
        $message = TelegramMessage::create()->line('🏹 *Today*');

        foreach ($this->todos as $todo) {
            $message->escapedLine('• '.$todo->name);
        }

        return $message;
    }
}
