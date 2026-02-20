<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll';
    protected $description = 'Start Telegram bot long-polling (for local development)';

    public function handle(TelegramBotService $bot): int
    {
        $this->info('Telegram Bot polling started... (Ctrl+C to stop)');
        $this->info('Bot is ready to receive messages!');

        $offset = 0;

        while (true) {
            try {
                $response = Http::timeout(35)->get(
                    "https://api.telegram.org/bot" . config('telegram.bot_token') . "/getUpdates",
                    ['offset' => $offset, 'timeout' => 30]
                )->json();

                if (!empty($response['result'])) {
                    foreach ($response['result'] as $update) {
                        $from = $update['message']['from']['first_name']
                            ?? $update['callback_query']['from']['first_name']
                            ?? 'Unknown';
                        $text = $update['message']['text']
                            ?? $update['callback_query']['data']
                            ?? '(no text)';

                        $this->line("<fg=cyan>[" . now()->format('H:i:s') . "]</> <fg=yellow>{$from}</>: {$text}");

                        $bot->handleUpdate($update);
                        $offset = $update['update_id'] + 1;
                    }
                }
            } catch (\Exception $e) {
                $this->error("Error: {$e->getMessage()}");
                sleep(5);
            }
        }

        return Command::SUCCESS;
    }
}
