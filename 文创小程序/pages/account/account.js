// pages/account/account.js - 账号安全（账号 / 角色 / 密码 / 手机号 / 邮箱）
const db = require('../../utils/db');

const roleMap = {
  admin: '管理员',
  author: '作者',
  user: '普通用户'
};

Page({
  data: {
    user: null,
    accountText: '',
    roleText: '',
    phoneText: '',
    emailText: '',
    // 通用编辑弹窗（账号 / 手机号 / 邮箱）
    editVisible: false,
    editField: '',
    editTitle: '',
    editSub: '',
    editPlaceholder: '',
    editValue: '',
    // 修改密码弹窗
    pwdVisible: false,
    hasPassword: false,
    pwdShowOld: false,
    pwdShowNew: false,
    pwdShowConfirm: false,
    pwdOld: '',
    pwdNew: '',
    pwdConfirm: ''
  },

  onShow() {
    this.load();
  },

  load() {
    const user = db.getCurrentUser();
    if (!user) {
      wx.redirectTo({ url: '/pages/login/login' });
      return;
    }
    this.setData({
      user,
      accountText: user.account || '未设置',
      roleText: roleMap[user.role] || '普通用户',
      phoneText: user.phone
        ? user.phone.replace(/^(\d{3})\d{4}(\d{4})$/, '$1****$2')
        : '未绑定',
      emailText: user.email || '未绑定'
    });
  },

  // 同步用户表与当前登录缓存
  applyUpdate(updated) {
    db.setCurrentUser(updated);
    this.load();
  },

  // 修改登录账号
  onChangeAccount() {
    if (!this.data.user.account) {
      wx.showToast({ title: '微信注册用户暂不支持设置账号', icon: 'none' });
      return;
    }
    this.setData({
      editVisible: true,
      editField: 'account',
      editTitle: '修改账号',
      editSub: '修改后可使用新账号登录',
      editPlaceholder: '请输入新的登录账号',
      editValue: this.data.user.account || ''
    });
  },

  // 修改账号角色
  onChangeRole() {
    const user = this.data.user;
    const roles = ['admin', 'author', 'user'];
    wx.showActionSheet({
      itemList: roles.map(role => roleMap[role]),
      success: async (res) => {
        const role = roles[res.tapIndex];
        if (role === user.role) return;
        try {
          const updated = await db.updateRole(user._id, role);
          this.applyUpdate(updated);
          wx.showToast({ title: `已切换为${roleMap[role]}`, icon: 'none' });
        } catch (e) {
          wx.showToast({ title: e.message || '修改失败', icon: 'none' });
        }
      }
    });
  },

  // ---------- 通用编辑弹窗 ----------
  onBindPhone() {
    const user = this.data.user;
    this.setData({
      editVisible: true,
      editField: 'phone',
      editTitle: user.phone ? '更换手机号' : '绑定手机号',
      editSub: '绑定后可使用手机号登录',
      editPlaceholder: '请输入 11 位手机号',
      editValue: user.phone || ''
    });
  },

  onBindEmail() {
    const user = this.data.user;
    this.setData({
      editVisible: true,
      editField: 'email',
      editTitle: user.email ? '更换邮箱' : '绑定邮箱',
      editSub: '用于接收审核结果与回复通知',
      editPlaceholder: '请输入常用邮箱，如 name@example.com',
      editValue: user.email || ''
    });
  },

  onEditInput(e) {
    this.setData({ editValue: e.detail.value });
  },

  closeEditModal() {
    this.setData({ editVisible: false });
  },

  noop() {},

  async confirmEdit() {
    const { user, editField, editValue } = this.data;
    try {
      let updated;
      if (editField === 'account') {
        updated = await db.updateAccount(user._id, editValue);
        wx.showToast({ title: '账号已修改', icon: 'success' });
      } else if (editField === 'phone') {
        updated = await db.bindPhone(user._id, editValue);
        wx.showToast({ title: '手机号已绑定', icon: 'success' });
      } else if (editField === 'email') {
        updated = await db.bindEmail(user._id, editValue);
        wx.showToast({ title: '邮箱已绑定', icon: 'success' });
      }
      this.setData({ editVisible: false });
      this.applyUpdate(updated);
    } catch (e) {
      wx.showToast({ title: e.message || '修改失败', icon: 'none' });
    }
  },

  // ---------- 修改密码 ----------
  onChangePassword() {
    this.setData({
      pwdVisible: true,
      hasPassword: !!this.data.user.password,
      pwdShowOld: false,
      pwdShowNew: false,
      pwdShowConfirm: false,
      pwdOld: '',
      pwdNew: '',
      pwdConfirm: ''
    });
  },

  closePwdModal() {
    this.setData({ pwdVisible: false });
  },

  togglePwdOld() {
    this.setData({ pwdShowOld: !this.data.pwdShowOld });
  },

  togglePwdNew() {
    this.setData({ pwdShowNew: !this.data.pwdShowNew });
  },

  togglePwdConfirm() {
    this.setData({ pwdShowConfirm: !this.data.pwdShowConfirm });
  },

  onPwdOldInput(e) {
    this.setData({ pwdOld: e.detail.value });
  },

  onPwdNewInput(e) {
    this.setData({ pwdNew: e.detail.value });
  },

  onPwdConfirmInput(e) {
    this.setData({ pwdConfirm: e.detail.value });
  },

  async confirmChangePassword() {
    const { user, hasPassword, pwdOld, pwdNew, pwdConfirm } = this.data;
    const newPwd = pwdNew.trim();
    if (hasPassword && !pwdOld.trim()) {
      wx.showToast({ title: '请输入原密码', icon: 'none' });
      return;
    }
    if (newPwd !== pwdConfirm.trim()) {
      wx.showToast({ title: '两次输入的新密码不一致', icon: 'none' });
      return;
    }
    try {
      const updated = await db.changePassword(user._id, hasPassword ? pwdOld : '', newPwd);
      // 真实性校验：用新密码重新登录一次，确认密码确实已生效
      const key = user.account || user.phone;
      if (key) {
        const login = await db.loginByAccount(key, pwdNew.trim());
        if (!login) {
          wx.showToast({ title: '密码修改未生效，请重试', icon: 'none' });
          return;
        }
        db.setCurrentUser(login);
      }
      this.setData({ pwdVisible: false, pwdOld: '', pwdNew: '', pwdConfirm: '' });
      wx.showToast({ title: '密码已修改，下次请用新密码登录', icon: 'none', duration: 2200 });
      this.load();
    } catch (e) {
      wx.showToast({ title: e.message || '修改失败', icon: 'none' });
    }
  }
});
