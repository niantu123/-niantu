// utils/db.js - 数据访问层
// 云开发已配置时读写云数据库；未配置时自动使用本地缓存模拟数据，
// 保证项目在未开通云环境的情况下也能完整演示。

const config = require('./config');
const seed = require('./data');

const useCloud = () => !!(config.envId && wx.cloud && wx.cloud.database);

// ---------------- 本地缓存工具 ----------------
const ls = (key, def) => {
  try {
    const value = wx.getStorageSync(key);
    return value === '' || value === null || value === undefined ? def : value;
  } catch (e) {
    return def;
  }
};

const ss = (key, value) => {
  try {
    wx.setStorageSync(key, value);
  } catch (e) {
    console.warn('本地缓存写入失败', e);
  }
};

const clone = (data) => JSON.parse(JSON.stringify(data));

const genId = (prefix) => `${prefix}${Date.now()}${Math.floor(Math.random() * 1000)}`;

// 作品名称末尾的审核状态括号，如「（待审核）」「（已上架）」
const STATUS_SUFFIX = /（(待审核|已上架|已驳回|已下架)）\s*$/;

// 首次运行时写入演示数据
const ensureSeed = () => {
  if (!ls('mock_products', null)) ss('mock_products', clone(seed.productList));
  if (!ls('mock_users', null)) ss('mock_users', clone(seed.userList));
  if (!ls('mock_messages', null)) ss('mock_messages', clone(seed.messageList));

  // 种子版本升级：内置文案/图片更新后，把旧缓存中的内置数据刷新为最新种子（用户自建数据保留）
  if (ls('mock_seed_version', 0) !== seed.SEED_VERSION) {
    const seedIds = new Set(seed.productList.map(p => p._id));
    const products = ls('mock_products', []).map(item =>
      seedIds.has(item._id) ? clone(seed.productList.find(p => p._id === item._id)) : item
    );
    ss('mock_products', products);

    const builtInUserIds = new Set(seed.userList.map(u => u._id));
    const users = ls('mock_users', []).map(item =>
      builtInUserIds.has(item._id) ? clone(seed.userList.find(u => u._id === item._id)) : item
    );
    if (!users.some(item => item._id === 'u_admin')) users.unshift(clone(seed.userList[0]));
    ss('mock_users', users);

    ss('mock_messages', ls('mock_messages', []));
    ss('mock_seed_version', seed.SEED_VERSION);
  }

  // 兼容旧缓存：校正内置管理员账号，补齐账号与邮箱字段
  const users = ls('mock_users', []);
  let changed = false;
  if (!users.some(item => item._id === 'u_admin')) {
    users.unshift(clone(seed.userList[0]));
    changed = true;
  }
  users.forEach(item => {
    if (item._id === 'u_admin') {
      if (item.account !== 'admin') {
        item.account = 'admin';
        changed = true;
      }
      // 仅首次兼容旧缓存时校正管理员密码（pwdMigrated 标记后不再强制重置，尊重用户修改）
      if (!item.pwdMigrated) {
        if (item.password !== '123456') {
          item.password = '123456';
          changed = true;
        }
        item.pwdMigrated = true;
        changed = true;
      }
    }
    if (typeof item.account !== 'string') {
      item.account = '';
      changed = true;
    }
    if (typeof item.email !== 'string') {
      item.email = '';
      changed = true;
    }
    // 旧缓存中的内置用户头像仍指向网络图片，统一同步为本地头像
    const seedUser = seed.userList.find(u => u._id === item._id);
    if (seedUser && /^https?:/.test(item.avatarUrl || '') && item.avatarUrl !== seedUser.avatarUrl) {
      item.avatarUrl = seedUser.avatarUrl;
      changed = true;
    }
  });
  if (changed) ss('mock_users', users);

  // 兼容旧缓存：去掉作品名称末尾的审核状态括号，状态改由标签展示
  const products = ls('mock_products', []);
  let productChanged = false;
  products.forEach(item => {
    if (typeof item.name === 'string' && STATUS_SUFFIX.test(item.name)) {
      item.name = item.name.replace(STATUS_SUFFIX, '');
      productChanged = true;
    }
    // 旧缓存中的内置作品仍指向网络图片，统一同步为本地演示图片（不影响用户改过的本地封面）
    const seedItem = seed.productList.find(p => p._id === item._id);
    if (seedItem && /^https?:/.test(item.cover || '') && item.cover !== seedItem.cover) {
      item.cover = seedItem.cover;
      item.images = clone(seedItem.images);
      productChanged = true;
    }
  });
  if (productChanged) ss('mock_products', products);
};

const localProducts = () => {
  ensureSeed();
  return ls('mock_products', []);
};
const localUsers = () => {
  ensureSeed();
  return ls('mock_users', []);
};
const localMessages = () => {
  ensureSeed();
  return ls('mock_messages', []);
};

const cloudDb = () => wx.cloud.database();

// ---------------- 登录与用户 ----------------
// 获取微信 openid（云环境通过登录云函数获取）
const getOpenId = () => {
  if (!useCloud()) {
    return Promise.resolve('local_wechat_user');
  }
  return wx.cloud
    .callFunction({ name: 'login' })
    .then(res => res.result.openid);
};

