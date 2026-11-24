// app.js - 校园文创产品展示小程序入口
const config = require('./utils/config');

App({
  globalData: {
    userInfo: null,
    listParam: null, // 首页跳转列表页时传递的分类/搜索参数
    cloudEnabled: false
  },

  onLaunch() {
    // 配置了云环境 ID 时初始化云开发
    if (config.envId && wx.cloud) {
      wx.cloud.init({
        env: config.envId,
        traceUser: true
      });
      this.globalData.cloudEnabled = true;
    }

    // 恢复登录状态
    const user = wx.getStorageSync('currentUser');
    if (user) {
      this.globalData.userInfo = user;
    }
  }
});
