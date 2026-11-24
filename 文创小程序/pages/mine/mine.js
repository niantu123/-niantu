// pages/mine/mine.js - 个人中心
const db = require('../../utils/db');
const config = require('../../utils/config');

Page({
  data: {
    user: null,
    isAdmin: false,
    isAuthor: false,
    roleText: '',
    favoritesCount: 0,
    messageCount: 0,
    worksCount: 0
  },

  onShow() {
    this.refresh();
  },

  async refresh() {
    const user = db.getCurrentUser();
    this.setData({
      user,
      isAdmin: !!(user && user.role === 'admin'),
      isAuthor: !!(user && (user.role === 'author' || user.role === 'admin')),
      roleText: user ? this.roleLabel(user.role) : ''
    });
    if (!user) {
      this.setData({ favoritesCount: 0, messageCount: 0, worksCount: 0 });
      return;
    }

    try {
      const [favoriteIds, messages, works] = await Promise.all([
        db.getFavoriteIds(user._id),
        db.getMessages({ userId: user._id }),
        db.getProducts({ authorId: user._id })
      ]);
      this.setData({
        favoritesCount: favoriteIds.length,
        messageCount: messages.length,
        worksCount: works.length
      });
    } catch (e) {
      console.warn('个人中心数据加载失败', e);
    }
  },

  roleLabel(role) {
    if (role === 'admin') return '平台管理员';
    if (role === 'author') return '文创作者';
    return '校园文创体验官';
  },

  goLogin() {
    wx.navigateTo({ url: '/pages/login/login' });
  },

  // 点击头像更换头像图片
  onChangeAvatar() {
    if (!this.data.user) return this.goLogin();
    wx.chooseMedia({
      count: 1,
      mediaType: ['image'],
      sourceType: ['album', 'camera'],
      sizeType: ['compressed'],
      success: async (res) => {
        const tempFilePath = res.tempFiles[0].tempFilePath;
        wx.showLoading({ title: '更新中' });
        try {
          const user = await db.updateAvatar(tempFilePath);
          wx.hideLoading();
          this.setData({ user });
          wx.showToast({ title: '头像已更新', icon: 'success' });
        } catch (e) {
          wx.hideLoading();
          wx.showToast({ title: e.message || '头像更新失败', icon: 'none' });
        }
      }
    });
  },

  goFavorites() {
    if (!this.data.user) return this.goLogin();
    wx.navigateTo({ url: '/pages/favorites/favorites' });
  },

  goMessages() {
    // 留言页已移出 tabBar，改为普通页面跳转
    wx.navigateTo({ url: '/pages/message/message' });
  },

  goOrders() {
    if (!this.data.user) return this.goLogin();
    wx.navigateTo({ url: '/pages/orders/orders' });
  },

  goFeedback() {
    wx.navigateTo({ url: '/pages/feedback/feedback' });
  },

  goPublish() {
    if (!this.data.user) return this.goLogin();
    wx.navigateTo({ url: '/pages/publish/publish' });
  },

  goMyWorks() {
    if (!this.data.user) return this.goLogin();
    wx.navigateTo({ url: '/pages/my-works/my-works' });
  },

  goAccount() {
    if (!this.data.user) return this.goLogin();
    wx.navigateTo({ url: '/pages/account/account' });
  },

  goAdmin() {
    wx.navigateTo({ url: '/pages/admin/admin' });
  },

  // 作者：收到的订单（含取消原因）
  goAuthorOrders() {
    wx.navigateTo({ url: '/pages/author-orders/author-orders' });
  },

  // 作者：收到的咨询（可回复）
  goAuthorMessages() {
    wx.navigateTo({ url: '/pages/author-messages/author-messages' });
  },

  goAssistant() {
    wx.navigateTo({ url: '/pages/assistant/assistant' });
  },

  onAbout() {
    wx.showModal({
      title: '关于校园文创展示',
      content: `新安学院校园文创产品展示平台\n\n以「让校园记忆被看见」为理念，汇集校园文具、服饰周边、生活好物、数字文创、纪念收藏与手作艺术六大类作品，支持作者上传、平台审核与师生留言互动。\n\n数据模式：${db.useCloud() ? '云开发数据库' : '本地演示数据'}`,
      showCancel: false
    });
  },

  onLogout() {
    wx.showModal({
      title: '提示',
      content: '确定要退出登录吗？',
      success: (res) => {
        if (res.confirm) {
          db.clearCurrentUser();
          this.refresh();
          wx.showToast({ title: '已退出登录', icon: 'success' });
        }
      }
    });
  }
});
