// 食材购物车：加购和改数量都走 fetch，页面不刷新、位置不跳动。
// 万一请求失败，退回普通表单提交，保证仍然能用。

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const jsonHeaders = {
    'X-CSRF-TOKEN': csrfToken,
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'application/json',
};

function money(value) {
    return '¥' + Number(value).toFixed(2);
}

/** 提交表单，返回解析后的 JSON */
function sendForm(form, extra = {}) {
    const body = new FormData(form);
    Object.entries(extra).forEach(([key, value]) => body.set(key, value));

    return fetch(form.action, {
        method: 'POST',
        headers: jsonHeaders,
        body,
        credentials: 'same-origin',
    }).then((response) => {
        if (!response.ok) {
            throw new Error('cart request failed: ' + response.status);
        }

        return response.json();
    });
}

/** 页面上所有购物车件数（头像下拉、用户中心侧栏、右下角浮标）一起更新 */
function syncCartCount(count) {
    document.querySelectorAll('[data-cart-badge]').forEach((el) => {
        el.textContent = count;
        el.hidden = count <= 0;
    });

    document.querySelectorAll('[data-cart-float]').forEach((el) => {
        el.hidden = count <= 0;
        const num = el.querySelector('[data-cart-float-num]');
        if (num) num.textContent = count;
    });
}

/** 购物车页的合计与「共 N 件」同步 */
function syncCartTotals(total, count) {
    document.querySelectorAll('[data-cart-total]').forEach((el) => {
        el.textContent = money(total);
    });

    document.querySelectorAll('[data-cart-summary]').forEach((el) => {
        el.textContent = `共 ${count} 件食材，合计 ${money(total)}。`;
    });

    document.querySelectorAll('[data-cart-modal-summary]').forEach((el) => {
        el.textContent = `共 ${count} 件 · ${money(total)}`;
    });
}

let toastTimer = null;

/** 轻提示：替代整页刷新后的绿色横幅 */
function cartToast(message) {
    let el = document.querySelector('.cart-toast');

    if (!el) {
        el = document.createElement('div');
        el.className = 'cart-toast';
        el.setAttribute('role', 'status');
        document.body.appendChild(el);
    }

    el.textContent = message;
    el.classList.add('is-on');

    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove('is-on'), 1800);
}

/** 加购按钮：食材店详情页的「加入购物车」和列表里的圆形「+」 */
document.querySelectorAll('form[data-cart-add]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const btn = form.querySelector('[data-cart-btn]');
        if (btn) btn.disabled = true;

        sendForm(form)
            .then((data) => {
                if (btn) {
                    if (btn.dataset.cartBtn === 'text') {
                        btn.textContent = data.quantity > 0 ? '再加一份' : '加入购物车';
                        btn.title = `购物车已有 ${data.quantity} 份`;
                    } else {
                        btn.textContent = data.quantity > 0 ? data.quantity : '+';
                        btn.classList.toggle('is-in', data.quantity > 0);
                        btn.title = `购物车已有 ${data.quantity} 份`;
                    }
                }

                // 主推卡片里的「购物车已有 N 份」
                const note = form.closest('.dish-card')?.querySelector('[data-cart-note]');
                if (note) {
                    note.hidden = false;
                    const num = note.querySelector('[data-cart-note-num]');
                    if (num) num.textContent = data.quantity;
                }

                syncCartCount(data.cart_count);
                cartToast(data.message || '已加入购物车');
            })
            .catch(() => form.submit())
            .finally(() => {
                if (btn) btn.disabled = false;
            });
    });
});

/** 购物车页的加减号：只改数字和小计 */
document.querySelectorAll('form[data-cart-qty]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const clicked = event.submitter;
        const extra = clicked && clicked.name ? { [clicked.name]: clicked.value } : {};

        sendForm(form, extra)
            .then((data) => {
                const row = form.closest('[data-cart-row]');

                if (row) {
                    const num = row.querySelector('[data-cart-qty-num]');
                    if (num) num.textContent = data.quantity;

                    const subtotal = row.querySelector('[data-cart-subtotal]');
                    if (subtotal) subtotal.textContent = money(data.subtotal);

                    const minus = row.querySelector('[data-cart-step="minus"]');
                    if (minus) {
                        minus.value = Math.max(1, data.quantity - 1);
                        minus.disabled = data.quantity <= 1;
                    }

                    const plus = row.querySelector('[data-cart-step="plus"]');
                    if (plus) {
                        plus.value = Math.min(99, data.quantity + 1);
                        plus.disabled = data.quantity >= 99;
                    }

                    const groupTotal = row.closest('.sg-card')?.querySelector('[data-cart-group-total]');
                    if (groupTotal) groupTotal.textContent = money(data.supplier_total);
                }

                syncCartCount(data.cart_count);
                syncCartTotals(data.total, data.cart_count);
            })
            .catch(() => form.submit());
    });
});