// 微信授权登录
const loginByWechat = async (profile) => {
  const nickName = (profile && profile.nickName) || '微信用户';
  const avatarUrl = (profile && profile.avatarUrl) || '';

  if (!useCloud()) {
    const users = localUsers();
    let user = users.find(item => item.openid === 'local_wechat_user');
    if (!user) {
      user = {
        _id: genId('u'),
        openid: 'local_wechat_user',
        account: '',
        phone: '',
        email: '',
        password: '',
        nickName,
        avatarUrl,
        role: 'user',
        createdAt: Date.now()
      };
      users.push(user);
      ss('mock_users', users);
    } else {
      user.nickName = nickName;
      user.avatarUrl = avatarUrl || user.avatarUrl;
      ss('mock_users', users);
    }
    return clone(user);
  }

  const openid = await getOpenId();
  const res = await cloudDb().collection(config.collections.users).where({ openid }).get();
  if (res.data.length > 0) {
    return res.data[0];
  }
  const data = {
    openid,
    account: '',
    phone: '',
    email: '',
    password: '',
    nickName,
    avatarUrl,
    role: 'user',
    createdAt: Date.now()
  };
  const addRes = await cloudDb().collection(config.collections.users).add({ data });
  return { _id: addRes._id, ...data };
};

// 账号登录：支持手机号或自定义账号
const loginByAccount = async (identifier, password) => {
  const key = (identifier || '').trim();
  if (!key) throw new Error('请输入手机号或账号');

  if (!useCloud()) {
    const user = localUsers().find(item => item.phone === key || item.account === key);
    if (!user) throw new Error('账号不存在，请先注册');
    if (user.password !== password) throw new Error('密码错误');
    return clone(user);
  }

  const collection = cloudDb().collection(config.collections.users);
  let res = await collection.where({ phone: key }).get();
  if (!res.data.length) {
    res = await collection.where({ account: key }).get();
  }
  if (!res.data.length) throw new Error('账号或密码错误');
  if (res.data[0].password !== password) throw new Error('密码错误');
  return res.data[0];
};

// 手机号注册：注册时设置登录账号，昵称默认与账号一致
const registerByPhone = async ({ phone, password, account: rawAccount }) => {
  const account = (rawAccount || '').trim();
  if (!phone || !/^1\d{10}$/.test(phone)) throw new Error('请输入正确的手机号');
  if (!password || password.length < 6) throw new Error('密码至少 6 位');
  if (!account) throw new Error('请输入账号');

  if (!useCloud()) {
    const users = localUsers();
    if (users.some(item => item.phone === phone)) throw new Error('该手机号已注册');
    if (users.some(item => item.account === account)) throw new Error('该账号已被使用');
    const user = {
      _id: genId('u'),
      openid: '',
      account,
      phone,
      email: '',
      password,
      nickName: account,
      avatarUrl: '',
      role: 'user',
      createdAt: Date.now()
    };
    users.push(user);
    ss('mock_users', users);
    return clone(user);
  }

  const db = cloudDb();
  const exist = await db.collection(config.collections.users).where({ phone }).get();
  if (exist.data.length) throw new Error('该手机号已注册');
  const existAccount = await db.collection(config.collections.users).where({ account }).get();
  if (existAccount.data.length) throw new Error('该账号已被使用');
  const data = {
    openid: '',
    account,
    phone,
    email: '',
    password,
    nickName: account,
    avatarUrl: '',
    role: 'user',
    createdAt: Date.now()
  };
  const addRes = await db.collection(config.collections.users).add({ data });
  return { _id: addRes._id, ...data };
};

// 获取全部用户（管理员）
const getUsers = async () => {
  if (!useCloud()) {
    return localUsers().slice().sort((a, b) => b.createdAt - a.createdAt);
  }
  const res = await cloudDb().collection(config.collections.users).orderBy('createdAt', 'desc').get();
  return res.data;
};

// 更新用户信息（同步登录缓存，避免缓存里的密码等字段过期）
const updateUser = async (userId, data) => {
  if (!useCloud()) {
    const users = localUsers().map(item => (item._id === userId ? { ...item, ...data } : item));
    ss('mock_users', users);
    const updated = users.find(item => item._id === userId);
    const current = getCurrentUser();
    if (updated && current && current._id === userId) setCurrentUser(updated);
    return updated;
  }
  await cloudDb().collection(config.collections.users).doc(userId).update({ data });
  const current = getCurrentUser();
  if (current && current._id === userId) setCurrentUser({ ...current, ...data });
  return { _id: userId, ...data };
};

// 绑定手机号
const bindPhone = async (userId, phone) => {
  const value = (phone || '').trim();
  if (!userId) throw new Error('请先登录');
  if (!/^1\d{10}$/.test(value)) throw new Error('请输入 11 位手机号');

  if (!useCloud()) {
    const users = localUsers();
    if (users.some(item => item._id !== userId && item.phone === value)) {
      throw new Error('该手机号已被其他账号绑定');
    }
    return updateUser(userId, { phone: value });
  }

  const res = await cloudDb().collection(config.collections.users).where({ phone: value }).get();
  if (res.data.some(item => item._id !== userId)) throw new Error('该手机号已被其他账号绑定');
  return updateUser(userId, { phone: value });
};

// 绑定邮箱
const bindEmail = async (userId, email) => {
  const value = (email || '').trim();
  if (!userId) throw new Error('请先登录');
  if (!/^[\w.+-]+@[\w-]+(\.[\w-]+)+$/.test(value)) throw new Error('请输入正确的邮箱');

  if (!useCloud()) {
    const users = localUsers();
    if (users.some(item => item._id !== userId && item.email === value)) {
      throw new Error('该邮箱已被其他账号绑定');
    }
    return updateUser(userId, { email: value });
  }
  const res = await cloudDb().collection(config.collections.users).where({ email: value }).get();
  if (res.data.some(item => item._id !== userId)) throw new Error('该邮箱已被其他账号绑定');
  return updateUser(userId, { email: value });
};

