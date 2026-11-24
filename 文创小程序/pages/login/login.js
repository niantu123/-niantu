// pages/login/login.js - 登录注册
const db = require('../../utils/db');

Page({
  data: {
    mode: 'wechat', // wechat | phone | register
    phone: '',
    password: '',
    account: '',
    pwdShow: false,
    loading: false
  },

  switchMode(e) {
    this.setData({ mode: e.currentTarget.dataset.mode });
  },

  // 密码显示 / 隐藏
  togglePwd() {
    this.setData({ pwdShow: !this.data.pwdShow });
  },

  onPhoneInput(e) {
    this.setData({ phone: e.detail.value });
  },

  onPasswordInput(e) {
    this.setData({ password: e.detail.value });
  },

  onAccountInput(e) {
    this.setData({ account: e.detail.value });
  },

  // 微信授权登录
  onWechatLogin() {
    if (this.data.loading) return;
    wx.getUserProfile({
      desc: '用于展示作者昵称与头像',
      success: async (res) => {
        this.setData({ loading: true });
        try {
          const user = await db.loginByWechat({
            nickName: res.userInfo.nickName,
            avatarUrl: res.userInfo.avatarUrl
          });
          this.finishLogin(user);
        } catch (e) {
          this.setData({ loading: false });
          wx.showToast({ title: e.message || '登录失败', icon: 'none' });
        }
      },
      fail: () => {
        wx.showToast({ title: '已取消授权', icon: 'none' });
      }
    });
  },

  // 账号登录（手机号或管理员账号）
  async onPhoneLogin() {
    const { phone, password } = this.data;
    if (!phone || !password) {
      wx.showToast({ title: '请输入手机号或账号和密码', icon: 'none' });
      return;
    }
    this.setData({ loading: true });
    try {
      const user = await db.loginByAccount(phone.trim(), password);
      this.finishLogin(user);
    } catch (e) {
      this.setData({ loading: false });
      wx.showToast({ title: e.message || '登录失败', icon: 'none' });
    }
  },

  // 注册
  async onRegister() {
    const { phone, password, account } = this.data;
    this.setData({ loading: true });
    try {
      const user = await db.registerByPhone({
        phone: phone.trim(),
        password,
        account: account.trim()
      });
      this.finishLogin(user);
    } catch (e) {
      this.setData({ loading: false });
      wx.showToast({ title: e.message || '注册失败', icon: 'none' });
    }
  },

  fillAdmin() {
    this.setData({ mode: 'phone', phone: 'admin', password: '123456' });
  },

  finishLogin(user) {
    db.setCurrentUser(user);
    this.setData({ loading: false });
    wx.showToast({ title: '登录成功', icon: 'success' });
    setTimeout(() => {
      const pages = getCurrentPages();
      if (pages.length > 1) {
        wx.navigateBack();
      } else {
        wx.switchTab({ url: '/pages/mine/mine' });
      }
    }, 600);
  }
});
