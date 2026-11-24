// pages/list/list.js - 产品列表页
const app = getApp();
const db = require('../../utils/db');
const config = require('../../utils/config');

Page({
  data: {
    categoryList: [{ id: 0, name: '全部', icon: '🏫' }].concat(config.categories),
    currentCategory: 0,
    currentTag: '',
    keyword: '',
    products: [],
    loading: true
  },

  onLoad(options) {
    if (options.categoryId) this.setData({ currentCategory: Number(options.categoryId) });
    if (options.keyword) this.setData({ keyword: options.keyword });
    if (options.tag) this.setData({ currentTag: options.tag });
    this.loadProducts();
  },

  onShow() {
    // 首页通过全局变量传递筛选条件（tabBar 页面不支持带参数跳转）
    const param = app.globalData.listParam;
    if (param) {
      app.globalData.listParam = null;
      this.setData({
        currentCategory: param.categoryId ? Number(param.categoryId) : 0,
        currentTag: param.tag || '',
        keyword: param.keyword || ''
      });
      this.loadProducts();
    }
  },

  async loadProducts() {
    const { currentCategory, currentTag, keyword } = this.data;
    this.setData({ loading: true });
    try {
      const list = await db.getProducts({
        status: 'approved',
        categoryId: currentCategory || undefined,
        tag: currentTag || undefined,
        keyword: keyword || undefined
      });
      this.setData({ products: list, loading: false });
    } catch (e) {
      console.warn('作品列表加载失败', e);
      this.setData({ loading: false });
      wx.showToast({ title: '加载失败，请重试', icon: 'none' });
    }
  },

  onCategoryTap(e) {
    this.setData({ currentCategory: Number(e.currentTarget.dataset.id) });
    this.loadProducts();
  },

  onTagTap(e) {
    const tag = e.currentTarget.dataset.tag;
    this.setData({ currentTag: this.data.currentTag === tag ? '' : tag });
    this.loadProducts();
  },

  onSearchInput(e) {
    this.setData({ keyword: e.detail.value });
  },

  onSearch() {
    this.loadProducts();
  },

  clearKeyword() {
    this.setData({ keyword: '' });
    this.loadProducts();
  },

  onProductTap(e) {
    wx.navigateTo({ url: `/pages/detail/detail?id=${e.currentTarget.dataset.id}` });
  }
});