// 修改登录账号
const updateAccount = async (userId, account) => {
  const value = (account || '').trim();
  if (!userId) throw new Error('请先登录');
  if (!value) throw new Error('请输入账号');

  if (!useCloud()) {
    const users = localUsers();
    if (users.some(item => item._id !== userId && item.account === value)) {
      throw new Error('该账号已被使用');
    }
    return updateUser(userId, { account: value });
  }
  const res = await cloudDb().collection(config.collections.users).where({ account: value }).get();
  if (res.data.some(item => item._id !== userId)) throw new Error('该账号已被使用');
  return updateUser(userId, { account: value });
};

// 修改账号角色
const roleList = ['admin', 'author', 'user'];
const updateRole = async (userId, role) => {
  if (!userId) throw new Error('请先登录');
  if (roleList.indexOf(role) === -1) throw new Error('角色不合法');
  return updateUser(userId, { role });
};

// 管理员编辑用户资料（昵称/账号/手机号/邮箱/角色）
const adminUpdateUser = async (userId, data) => {
  const admin = getCurrentUser();
  if (!admin || admin.role !== 'admin') throw new Error('仅管理员可操作');

  const patch = {};
  if (data.nickName !== undefined) {
    const v = String(data.nickName).trim();
    if (!v) throw new Error('昵称不能为空');
    patch.nickName = v;
  }
  if (data.account !== undefined) {
    const v = String(data.account).trim();
    if (!v) throw new Error('账号不能为空');
    const users = useCloud()
      ? (await cloudDb().collection(config.collections.users).where({ account: v }).get()).data
      : localUsers();
    if (users.some(item => item._id !== userId && item.account === v)) {
      throw new Error('该账号已被使用');
    }
    patch.account = v;
  }
  if (data.phone !== undefined) {
    const v = String(data.phone).trim();
    if (v && !/^1\d{10}$/.test(v)) throw new Error('请输入正确的手机号');
    patch.phone = v;
  }
  if (data.email !== undefined) {
    patch.email = String(data.email).trim();
  }
  if (data.role !== undefined) {
    if (roleList.indexOf(data.role) === -1) throw new Error('角色不合法');
    if (userId === 'u_admin' && data.role !== 'admin') throw new Error('不能修改内置管理员的角色');
    patch.role = data.role;
  }
  if (!Object.keys(patch).length) throw new Error('没有需要修改的内容');
  return updateUser(userId, patch);
};

// 管理员删除用户：不能删除自己与内置管理员
const deleteUser = async (userId) => {
  const admin = getCurrentUser();
  if (!admin || admin.role !== 'admin') throw new Error('仅管理员可操作');
  if (userId === admin._id) throw new Error('不能删除当前登录的账号');
  if (userId === 'u_admin') throw new Error('内置管理员不可删除');
  if (!useCloud()) {
    ss('mock_users', localUsers().filter(item => item._id !== userId));
    return true;
  }
  await cloudDb().collection(config.collections.users).doc(userId).remove();
  return true;
};

// 修改密码：有原密码需核对；从未设置过密码（如微信授权用户）可直接设置
const changePassword = async (userId, oldPassword, newPassword) => {
  if (!userId) throw new Error('请先登录');
  const oldPwd = (oldPassword || '').trim();
  const newPwd = (newPassword || '').trim();
  if (!newPwd || newPwd.length < 6) throw new Error('新密码至少 6 位');
  if (oldPwd && oldPwd === newPwd) throw new Error('新密码不能与原密码相同');

  if (!useCloud()) {
    const users = localUsers();
    const target = users.find(item => item._id === userId);
    if (!target) throw new Error('用户不存在');
    if (target.password && target.password !== oldPwd) throw new Error('原密码错误');
    return updateUser(userId, { password: newPwd });
  }

  const res = await cloudDb().collection(config.collections.users).doc(userId).get();
  const savedPassword = res.data.password;
  if (savedPassword && savedPassword !== oldPwd) throw new Error('原密码错误');
  return updateUser(userId, { password: newPwd });
};

// ---------------- 作品 ----------------
// 查询作品：status 为空表示不过滤（作者端/管理端）
const getProducts = async (filter = {}) => {
  const { status, categoryId, tag, keyword, authorId } = filter;

  if (useCloud()) {
    const where = {};
    if (status) where.status = status;
    if (categoryId) where.categoryId = categoryId;
    if (authorId) where.authorId = authorId;
    let query = cloudDb().collection(config.collections.products);
    if (Object.keys(where).length) query = query.where(where);
    const res = await query.orderBy('createdAt', 'desc').get();
    let list = res.data;
    if (tag) list = list.filter(item => (item.tags || []).indexOf(tag) > -1);
    if (keyword) list = list.filter(item => item.name.indexOf(keyword) > -1 || item.summary.indexOf(keyword) > -1);
    return list;
  }

  let list = localProducts();
  if (status) list = list.filter(item => item.status === status);
  if (categoryId) list = list.filter(item => item.categoryId === Number(categoryId));
  if (authorId) list = list.filter(item => item.authorId === authorId);
  if (tag) list = list.filter(item => (item.tags || []).indexOf(tag) > -1);
  if (keyword) {
    list = list.filter(
      item => item.name.indexOf(keyword) > -1 || item.summary.indexOf(keyword) > -1
    );
  }
  return clone(list.sort((a, b) => b.createdAt - a.createdAt));
};

