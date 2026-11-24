@extends('layouts.app')

@section('title', '美食助手 · 食光')

@section('content')
    <div class="assistant-page"
         data-assistant
         data-ai="{{ $aiEnabled ? '1' : '0' }}"
         data-endpoint="{{ route('assistant.store') }}"
         data-clear-endpoint="{{ route('assistant.clear') }}">
        <div class="container">
            <nav class="small text-muted mb-3">
                <a href="{{ route('home') }}" class="text-secondary">首页</a>
                <span class="mx-2">/</span>
                <span>美食助手</span>
            </nav>

            <header class="assistant-page__head">
                <div>
                    <h1>美食助手</h1>
                    <p>菜谱做法、去哪儿吃、食材在哪买 —— 站里收录的、站外想知道的，一句话问我就行。</p>
                </div>
                <div class="assistant-page__stat">
                    <span><b>{{ $stats['recipes'] }}</b> 道菜谱</span>
                    <span><b>{{ $stats['restaurants'] }}</b> 家餐厅</span>
                    <span><b>{{ $stats['suppliers'] }}</b> 家食材店</span>
                    <span><b>{{ $stats['ingredients'] }}</b> 种食材</span>
                </div>
            </header>

            <section class="chat-card">
                <div class="chat-card__head">
                    <div class="chat-identity">
                        <div class="chat-avatar" aria-hidden="true">食</div>
                        <div>
                            <div class="chat-identity__name">食光小助手</div>
                            <div class="chat-identity__state">
                                <span class="chat-dot" aria-hidden="true"></span>
                                {{ $aiEnabled ? 'DeepSeek 在线' : '本地知识库' }}
                                ·
                                {{ $signedIn ? '对话会存进你的账号' : '游客模式，对话只保存在本次浏览' }}
                            </div>
                        </div>
                    </div>
                    <div class="chat-head__tools">
                        <span class="chat-mode {{ $aiEnabled ? 'chat-mode--ai' : '' }}">
                            {{ $aiEnabled ? $aiModel : '本地模式' }}
                        </span>
                        <button type="button" class="chat-clear" data-chat-clear>清空对话</button>
                    </div>
                </div>

                <div class="chat-log" data-chat-log></div>

                <div class="chat-starters" data-chat-starters>
                    <span>试着问：</span>
                    @foreach ($starters as $starter)
                        <button type="button" class="chat-chip" data-chat-chip="{{ $starter }}">{{ $starter }}</button>
                    @endforeach
                </div>

                <form class="chat-composer" data-chat-form autocomplete="off">
                    <textarea name="message" rows="1" maxlength="300" data-chat-input
                              placeholder="比如：番茄炒蛋要哪些食材 / 徐汇区人均 200 的店 / 鲈鱼哪买"></textarea>
                    <button type="submit" class="chat-send" data-chat-send aria-label="发送">
                        发送
                    </button>
                </form>

                <p class="chat-foot">
                    @if ($aiEnabled)
                        站内 {{ $stats['recipes'] }} 道菜谱、{{ $stats['restaurants'] }} 家餐厅、{{ $stats['suppliers'] }} 家食材店的价格和地址都照实回答；站外的菜和餐厅也能问，我会标出哪些不是站内收录的。
                    @else
                        回答由站内菜谱、餐厅、食材店数据检索生成，没收录的内容会直接说不知道。
                    @endif
                </p>
            </section>
        </div>
    </div>

    {{-- 开场白模板：首屏与「清空对话」后都用它 --}}
    <template data-welcome-tpl>
        <div class="chat-row chat-row--assistant chat-welcome">
            <div class="chat-avatar" aria-hidden="true">食</div>
            <div class="chat-bubble">
                <p class="mb-2">
                    @if ($aiEnabled)
                        你好，我是食光的美食助手，由 DeepSeek 驱动。站里的菜谱、餐厅和食材店我都读过，可以帮你：
                    @else
                        你好，我是食光的美食助手。可以帮你：
                    @endif
                </p>
                <ul class="chat-list">
                    <li><b>查做法</b>：站里收录的给完整用料和耗时，没收录的我按常见做法讲</li>
                    <li><b>找餐厅</b>：按菜系、区县、人均预算筛，也能查哪家店做某道菜</li>
                    <li><b>买食材</b>：哪家店有货、什么价，顺带说说怎么挑</li>
                    @if ($aiEnabled)
                        <li><b>问站外</b>：外地的餐厅、别的菜系，我给建议并说明哪些不在食光收录里</li>
                    @endif
                </ul>
                <p class="mb-0 mt-2 text-muted" style="font-size:.8125rem;">从下面挑一个问题，或者直接说说你现在想吃什么。</p>
            </div>
        </div>
    </template>

    <script type="application/json" data-chat-history>@json($messages)</script>
@endsection
