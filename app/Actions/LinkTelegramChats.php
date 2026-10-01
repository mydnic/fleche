<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use NotificationChannels\Telegram\TelegramMessage;
use NotificationChannels\Telegram\TelegramUpdates;

/**
 * Reads the bot's pending messages and links every `/start <token>` to its
 * account. Telegram only hands messages to whoever asks (no public webhook
 * needed, so it works self-hosted), and we only ask while someone is waiting
 * on the Settings page.
 *
 * Every pending link is handled, not just the asker's: messages are consumed
 * once read. The lock keeps two waiting users from reading the same batch.
 */
class LinkTelegramChats
{
    public function handle(): void
    {
        if (blank(config('services.telegram.token'))) {
            return;
        }

        Cache::lock('telegram-updates', 15)->get(function (): void {
            // ponytail: lost offset (cache flushed) just re-reads Telegram's last
            // 24h of messages; already-used tokens get a harmless "expired" reply.
            $offset = (int) Cache::get('telegram-offset', 0);

            $updates = TelegramUpdates::create()
                ->options(['offset' => $offset, 'timeout' => 0, 'allowed_updates' => ['message']])
                ->get()['result'] ?? [];

            foreach ($updates as $update) {
                $offset = $update['update_id'] + 1;
                $text = (string) data_get($update, 'message.text', '');

                if (Str::startsWith($text, '/start ')) {
                    $this->link((string) data_get($update, 'message.chat.id'), trim(Str::after($text, '/start ')));
                }
            }

            Cache::forever('telegram-offset', $offset);
        });
    }

    private function link(string $chatId, string $token): void
    {
        $user = User::query()->where('telegram_link_token', $token)->first();

        if ($user === null) {
            $this->reply($chatId, 'This link expired. Open Settings in Fleche and click "Connect Telegram" again.');

            return;
        }

        $user->forceFill(['telegram_chat_id' => $chatId, 'telegram_link_token' => null, 'notify_telegram' => true])->save();
        $this->reply($chatId, "🏹 Connected! You'll get your todos here when your day starts, {$user->name}.");
    }

    private function reply(string $chatId, string $text): void
    {
        TelegramMessage::create()->to($chatId)->escapedLine($text)->send();
    }
}