// 获取单个作品
const getProduct = async (id) => {
  if (!id) return null;
  if (!useCloud()) {
    return clone(localProducts().find(item => item._id === id) || null);
  }
  try {
    const res = await cloudDb().collection(config.collections.products).doc(id).get();
    return res.data;
  } catch (e) {
    return null;
  }
};

// 新增作品（作者发布，默认待审核）
const addProduct = async (payload, user) => {
  const data = {
    ...payload,
    status: payload.status || 'pending',
    authorId: user ? user._id : '',
    authorName: user ? user.nickName : '匿名作者',
    views: 0,
    createdAt: Date.now()
  };

  if (!useCloud()) {
    const list = localProducts();
    const product = { _id: genId('p'), ...data };
    list.unshift(product);
    ss('mock_products', list);
    return clone(product);
  }

  const res = await cloudDb().collection(config.collections.products).add({ data });
  return { _id: res._id, ...data };
};

// 更新作品
const updateProduct = async (id, data) => {
  if (!useCloud()) {
    const list = localProducts().map(item => (item._id === id ? { ...item, ...data } : item));
    ss('mock_products', list);
    return clone(list.find(item => item._id === id));
  }
  await cloudDb().collection(config.collections.products).doc(id).update({ data });
  return { _id: id, ...data };
};

// 删除作品
const deleteProduct = async (id) => {
  if (!useCloud()) {
    ss('mock_products', localProducts().filter(item => item._id !== id));
    return true;
  }
  await cloudDb().collection(config.collections.products).doc(id).remove();
  return true;
};

// 审核作品
const setProductStatus = async (id, status, rejectReason) => {
  const data = {
    status,
    rejectReason: status === 'rejected' ? rejectReason || '不符合展示规范' : ''
  };
  // 上架时去掉名称中的状态后缀，展品内不再显示
  if (status === 'approved') {
    const product = await getProduct(id);
    if (product && STATUS_SUFFIX.test(product.name || '')) {
      data.name = product.name.replace(STATUS_SUFFIX, '');
    }
  }
  return updateProduct(id, data);
};

// ---------------- 收藏 ----------------
const favoriteKey = (userId) => `favorites_${userId || 'guest'}`;

const getFavoriteIds = async (userId) => {
  if (!userId) return [];
  if (!useCloud()) {
    return ls(favoriteKey(userId), []).slice();
  }
  const res = await cloudDb()
    .collection(config.collections.favorites)
    .where({ userId })
    .get();
  return res.data.map(item => item.productId);
};

// 切换收藏，返回收藏后的状态
const toggleFavorite = async (productId, userId) => {
  if (!userId) throw new Error('请先登录');
  if (!useCloud()) {
    const ids = ls(favoriteKey(userId), []);
    const index = ids.indexOf(productId);
    let favorited;
    if (index > -1) {
      ids.splice(index, 1);
      favorited = false;
    } else {
      ids.push(productId);
      favorited = true;
    }
    ss(favoriteKey(userId), ids);
    return favorited;
  }

  const db = cloudDb();
  const res = await db
    .collection(config.collections.favorites)
    .where({ userId, productId })
    .get();
  if (res.data.length) {
    await db.collection(config.collections.favorites).doc(res.data[0]._id).remove();
    return false;
  }
  await db.collection(config.collections.favorites).add({
    data: { userId, productId, createdAt: Date.now() }
  });
  return true;
};

const getFavoriteProducts = async (userId) => {
  const ids = await getFavoriteIds(userId);
  if (!ids.length) return [];
  const all = await getProducts({});
  // 仅展示仍在架的作品，下架/待审核作品不再出现在收藏列表
  return all.filter(item => ids.indexOf(item._id) > -1 && item.status === 'approved');
};

// ---------------- 商店：购物车 ----------------
const cartKey = (userId) => `cart_${userId || 'guest'}`;

// 读取购物车并关联作品信息；已下架/已删除/未定价的作品自动过滤
const getCart = async (userId) => {
  if (!userId) return [];
  let items;
  if (!useCloud()) {
    items = ls(cartKey(userId), []).slice();
  } else {
    const res = await cloudDb().collection(config.collections.carts).where({ userId }).get();
    items = res.data.map(item => ({ productId: item.productId, qty: item.qty }));
  }
  if (!items.length) return [];
  const all = await getProducts({});
  const map = {};
  all.forEach(p => { map[p._id] = p; });
  return items
    .map(item => {
      const p = map[item.productId];
      if (!p || p.status !== 'approved' || !(p.price > 0)) return null;
      return { productId: p._id, qty: item.qty, name: p.name, cover: p.cover, price: p.price };
    })
    .filter(Boolean);
};

// 购物车商品总件数（用于 tab/页面角标）
const getCartCount = async (userId) => {
  const items = await getCart(userId);
  return items.reduce((sum, item) => sum + item.qty, 0);
};

// 加入购物车：仅已上架且已定价的作品可购买
const addToCart = async (productId, qty, user) => {
  if (!user) throw new Error('请先登录');
  const n = Math.max(1, Math.min(99, qty || 1));
  const product = await getProduct(productId);
  if (!product || product.status !== 'approved') throw new Error('该作品当前不可购买');
  if (!(product.price > 0)) throw new Error('该作品尚未定价，无法购买');

  if (!useCloud()) {
    const items = ls(cartKey(user._id), []);
    const found = items.find(item => item.productId === productId);
    if (found) found.qty = Math.min(99, found.qty + n);
    else items.push({ productId, qty: n });
    ss(cartKey(user._id), items);
    return;
  }
  const db = cloudDb();
  const res = await db.collection(config.collections.carts)
    .where({ userId: user._id, productId })
    .get();
  if (res.data.length) {
    await db.collection(config.collections.carts).doc(res.data[0]._id)
      .update({ data: { qty: Math.min(99, res.data[0].qty + n) } });
  } else {
    await db.collection(config.collections.carts)
      .add({ data: { userId: user._id, productId, qty: n, createdAt: Date.now() } });
  }
};

