// pages/author-orders/author-orders.js - 收到的订单（作者视角）
const db = require('../../utils/db');
const { formatTime } = require('../../utils/util');

const statusClassOf = (status) => {
  if (status === '已完成') return 'status-approved';
  if (status === '已取消') return 'status-cancel';
  return 'status-active';
};

Page({
  data: {
    orders: [],
    loading: true,
    user: null
  },

  onShow() {
    this.refresh();
  },

  async refresh() {
    const user = db.getCurrentUser();
    if (!user || (user.role !== 'author' && user.role !== 'admin')) {
      this.setData({ user: null, orders: [], loading: false });
      return;
    }
    this.setData({ user, loading: true });
    try {
      // 自己的作品列表，用于筛选出包含自己作品的订单
      const myWorks = await db.getProducts({ authorId: user._id });
      const myIds = new Set(myWorks.map(w => w._id));
      const all = await db.getAllOrders(user);

      const orders = all
        .filter(order => (order.items || []).some(i => myIds.has(i.productId)))
        .map(order => {
          // 只展示与自己作品相关的条目及小计
          const myItems = order.items.filter(i => myIds.has(i.productId));
          const subtotal = Math.round(
            myItems.reduce((sum, i) => sum + i.price * i.qty, 0) * 100
          ) / 100;
          return {
            ...order,
            statusClass: statusClassOf(order.status),
            timeText: formatTime(new Date(order.createdAt)),
            cancelledText: order.cancelledAt ? formatTime(new Date(order.cancelledAt)) : '',
            itemsText: myItems.map(i => `${i.name} × ${i.qty}`).join('、'),
            subtotal,
            cover: myItems.length ? myItems[0].cover : ''
          };
        });

      this.setData({ orders, loading: false });
    } catch (e) {
      console.warn('收到的订单加载失败', e);
      this.setData({ loading: false });
    }
  },

  goStore() {
    wx.switchTab({ url: '/pages/store/store' });
  },

  requireLogin() {
    wx.showModal({
      title: '提示',
      content: '登录后才能查看收到的订单',
      confirmText: '去登录',
      success: (res) => {
        if (res.confirm) wx.navigateTo({ url: '/pages/login/login' });
      }
    });
  }
});
