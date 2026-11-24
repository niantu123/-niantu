// pages/index/index.js - 首页
const app = getApp();
const db = require('../../utils/db');
const config = require('../../utils/config');

Page({
  data: {
    bannerList: [],
    noticeList: [],
    categoryList: config.categories,
    hotProducts: [],
    newProducts: [],
    keyword: '',
    loading: true
  },

  onLoad() {
    this.loadData();
  },

  onShow() {
    this.loadData();
  },

  async loadData() {
    try {
      const list = await db.getProducts({ status: 'approved' });
      const hotProducts = list.filter(item => (item.tags || []).indexOf('热门') > -1).slice(0, 6);
      const newProducts = list.filter(item => (item.tags || []).indexOf('新品') > -1).slice(0, 6);
      this.setData({
        bannerList: require('../../utils/data').bannerList,
        noticeList: require('../../utils/data').noticeList,
        hotProducts: hotProducts.length ? hotProducts : list.slice(0, 4),
        newProducts: newProducts.length ? newProducts : list.slice(0, 4),
        loading: false
      });
    } catch (e) {
      console.warn('首页数据加载失败', e);
      this.setData({ loading: false });
      wx.showToast({ title: '数据加载失败', icon: 'none' });
    }
  },

  onSearchInput(e) {
    this.setData({ keyword: e.detail.value });
  },

  onSearch() {
    const keyword = this.data.keyword.trim();
    if (!keyword) {
      wx.showToast({ title: '请输入作品名称', icon: 'none' });
      return;
    }
    app.globalData.listParam = { keyword };
    wx.switchTab({ url: '/pages/list/list' });
  },

  goAssistant() {
    wx.navigateTo({ url: '/pages/assistant/assistant' });
  },

  onCategoryTap(e) {
    const categoryId = e.currentTarget.dataset.id;
    app.globalData.listParam = { categoryId };
    wx.switchTab({ url: '/pages/list/list' });
  },

  onProductTap(e) {
    wx.navigateTo({ url: `/pages/detail/detail?id=${e.currentTarget.dataset.id}` });
  },

  onBannerTap(e) {
    app.globalData.listParam = { tag: '新品' };
    wx.switchTab({ url: '/pages/list/list' });
  },

  goList() {
    wx.switchTab({ url: '/pages/list/list' });
  },

  onPullDownRefresh() {
    this.loadData().then(() => wx.stopPullDownRefresh());
  }
});