// 修改购物车中某商品数量（1~99）
const updateCartQty = async (productId, qty, user) => {
  if (!user) throw new Error('请先登录');
  const n = Math.round(qty);
  if (!useCloud()) {
    const items = ls(cartKey(user._id), []);
    const found = items.find(item => item.productId === productId);
    if (!found) throw new Error('购物车中不存在该商品');
    found.qty = Math.max(1, Math.min(99, n));
    ss(cartKey(user._id), items);
    return;
  }
  const res = await cloudDb().collection(config.collections.carts)
    .where({ userId: user._id, productId })
    .get();
  if (!res.data.length) throw new Error('购物车中不存在该商品');
  await cloudDb().collection(config.collections.carts).doc(res.data[0]._id)
    .update({ data: { qty: Math.max(1, Math.min(99, n)) } });
};

// 移除购物车中的单个商品
const removeFromCart = async (productId, user) => {
  if (!user) throw new Error('请先登录');
  if (!useCloud()) {
    const items = ls(cartKey(user._id), []).filter(item => item.productId !== productId);
    ss(cartKey(user._id), items);
    return;
  }
  const res = await cloudDb().collection(config.collections.carts)
    .where({ userId: user._id, productId })
    .get();
  for (const doc of res.data) {
    await cloudDb().collection(config.collections.carts).doc(doc._id).remove();
  }
};

// 清空购物车
const clearCart = async (user) => {
  if (!user) throw new Error('请先登录');
  if (!useCloud()) {
    ss(cartKey(user._id), []);
    return;
  }
  const res = await cloudDb().collection(config.collections.carts)
    .where({ userId: user._id })
    .get();
  for (const doc of res.data) {
    await cloudDb().collection(config.collections.carts).doc(doc._id).remove();
  }
};

// ---------------- 商店：收货信息 ----------------
// 记住用户最近一次填写的收货地址/联系方式，下单时预填
const shipKey = (userId) => `ship_${userId || 'guest'}`;
const getShipInfo = (userId) => {
  const info = ls(shipKey(userId), null) || { address: '', contact: '' };
  return { address: info.address || '', contact: info.contact || '' };
};
const saveShipInfo = (userId, info) => {
  ss(shipKey(userId), { address: (info && info.address) || '', contact: (info && info.contact) || '' });
};

// 校验并规整收货信息
const checkShipInfo = (extra) => {
  const address = ((extra && extra.address) || '').trim();
  const contact = ((extra && extra.contact) || '').trim();
  if (!address) throw new Error('请填写收货地址');
  if (address.length < 5) throw new Error('收货地址太短，请填写详细地址');
  if (!contact) throw new Error('请填写联系方式');
  return { address, contact };
};

// ---------------- 商店：订单 ----------------
// 本地统一存储于 mock_orders（带 userId 字段，便于后续管理端扩展）
const localOrders = () => {
  ensureSeed();
  return ls('mock_orders', []);
};

// 结算下单：以当前购物车全部商品生成订单并清空购物车（必填收货信息）
const createOrder = async (user, extra) => {
  if (!user) throw new Error('请先登录');
  const items = await getCart(user._id);
  if (!items.length) throw new Error('购物车是空的');
  const ship = checkShipInfo(extra);
  ss(shipKey(user._id), ship); // 记住收货信息，下次下单预填
  const total = Math.round(items.reduce((sum, item) => sum + item.price * item.qty, 0) * 100) / 100;
  const order = {
    _id: genId('o'),
    userId: user._id,
    userName: user.nickName,
    items,
    total,
    address: ship.address,
    contact: ship.contact,
    status: '已下单',
    createdAt: Date.now()
  };

  if (!useCloud()) {
    const orders = localOrders();
    orders.unshift(order);
    ss('mock_orders', orders);
    ss(cartKey(user._id), []);
    return order;
  }
  await cloudDb().collection(config.collections.orders).add({ data: order });
  const res = await cloudDb().collection(config.collections.carts)
    .where({ userId: user._id })
    .get();
  for (const doc of res.data) {
    await cloudDb().collection(config.collections.carts).doc(doc._id).remove();
  }
  return order;
};

// 我的订单（按下单时间倒序）
const getOrders = async (userId) => {
  if (!userId) return [];
  if (!useCloud()) {
    return localOrders()
      .filter(item => item.userId === userId)
      .sort((a, b) => b.createdAt - a.createdAt);
  }
  const res = await cloudDb().collection(config.collections.orders)
    .where({ userId })
    .orderBy('createdAt', 'desc')
    .get();
  return res.data;
};

// 确认收货：已下单 → 已完成（仅本人可操作）
const confirmOrder = async (id, user) => {
  if (!user) throw new Error('请先登录');
  if (!useCloud()) {
    const orders = localOrders();
    const order = orders.find(item => item._id === id);
    if (!order || order.userId !== user._id) throw new Error('订单不存在');
    if (order.status !== '已下单') throw new Error('当前状态不可确认收货');
    order.status = '已完成';
    ss('mock_orders', orders);
    return;
  }
  const res = await cloudDb().collection(config.collections.orders)
    .where({ _id: id, userId: user._id })
    .get();
  if (!res.data.length) throw new Error('订单不存在');
  if (res.data[0].status !== '已下单') throw new Error('当前状态不可确认收货');
  await cloudDb().collection(config.collections.orders).doc(id)
    .update({ data: { status: '已完成' } });
};

