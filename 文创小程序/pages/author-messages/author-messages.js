// pages/author-messages/author-messages.js - 收到的咨询（作者视角）
const db = require('../../utils/db');
const { formatTime } = require('../../utils/util');

Page({
  data: {
    list: [],
    loading: true,
    user: null,
    // 回复弹窗
    replyVisible: false,
    replyId: '',
    replyText: ''
  },

  onShow() {
    this.refresh();
  },

  async refresh() {
    const user = db.getCurrentUser();
    if (!user || (user.role !== 'author' && user.role !== 'admin')) {
      this.setData({ user: null, list: [], loading: false });
      return;
    }
    this.setData({ user, loading: true });
    try {
      // 自己的作品，用于兼容旧数据（无 authorId 字段）按 productId 匹配
      const myWorks = await db.getProducts({ authorId: user._id });
      const myIds = new Set(myWorks.map(w => w._id));

      const messages = user.role === 'admin'
        ? await db.getMessages({})
        : await db.getMessages({ authorId: user._id });

      const list = messages
        .filter(item => (item.kind || 'inquiry') === 'inquiry')
        .filter(item => {
          if (user.role === 'admin') return true;
          return item.authorId === user._id || myIds.has(item.productId);
        })
        .map(item => ({
          ...item,
          timeText: formatTime(new Date(item.createdAt)),
          repliedAtText: item.replyAt ? formatTime(new Date(item.replyAt)) : ''
        }));

      this.setData({ list, loading: false });
    } catch (e) {
      console.warn('咨询加载失败', e);
      this.setData({ loading: false });
    }
  },

  // ---------- 回复弹窗 ----------
  openReply(e) {
    const id = e.currentTarget.dataset.id;
    const item = this.data.list.find(m => m._id === id);
    this.setData({
      replyVisible: true,
      replyId: id,
      replyText: item && item.reply ? item.reply : ''
    });
  },

  closeReply() {
    this.setData({ replyVisible: false });
  },

  onReplyInput(e) {
    this.setData({ replyText: e.detail.value });
  },

  noop() {},

  async confirmReply() {
    const reply = this.data.replyText.trim();
    if (!reply) {
      wx.showToast({ title: '回复内容不能为空', icon: 'none' });
      return;
    }
    try {
      await db.replyInquiry(this.data.replyId, reply, this.data.user);
      this.setData({ replyVisible: false });
      wx.showToast({ title: '回复成功', icon: 'success' });
      this.refresh();
    } catch (err) {
      wx.showToast({ title: err.message || '回复失败', icon: 'none' });
    }
  },

  goStore() {
    wx.switchTab({ url: '/pages/store/store' });
  },

  requireLogin() {
    wx.showModal({
      title: '提示',
      content: '登录后才能查看收到的咨询',
      confirmText: '去登录',
      success: (res) => {
        if (res.confirm) wx.navigateTo({ url: '/pages/login/login' });
      }
    });
  }
});
