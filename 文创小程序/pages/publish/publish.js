// pages/publish/publish.js - 作者发布作品
const db = require('../../utils/db');
const config = require('../../utils/config');
const assistant = require('../../utils/assistant');

// 用隐藏 Canvas 绘制图片并导出 base64（不依赖 FileSystemManager，工具与真机都可靠）
const canvasToBase64 = (filePath) =>
  new Promise((resolve, reject) => {
    wx.createSelectorQuery()
      .select('#aiCanvas')
      .fields({ node: true, size: true })
      .exec((res) => {
        if (!res || !res[0] || !res[0].node) {
          reject(new Error('画布初始化失败，请重试'));
          return;
        }
        const canvas = res[0].node;
        const ctx = canvas.getContext('2d');
        const img = canvas.createImage();
        img.onload = () => {
          // 限制最长边 1024，控制 base64 体积与识别速度
          const maxSide = 1024;
          const scale = Math.min(1, maxSide / Math.max(img.width, img.height));
          canvas.width = Math.round(img.width * scale);
          canvas.height = Math.round(img.height * scale);
          ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
          try {
            const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
            resolve(dataUrl.split(',')[1]);
          } catch (e) {
            reject(new Error('画布导出失败，请重试'));
          }
        };
        img.onerror = () => reject(new Error('图片加载失败，请重新选择图片'));
        img.src = filePath;
      });
  });

