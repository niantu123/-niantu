// pages/admin/admin.js - 管理员后台
const db = require('../../utils/db');
const config = require('../../utils/config');
const { formatTime } = require('../../utils/util');

const roleMap = {
  admin: '管理员',
  author: '作者',
  user: '普通用户'
};

// 用户编辑弹窗的角色选项（与 roleMap 对应）
const ROLE_VALUES = ['admin', 'author', 'user'];
const ROLE_NAMES = ['管理员', '作者', '普通用户'];

const statusClassOf = (status) => {
  if (status === 'approved') return 'status-approved';
  if (status === 'rejected') return 'status-rejected';
  if (status === 'offline') return 'status-offline';
  return 'status-pending';
};

Page({
  data: {
    tab: 'review',
    reviewList: [],
    allList: [],
    userList: [],
    messageList: [],
    feedbackList: [],
    orderList: [],
    loading: true,
    rejectVisible: false,
    rejectId: '',
    rejectName: '',
    rejectInput: '',
    quickReasons: ['图片清晰度不足', '作品信息不完整', '不符合校园文创主题', '疑似涉及侵权内容'],
    // 回复弹窗（留言 / 反馈共用）
    replyVisible: false,
    replyKind: 'message', // message | feedback
    replyId: '',
    replyTitle: '',
    replySub: '',
    replyText: '',
    // 反馈类型筛选
    fbType: '全部',
    fbTypes: ['全部', '意见', '问题'],
    // 用户编辑弹窗
    userEditVisible: false,
    userEditId: '',
    userEditForm: { nickName: '', account: '', role: 'user', phone: '', email: '' },
    roleIndex: 2,
    roleNames: ROLE_NAMES
  },

  // 支持从其他页带 tab 参数直达（如 /pages/admin/admin?tab=orders）
  onLoad(options) {
    if (options && options.tab) {
      this.setData({ tab: options.tab });
    }
  },

  onShow() {
    const user = db.getCurrentUser();
    if (!user || user.role !== 'admin') {
      wx.showToast({ title: '仅管理员可访问', icon: 'none' });
      setTimeout(() => wx.navigateBack(), 900);
      return;
    }
    this.loadData();
  },

  async loadData() {
    this.setData({ loading: true });
    try {
      const [allList, users, messages, feedbacks, orders] = await Promise.all([
        db.getProducts({}),
        db.getUsers(),
        db.getMessages({}),
        db.getFeedbacks({}),
        db.getAllOrders(db.getCurrentUser())
      ]);

      const works = allList.map(item => ({
        ...item,
        statusText: config.statusMap[item.status] || item.status,
        statusClass: statusClassOf(item.status),
        timeText: formatTime(new Date(item.createdAt))
      }));

      // 作者视角：只保留包含自己作品的订单
      const me = db.getCurrentUser();
      let orderList = orders;
      if (me && me.role === 'author') {
        const myIds = new Set(works.filter(w => w.authorId === me._id).map(w => w._id));
        orderList = orders.filter(order => (order.items || []).some(i => myIds.has(i.productId)));
      }
      const orderStatusClass = (status) => {
        if (status === '已完成') return 'status-approved';
        if (status === '已取消') return 'status-offline';
        return 'status-pending';
      };

      this.setData({
        // 作品审核：展示所有未上架作品（待审核 / 已驳回 / 已下架）
        reviewList: works.filter(item => item.status !== 'approved'),
        allList: works,
        userList: users.map(item => ({
          ...item,
          roleText: roleMap[item.role] || '普通用户',
          phoneText: item.phone || '未绑定手机号',
          timeText: formatTime(new Date(item.createdAt))
        })),
        messageList: messages.map(item => ({
          ...item,
          timeText: formatTime(new Date(item.createdAt))
        })),
        feedbackList: feedbacks.map(item => ({
          ...item,
          timeText: formatTime(new Date(item.createdAt))
        })),
        orderList: orderList.map(item => ({
          ...item,
          statusText: item.status,
          statusClass: orderStatusClass(item.status),
          timeText: formatTime(new Date(item.createdAt)),
          cancelledText: item.cancelledAt ? formatTime(new Date(item.cancelledAt)) : '',
          itemsText: (item.items || []).map(i => `${i.name} × ${i.qty}`).join('、')
        })),
        loading: false
      });
    } catch (e) {
      console.warn('后台数据加载失败', e);
      this.setData({ loading: false });
      wx.showToast({ title: '数据加载失败', icon: 'none' });
    }
  },

  switchTab(e) {
    this.setData({ tab: e.currentTarget.dataset.tab });
  },

  async onApprove(e) {
    const id = e.currentTarget.dataset.id;
    try {
      await db.setProductStatus(id, 'approved');
      wx.showToast({ title: '已通过审核', icon: 'success' });
      this.loadData();
    } catch (err) {
      wx.showToast({ title: '操作失败', icon: 'none' });
    }
  },

  // 作品管理：点击作品查看详情
  goWorkDetail(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: `/pages/detail/detail?id=${id}` });
  },

  // 作品管理：上架 / 下架
  async onToggleShelf(e) {
    const { id, status } = e.currentTarget.dataset;
    // 仅「已上架」可下架、「已下架」可重新上架；待审核与已驳回须先通过审核
    if (status !== 'approved' && status !== 'offline') {
      wx.showToast({ title: '请先在作品审核中通过', icon: 'none' });
      return;
    }
    const next = status === 'approved' ? 'offline' : 'approved';
    try {
      await db.setProductStatus(id, next);
      wx.showToast({ title: next === 'approved' ? '已上架' : '已下架', icon: 'none' });
      this.loadData();
    } catch (err) {
      wx.showToast({ title: '操作失败', icon: 'none' });
    }
  },

  // 作品管理：删除
  onDeleteWork(e) {
    const id = e.currentTarget.dataset.id;
    wx.showModal({
      title: '删除作品',
      content: '删除后不可恢复，确定继续吗？',
      success: async (res) => {
        if (!res.confirm) return;
        await db.deleteProduct(id);
        wx.showToast({ title: '已删除', icon: 'success' });
        this.loadData();
      }
    });
  },

  // 打开驳回弹窗
  openReject(e) {
    const id = e.currentTarget.dataset.id;
    const item = this.data.reviewList.find(work => work._id === id);
    this.setData({
      rejectVisible: true,
      rejectId: id,
      rejectName: item ? item.name : '',
      rejectInput: item && item.status === 'rejected' ? item.rejectReason || '' : ''
    });
  },

  closeReject() {
    this.setData({ rejectVisible: false });
  },

  noop() {},

  onRejectInput(e) {
    this.setData({ rejectInput: e.detail.value });
  },

  // 快捷理由：点选后追加到输入框
  pickReason(e) {
    const text = e.currentTarget.dataset.text;
    const current = this.data.rejectInput.trim();
    if (current.indexOf(text) > -1) return;
    const next = current ? `${current}；${text}` : text;
    this.setData({ rejectInput: next.slice(0, 100) });
  },

  // 确认驳回
  async confirmReject() {
    const reason = this.data.rejectInput.trim() || '不符合平台展示规范';
    try {
      await db.setProductStatus(this.data.rejectId, 'rejected', reason);
      this.setData({ rejectVisible: false });
      wx.showToast({ title: '已驳回', icon: 'none' });
      this.loadData();
    } catch (err) {
      wx.showToast({ title: '操作失败', icon: 'none' });
    }
  },

  // 留言管理：删除任意留言
  onDeleteMessage(e) {
    const id = e.currentTarget.dataset.id;
    wx.showModal({
      title: '删除留言',
      content: '删除后用户将不可见，确定删除吗？',
      success: async (res) => {
        if (!res.confirm) return;
        try {
          await db.deleteMessage(id, db.getCurrentUser());
          wx.showToast({ title: '已删除', icon: 'success' });
          this.loadData();
        } catch (err) {
          wx.showToast({ title: err.message || '删除失败', icon: 'none' });
        }
      }
    });
  },

  onReply(e) {
    const id = e.currentTarget.dataset.id;
    const item = this.data.messageList.find(m => m._id === id);
    this.setData({
      replyVisible: true,
      replyKind: 'message',
      replyId: id,
      replyTitle: '回复留言',
      replySub: item && item.userName ? `用户「${item.userName}」的留言` : '',
      replyText: item && item.reply ? item.reply : ''
    });
  },

  // ---------- 回复弹窗（留言 / 反馈共用） ----------
  closeReply() {
    this.setData({ replyVisible: false });
  },

  onReplyInput(e) {
    this.setData({ replyText: e.detail.value });
  },

  async confirmReply() {
    const reply = this.data.replyText.trim();
    if (!reply) {
      wx.showToast({ title: '回复内容不能为空', icon: 'none' });
      return;
    }
    try {
      if (this.data.replyKind === 'feedback') {
        await db.replyFeedback(this.data.replyId, reply);
      } else {
        await db.replyMessage(this.data.replyId, reply);
      }
      this.setData({ replyVisible: false });
      wx.showToast({ title: '回复成功', icon: 'success' });
      this.loadData();
    } catch (err) {
      wx.showToast({ title: err.message || '回复失败', icon: 'none' });
    }
  },

  // ---------- 意见反馈管理 ----------
  // 类型筛选：全部 / 意见 / 问题
  switchFbType(e) {
    this.setData({ fbType: e.currentTarget.dataset.type });
  },

  openFbReply(e) {
    const id = e.currentTarget.dataset.id;
    const item = this.data.feedbackList.find(f => f._id === id);
    this.setData({
      replyVisible: true,
      replyKind: 'feedback',
      replyId: id,
      replyTitle: '回复反馈',
      replySub: item ? `用户「${item.userName}」的${item.type}` : '',
      replyText: item && item.reply ? item.reply : ''
    });
  },

  // 标记已处理 / 恢复待处理
  async onMarkFeedback(e) {
    const id = e.currentTarget.dataset.id;
    const item = this.data.feedbackList.find(f => f._id === id);
    const next = item && item.status === '已处理' ? '待处理' : '已处理';
    try {
      await db.setFeedbackStatus(id, next);
      wx.showToast({ title: next === '已处理' ? '已标记处理完成' : '已恢复为待处理', icon: 'none' });
      this.loadData();
    } catch (err) {
      wx.showToast({ title: err.message || '操作失败', icon: 'none' });
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
          this.loadData();
        } catch (err) {
          wx.showToast({ title: err.message || '删除失败', icon: 'none' });
        }
      }
    });
  },

  onUserDetail(e) {
    const id = e.currentTarget.dataset.id;
    const user = this.data.userList.find(item => item._id === id);
    if (!user) return;
    wx.showModal({
      title: user.nickName,
      content: `角色：${user.roleText}\n手机号：${user.phoneText}\n注册时间：${user.timeText}`,
      showCancel: false
    });
  },

  // ---------- 用户管理：编辑 / 删除 ----------
  onEditUser(e) {
    const id = e.currentTarget.dataset.id;
    const u = this.data.userList.find(item => item._id === id);
    if (!u) return;
    this.setData({
      userEditVisible: true,
      userEditId: id,
      roleIndex: Math.max(0, ROLE_VALUES.indexOf(u.role)),
      userEditForm: {
        nickName: u.nickName || '',
        account: u.account || '',
        role: u.role || 'user',
        phone: u.phone || '',
        email: u.email || ''
      }
    });
  },

  closeUserEdit() {
    this.setData({ userEditVisible: false });
  },

  onUserInput(e) {
    const field = e.currentTarget.dataset.field;
    this.setData({ [`userEditForm.${field}`]: e.detail.value });
  },

  onRoleChange(e) {
    const idx = Number(e.detail.value);
    this.setData({ roleIndex: idx, 'userEditForm.role': ROLE_VALUES[idx] });
  },

  async confirmUserEdit() {
    const f = this.data.userEditForm;
    try {
      await db.adminUpdateUser(this.data.userEditId, f);
      this.setData({ userEditVisible: false });
      wx.showToast({ title: '已保存', icon: 'success' });
      this.loadData();
    } catch (err) {
      wx.showToast({ title: err.message || '保存失败', icon: 'none' });
    }
  },

  onDeleteUser(e) {
    const id = e.currentTarget.dataset.id;
    const user = this.data.userList.find(item => item._id === id);
    if (!user) return;
    wx.showModal({
      title: '删除用户',
      content: `确定删除用户「${user.nickName}」吗？删除后不可恢复。`,
      confirmColor: '#cf1322',
      success: async (res) => {
        if (!res.confirm) return;
        try {
          await db.deleteUser(id);
          wx.showToast({ title: '已删除', icon: 'success' });
          this.loadData();
        } catch (err) {
          wx.showToast({ title: err.message || '删除失败', icon: 'none' });
        }
      }
    });
  }
});
