// pages/favorites/favorites.js - 我的收藏
const db = require('../../utils/db');

Page({
  data: {
    products: [],
    loading: true
  },

  onShow() {
    this.loadFavorites();
  },

  async loadFavorites() {
    const user = db.getCurrentUser();
    if (!user) {
      this.setData({ products: [], loading: false });
      return;
    }
    try {
      const products = await db.getFavoriteProducts(user._id);
      this.setData({ products, loading: false });
    } catch (e) {
      console.warn('收藏加载失败', e);
      this.setData({ loading: false });
    }
  },

  onProductTap(e) {
    wx.navigateTo({ url: `/pages/detail/detail?id=${e.currentTarget.dataset.id}` });
  },

  async onRemove(e) {
    const id = e.currentTarget.dataset.id;
    const user = db.getCurrentUser();
    if (!user) return;
    wx.showModal({
      title: '提示',
      content: '确定取消收藏这件作品吗？',
      success: async (res) => {
        if (!res.confirm) return;
        await db.toggleFavorite(id, user._id);
        this.loadFavorites();
        wx.showToast({ title: '已取消收藏', icon: 'none' });
      }
    });
  },

  goList() {
    wx.switchTab({ url: '/pages/list/list' });
  }
});
