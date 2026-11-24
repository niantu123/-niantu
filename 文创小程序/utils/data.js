// utils/data.js - 模拟数据
// 新安学院 · 校园文创产品展示（云开发环境不可用时的本地演示数据）
// 图片均为本地资源：images/works/、images/banner/、images/avatar/

// 种子数据版本：内置文案/图片更新后递增，运行时自动刷新旧缓存中的内置数据
const SEED_VERSION = 4;

// 轮播图
const bannerList = [
  {
    _id: 'b1',
    image: '/images/banner/b1.jpg',
    title: '新安文创 · 新季上新',
    subtitle: '把校园记忆带回家'
  },
  {
    _id: 'b2',
    image: '/images/banner/b2.jpg',
    title: '校庆限定 · 新安印记',
    subtitle: '编号发售 · 校庆纪念'
  },
  {
    _id: 'b3',
    image: '/images/banner/b3.jpg',
    title: '毕业季 · 把校园带走',
    subtitle: '写给青春的最后一张明信片'
  },
  {
    _id: 'b4',
    image: '/images/banner/b4.jpg',
    title: '手作艺术 · 非遗扎染',
    subtitle: '与非遗工坊联名出品'
  }
];

// 校园公告
const noticeList = [
  { _id: 'n1', content: '校庆文创征集开启，欢迎同学投稿原创设计' },
  { _id: 'n2', content: '毕业季限定礼盒已上架，支持校园自提' }
];

// 商品分类
const categoryList = [
  { id: 1, name: '校园文具', icon: '✒️' },
  { id: 2, name: '服饰周边', icon: '👕' },
  { id: 3, name: '生活好物', icon: '☕' },
  { id: 4, name: '数字文创', icon: '💻' },
  { id: 5, name: '纪念收藏', icon: '🎓' },
  { id: 6, name: '手作艺术', icon: '🎨' }
];

