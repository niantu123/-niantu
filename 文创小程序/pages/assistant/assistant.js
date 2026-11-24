// pages/assistant/assistant.js - 文创产品助手
const db = require('../../utils/db');
const config = require('../../utils/config');
const { answer, askRemote, GREETING } = require('../../utils/assistant');

const QUICK_QUESTIONS = ['有什么推荐？', '有哪些新品？', '发布什么作品好呢？', '怎么发布作品？', '发布作品有什么建议？'];
let msgSeq = 0;

Page({
  data: {
    messages: [],
    quick: QUICK_QUESTIONS,
    input: '',
    sending: false,
    online: !!((config.assistant && config.assistant.enabled && config.assistant.apiKey)),
    remoteConfigured: !!((config.assistant && config.assistant.apiKey)),
    scrollInto: ''
  },

  async onLoad() {
    try {
      this.products = await db.getProducts({ status: 'approved' });
    } catch (e) {
      this.products = [];
    }
    this.pushBot(GREETING, []);
  },

  pushUser(text) {
    const msg = { id: `m${++msgSeq}`, role: 'user', type: 'text', text };
    this.setData({ messages: this.data.messages.concat(msg) });
    this.scrollToBottom();
  },

  pushBot(text, products) {
    const msg = {
      id: `m${++msgSeq}`,
      role: 'bot',
      type: products && products.length ? 'products' : 'text',
      text,
      products: products || []
    };
    this.setData({ messages: this.data.messages.concat(msg) });
    this.scrollToBottom();
  },

  scrollToBottom() {
    const last = this.data.messages[this.data.messages.length - 1];
    if (last) this.setData({ scrollInto: last.id });
  },

  onInput(e) {
    this.setData({ input: e.detail.value });
  },

  // 联网搜索开关
  toggleOnline() {
    if (!this.data.remoteConfigured) {
      wx.showModal({
        title: '联网搜索未配置',
        content: '请在 utils/config.js 的 assistant.apiKey 中填入大模型 API Key（支持智谱 / DeepSeek 等 OpenAI 兼容接口）',
        showCancel: false
      });
      return;
    }
    this.setData({ online: !this.data.online });
    wx.showToast({ title: this.data.online ? '已开启联网搜索' : '已关闭联网搜索', icon: 'none' });
  },

  async ask(text) {
    const local = answer(text, this.products);
    // 本地命中或未开启联网：直接用本地答案
    if (!local.fallback || !this.data.online) return local;
    // 本地兜底 + 联网开启：请求大模型
    try {
      const text2 = await askRemote(text, this.products);
      return { text: `🌐 ${text2}`, products: [] };
    } catch (e) {
      return { text: `${local.text}\n\n（联网搜索失败：${e.message}）`, products: [] };
    }
  },

  async onSend() {
    const text = this.data.input.trim();
    if (!text || this.data.sending) return;
    this.setData({ input: '', sending: true });
    this.pushUser(text);
    const res = await this.ask(text);
    this.pushBot(res.text, res.products);
    this.setData({ sending: false });
  },

  async onQuickTap(e) {
    const text = e.currentTarget.dataset.q;
    if (!text || this.data.sending) return;
    this.setData({ sending: true });
    this.pushUser(text);
    const res = await this.ask(text);
    this.pushBot(res.text, res.products);
    this.setData({ sending: false });
  },

  goDetail(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: `/pages/detail/detail?id=${id}` });
  }
});
