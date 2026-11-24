// utils/config.js - 项目配置
// 云开发环境 ID：填入自己的云环境 ID 后，项目会自动切换到云数据库；
// 留空时使用本地缓存模拟数据，保证无需云环境也能直接运行预览。
module.exports = {
  envId: '',

  // 云数据库集合名
  collections: {
    products: 'products',
    messages: 'messages',
    favorites: 'favorites',
    carts: 'carts',
    orders: 'orders',
    users: 'users',
    feedbacks: 'feedbacks'
  },

  // 内置管理员账号（本地模拟模式下使用）
  admin: {
    account: 'admin',
    password: '123456'
  },

  // 作品审核状态
  statusMap: {
    pending: '待审核',
    approved: '已上架',
    rejected: '已驳回',
    offline: '已下架'
  },

  // 作品分类（与云数据库 categoryId 对应）
  categories: [
    { id: 1, name: '校园文具', icon: '✒️' },
    { id: 2, name: '服饰周边', icon: '👕' },
    { id: 3, name: '生活好物', icon: '☕' },
    { id: 4, name: '数字文创', icon: '💻' },
    { id: 5, name: '纪念收藏', icon: '🎓' },
    { id: 6, name: '手作艺术', icon: '🎨' }
  ],

  // 文创助手联网搜索配置（OpenAI 兼容接口，留空 apiKey 则仅用本地问答）
  // 示例（智谱免费模型）：apiUrl https://open.bigmodel.cn/api/paas/v4/chat/completions  model glm-4-flash
  // 示例（DeepSeek）  ：apiUrl https://api.deepseek.com/chat/completions        model deepseek-chat
  assistant: {
    enabled: true,
    apiUrl: 'https://open.bigmodel.cn/api/paas/v4/chat/completions',
    apiKey: '',
    model: 'glm-4-flash',
    // 视觉模型（发布页「AI 根据图片描述」用）：默认智谱 glm-4v-flash 免费，需单独填 Key
    visionApiUrl: 'https://open.bigmodel.cn/api/paas/v4/chat/completions',
    visionApiKey: '',
    visionModel: 'glm-4v-flash'
  }
};