// 作品列表
const productList = [
  {
    _id: 'p1',
    name: '新安校徽烫金笔记本',
    categoryId: 1,
    cover: '/images/works/p01.jpg',
    images: ['/images/works/p01.jpg'],
    summary: '布面精装 A5 笔记本，封面烫金校徽，内页印有校训与校园剪影。',
    story: '设计灵感来自图书馆闭馆前的最后一盏灯。我们把校徽做成烫金压印，翻开的每一页都能看到校训与老建筑线稿，希望它能陪你写完四年的笔记。',
    material: '布面精装封面 / 100g 道林纸',
    size: 'A5 · 148×210mm · 192 页',
    price: 39.9,
    tags: ['热门'],
    status: 'approved',
    authorId: 'u_author_1',
    authorName: '设计学院 · 林同学',
    views: 1860,
    createdAt: 1760000000000
  },
  {
    _id: 'p2',
    name: '校园四季手账本套装',
    categoryId: 1,
    cover: '/images/works/p02.jpg',
    images: ['/images/works/p02.jpg'],
    summary: '春夏秋冬四册一套，插画取自校园四季实景，裸脊装订可 180 度平摊。',
    story: '四本手账分别对应樱花大道、荷塘、银杏与初雪。插画由插画社同学耗时两个月完成，每一页角落都藏着一处校园地标。',
    material: '裸脊线装 / 120g 米白纸',
    size: 'B6 · 128×182mm · 每册 96 页',
    price: 68,
    tags: ['新品'],
    status: 'approved',
    authorId: 'u_author_1',
    authorName: '设计学院 · 林同学',
    views: 742,
    createdAt: 1760500000000
  },
  {
    _id: 'p3',
    name: '校训黄铜书签',
    categoryId: 1,
    cover: '/images/works/p03.jpg',
    images: ['/images/works/p03.jpg'],
    summary: '黄铜镂空工艺，刻有校训「自强不息」，配流苏与礼盒。',
    story: '书签上的四个字取自老校门石刻。黄铜会随时间氧化出温润的色泽，就像校园时光在书页间慢慢沉淀。',
    material: '黄铜镀古铜 / 真丝流苏',
    size: '35×120mm',
    price: 25,
    tags: ['热门'],
    status: 'approved',
    authorId: 'u_author_2',
    authorName: '新安文创社',
    views: 2431,
    createdAt: 1760700000000
  },
  {
    _id: 'p4',
    name: '新安限定文化衫',
    categoryId: 2,
    cover: '/images/works/p04.jpg',
    images: ['/images/works/p04.jpg'],
    summary: '260g 纯棉面料，胸前新安手绘图案，宽松落肩版型。',
    story: '图案取自北门那面爬满青藤的老墙。我们保留了手绘的笔触感，不做过度的修饰，想让每个穿上它的人都能想起夏天的风。',
    material: '260g 精梳纯棉',
    size: 'S / M / L / XL 落肩宽松版',
    price: 79,
    tags: ['热门'],
    status: 'approved',
    authorId: 'u_author_2',
    authorName: '新安文创社',
    views: 1562,
    createdAt: 1760800000000
  },
  {
    _id: 'p5',
    name: '校徽刺绣棒球帽',
    categoryId: 2,
    cover: '/images/works/p05.jpg',
    images: ['/images/works/p05.jpg'],
    summary: '立体刺绣校徽，可调节后扣，四季百搭，帽檐内侧印有校名。',
    story: '帽型参考了校运动队的旧队帽。校徽采用 3D 立体刺绣，帽檐内侧藏着一行小小的校名，只有戴的人自己知道。',
    material: '纯棉斜纹布 / 立体刺绣',
    size: '均码 · 可调节 54-60cm',
    price: 59,
    tags: [],
    status: 'approved',
    authorId: 'u_author_2',
    authorName: '新安文创社',
    views: 913,
    createdAt: 1760900000000
  },
  {
    _id: 'p6',
    name: '校门插画帆布托特包',
    categoryId: 2,
    cover: '/images/works/p06.jpg',
    images: ['/images/works/p06.jpg'],
    summary: '加厚帆布，正面印手绘校门全景，内置电脑隔层与钥匙扣。',
    story: '手绘稿画了整整七版，最后选了清晨光线斜照校门的角度。包内留了电脑隔层，希望它能装下你上课、去图书馆和远行的所有东西。',
    material: '16 安加厚帆布',
    size: '38×42cm · 可放 14 寸电脑',
    price: 45,
    tags: ['热门'],
    status: 'approved',
    authorId: 'u_author_1',
    authorName: '设计学院 · 林同学',
    views: 1789,
    createdAt: 1761000000000
  },
  {
    _id: 'p7',
    name: '图书馆咖啡杯',
    categoryId: 3,
    cover: '/images/works/p07.jpg',
    images: ['/images/works/p07.jpg'],
    summary: '骨瓷杯身，杯底印图书馆剪影，配同色防烫杯套。',
    story: '把杯子喝到见底时，会看到杯底印着的图书馆剪影。这是留给自习到最后一刻的人的一点小惊喜。',
    material: '骨瓷 / 食品级釉下彩',
    size: '400ml · 口径 85mm',
    price: 52,
    tags: [],
    status: 'approved',
    authorId: 'u_author_2',
    authorName: '新安文创社',
    views: 1086,
    createdAt: 1761100000000
  },
  {
    _id: 'p8',
    name: '校园建筑冰箱贴六枚装',
    categoryId: 3,
    cover: '/images/works/p08.jpg',
    images: ['/images/works/p08.jpg'],
    summary: '校门、图书馆、钟楼等六处地标，立体浮雕工艺，附收藏展示卡。',
    story: '六枚冰箱贴对应六处校园地标，每一枚背面都印着建筑的建成年代。把它们贴在冰箱上，就像把校园地图带回了家。',
    material: 'PVC 立体浮雕 / 强磁背胶',
    size: '单枚约 45×45mm · 六枚礼盒装',
    price: 36,
    tags: ['热门'],
    status: 'approved',
    authorId: 'u_author_2',
    authorName: '新安文创社',
    views: 1345,
    createdAt: 1761200000000
  },
  {
    _id: 'p9',
    name: '图书馆的味道香氛蜡烛',
    categoryId: 3,
    cover: '/images/works/p09.jpg',
    images: ['/images/works/p09.jpg'],
    summary: '木质调香氛，前调纸张与雪松，还原旧书区的安静气息。',
    story: '调香师在旧书区待了三个下午，才调出这种纸张、木架与雪松混合的味道。点燃它，就像回到靠窗的那个座位。',
    material: '大豆蜡 / 天然香精油 / 玻璃杯',
    size: '180g · 燃烧约 40 小时',
    price: 68,
    tags: ['新品'],
    status: 'approved',
    authorId: 'u_author_2',
    authorName: '新安文创社',
    views: 604,
    createdAt: 1761300000000
  },
  {
    _id: 'p10',
    name: '手绘校园全景明信片',
    categoryId: 5,
    cover: '/images/works/p10.jpg',
    images: ['/images/works/p10.jpg'],
    summary: '12 张一套，手绘校园四季与地标，附赠邮票贴纸与留言卡。',
    story: '十二张明信片对应十二个月，画的是同一个校园在一年里的样子。我们希望它能寄给未来的自己，也能寄给很久没回来的校友。',
    material: '300g 特种纸 / 手绘印刷',
    size: '148×100mm · 12 张套装',
    price: 15,
    tags: ['热门', '新品'],
    status: 'approved',
    authorId: 'u_author_1',
    authorName: '设计学院 · 林同学',
    views: 3208,
    createdAt: 1761400000000
  },
  {
    _id: 'p11',
    name: '校庆限定金属徽章',
    categoryId: 5,
    cover: '/images/works/p11.jpg',
    images: ['/images/works/p11.jpg'],
    summary: '校庆限定，编号发售，背面刻有校训。',
    story: '为校庆特别设计，全球限量 1200 枚。每一枚背面都有独立编号，像是一张属于你的校庆入场券。',
    material: '锌合金 / 珐琅填色',
    size: '直径 40mm · 独立编号',
    price: 29,
    tags: ['热门'],
    status: 'approved',
    authorId: 'u_author_2',
    authorName: '新安文创社',
    views: 1462,
    createdAt: 1761500000000
  },
  {
    _id: 'p12',
    name: '学生手作陶艺杯',
    categoryId: 6,
    cover: '/images/works/p12.jpg',
    images: ['/images/works/p12.jpg'],
    summary: '陶艺社学生手作，每只纹理独一无二，附作者手写卡片。',
    story: '每一只都由陶艺社同学在拉坯机上完成，釉色在窑变中随机生成，所以世界上不会有两只完全相同的杯子。',
    material: '高温陶土 / 无铅釉',
    size: '约 300ml · 口径 80mm',
    price: 88,
    tags: ['新品'],
    status: 'approved',
    authorId: 'u_author_3',
    authorName: '陶艺社 · 周同学',
    views: 376,
    createdAt: 1761600000000
  },
  {
    _id: 'p13',
    name: '非遗扎染方巾',
    categoryId: 6,
    cover: '/images/works/p13.jpg',
    images: ['/images/works/p13.jpg'],
    summary: '植物靛蓝扎染，纹样随机不重复，与本地非遗工坊联名出品。',
    story: '与本地非遗扎染工坊联名，用植物靛蓝反复浸染。扎结的方式决定纹样，所以每一条方巾都是不可复制的偶然。',
    material: '纯棉坯布 / 植物靛蓝',
    size: '55×55cm',
    price: 66,
    tags: ['新品'],
    status: 'approved',
    authorId: 'u_author_3',
    authorName: '非遗工坊 · 蓝染小组',
    views: 692,
    createdAt: 1761700000000
  },
  {
    _id: 'p14',
    name: '校园风光数字壁纸包',
    categoryId: 4,
    cover: '/images/works/p14.jpg',
    images: ['/images/works/p14.jpg'],
    summary: '含手机与电脑壁纸共 30 张，校园四季实拍与插画双版本。',
    story: '摄影社与插画社联合出品，把校园四季做成了手机与电脑壁纸。购买后可在“我的收藏”中获取下载方式。',
    material: '数字文件 / 4K 分辨率',
    size: '手机 20 张 + 电脑 10 张',
    price: 9.9,
    tags: ['新品'],
    status: 'approved',
    authorId: 'u_author_1',
    authorName: '摄影社 · 陈同学',
    views: 4127,
    createdAt: 1761800000000
  },
  {
    _id: 'p15',
    name: '校史馆 AR 明信片',
    categoryId: 4,
    cover: '/images/works/p15.jpg',
    images: ['/images/works/p15.jpg'],
    summary: '扫码即可观看校史馆三维场景与老照片故事，附实体明信片。',
    story: '扫描明信片上的图案，就能看到校史馆的三维场景和老照片背后的故事。我们想把“参观校史馆”装进口袋，让远方的校友也能随时回来看看。',
    material: '特种纸印刷 / AR 识别码',
    size: '148×100mm · 单张',
    price: 35,
    tags: ['新品'],
    status: 'approved',
    authorId: 'u_author_1',
    authorName: '数字媒体 · 吴同学',
    views: 528,
    createdAt: 1761900000000
  },
  {
    _id: 'p16',
    name: '校庆纪念帆布包',
    categoryId: 2,
    cover: '/images/works/p16.jpg',
    images: ['/images/works/p16.jpg'],
    summary: '为校庆设计的纪念帆布包，等待平台审核。',
    story: '以校庆标识为主视觉，搭配手写体校名，目前正在等待管理员审核上架。',
    material: '12 安帆布',
    size: '36×40cm',
    price: 58,
    tags: [],
    status: 'pending',
    authorId: 'u_author_1',
    authorName: '设计学院 · 林同学',
    views: 0,
    createdAt: 1761950000000
  },
  {
    _id: 'p17',
    name: '校园夜景胶片册',
    categoryId: 5,
    cover: '/images/works/p17.jpg',
    images: ['/images/works/p17.jpg'],
    summary: '校园夜景胶片摄影册。',
    story: '胶片扫描件清晰度不足，被管理员驳回，作者可修改图片后重新提交。',
    material: '铜版纸印刷',
    size: 'A5 · 40 页',
    price: 42,
    tags: [],
    status: 'rejected',
    rejectReason: '图片清晰度不足，请补充高清原图后重新提交。',
    authorId: 'u_author_1',
    authorName: '设计学院 · 林同学',
    views: 0,
    createdAt: 1761960000000
  }
];

