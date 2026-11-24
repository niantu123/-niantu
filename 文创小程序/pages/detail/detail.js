// pages/detail/detail.js - 作品详情页
const app = getApp();
const db = require('../../utils/db');
const config = require('../../utils/config');
const { formatTime } = require('../../utils/util');

Page({
  data: {
    product: null,
    images: [],
    current: 0,
    favorited: false,
    categoryName: '',
    previewVisible: false,
    previewSrc: '',
    // 购买弹窗
    buyVisible: false,
    buyQty: 1,
    buyTotal: 0,
    // 收货信息
    shipAddress: '',
    shipContact: '',
    // 直接评论
    commentInput: '',
    commentSubmitting: false,
    commentVisible: false, // 发表评论弹窗
    // 评论编辑弹窗
    editVisible: false,
    editCommentId: '',
    editCommentText: '',
    // 作品留言区
    comments: [],
    user: null,
    isAdmin: false
  },

  async onLoad(options) {
    this.productId = options.id;
    // 不调用 wx.showShareMenu：onShareAppMessage 默认即生效（转发给朋友）。
    // 此前开启 shareTimeline（朋友圈）会因小程序未认证弹出「未认证」提示，已移除。
    const product = await db.getProduct(options.id);
    if (!product) {
      wx.showToast({ title: '作品不存在', icon: 'none' });
      setTimeout(() => wx.navigateBack(), 1200);
      return;
    }
    // 下架/待审核/已驳回的作品仅作者本人与管理员可查看
    const user = db.getCurrentUser();
    const canView =
      product.status === 'approved' ||
      (user && (user._id === product.authorId || user.role === 'admin'));
    if (!canView) {
      wx.showToast({ title: '作品已下架', icon: 'none' });
      setTimeout(() => wx.navigateBack(), 1000);
      return;
    }
    const category = config.categories.find(item => item.id === product.categoryId);
    this.setData({
      product,
      images: product.images && product.images.length ? product.images : [product.cover],
      categoryName: category ? category.name : '文创作品'
    });
    this.checkFavorite();
  },

  onShow() {
    const user = db.getCurrentUser();
    this.setData({
      user,
      isAdmin: !!user && user.role === 'admin'
    });
    if (this.productId) this.loadComments();
  },

  // 记录滚动位置，评论增删改后恢复
  onPageScroll(e) {
    this._lastScroll = e.scrollTop;
  },

  // 作品下方展示公开评论（咨询是发给作者的私密信息，不在此展示）
  // _keepScroll 为 true 时，刷新后恢复之前的滚动位置
  async loadComments(keepScroll) {
    const scrollTop = this._lastScroll || 0;
    try {
      const messages = await db.getMessages({ productId: this.productId });
      const user = this.data.user;
      this.setData({
        comments: messages
          .filter(m => (m.kind || 'inquiry') === 'comment')
          .map(item => ({
            ...item,
            timeText: formatTime(new Date(item.createdAt)),
            isMine: !!user && item.userId === user._id
          }))
      });
      if (keepScroll) {
        wx.pageScrollTo({ scrollTop, duration: 0 });
      }
    } catch (e) {
      console.warn('留言加载失败', e);
    }
  },

  // 点击自己的留言 → 当前页面弹窗编辑
  onEditComment(e) {
    const id = e.currentTarget.dataset.id;
    const item = this.data.comments.find(c => c._id === id);
    if (!item || !item.isMine) return;
    this.setData({
      editVisible: true,
      editCommentId: id,
      editCommentText: item.content
    });
  },

  closeEditComment() {
    this.setData({ editVisible: false });
  },

  onEditCommentInput(e) {
    this.setData({ editCommentText: e.detail.value });
  },

  // 确认修改评论
  async confirmEditComment() {
    const content = this.data.editCommentText.trim();
    if (!content) {
      wx.showToast({ title: '评论内容不能为空', icon: 'none' });
      return;
    }
    const user = db.getCurrentUser();
    if (!user) return;
    try {
      await db.editMessage(this.data.editCommentId, user._id, content);
      this.setData({ editVisible: false });
      wx.showToast({ title: '评论已修改', icon: 'success' });
      this.loadComments(true);
    } catch (e) {
      wx.showToast({ title: e.message || '修改失败', icon: 'none' });
    }
  },

  // 删除留言（本人或管理员）
  onDeleteComment(e) {
    const id = e.currentTarget.dataset.id;
    wx.showModal({
      title: '删除留言',
      content: '删除后不可恢复，确定删除吗？',
      success: async (res) => {
        if (!res.confirm) return;
        try {
          await db.deleteMessage(id, db.getCurrentUser());
          wx.showToast({ title: '已删除', icon: 'success' });
          this.loadComments(true);
        } catch (err) {
          wx.showToast({ title: err.message || '删除失败', icon: 'none' });
        }
      }
    });
  },

  async checkFavorite() {
    const user = db.getCurrentUser();
    if (!user) return;
    const ids = await db.getFavoriteIds(user._id);
    this.setData({ favorited: ids.indexOf(this.productId) > -1 });
  },

  onSwiperChange(e) {
    this.setData({ current: e.detail.current });
  },

  // 全屏预览：wx.previewImage 不支持包内路径（/images/...），自建预览层本地/云图片通用
  openPreview(e) {
    this.setData({ previewVisible: true, previewSrc: e.currentTarget.dataset.src });
  },

  closePreview() {
    this.setData({ previewVisible: false });
  },

  noop() {},

  async onToggleFavorite() {
    const user = db.getCurrentUser();
    if (!user) {
      wx.showModal({
        title: '提示',
        content: '登录后才能收藏作品',
        confirmText: '去登录',
        success: (res) => {
          if (res.confirm) wx.navigateTo({ url: '/pages/login/login' });
        }
      });
      return;
    }
    try {
      const favorited = await db.toggleFavorite(this.productId, user._id);
      this.setData({ favorited });
      wx.showToast({ title: favorited ? '已收藏' : '已取消收藏', icon: 'none' });
    } catch (e) {
      wx.showToast({ title: e.message || '操作失败', icon: 'none' });
    }
  },

  // 咨询这件作品：未登录先引导登录，已登录携带作品信息跳转留言页
  goMessage() {
    const user = db.getCurrentUser();
    if (!user) {
      wx.showModal({
        title: '提示',
        content: '登录后才能咨询作品',
        confirmText: '去登录',
        success: (res) => {
          if (res.confirm) wx.navigateTo({ url: '/pages/login/login' });
        }
      });
      return;
    }
    const product = this.data.product;
    // 留言页已移出 tabBar，通过全局变量传递作品信息后普通跳转
    app.globalData.messageParam = {
      productId: product._id,
      productName: product.name
    };
    wx.navigateTo({ url: '/pages/message/message' });
  },

  // 打开购买弹窗（可调整数量，预填收货信息）
  openBuy() {
    const product = this.data.product;
    if (!product || !(product.price > 0)) {
      wx.showToast({ title: '该作品尚未定价，无法购买', icon: 'none' });
      return;
    }
    const user = db.getCurrentUser();
    const ship = db.getShipInfo(user ? user._id : '');
    this.setData({
      buyVisible: true,
      buyQty: 1,
      buyTotal: product.price,
      shipAddress: ship.address,
      shipContact: ship.contact || (user && user.phone) || ''
    });
  },

  closeBuy() {
    this.setData({ buyVisible: false });
  },

  onBuyMinus() {
    if (this.data.buyQty <= 1) return;
    const qty = this.data.buyQty - 1;
    this.setData({ buyQty: qty, buyTotal: Math.round(qty * this.data.product.price * 100) / 100 });
  },

  onBuyPlus() {
    if (this.data.buyQty >= 99) return;
    const qty = this.data.buyQty + 1;
    this.setData({ buyQty: qty, buyTotal: Math.round(qty * this.data.product.price * 100) / 100 });
  },

  requireBuyLogin() {
    wx.showModal({
      title: '提示',
      content: '登录后才能购买文创产品',
      confirmText: '去登录',
      success: (res) => {
        if (res.confirm) wx.navigateTo({ url: '/pages/login/login' });
      }
    });
  },

  // 收货信息输入
  onShipAddress(e) {
    this.setData({ shipAddress: e.detail.value });
  },

  onShipContact(e) {
    this.setData({ shipContact: e.detail.value });
  },

  // 提交前校验收货信息，返回规整后的值（不通过返回 null）
  validateShip() {
    const address = this.data.shipAddress.trim();
    const contact = this.data.shipContact.trim();
    if (!address) {
      wx.showToast({ title: '请填写收货地址', icon: 'none' });
      return null;
    }
    if (address.length < 5) {
      wx.showToast({ title: '收货地址太短，请填写详细地址', icon: 'none' });
      return null;
    }
    if (!contact) {
      wx.showToast({ title: '请填写联系方式', icon: 'none' });
      return null;
    }
    return { address, contact };
  },

  // 弹窗内：加入购物车（带所选数量）
  async confirmAddCart() {
    const user = db.getCurrentUser();
    if (!user) return this.requireBuyLogin();
    try {
      await db.addToCart(this.productId, this.data.buyQty, user);
      this.setData({ buyVisible: false });
      wx.showToast({ title: '已加入购物车', icon: 'success' });
    } catch (e) {
      wx.showToast({ title: e.message || '加入失败', icon: 'none' });
    }
  },

  // 弹窗内：立即购买（直接生成单件订单，不影响购物车）
  confirmBuyNow() {
    const user = db.getCurrentUser();
    if (!user) return this.requireBuyLogin();
    const qty = this.data.buyQty;
    const ship = this.validateShip();
    if (!ship) return;
    db.saveShipInfo(user._id, ship);
    wx.showModal({
      title: '确认下单',
      content: `购买 ${qty} 件，合计 ¥${this.data.buyTotal}，送至：${ship.address}`,
      confirmText: '提交订单',
      success: async (res) => {
        if (!res.confirm) return;
        try {
          await db.buyNow(this.productId, qty, user, ship);
          this.setData({ buyVisible: false });
          wx.showModal({
            title: '下单成功',
            content: '可在「我的订单」中查看与管理',
            confirmText: '查看订单',
            showCancel: false,
            success: () => {
              wx.navigateTo({ url: '/pages/orders/orders' });
            }
          });
        } catch (e) {
          wx.showToast({ title: e.message || '下单失败', icon: 'none' });
        }
      }
    });
  },

  // ---------- 作品评论区（直接评论） ----------
  // 评论条为纯 view：点击弹出评论弹窗输入（页面无常驻原生 input，杜绝穿透）
  openCommentEditor() {
    this.setData({ commentVisible: true });
  },

  closeCommentEditor() {
    this.setData({ commentVisible: false });
  },

  onCommentInput(e) {
    this.setData({ commentInput: e.detail.value });
  },

  async onSubmitComment() {
    const content = this.data.commentInput.trim();
    if (!content) {
      wx.showToast({ title: '评论内容不能为空', icon: 'none' });
      return;
    }
    const user = db.getCurrentUser();
    if (!user) {
      wx.showModal({
        title: '提示',
        content: '登录后才能评论作品',
        confirmText: '去登录',
        success: (res) => {
          if (res.confirm) wx.navigateTo({ url: '/pages/login/login' });
        }
      });
      return;
    }
    if (this.data.commentSubmitting) return;
    this.setData({ commentSubmitting: true });
    try {
      await db.addMessage(
        { productId: this.productId, productName: this.data.product.name, content, kind: 'comment' },
        user
      );
      this.setData({ commentInput: '', commentSubmitting: false, commentVisible: false });
      wx.showToast({ title: '评论成功', icon: 'success' });
      this.loadComments(true);
    } catch (e) {
      this.setData({ commentSubmitting: false });
      wx.showToast({ title: e.message || '评论失败', icon: 'none' });
    }
  },

  // 未登录点击评论引导
  goLoginTip() {
    wx.showModal({
      title: '提示',
      content: '登录后才能评论作品',
      confirmText: '去登录',
      success: (res) => {
        if (res.confirm) wx.navigateTo({ url: '/pages/login/login' });
      }
    });
  },

  // 自定义分享卡片：封面 + 标题
  onShareAppMessage() {
    const product = this.data.product;
    // 封面为临时路径（用户上传的 wxfile://、http://tmp）或大图时，真机偶发「分享失败」，
    // 此时省略 imageUrl 让微信自动截取页面顶部作为分享图
    const cover = product ? product.cover : '';
    const safeCover = cover && !/^(wxfile:|http:\/\/tmp|cloud:)/.test(cover) ? cover : '';
    return {
      title: product ? `《${product.name}》｜新安学院校园原创文创` : '新安学院校园文创展示',
      path: `/pages/detail/detail?id=${this.productId}`,
      imageUrl: safeCover
    };
  },

  // 朋友圈分享需要微信认证，未认证账号不可用；认证后补充：
  // onShareTimeline() { ... }
});