Page({
  data: {
    isEdit: false,
    editId: '',
    categories: config.categories,
    categoryIndex: 0,
    images: [],
    name: '',
    summary: '',
    story: '',
    material: '',
    size: '',
    price: '',
    submitting: false,
    aiLoading: false
  },

  onLoad(options) {
    const user = db.getCurrentUser();
    if (!user) {
      wx.showModal({
        title: '提示',
        content: '发布作品前请先登录',
        confirmText: '去登录',
        success: (res) => {
          if (res.confirm) wx.navigateTo({ url: '/pages/login/login' });
          else wx.navigateBack();
        }
      });
      return;
    }

    if (options.id) {
      this.setData({ isEdit: true, editId: options.id });
      this.loadProduct(options.id, user);
    }
  },

  async loadProduct(id, user) {
    const product = await db.getProduct(id);
    if (!product) {
      wx.showToast({ title: '作品不存在', icon: 'none' });
      setTimeout(() => wx.navigateBack(), 1200);
      return;
    }
    if (product.authorId !== user._id && user.role !== 'admin') {
      wx.showToast({ title: '无权编辑该作品', icon: 'none' });
      setTimeout(() => wx.navigateBack(), 1200);
      return;
    }
    const categoryIndex = config.categories.findIndex(item => item.id === product.categoryId);
    this.setData({
      name: product.name,
      summary: product.summary,
      story: product.story || '',
      material: product.material || '',
      size: product.size || '',
      price: product.price > 0 ? String(product.price) : '',
      images: product.images || [product.cover],
      categoryIndex: categoryIndex > -1 ? categoryIndex : 0
    });
  },

  onFieldInput(e) {
    const field = e.currentTarget.dataset.field;
    this.setData({ [field]: e.detail.value });
  },

  // AI 根据第一张图片生成一句话简介
  async onAiDescribe() {
    if (this.data.aiLoading) return;
    if (!this.data.images.length) {
      wx.showToast({ title: '请先上传作品图片', icon: 'none' });
      return;
    }
    this.setData({ aiLoading: true });
    wx.showLoading({ title: 'AI 识别图片中…' });
    try {
      // 开发者工具部分版本 FileSystemManager 读不到 http://tmp 临时路径，
      // 统一走隐藏 Canvas 绘图导出 base64，工具与真机都可靠
      const base64 = await canvasToBase64(this.data.images[0]);
      const text = await assistant.describeBase64(base64);
      this.setData({ summary: text });
      wx.hideLoading();
      wx.showToast({ title: '已生成，可自行修改', icon: 'none' });
    } catch (e) {
      wx.hideLoading();
      console.error('[AI 描述] 完整错误信息：', e && e.message);
      wx.showToast({ title: e.message || '生成失败', icon: 'none' });
    } finally {
      this.setData({ aiLoading: false });
    }
  },

  onCategoryChange(e) {
    this.setData({ categoryIndex: Number(e.detail.value) });
  },

  async chooseImages() {
    const remain = 9 - this.data.images.length;
    if (remain <= 0) {
      wx.showToast({ title: '最多上传 9 张图片', icon: 'none' });
      return;
    }
    // 用 chooseImage 而非 chooseMedia：开发者工具中 chooseMedia 返回的 http://tmp 路径
    // FileSystemManager 读不到（not found），chooseImage 返回的 wxfile:// 路径可正常读取
    wx.chooseImage({
      count: remain,
      sizeType: ['compressed'],
      sourceType: ['album', 'camera'],
      success: async (res) => {
        const paths = res.tempFilePaths;
        try {
          wx.showLoading({ title: '上传中' });
          const urls = db.useCloud()
            ? await Promise.all(
                paths.map((path, index) =>
                  wx.cloud
                    .uploadFile({
                      cloudPath: `products/${Date.now()}-${index}.jpg`,
                      filePath: path
                    })
                    .then(r => r.fileID)
                )
              )
            : paths;
          this.setData({ images: this.data.images.concat(urls) });
          wx.hideLoading();
        } catch (e) {
          wx.hideLoading();
          wx.showToast({ title: '图片上传失败', icon: 'none' });
        }
      }
    });
  },

  removeImage(e) {
    const index = Number(e.currentTarget.dataset.index);
    const images = this.data.images.slice();
    images.splice(index, 1);
    this.setData({ images });
  },

  async onSubmit() {
    const { name, summary, story, material, size, images, categoryIndex, isEdit, editId } = this.data;
    const user = db.getCurrentUser();

    if (!user) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }
    if (!name.trim()) {
      wx.showToast({ title: '请填写作品名称', icon: 'none' });
      return;
    }
    if (!summary.trim()) {
      wx.showToast({ title: '请填写一句话简介', icon: 'none' });
      return;
    }
    if (!images.length) {
      wx.showToast({ title: '请至少上传一张图片', icon: 'none' });
      return;
    }
    // 定价选填：填写则需为 0~9999 的有效金额
    let priceNum = 0;
    if (String(this.data.price).trim()) {
      priceNum = Math.round(parseFloat(this.data.price) * 100) / 100;
      if (!(priceNum > 0) || priceNum > 9999) {
        wx.showToast({ title: '定价需为 0~9999 的金额', icon: 'none' });
        return;
      }
    }
    if (this.data.submitting) return;

    const payload = {
      name: name.trim(),
      summary: summary.trim(),
      story: story.trim(),
      material: material.trim(),
      size: size.trim(),
      price: priceNum,
      categoryId: config.categories[categoryIndex].id,
      images,
      cover: images[0],
      tags: ['新品'],
      status: 'pending',
      rejectReason: ''
    };

    this.setData({ submitting: true });
    try {
      if (isEdit) {
        await db.updateProduct(editId, payload);
      } else {
        await db.addProduct(payload, user);
      }
      // 首次发布后升级为作者身份
      if (user.role === 'user') {
        const updated = await db.updateUser(user._id, { role: 'author' });
        db.setCurrentUser(updated);
      }
      this.setData({ submitting: false });
      wx.showModal({
        title: isEdit ? '已重新提交' : '发布成功',
        content: '作品已提交，管理员审核通过后将公开展示。',
        showCancel: false,
        success: () => {
          wx.redirectTo({ url: '/pages/my-works/my-works' });
        }
      });
    } catch (e) {
      this.setData({ submitting: false });
      wx.showToast({ title: e.message || '提交失败', icon: 'none' });
    }
  }
});