// 取消订单：已下单 → 已取消（仅本人可操作，保留记录与取消原因）
const cancelOrder = async (id, user, reason) => {
  if (!user) throw new Error('请先登录');
  const cancelReason = (reason || '').trim();
  if (!cancelReason) throw new Error('请填写取消原因');
  if (!useCloud()) {
    const orders = localOrders();
    const order = orders.find(item => item._id === id);
    if (!order || order.userId !== user._id) throw new Error('订单不存在');
    if (order.status !== '已下单') throw new Error('当前状态不可取消');
    order.status = '已取消';
    order.cancelReason = cancelReason;
    order.cancelledAt = Date.now();
    ss('mock_orders', orders);
    return;
  }
  const res = await cloudDb().collection(config.collections.orders)
    .where({ _id: id, userId: user._id })
    .get();
  if (!res.data.length) throw new Error('订单不存在');
  if (res.data[0].status !== '已下单') throw new Error('当前状态不可取消');
  await cloudDb().collection(config.collections.orders).doc(id)
    .update({ data: { status: '已取消', cancelReason, cancelledAt: Date.now() } });
};

// 后台订单查询：管理员查全部，作者查与自己作品相关的订单
const getAllOrders = async (user) => {
  if (!user || (user.role !== 'admin' && user.role !== 'author')) {
    throw new Error('无权查看订单数据');
  }
  if (!useCloud()) {
    return localOrders().sort((a, b) => b.createdAt - a.createdAt);
  }
  const res = await cloudDb().collection(config.collections.orders)
    .orderBy('createdAt', 'desc')
    .get();
  return res.data;
};

// 删除订单：仅允许删除本人「已取消」的订单
const deleteOrder = async (id, user) => {
  if (!user) throw new Error('请先登录');
  if (!useCloud()) {
    const orders = localOrders();
    const order = orders.find(item => item._id === id);
    if (!order || order.userId !== user._id) throw new Error('订单不存在');
    if (order.status !== '已取消') throw new Error('仅已取消的订单可删除');
    ss('mock_orders', orders.filter(item => item._id !== id));
    return;
  }
  const res = await cloudDb().collection(config.collections.orders)
    .where({ _id: id, userId: user._id, status: '已取消' })
    .get();
  if (!res.data.length) throw new Error('仅已取消的订单可删除');
  await cloudDb().collection(config.collections.orders).doc(id).remove();
};

// 立即购买：单件直接下单，不经过购物车（必填收货信息）
const buyNow = async (productId, qty, user, extra) => {
  if (!user) throw new Error('请先登录');
  const n = Math.max(1, Math.min(99, Math.round(qty) || 1));
  const product = await getProduct(productId);
  if (!product || product.status !== 'approved') throw new Error('该作品当前不可购买');
  if (!(product.price > 0)) throw new Error('该作品尚未定价，无法购买');
  const ship = checkShipInfo(extra);
  ss(shipKey(user._id), ship); // 记住收货信息，下次下单预填

  const order = {
    _id: genId('o'),
    userId: user._id,
    userName: user.nickName,
    items: [{ productId: product._id, qty: n, name: product.name, cover: product.cover, price: product.price }],
    total: Math.round(product.price * n * 100) / 100,
    address: ship.address,
    contact: ship.contact,
    status: '已下单',
    createdAt: Date.now()
  };

  if (!useCloud()) {
    const orders = localOrders();
    orders.unshift(order);
    ss('mock_orders', orders);
    return order;
  }
  await cloudDb().collection(config.collections.orders).add({ data: order });
  return order;
};

// ---------------- 留言 ----------------
// 提交留言：必须选择对应作品
const addMessage = async (payload, user) => {
  if (!payload.productId) throw new Error('请先选择咨询的作品');
  // kind: comment = 作品下方公开评论；inquiry = 私密咨询（发给作者）
  const kind = payload.kind === 'inquiry' ? 'inquiry' : 'comment';
  const product = await getProduct(payload.productId);
  const data = {
    productId: payload.productId || '',
    productName: payload.productName || '',
    authorId: product ? product.authorId : '',
    kind,
    content: payload.content,
    contact: payload.contact || '',
    userId: user ? user._id : '',
    userName: user ? user.nickName : '游客',
    reply: '',
    replyAt: 0,
    status: '待回复',
    createdAt: Date.now()
  };

  if (!useCloud()) {
    const list = localMessages();
    const message = { _id: genId('msg'), ...data };
    list.unshift(message);
    ss('mock_messages', list);
    return clone(message);
  }

  const res = await cloudDb().collection(config.collections.messages).add({ data });
  return { _id: res._id, ...data };
};

// 查询留言：userId 仅本人留言；productId 仅该作品留言；authorId 仅发给该作者的咨询
const getMessages = async (filter = {}) => {
  const { userId, productId, authorId } = filter;
  if (!useCloud()) {
    let list = localMessages();
    if (userId) list = list.filter(item => item.userId === userId);
    if (productId) list = list.filter(item => item.productId === productId);
    if (authorId) list = list.filter(item => item.authorId === authorId);
    return clone(list.sort((a, b) => b.createdAt - a.createdAt));
  }
  const cond = {};
  if (userId) cond.userId = userId;
  if (productId) cond.productId = productId;
  if (authorId) cond.authorId = authorId;
  let query = cloudDb().collection(config.collections.messages);
  if (Object.keys(cond).length) query = query.where(cond);
  const res = await query.orderBy('createdAt', 'desc').get();
  return res.data;
};

