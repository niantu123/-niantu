// pages/orders/orders.js - 我的订单
const db = require('../../utils/db');
const { formatTime } = require('../../utils/util');

const statusClassOf = (status) => {
  if (status === '已完成') return 'status-done';
  if (status === '已取消') return 'status-cancel';
  return 'status-active';
};

Page({
  data: {
    orders: [],
    loading: true,
    user: null,
    // 取消订单弹窗
    cancelVisible: false,
    cancelId: '',
    cancelReason: '',
    quickReasons: ['不想要了', '拍错了', '价格不合适', '收货信息填错了', '等太久不想等了']
  },

  onShow() {
    this.refresh();
  },

  async refresh() {
    const user = db.getCurrentUser();
    if (!user) {
      this.setData({ user: null, orders: [], loading: false });
      return;
    }
    this.setData({ user, loading: true });
    try {
      const orders = await db.getOrders(user._id);
      this.setData({
        orders: orders.map(order => ({
          ...order,
          statusClass: statusClassOf(order.status),
          timeText: formatTime(new Date(order.createdAt)),
          // 商品摘要：名称×数量，最多展示两条
          itemsText: order.items.map(item => `${item.name} × ${item.qty}`).join('、'),
          cover: order.items.length ? order.items[0].cover : ''
        })),
        loading: false
      });
    } catch (e) {
      console.warn('订单加载失败', e);
      this.setData({ loading: false });
    }
  },

  goStore() {
    wx.switchTab({ url: '/pages/store/store' });
  },

  requireLogin() {
    wx.showModal({
      title: '提示',
      content: '登录后才能查看订单',
      confirmText: '去登录',
      success: (res) => {
        if (res.confirm) wx.navigateTo({ url: '/pages/login/login' });
      }
    });
  },

  // 确认收货：已下单 → 已完成
  onConfirm(e) {
    const id = e.currentTarget.dataset.id;
    const user = this.data.user;
    wx.showModal({
      title: '确认收货',
      content: '确认已收到全部商品吗？',
      success: async (res) => {
        if (!res.confirm) return;
        try {
          await db.confirmOrder(id, user);
          wx.showToast({ title: '交易完成', icon: 'success' });
          this.refresh();
        } catch (err) {
          wx.showToast({ title: err.message || '操作失败', icon: 'none' });
        }
      }
    });
  },

  // 取消订单：弹窗填写原因
  onCancel(e) {
    const id = e.currentTarget.dataset.id;
    this.setData({ cancelVisible: true, cancelId: id, cancelReason: '' });
  },

  closeCancel() {
    this.setData({ cancelVisible: false });
  },

  onCancelInput(e) {
    this.setData({ cancelReason: e.detail.value });
  },

  pickCancelReason(e) {
    const text = e.currentTarget.dataset.text;
    const current = this.data.cancelReason.trim();
    if (current.indexOf(text) > -1) return;
    const next = current ? `${current}；${text}` : text;
    this.setData({ cancelReason: next.slice(0, 100) });
  },

  async confirmCancel() {
    const reason = this.data.cancelReason.trim();
    if (!reason) {
      wx.showToast({ title: '请填写取消原因', icon: 'none' });
      return;
    }
    try {
      await db.cancelOrder(this.data.cancelId, this.data.user, reason);
      this.setData({ cancelVisible: false });
      wx.showToast({ title: '已取消订单', icon: 'none' });
      this.refresh();
    } catch (err) {
      wx.showToast({ title: err.message || '操作失败', icon: 'none' });
    }
  },

  noop() {},

  // 删除订单：仅「已取消」的订单可删除
  onDelete(e) {
    const id = e.currentTarget.dataset.id;
    const user = this.data.user;
    wx.showModal({
      title: '删除订单',
      content: '删除后该订单记录将不可恢复，确定删除吗？',
      confirmColor: '#e64340',
      success: async (res) => {
        if (!res.confirm) return;
        try {
          await db.deleteOrder(id, user);
          wx.showToast({ title: '已删除', icon: 'success' });
          this.refresh();
        } catch (err) {
          wx.showToast({ title: err.message || '操作失败', icon: 'none' });
        }
      }
    });
  }
});
