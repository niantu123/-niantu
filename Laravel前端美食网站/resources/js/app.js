import './bootstrap';

import * as bootstrap from 'bootstrap';

import './assistant';

import './cart';

// 密码显示 / 隐藏
document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const input = document.querySelector(btn.dataset.togglePassword);
        if (!input) return;

        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        btn.textContent = showing ? '显示' : '隐藏';
    });
});

// 移动端导航点完自动收起
document.querySelectorAll('.site-nav .nav-link').forEach((link) => {
    link.addEventListener('click', () => {
        const collapse = document.querySelector('.site-nav .navbar-collapse.show');
        if (collapse) bootstrap.Collapse.getOrCreateInstance(collapse).hide();
    });
});

// 选好头像文件后先在本地预览，不必等保存
document.querySelectorAll('[data-avatar-input]').forEach((input) => {
    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        const holder = document.querySelector(input.dataset.avatarInput);
        if (!file || !holder) return;

        let img = holder.querySelector('img');
        if (!img) {
            holder.textContent = '';
            img = document.createElement('img');
            img.className = 'avatar-lg';
            img.alt = '头像预览';
            holder.appendChild(img);
        }

        img.src = URL.createObjectURL(file);
    });
});

// 相册缩略图：点击后同步高亮，轮播本体由 Bootstrap data 属性接管
document.querySelectorAll('.gallery-thumbs img').forEach((thumb) => {
    thumb.addEventListener('click', () => {
        thumb.closest('.gallery-thumbs')?.querySelectorAll('img').forEach((img) => img.classList.remove('active'));
        thumb.classList.add('active');
    });
});

// 简介输入框的实时字数
document.querySelectorAll('[data-count-for]').forEach((target) => {
    const input = document.querySelector(target.dataset.countFor);
    if (!input) return;

    const max = Number(input.getAttribute('maxlength')) || 0;
    const update = () => {
        target.textContent = max ? `${input.value.length} / ${max}` : `${input.value.length}`;
    };

    input.addEventListener('input', update);
    update();
});
