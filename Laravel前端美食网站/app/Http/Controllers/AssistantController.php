<?php

namespace App\Http\Controllers;

use App\Models\AssistantMessage;
use App\Models\Recipe;
use App\Models\Restaurant;
use App\Models\Supplier;
use App\Support\DeepSeekClient;
use App\Support\FoodAssistant;
use App\Support\KitchenKnowledge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class AssistantController extends Controller
{
    /** 一次展示的历史条数 */
    private const HISTORY_LIMIT = 60;

    /**
     * 聊天页：登录用户看自己的记录，游客看本次会话的记录
     */
    public function index(Request $request, DeepSeekClient $ai): View
    {
        $messages = $this->historyQuery($request)
            ->orderBy('id')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->map(fn (AssistantMessage $m) => [
                'role' => $m->role,
                'content' => $m->content,
                'cards' => $m->cards ?? [],
                'quick' => $m->quick ?? [],
            ])
            ->values();

        // 开了大模型就能答站外的菜和外地餐厅，快捷提问也换一批示例
        $starters = $ai->enabled()
            ? ['宫保鸡丁怎么做', '静安区有什么好吃的', '五花肉在哪买', '成都有什么好吃的川菜']
            : ['红烧肉怎么做', '静安区有什么好吃的', '五花肉在哪买', '哪家店能吃到麻婆豆腐'];

        return view('assistant.index', [
            'messages' => $messages,
            'starters' => $starters,
            'signedIn' => (bool) $request->user(),
            'aiEnabled' => $ai->enabled(),
            'aiModel' => $ai->model(),
            'stats' => [
                'recipes' => Recipe::count(),
                'restaurants' => Restaurant::count(),
                'suppliers' => Supplier::count(),
                'ingredients' => DB::table('recipe_ingredients')->distinct()->count('name'),
            ],
        ]);
    }

    /**
     * 发一句话，返回助手的回答（同时存入数据库）
     *
     * 配了 DeepSeek 就交给大模型写回答，没配或调用失败时退回本地知识库；
     * 不管谁写文字，卡片都来自本地实体识别，保证点进去是站内真实页面。
     */
    public function store(
        Request $request,
        FoodAssistant $assistant,
        DeepSeekClient $ai,
        KitchenKnowledge $knowledge
    ): JsonResponse {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:300'],
        ], [
            'message.required' => '说点什么再发送吧。',
            'message.max' => '一次最多 300 个字。',
        ]);

        $text = trim($data['message']);
        $context = $request->session()->get('assistant.context', []);

        // 本地引擎先跑一遍：拿卡片、快捷追问，也当兜底答案
        $reply = $assistant->reply($text, is_array($context) ? $context : []);

        $content = $reply['content'];
        $cards = $reply['cards'];
        $source = 'local';

        if ($ai->enabled()) {
            try {
                $content = $ai->chat($this->aiMessages($request, $knowledge, $text));
                $source = 'deepseek';

                // 站外问题由大模型用常识回答，站内卡片会误导；
                // 闲聊类也没必要挂卡片，其余情况按回答里点名的实体出卡片
                if (in_array($reply['intent'], ['greet', 'about', 'fix', 'thanks', 'empty', 'offsite'], true)) {
                    $cards = [];
                } else {
                    $mentioned = $assistant->mentionedCardsIn($content);

                    // 兜底回答若是随机的站内推荐，宁可不显示；模型点名了实体则照常出卡片
                    $cards = $mentioned ?: ($reply['intent'] === 'fallback'
                        ? []
                        : $this->orderCardsByReply($cards, $content));
                }
            } catch (Throwable $e) {
                Log::warning('DeepSeek 调用失败，已退回本地知识库', [
                    'message' => $e->getMessage(),
                    'question' => $text,
                ]);
            }
        }

        DB::transaction(function () use ($request, $text, $content, $cards, $reply, $source) {
            $base = [
                'user_id' => $request->user()?->id,
                'session_key' => $request->session()->getId(),
            ];

            AssistantMessage::create($base + [
                'role' => 'user',
                'content' => $text,
            ]);

            AssistantMessage::create($base + [
                'role' => 'assistant',
                'content' => $content,
                'cards' => $cards,
                'quick' => $reply['quick'],
                'intent' => $reply['intent'],
                'source' => $source,
            ]);
        });

        // 记住这轮聊到的菜／菜系，下一句省略说法也能接上
        $request->session()->put('assistant.context', $reply['context']);

        return response()->json([
            'reply' => [
                'content' => $content,
                'cards' => $cards,
                'quick' => $reply['quick'],
                'source' => $source,
            ],
        ]);
    }

    /**
     * 交给模型的消息：系统提示（含站内资料）+ 最近三轮对话 + 本次提问
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function aiMessages(Request $request, KitchenKnowledge $knowledge, string $text): array
    {
        $messages = [
            ['role' => 'system', 'content' => $knowledge->systemPrompt()],
        ];

        $history = $this->historyQuery($request)
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->reverse();

        foreach ($history as $row) {
            $messages[] = [
                'role' => $row->role === 'user' ? 'user' : 'assistant',
                'content' => $row->content,
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $text];

        return $messages;
    }

    /**
     * 按回答里提到的先后重排卡片
     *
     * 模型首推哪家，哪家的卡片就排第一，避免「说 A 好、卡片先显示 B」的错位。
     *
     * @param  array<int, array<string, mixed>>  $cards
     * @return array<int, array<string, mixed>>
     */
    private function orderCardsByReply(array $cards, string $content): array
    {
        if (count($cards) < 2) {
            return $cards;
        }

        $ranked = [];
        foreach ($cards as $index => $card) {
            $title = (string) ($card['title'] ?? '');
            $pos = $title === '' ? false : mb_strpos($content, $title);
            $ranked[] = [
                'card' => $card,
                'pos' => $pos === false ? PHP_INT_MAX : $pos,
                'index' => $index,
            ];
        }

        usort($ranked, fn (array $a, array $b) => [$a['pos'], $a['index']] <=> [$b['pos'], $b['index']]);

        return array_column($ranked, 'card');
    }

    /**
     * 清空对话
     */
    public function clear(Request $request): JsonResponse
    {
        $this->historyQuery($request)->delete();
        $request->session()->forget('assistant.context');

        return response()->json(['ok' => true]);
    }

    /**
     * 当前对话的消息查询：一个账号一条线，游客按会话走
     */
    private function historyQuery(Request $request)
    {
        $query = AssistantMessage::query();

        if ($user = $request->user()) {
            return $query->where('user_id', $user->id);
        }

        return $query->whereNull('user_id')->where('session_key', $request->session()->getId());
    }
}