// 用户数据
const userList = [
  {
    _id: 'u_admin',
    openid: 'local_admin',
    account: 'admin',
    phone: '13800000000',
    email: '',
    password: '123456',
    nickName: '平台管理员',
    avatarUrl: '/images/avatar/admin.jpg',
    role: 'admin',
    createdAt: 1759000000000
  },
  {
    _id: 'u_author_1',
    openid: 'local_author_1',
    account: '',
    phone: '13900000001',
    email: '',
    password: '123456',
    nickName: '林同学',
    avatarUrl: '/images/avatar/author1.jpg',
    role: 'author',
    createdAt: 1759100000000
  },
  {
    _id: 'u_user_1',
    openid: 'local_user_1',
    account: '',
    phone: '13700000002',
    email: '',
    password: '123456',
    nickName: '校友小陈',
    avatarUrl: '/images/avatar/user1.jpg',
    role: 'user',
    createdAt: 1759200000000
  }
];

// 留言数据
const messageList = [
  {
    _id: 'msg1',
    kind: 'comment',
    productId: 'p1',
    productName: '新安校徽烫金笔记本',
    authorId: 'u_author_1',
    content: '毕业五年了，看到这本笔记本想起图书馆闭馆的音乐，已下单。',
    contact: '',
    userId: 'u_user_1',
    userName: '校友小陈',
    reply: '',
    replyAt: 0,
    status: '待回复',
    createdAt: 1760100000000
  },
  {
    _id: 'msg2',
    kind: 'inquiry',
    productId: 'p1',
    productName: '新安校徽烫金笔记本',
    authorId: 'u_author_1',
    content: '请问校庆限定徽章还会补货吗？想买两枚送给室友。',
    contact: '微信：qingteng2024',
    userId: 'u_user_1',
    userName: '校友小陈',
    reply: '',
    replyAt: 0,
    status: '待回复',
    createdAt: 1760400000000
  }
];

module.exports = {
  SEED_VERSION,
  bannerList,
  noticeList,
  categoryList,
  productList,
  userList,
  messageList
};