// 回复留言（管理员）
const replyMessage = async (id, reply) => {
  return updateMessage(id, { reply, replyAt: Date.now(), status: '已回复' });
};

// 作者回复自己作品收到的咨询（管理员也可回复任意咨询）
const replyInquiry = async (id, reply, user) => {
  if (!user) throw new Error('请先登录');
  const target = localMessages().find(item => item._id === id);
  const msg = useCloud()
    ? (await cloudDb().collection(config.collections.messages).doc(id).get()).data
    : target;
  if (!msg) throw new Error('咨询不存在');
  const isAuthor = msg.authorId && msg.authorId === user._id;
  const isOwner = user.role === 'admin';
  if (!isAuthor && !isOwner) throw new Error('只能回复自己作品收到的咨询');
  return updateMessage(id, { reply, replyAt: Date.now(), status: '已回复' });
};

const updateMessage = async (id, data) => {
  if (!useCloud()) {
    const list = localMessages().map(item => (item._id === id ? { ...item, ...data } : item));
    ss('mock_messages', list);
    return clone(list.find(item => item._id === id));
  }
  await cloudDb().collection(config.collections.messages).doc(id).update({ data });
  return { _id: id, ...data };
};

// 用户编辑自己的留言
const editMessage = async (id, userId, content) => {
  if (!userId) throw new Error('请先登录');
  if (!useCloud()) {
    const target = localMessages().find(item => item._id === id);
    if (!target) throw new Error('留言不存在');
    if (target.userId !== userId) throw new Error('只能编辑自己的留言');
    return updateMessage(id, { content });
  }
  const res = await cloudDb().collection(config.collections.messages).where({ _id: id, userId }).get();
  if (!res.data.length) throw new Error('只能编辑自己的留言');
  return updateMessage(id, { content });
};

// 删除留言：本人或管理员
const deleteMessage = async (id, user) => {
  if (!user) throw new Error('请先登录');
  if (!useCloud()) {
    const list = localMessages();
    const target = list.find(item => item._id === id);
    if (!target) throw new Error('留言不存在');
    if (target.userId !== user._id && user.role !== 'admin') throw new Error('无权限删除该留言');
    ss('mock_messages', list.filter(item => item._id !== id));
    return true;
  }
  if (user.role === 'admin') {
    await cloudDb().collection(config.collections.messages).doc(id).remove();
    return true;
  }
  const res = await cloudDb().collection(config.collections.messages).where({ _id: id, userId: user._id }).get();
  if (!res.data.length) throw new Error('无权限删除该留言');
  await cloudDb().collection(config.collections.messages).doc(id).remove();
  return true;
};

// ---------------- 意见反馈 ----------------
// 用户提交意见或问题
const addFeedback = async (payload, user) => {
  const type = payload.type === '问题' ? '问题' : '意见';
  const content = (payload.content || '').trim();
  if (!content) throw new Error('请填写反馈内容');
  const data = {
    userId: user ? user._id : '',
    userName: user ? user.nickName : '游客',
    type,
    content,
    status: '待处理',
    createdAt: Date.now()
  };
  if (!useCloud()) {
    const list = ls('mock_feedbacks', []);
    const item = { _id: genId('fb'), ...data };
    list.unshift(item);
    ss('mock_feedbacks', list);
    return clone(item);
  }
  const res = await cloudDb().collection(config.collections.feedbacks).add({ data });
  return { _id: res._id, ...data };
};

// 查询反馈：userId 仅本人；管理员传 {} 查看全部
const getFeedbacks = async (filter = {}) => {
  const { userId } = filter;
  if (!useCloud()) {
    let list = ls('mock_feedbacks', []);
    if (userId) list = list.filter(item => item.userId === userId);
    return clone(list.sort((a, b) => b.createdAt - a.createdAt));
  }
  let query = cloudDb().collection(config.collections.feedbacks);
  if (userId) query = query.where({ userId });
  const res = await query.orderBy('createdAt', 'desc').get();
  return res.data;
};

// 用户编辑自己的反馈
const editFeedback = async (id, userId, content) => {
  if (!userId) throw new Error('请先登录');
  if (!useCloud()) {
    const list = ls('mock_feedbacks', []);
    const target = list.find(item => item._id === id);
    if (!target) throw new Error('反馈不存在');
    if (target.userId !== userId) throw new Error('只能编辑自己的反馈');
    target.content = content;
    ss('mock_feedbacks', list);
    return clone(target);
  }
  const res = await cloudDb().collection(config.collections.feedbacks).where({ _id: id, userId }).get();
  if (!res.data.length) throw new Error('只能编辑自己的反馈');
  await cloudDb().collection(config.collections.feedbacks).doc(id).update({ data: { content } });
  return { _id: id, content };
};

// 删除反馈：本人或管理员
const deleteFeedback = async (id, user) => {
  if (!user) throw new Error('请先登录');
  if (!useCloud()) {
    const list = ls('mock_feedbacks', []);
    const target = list.find(item => item._id === id);
    if (!target) throw new Error('反馈不存在');
    if (target.userId !== user._id && user.role !== 'admin') throw new Error('无权限删除该反馈');
    ss('mock_feedbacks', list.filter(item => item._id !== id));
    return true;
  }
  if (user.role === 'admin') {
    await cloudDb().collection(config.collections.feedbacks).doc(id).remove();
    return true;
  }
  const res = await cloudDb().collection(config.collections.feedbacks).where({ _id: id, userId: user._id }).get();
  if (!res.data.length) throw new Error('无权限删除该反馈');
  await cloudDb().collection(config.collections.feedbacks).doc(id).remove();
  return true;
};

