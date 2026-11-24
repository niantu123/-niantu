<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * DeepSeek 对话接口（OpenAI 兼容格式）
 *
 * 只在 config('services.deepseek.key') 有值时才启用；
 * 任何失败都抛异常，由调用方决定是否退回本地知识库。
 */
class DeepSeekClient
{
    public function enabled(): bool
    {
        return filled(config('services.deepseek.key'));
    }

    public function model(): string
    {
        return (string) config('services.deepseek.model', 'deepseek-chat');
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, float $temperature = 0.6, int $maxTokens = 800): string
    {
        if (! $this->enabled()) {
            throw new RuntimeException('没有配置 DEEPSEEK_API_KEY');
        }

        $response = Http::withToken((string) config('services.deepseek.key'))
            ->asJson()
            ->acceptJson()
            ->timeout((int) config('services.deepseek.timeout', 60))
            ->post(rtrim((string) config('services.deepseek.base_url'), '/').'/chat/completions', [
                'model' => $this->model(),
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
                'stream' => false,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('DeepSeek 返回 '.$response->status().'：'.mb_substr($response->body(), 0, 200));
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('DeepSeek 返回了空内容');
        }

        return trim($content);
    }
}
