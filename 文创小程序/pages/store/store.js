// pages/store/store.js - 校园文创商店
const db = require('../../utils/db');

Page({
  data: {
    products: [],
    displayProducts: [],
    cartCount: 0,
    loading: true,
    user: null
  },

  onShow() {
    this.refresh();
  },

  async refresh() {
    const user = db.getCurrentUser();
    this.setData({ user, loading: true });
    try {
      const [list, cartCount] = await Promise.all([
        db.getProducts({ status: 'approved' }),
        db.getCartCount(user ? user._id : '')
      ]);
      const products = list.map(item => ({
        _id: item._id,
        name: item.name,
        cover: item.cover,
        summary: item.summary,
        price: item.price > 0 ? item.price : null,
        sellable: item.price > 0
      }));
      this.setData({
        products,
        displayProducts: this.pickRandom(products, 6),
        cartCount,
        loading: false
      });
    } catch (e) {
      console.warn('商店数据加载失败', e);
      this.setData({ loading: false });
    }
  },

  // 随机抽取 n 件作品作为推荐
  pickRandom(list, n) {
    const arr = list.slice();
    for (let i = arr.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr.slice(0, n);
  },

  // 换一批：重新随机推荐
  onShuffle() {
    this.setData({ displayProducts: this.pickRandom(this.data.products, 6) });
    wx.showToast({ title: '已换一批', icon: 'none' });
  },

  goDetail(e) {
    wx.navigateTo({ url: `/pages/detail/detail?id=${e.currentTarget.dataset.id}` });
  },

  goCart() {
    if (!this.data.user) return this.requireLogin();
    wx.navigateTo({ url: '/pages/cart/cart' });
  },

  goOrders() {
    if (!this.data.user) return this.requireLogin();
    wx.navigateTo({ url: '/pages/orders/orders' });
  },

  requireLogin() {
    wx.showModal({
      title: '提示',
      content: '登录后才能购买文创产品',
      confirmText: '去登录',
      success: (res) => {
        if (res.confirm) wx.navigateTo({ url: '/pages/login/login' });
      }
    });
  },

  async onAddCart(e) {
    const id = e.currentTarget.dataset.id;
    const user = db.getCurrentUser();
    if (!user) return this.requireLogin();
    try {
      await db.addToCart(id, 1, user);
      const cartCount = await db.getCartCount(user._id);
      this.setData({ cartCount });
      wx.showToast({ title: '已加入购物车', icon: 'success' });
    } catch (err) {
      wx.showToast({ title: err.message || '加入失败', icon: 'none' });
    }
  }
});