// 管理员回复反馈
const replyFeedback = async (id, reply) => {
  const user = getCurrentUser();
  if (!user || user.role !== 'admin') throw new Error('仅管理员可回复');
  const text = (reply || '').trim();
  if (!text) throw new Error('请输入回复内容');
  if (!useCloud()) {
    const list = ls('mock_feedbacks', []);
    const target = list.find(item => item._id === id);
    if (!target) throw new Error('反馈不存在');
    target.reply = text;
    target.replyAt = Date.now();
    ss('mock_feedbacks', list);
    return clone(target);
  }
  await cloudDb().collection(config.collections.feedbacks).doc(id).update({
    data: { reply: text, replyAt: Date.now() }
  });
  return { _id: id, reply: text };
};

// 管理员标记反馈处理状态：待处理 / 已处理
const setFeedbackStatus = async (id, status) => {
  const user = getCurrentUser();
  if (!user || user.role !== 'admin') throw new Error('仅管理员可操作');
  if (['待处理', '已处理'].indexOf(status) === -1) throw new Error('状态不合法');
  if (!useCloud()) {
    const list = ls('mock_feedbacks', []);
    const target = list.find(item => item._id === id);
    if (!target) throw new Error('反馈不存在');
    target.status = status;
    ss('mock_feedbacks', list);
    return clone(target);
  }
  await cloudDb().collection(config.collections.feedbacks).doc(id).update({ data: { status } });
  return { _id: id, status };
};

// ---------------- 当前登录用户 ----------------
const getCurrentUser = () => ls('currentUser', null);

const setCurrentUser = (user) => {
  ss('currentUser', user);
  const app = getApp();
  if (app && app.globalData) app.globalData.userInfo = user;
  return user;
};

const clearCurrentUser = () => {
  try {
    wx.removeStorageSync('currentUser');
  } catch (e) {
    console.warn(e);
  }
  const app = getApp();
  if (app && app.globalData) app.globalData.userInfo = null;
};

// 本地模式：将选图持久化到用户数据目录，避免重新编译后临时路径失效
const persistLocalImage = (tempFilePath) =>
  new Promise((resolve) => {
    try {
      const fs = wx.getFileSystemManager();
      const target = `${wx.env.USER_DATA_PATH}/avatar_${Date.now()}.jpg`;
      fs.copyFile({
        srcPath: tempFilePath,
        destPath: target,
        success: () => resolve(target),
        fail: () => resolve(tempFilePath) // 复制失败降级为临时路径
      });
    } catch (e) {
      resolve(tempFilePath);
    }
  });

// 删除旧的本地持久化头像文件，避免文件堆积
const removeOldAvatarFile = (avatarUrl) => {
  if (!avatarUrl || avatarUrl.indexOf(wx.env.USER_DATA_PATH) !== 0) return;
  try {
    wx.getFileSystemManager().unlink({ filePath: avatarUrl, fail: () => {} });
  } catch (e) {
    // 静默失败即可
  }
};

// 更新当前用户头像：本地模式持久化到用户数据目录，云模式上传至云存储 avatars/ 目录
const updateAvatar = async (filePath) => {
  if (!filePath) throw new Error('请选择图片');
  const user = getCurrentUser();
  if (!user) throw new Error('请先登录');

  let avatarUrl = filePath;
  if (useCloud()) {
    const res = await wx.cloud.uploadFile({
      cloudPath: `avatars/${Date.now()}.jpg`,
      filePath
    });
    avatarUrl = res.fileID;
    await cloudDb().collection(config.collections.users).doc(user._id).update({ data: { avatarUrl } });
  } else {
    avatarUrl = await persistLocalImage(filePath);
    removeOldAvatarFile(user.avatarUrl);
    const users = localUsers();
    const target = users.find(item => item._id === user._id);
    if (target) {
      target.avatarUrl = avatarUrl;
      ss('mock_users', users);
    }
  }
  return setCurrentUser({ ...user, avatarUrl });
};

module.exports = {
  useCloud,
  getOpenId,
  loginByWechat,
  loginByAccount,
  registerByPhone,
  updateAccount,
  updateRole,
  adminUpdateUser,
  deleteUser,
  changePassword,
  getUsers,
  updateUser,
  bindPhone,
  bindEmail,
  getProducts,
  getProduct,
  addProduct,
  updateProduct,
  deleteProduct,
  setProductStatus,
  getFavoriteIds,
  toggleFavorite,
  getFavoriteProducts,
  getCart,
  getCartCount,
  addToCart,
  updateCartQty,
  removeFromCart,
  clearCart,
  createOrder,
  getOrders,
  confirmOrder,
  cancelOrder,
  deleteOrder,
  getAllOrders,
  buyNow,
  getShipInfo,
  saveShipInfo,
  addMessage,
  getMessages,
  replyMessage,
  replyInquiry,
  editMessage,
  deleteMessage,
  addFeedback,
  getFeedbacks,
  editFeedback,
  deleteFeedback,
  replyFeedback,
  setFeedbackStatus,
  getCurrentUser,
  setCurrentUser,
  updateAvatar,
  clearCurrentUser
};
