// pages/feedback/feedback.js - 意见反馈（用户提交意见/问题，管理员后台查看）
const db = require('../../utils/db');
const { formatTime } = require('../../utils/util');

Page({
  data: {
    type: '意见', // 意见 | 问题
    content: '',
    user: null,
    list: [],
    submitting: false,
    // 编辑反馈弹窗
    editVisible: false,
    editId: '',
    editContent: ''
  },

  onShow() {
    this.setData({ user: db.getCurrentUser() });
    this.loadList();
  },

  // 我的反馈列表（未登录则提示）
  async loadList() {
    const user = db.getCurrentUser();
    if (!user) {
      this.setData({ list: [] });
      return;
    }
    try {
      const list = await db.getFeedbacks({ userId: user._id });
      this.setData({
        list: list.map(item => ({
          ...item,
          timeText: formatTime(new Date(item.createdAt))
        }))
      });
    } catch (e) {
      console.warn('反馈加载失败', e);
    }
  },

  pickType(e) {
    this.setData({ type: e.currentTarget.dataset.type });
  },

  onContentInput(e) {
    this.setData({ content: e.detail.value });
  },

  async onSubmit() {
    const user = db.getCurrentUser();
    if (!user) {
      wx.showModal({
        title: '提示',
        content: '登录后才能提交反馈',
        confirmText: '去登录',
        success: (res) => {
          if (res.confirm) wx.navigateTo({ url: '/pages/login/login' });
        }
      });
      return;
    }
    const content = this.data.content.trim();
    if (content.length < 5) {
      wx.showToast({ title: '内容至少 5 个字', icon: 'none' });
      return;
    }
    if (this.data.submitting) return;
    this.setData({ submitting: true });
    try {
      await db.addFeedback({ type: this.data.type, content }, user);
      this.setData({ content: '', submitting: false });
      wx.showToast({ title: '反馈已提交', icon: 'success' });
      this.loadList();
    } catch (e) {
      this.setData({ submitting: false });
      wx.showToast({ title: e.message || '提交失败', icon: 'none' });
    }
  },

  goLogin() {
    wx.navigateTo({ url: '/pages/login/login' });
  },

  // ---------- 编辑 / 删除自己的反馈 ----------
  openEdit(e) {
    const id = e.currentTarget.dataset.id;
    const item = this.data.list.find(f => f._id === id);
    if (!item) return;
    this.setData({ editVisible: true, editId: id, editContent: item.content });
  },

  closeEdit() {
    this.setData({ editVisible: false, editId: '', editContent: '' });
  },

  onEditInput(e) {
    this.setData({ editContent: e.detail.value });
  },

  noop() {},

  async confirmEdit() {
    const content = this.data.editContent.trim();
    if (content.length < 5) {
      wx.showToast({ title: '内容至少 5 个字', icon: 'none' });
      return;
    }
    try {
      await db.editFeedback(this.data.editId, this.data.user._id, content);
      this.setData({ editVisible: false, editId: '', editContent: '' });
      wx.showToast({ title: '已更新', icon: 'success' });
      this.loadList();
    } catch (e) {
      wx.showToast({ title: e.message || '修改失败', icon: 'none' });
    }
  },

  onDeleteFeedback(e) {
    const id = e.currentTarget.dataset.id;
    wx.showModal({
      title: '删除反馈',
      content: '删除后不可恢复，确定删除吗？',
      success: async (res) => {
        if (!res.confirm) return;
        try {
          await db.deleteFeedback(id, db.getCurrentUser());
          wx.showToast({ title: '已删除', icon: 'success' });
          this.loadList();
        } catch (err) {
          wx.showToast({ title: err.message || '删除失败', icon: 'none' });
        }
      }
    });
  }
});
