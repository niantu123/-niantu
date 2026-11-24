// pages/my-works/my-works.js - 我的发布
const db = require('../../utils/db');
const config = require('../../utils/config');
const { formatTime } = require('../../utils/util');

Page({
  data: {
    works: [],
    loading: true,
    // 订单提醒（作者/管理员可见）：{ total, cancelled, latestReason }
    orderHint: null
  },

  onShow() {
    this.loadWorks();
  },

  async loadWorks() {
    const user = db.getCurrentUser();
    if (!user) {
      this.setData({ works: [], loading: false, orderHint: null });
      return;
    }
    try {
      const list = await db.getProducts({ authorId: user._id });
      this.setData({
        works: list.map(item => ({
          ...item,
          statusText: config.statusMap[item.status] || item.status,
          statusClass:
            item.status === 'approved'
              ? 'status-approved'
              : item.status === 'rejected'
              ? 'status-rejected'
              : 'status-pending',
          timeText: formatTime(new Date(item.createdAt))
        })),
        loading: false
      });
      this.loadOrderHint(user);
    } catch (e) {
      console.warn('我的发布加载失败', e);
      this.setData({ loading: false });
    }
  },

  // 订单提醒：统计包含自己作品的订单与取消原因
  async loadOrderHint(user) {
    if (user.role !== 'author' && user.role !== 'admin') return;
    try {
      const orders = await db.getAllOrders(user);
      const myIds = new Set(this.data.works.map(w => w._id));
      const mine = orders.filter(order => (order.items || []).some(i => myIds.has(i.productId)));
      if (!mine.length) {
        this.setData({ orderHint: null });
        return;
      }
      const cancelled = mine.filter(o => o.status === '已取消');
      // 最近一条取消原因作为预览
      const latestCancel = cancelled
        .filter(o => o.cancelledAt)
        .sort((a, b) => b.cancelledAt - a.cancelledAt)[0];
      this.setData({
        orderHint: {
          total: mine.length,
          active: mine.filter(o => o.status === '已下单').length,
          cancelled: cancelled.length,
          latestReason: latestCancel ? latestCancel.cancelReason || '未填写原因' : ''
        }
      });
    } catch (e) {
      // 普通用户无权查全部订单，静默跳过
    }
  },

  goOrders() {
    wx.navigateTo({ url: '/pages/author-orders/author-orders' });
  },

  onPreview(e) {
    wx.previewImage({ urls: [e.currentTarget.dataset.src] });
  },

  onEdit(e) {
    wx.navigateTo({ url: `/pages/publish/publish?id=${e.currentTarget.dataset.id}` });
  },

  onDelete(e) {
    const id = e.currentTarget.dataset.id;
    wx.showModal({
      title: '提示',
      content: '确定删除这件作品吗？删除后不可恢复。',
      success: async (res) => {
        if (!res.confirm) return;
        await db.deleteProduct(id);
        wx.showToast({ title: '已删除', icon: 'success' });
        this.loadWorks();
      }
    });
  },

  goPublish() {
    wx.navigateTo({ url: '/pages/publish/publish' });
  }
});
