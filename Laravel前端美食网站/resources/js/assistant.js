/**
 * 美食助手：聊天界面
 * 纯 fetch + DOM，不依赖框架；回答内容以 textContent 渲染，避免注入。
 */
const root = document.querySelector('[data-assistant]');

if (root) {
    const log = root.querySelector('[data-chat-log]');
    const form = root.querySelector('[data-chat-form]');
    const input = root.querySelector('[data-chat-input]');
    const sendBtn = root.querySelector('[data-chat-send]');
    const clearBtn = root.querySelector('[data-chat-clear]');
    const starters = root.querySelector('[data-chat-starters]');
    const welcomeTpl = document.querySelector('[data-welcome-tpl]');
    const historyEl = document.querySelector('[data-chat-history]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const endpoint = root.dataset.endpoint;
    const clearEndpoint = root.dataset.clearEndpoint;

    let busy = false;
    let history = [];

    try {
        history = JSON.parse(historyEl?.textContent || '[]');
    } catch (error) {
        history = [];
    }

    const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

    function scrollToEnd(smooth = true) {
        log.scrollTo({ top: log.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
    }

    function makeAvatar() {
        const avatar = document.createElement('div');
        avatar.className = 'chat-avatar';
        avatar.setAttribute('aria-hidden', 'true');
        avatar.textContent = '食';
        return avatar;
    }

    function renderCards(cards) {
        const list = document.createElement('div');
        list.className = 'chat-cards';

        cards.forEach((card) => {
            const link = document.createElement('a');
            link.className = 'chat-card-item';
            link.href = card.url;
            link.setAttribute('aria-label', card.title);

            if (card.image) {
                const img = document.createElement('img');
                img.src = card.image;
                img.alt = '';
                img.loading = 'lazy';
                link.appendChild(img);
            }

            const body = document.createElement('div');
            body.className = 'chat-card-item__body';

            const title = document.createElement('div');
            title.className = 'chat-card-item__title';
            title.textContent = card.title;
            body.appendChild(title);

            if (card.meta) {
                const meta = document.createElement('div');
                meta.className = 'chat-card-item__meta';
                meta.textContent = card.meta;
                body.appendChild(meta);
            }

            link.appendChild(body);

            if (card.tag) {
                const tag = document.createElement('span');
                tag.className = 'chat-card-item__tag';
                tag.textContent = card.tag;
                link.appendChild(tag);
            }

            const arrow = document.createElement('span');
            arrow.className = 'chat-card-item__arrow';
            arrow.setAttribute('aria-hidden', 'true');
            arrow.textContent = '›';
            link.appendChild(arrow);

            list.appendChild(link);
        });

        return list;
    }

    function renderQuick(items, onPick) {
        const bar = document.createElement('div');
        bar.className = 'chat-quick';

        items.forEach((text) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'chat-chip';
            btn.textContent = text;
            btn.addEventListener('click', () => onPick(text));
            bar.appendChild(btn);
        });

        return bar;
    }

    function append(role, content, cards = [], quick = [], options = {}) {
        const row = document.createElement('div');
        row.className = `chat-row chat-row--${role}`;

        if (role === 'assistant') {
            row.appendChild(makeAvatar());
        }

        const wrap = document.createElement('div');
        wrap.className = 'chat-bubble-wrap';

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        // 回答里有换行与列表符号，交给 CSS 的 pre-line 处理
        bubble.textContent = content;
        wrap.appendChild(bubble);

        if (cards && cards.length) {
            wrap.appendChild(renderCards(cards));
        }

        if (quick && quick.length) {
            wrap.appendChild(renderQuick(quick, (text) => send(text)));
        }

        if (options.note) {
            const note = document.createElement('div');
            note.className = 'chat-note';
            note.textContent = options.note;
            wrap.appendChild(note);
        }

        row.appendChild(wrap);
        log.appendChild(row);

        if (options.scroll !== false) {
            scrollToEnd();
        }

        return row;
    }

    const THINKING = ['正在翻菜谱…', '正在看哪家店合适…', '正在核对食材价格…', '快好了…'];

    function showTyping() {
        const row = document.createElement('div');
        row.className = 'chat-row chat-row--assistant chat-row--typing';
        row.appendChild(makeAvatar());

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble chat-bubble--typing';

        const dots = document.createElement('span');
        dots.className = 'chat-typing__dots';
        dots.innerHTML = '<i></i><i></i><i></i>';

        const text = document.createElement('span');
        text.className = 'chat-typing__text';
        text.textContent = THINKING[0];

        bubble.appendChild(dots);
        bubble.appendChild(text);

        row.appendChild(bubble);
        log.appendChild(row);
        scrollToEnd();

        // 模型思考通常几秒，轮换文案免得像卡住
        let index = 0;
        row._timer = window.setInterval(() => {
            index = (index + 1) % THINKING.length;
            text.textContent = THINKING[index];
        }, 2600);

        return row;
    }

    function stopTyping(row) {
        window.clearInterval(row._timer);
        row.remove();
    }

    function mountWelcome() {
        if (!welcomeTpl) return;
        log.appendChild(welcomeTpl.content.cloneNode(true));
    }

    function setBusy(state) {
        busy = state;
        sendBtn.disabled = state;
        input.disabled = state;
    }

    function autosize() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 132) + 'px';
    }

    async function send(rawText) {
        const text = String(rawText || '').trim();
        if (!text || busy) return;

        // 第一条用户消息出现时收起开场白
        log.querySelector('.chat-welcome')?.remove();

        setBusy(true);
        append('user', text);
        input.value = '';
        autosize();

        const typing = showTyping();
        const aiEnabled = root.dataset.ai === '1';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ message: text }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = data?.errors?.message?.[0] || '这句话没发出去，再试一次？';
                stopTyping(typing);
                append('assistant', message, [], []);
                return;
            }

            stopTyping(typing);
            // 模型通常要几秒，留一点时间让文字出现得不突兀
            await sleep(aiEnabled ? 120 : 240);

            const note = aiEnabled && data.reply.source === 'local'
                ? '这条是本地知识库答的：DeepSeek 刚才没连上。'
                : '';

            append('assistant', data.reply.content, data.reply.cards, data.reply.quick, { note });
        } catch (error) {
            stopTyping(typing);
            append('assistant', '网络好像断了，检查一下服务是否还在运行，然后重新发一次。', [], []);
        } finally {
            setBusy(false);
            input.focus();
        }
    }

    function boot() {
        if (!history.length) {
            mountWelcome();
            return;
        }

        log.classList.add('chat-log--booting');
        history.forEach((item) => {
            append(item.role, item.content, item.cards, item.quick, { scroll: false });
        });
        log.classList.remove('chat-log--booting');
        scrollToEnd(false);
    }

    // 快捷提问
    starters?.querySelectorAll('[data-chat-chip]').forEach((chip) => {
        chip.addEventListener('click', () => send(chip.dataset.chatChip));
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        send(input.value);
    });

    input.addEventListener('input', autosize);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            send(input.value);
        }
    });

    clearBtn?.addEventListener('click', async () => {
        if (busy || !window.confirm('清空所有聊天记录？')) return;

        try {
            await fetch(clearEndpoint, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
        } catch (error) {
            /* 清空失败也把界面恢复成开场白，刷新后仍会看到旧记录 */
        }

        history = [];
        log.innerHTML = '';
        mountWelcome();
        input.focus();
    });

    boot();
}
