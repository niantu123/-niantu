// pages/message/message.js - 留言页（选品留言 / 编辑删除自己的留言 / 管理员管理全部留言）
const app = getApp();
const db = require('../../utils/db');
const config = require('../../utils/config');
const { formatTime } = require('../../utils/util');

Page({
  data: {
    tab: 'form', // form | mine | all（all 仅管理员可见）
    products: [],        // 可选的上架作品（含封面）
    filteredProducts: [], // 弹窗内按分类过滤后的作品
    categories: [{ id: 0, name: '全部' }, ...config.categories], // 选品分类筛选
    pickerCategory: 0,   // 弹窗当前分类（0=全部）
    selectedProduct: null, // 已选中的咨询作品
    pickerVisible: false,  // 作品选择弹窗
    content: '',
    contact: '',
    user: null,
    isAdmin: false,
    messages: [],
    submitting: false,
    // 编辑留言弹窗
    editVisible: false,
    editId: '',
    editContent: ''
  },

  onLoad(options) {
    if (options.productId) {
      this.pendingProduct = {
        productId: options.productId,
        productName: decodeURIComponent(options.productName || '')
      };
    }
  },

  async onShow() {
    const user = db.getCurrentUser();
    this.setData({
      user,
      isAdmin: !!user && user.role === 'admin',
      tab: this.data.tab === 'all' && !(user && user.role === 'admin') ? 'form' : this.data.tab
    });
    await this.loadProducts();

    // 详情页通过全局变量传递作品信息（自动选中该作品）
    const param = app.globalData.messageParam;
    if (param) {
      app.globalData.messageParam = null;
      this.selectById(param.productId);
      this.setData({ tab: 'form' });
      if (param.productName) {
        wx.showToast({ title: `已选中《${param.productName}》`, icon: 'none' });
      }
    } else if (this.pendingProduct) {
      this.selectById(this.pendingProduct.productId);
      this.pendingProduct = null;
    }
    if (user) this.loadMessages();

    // 详情页点击「编辑」跳转过来：自动打开该留言的编辑弹窗
    const editParam = app.globalData.editMessageParam;
    if (editParam) {
      app.globalData.editMessageParam = null;
      if (user) {
        await this.loadMessages();
        const target = this.data.messages.find(m => m._id === editParam.id && m.isMine);
        if (target) {
          this.setData({ tab: 'mine', editVisible: true, editId: target._id, editContent: target.content });
        } else {
          wx.showToast({ title: '未找到该留言', icon: 'none' });
        }
      }
    }
  },

  // 按 id 选中已上架作品
  selectById(productId) {
    const target = this.data.products.find(p => p._id === productId);
    if (target) this.setData({ selectedProduct: target });
  },

  // 加载上架作品供留言选择
  async loadProducts() {
    try {
      const products = await db.getProducts({ status: 'approved' });
      this.setData({ products, filteredProducts: products });
    } catch (e) {
      console.warn('作品列表加载失败', e);
    }
  },

  // 加载咨询记录：我的咨询 → 本人；全部咨询（管理员）→ 所有。只显示 inquiry 类（发给作者的私密咨询）
  async loadMessages() {
    const user = this.data.user;
    if (!user) return;
    const byAll = this.data.tab === 'all' && this.data.isAdmin;
    try {
      const messages = await db.getMessages(byAll ? {} : { userId: user._id });
      this.setData({
        messages: messages
          .filter(item => (item.kind || 'inquiry') === 'inquiry')
          .map(item => ({
            ...item,
            timeText: formatTime(new Date(item.createdAt)),
            isMine: item.userId === user._id
          }))
      });
    } catch (e) {
      console.warn('留言加载失败', e);
    }
  },

  switchTab(e) {
    this.setData({ tab: e.currentTarget.dataset.tab });
    if (e.currentTarget.dataset.tab !== 'form') this.loadMessages();
  },

  // ---------- 作品选择弹窗 ----------
  openPicker() {
    this.setData({ pickerVisible: true, pickerCategory: 0, filteredProducts: this.data.products });
  },

  closePicker() {
    this.setData({ pickerVisible: false });
  },

  // 切换选品分类筛选
  switchPickerCategory(e) {
    const id = Number(e.currentTarget.dataset.id);
    const filtered = id
      ? this.data.products.filter(p => p.categoryId === id)
      : this.data.products;
    this.setData({ pickerCategory: id, filteredProducts: filtered });
  },

  // 点击选项选中作品
  onPickProduct(e) {
    const id = e.currentTarget.dataset.id;
    const target = this.data.products.find(p => p._id === id);
    if (target) this.setData({ selectedProduct: target, pickerVisible: false });
  },

  // 清除已选作品
  clearProduct() {
    this.setData({ selectedProduct: null });
  },

  // 点击选项查看作品详情
  goProductDetail(e) {
    wx.navigateTo({ url: `/pages/detail/detail?id=${e.currentTarget.dataset.id}` });
  },

  onContentInput(e) {
    this.setData({ content: e.detail.value });
  },

  onContactInput(e) {
    this.setData({ contact: e.detail.value });
  },

  async onSubmit() {
    const user = db.getCurrentUser();
    if (!user) {
      wx.showModal({
        title: '提示',
        content: '登录后才能提交留言',
        confirmText: '去登录',
        success: (res) => {
          if (res.confirm) wx.navigateTo({ url: '/pages/login/login' });
        }
      });
      return;
    }

    const product = this.data.selectedProduct;
    if (!product) {
      wx.showToast({ title: '请先选择咨询的作品', icon: 'none' });
      return;
    }
    const content = this.data.content.trim();
    if (content.length < 5) {
      wx.showToast({ title: '留言至少 5 个字', icon: 'none' });
      return;
    }
    if (this.data.submitting) return;

    this.setData({ submitting: true });
    try {
      await db.addMessage(
        {
          productId: product._id,
          productName: product.name,
          content,
          contact: this.data.contact.trim(),
          kind: 'inquiry'
        },
        user
      );
      this.setData({ content: '', contact: '', submitting: false, tab: 'mine' });
      await this.loadMessages();
      wx.showToast({ title: '留言已提交', icon: 'success' });
    } catch (e) {
      this.setData({ submitting: false });
      wx.showToast({ title: e.message || '提交失败', icon: 'none' });
    }
  },

  // ---------- 编辑 / 删除自己的留言 ----------
  openEdit(e) {
    const id = e.currentTarget.dataset.id;
    const item = this.data.messages.find(m => m._id === id);
    if (!item) return;
    this.setData({ editVisible: true, editId: id, editContent: item.content });
  },

  closeEdit() {
    this.setData({ editVisible: false, editId: '', editContent: '' });
  },

  onEditInput(e) {
    this.setData({ editContent: e.detail.value });
  },

  noop() {},

  async confirmEdit() {
    const content = this.data.editContent.trim();
    if (content.length < 5) {
      wx.showToast({ title: '留言至少 5 个字', icon: 'none' });
      return;
    }
    try {
      await db.editMessage(this.data.editId, this.data.user._id, content);
      this.setData({ editVisible: false, editId: '', editContent: '' });
      wx.showToast({ title: '已更新', icon: 'success' });
      this.loadMessages();
    } catch (e) {
      wx.showToast({ title: e.message || '修改失败', icon: 'none' });
    }
  },

  onDeleteMessage(e) {
    const id = e.currentTarget.dataset.id;
    wx.showModal({
      title: '删除留言',
      content: '删除后不可恢复，确定删除吗？',
      success: async (res) => {
        if (!res.confirm) return;
        try {
          await db.deleteMessage(id, db.getCurrentUser());
          wx.showToast({ title: '已删除', icon: 'success' });
          this.loadMessages();
        } catch (err) {
          wx.showToast({ title: err.message || '删除失败', icon: 'none' });
        }
      }
    });
  },

  goLogin() {
    wx.navigateTo({ url: '/pages/login/login' });
  }
});
