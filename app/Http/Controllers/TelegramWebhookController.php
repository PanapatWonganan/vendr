<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramBotService $bot, string $token): JsonResponse
    {
        // Validate webhook token against configured bot token
        $expectedToken = config('telegram.bot_token');
        if (!$expectedToken || !hash_equals($expectedToken, $token)) {
            Log::warning('Telegram webhook: invalid token attempt', [
                'ip' => $request->ip(),
            ]);
            abort(404);
        }

        $update = $request->all();

        Log::info('Telegram webhook received', ['update_id' => $update['update_id'] ?? null]);

        try {
            $bot->handleUpdate($update);
        } catch (\Exception $e) {
            Log::error("Telegram webhook error: {$e->getMessage()}");
        }

        return response()->json(['ok' => true]);
    }
}
