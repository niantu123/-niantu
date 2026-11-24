// pages/cart/cart.js - 购物车
const db = require('../../utils/db');
const config = require('../../utils/config');
const { formatTime } = require('../../utils/util');

Page({
  data: {
    user: null,
    items: [],
    total: 0,
    loading: true,
    submitting: false,
    // 收货信息填单弹窗
    shipVisible: false,
    shipAddress: '',
    shipContact: ''
  },

  onShow() {
    this.refresh();
  },

  async refresh() {
    const user = db.getCurrentUser();
    if (!user) {
      this.setData({ user: null, items: [], total: 0, loading: false });
      return;
    }
    this.setData({ user, loading: true });
    try {
      const items = await db.getCart(user._id);
      const total = Math.round(items.reduce((sum, item) => sum + item.price * item.qty, 0) * 100) / 100;
      this.setData({ items, total, loading: false });
    } catch (e) {
      console.warn('购物车加载失败', e);
      this.setData({ loading: false });
    }
  },

  goStore() {
    wx.switchTab({ url: '/pages/store/store' });
  },

  requireLogin() {
    wx.showModal({
      title: '提示',
      content: '登录后才能使用购物车',
      confirmText: '去登录',
      success: (res) => {
        if (res.confirm) wx.navigateTo({ url: '/pages/login/login' });
      }
    });
  },

  async changeQty(e) {
    const id = e.currentTarget.dataset.id;
    const delta = Number(e.currentTarget.dataset.delta) || 0;
    const user = this.data.user;
    if (!user) return this.requireLogin();
    const item = this.data.items.find(item => item.productId === id);
    if (!item) return;
    // 减到 0 时询问是否移除
    if (item.qty + delta <= 0) {
      wx.showModal({
        title: '移除商品',
        content: `将「${item.name}」移出购物车？`,
        success: async (res) => {
          if (!res.confirm) return;
          await db.removeFromCart(id, user);
          this.refresh();
        }
      });
      return;
    }
    try {
      await db.updateCartQty(id, item.qty + delta, user);
      this.refresh();
    } catch (err) {
      wx.showToast({ title: err.message || '操作失败', icon: 'none' });
    }
  },

  onDelete(e) {
    const id = e.currentTarget.dataset.id;
    const user = this.data.user;
    const item = this.data.items.find(item => item.productId === id);
    if (!item) return;
    wx.showModal({
      title: '移除商品',
      content: `将「${item.name}」移出购物车？`,
      success: async (res) => {
        if (!res.confirm) return;
        await db.removeFromCart(id, user);
        wx.showToast({ title: '已移除', icon: 'none' });
        this.refresh();
      }
    });
  },

  // 结算：打开收货信息填单弹窗（模拟交易，不接入真实支付）
  onCheckout() {
    const user = this.data.user;
    if (!user) return this.requireLogin();
    if (!this.data.items.length) {
      wx.showToast({ title: '购物车是空的', icon: 'none' });
      return;
    }
    if (this.data.submitting) return;
    // 预填：记住的上次收货信息 + 账号手机号
    const ship = db.getShipInfo(user._id);
    this.setData({
      shipVisible: true,
      shipAddress: ship.address,
      shipContact: ship.contact || user.phone || ''
    });
  },

  closeShip() {
    this.setData({ shipVisible: false });
  },

  onShipAddress(e) {
    this.setData({ shipAddress: e.detail.value });
  },

  onShipContact(e) {
    this.setData({ shipContact: e.detail.value });
  },

  // 提交订单：校验收货信息后生成订单并清空购物车
  confirmCheckout() {
    const user = this.data.user;
    const address = this.data.shipAddress.trim();
    const contact = this.data.shipContact.trim();
    if (!address) {
      wx.showToast({ title: '请填写收货地址', icon: 'none' });
      return;
    }
    if (address.length < 5) {
      wx.showToast({ title: '收货地址太短，请填写详细地址', icon: 'none' });
      return;
    }
    if (!contact) {
      wx.showToast({ title: '请填写联系方式', icon: 'none' });
      return;
    }
    if (this.data.submitting) return;
    this.setData({ submitting: true });
    db.saveShipInfo(user._id, { address, contact });
    db.createOrder(user, { address, contact })
      .then((order) => {
        this.setData({ submitting: false, shipVisible: false });
        wx.showModal({
          title: '下单成功',
          content: `订单金额 ¥${order.total}，货品将送至：${order.address}`,
          confirmText: '查看订单',
          showCancel: false,
          success: () => {
            wx.navigateTo({ url: '/pages/orders/orders' });
          }
        });
      })
      .catch((err) => {
        this.setData({ submitting: false });
        wx.showToast({ title: err.message || '下单失败', icon: 'none' });
      });
  }
});
