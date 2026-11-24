// utils/assistant.js - 文创产品助手（本地规则问答引擎，无需联网）
// answer(question, products) => { text, products: [匹配到的作品卡片] }

const GREETING = '你好呀～ 我是新安文创助手 🌿\n可以问我「有什么推荐」「有哪些新品」「怎么收藏作品」，也可以开启右上角 🌐 联网，让我推荐网上的文创好物、设计灵感，或者聊任何话题～';

const FALLBACK = '这个问题我还没学会呢 🤔\n你可以试试问我：\n· 有什么推荐 / 新品\n· 找某件作品（如 书签、蜡烛）\n· 怎么收藏、怎么发布作品\n· 审核与上架规则\n\n想聊平台之外的话题？点输入框左侧 🌐 开启联网，我都能答～';

// 明确指向平台之外的问题（网上推荐、外部平台、流行趋势等），交给联网大模型自由发挥
const EXTERNAL_RE = /(网上|网络上|互联网|全网|别的平台|其他平台|外面|淘宝|小红书|抖音|b站|B站|pinterest|behance|站酷|设计趋势|文创趋势|流行|最近大家|全网都)/i;
const EXTERNAL_HINT = '这个问题超出我的本地知识啦～ 点击输入框左侧 🌐 开启联网搜索，我可以帮你推荐网上的文创好物、设计灵感，什么都能聊！';

// 问「发布什么作品好」类：要的是选题方向建议，而不是发布操作说明
const IDEA_RE = /(发布什么样|发布怎么|发布怎样的|什么作品好|什么作品.*(好卖|欢迎)|做什么.*(作品|产品|好)|创作什么|设计什么|什么题材|什么方向|什么类型的作品|好卖|畅销|受欢迎的作品|推荐.*题材)/i;
const LOCAL_IDEA = '给你几个适合新安学院校庆的作品方向 🌿\n· 校庆纪念：纪念徽章、校庆限定书签、录取通知书主题明信片\n· 校园场景：图书馆便签本、樱花大道帆布袋、老校门冰箱贴\n· 实用文具：校徽中性笔、校园主题笔记本、和纸胶带\n· 手作艺术：押花书签、扎染布艺、陶艺杯\n\n下面这几件是当前人气作品，选题时可以参考避开重复～';

// 征求意见 / 创作咨询类问题（起名、定价、拍摄、改进建议等），需要个性化回答，交给联网大模型
const ADVICE_RE = /(意见|建议|怎么样|好不好|如何设计|怎么设计|如何改|怎么改|改进|优化|灵感|思路|起名|取名|起个|名字.*合适|定价|卖多少|怎么拍|如何拍|拍.*怎么样|更吸引|更受欢迎|帮我看看|帮我出)/i;
const LOCAL_ADVICE = '关于发布作品，先给你几个实用小建议 ✍️\n· 名称：简短有记忆点，可以带上「新安」「校庆」元素\n· 简介：一两句话讲清材质、工艺和使用场景\n· 背后故事：写灵感来源（图书馆、老校门、樱花大道…）最打动人\n· 图片：光线明亮、背景干净，2～3 张不同角度更好\n· 分类：选准 6 大分类之一，更容易通过审核\n\n开启 🌐 联网后，把你的作品名称和描述发给我，我可以给出更具体的修改意见～';

// 关键词 => 意图
const RULES = [
  {
    name: 'greet',
    match: q => /^(你好|您好|hi|hello|嗨|哈喽|在吗|在么)/i.test(q),
    reply: () => ({ text: GREETING })
  },
  {
    name: 'thanks',
    match: q => /(谢谢|感谢|thanks|辛苦)/i.test(q),
    reply: () => ({ text: '不客气～ 祝你在新安文创逛得开心，有需要随时找我 🌿' })
  },
  {
    name: 'hot',
    match: q => /(推荐|热门|人气|最火|最好|销量|喜欢什么)/.test(q),
    reply: (q, products) => {
      const hot = products.slice().sort((a, b) => (b.views || 0) - (a.views || 0)).slice(0, 3);
      return {
        text: '根据大家的浏览热度，最值得一看的是这三件：',
        products: hot
      };
    }
  },
  {
    name: 'new',
    match: q => /(新品|最新|上新|新季|最近有什么)/.test(q),
    reply: (q, products) => {
      const fresh = products.filter(p => (p.tags || []).indexOf('新品') > -1);
      if (!fresh.length) return { text: '暂时还没有打「新品」标签的作品，不过热门区也很精彩，可以看看～' };
      return { text: '这些是带「新品」标签的作品：', products: fresh.slice(0, 3) };
    }
  },
  {
    name: 'category',
    match: q => /(分类|类别|属于哪|什么类)/.test(q),
    reply: () => ({
      text: '平台共 6 个分类：\n✒️ 校园文具　👕 服饰周边\n☕ 生活好物　💻 数字文创\n🎓 纪念收藏　🎨 手作艺术\n可以在「展品」页左侧导航切换查看。'
    })
  },
  {
    name: 'all',
    match: q => /(都有什么|有哪些|全部作品|所有作品|逛逛|列表)/.test(q),
    reply: (q, products) => ({
      text: `平台上架作品共 ${products.length} 件，涵盖文具、服饰、生活好物、数字文创、纪念收藏和手作艺术六大类。先给你看几件人气作品：`,
      products: products.slice().sort((a, b) => (b.views || 0) - (a.views || 0)).slice(0, 3)
    })
  },
  {
    name: 'favorite',
    match: q => /(收藏|喜欢.*(怎么|如何)|加入收藏)/.test(q),
    reply: () => ({
      text: '进入作品详情页，点击「收藏」即可加入我的收藏；收藏列表在「我的 → 我的收藏」中查看。下架的作品会自动从收藏里隐藏哦。'
    })
  },
  {
    name: 'publish',
    match: q => /(发布|上传|投稿|怎么卖|上架作品|我想.*作品)/.test(q),
    reply: () => ({
      text: '在「我的 → 我的发布」点击右上角「+」即可发布作品：填写名称、简介、背后故事，上传图片后提交审核。审核通过会自动上架展示。'
    })
  },
  {
    name: 'review',
    match: q => /(审核|驳回|多久.*通过|为什么不.*通过|上不了架)/.test(q),
    reply: () => ({
      text: '作品提交后由平台管理员审核，一般 1～2 天内完成。如果被驳回会显示驳回理由（如图片清晰度不足），按理由修改后重新提交即可。已上架作品随时可以下架。'
    })
  },
  {
    name: 'admin',
    match: q => /(管理员|admin|客服|联系方式|谁在管)/.test(q),
    reply: () => ({
      text: '平台管理员账号是 admin，负责作品审核与留言回复。有建议可以在「留言」板块发布，管理员会回复你。'
    })
  },
  {
    name: 'account',
    match: q => /(登录|注册|账号|密码|头像)/.test(q),
    reply: () => ({
      text: '支持两种登录方式：\n1️⃣ 微信授权一键登录\n2️⃣ 手机号/账号 + 密码登录\n在「我的 → 账号安全」里可以修改账号、密码、绑定手机号和邮箱，还能点击头像更换图片。'
    })
  },
  {
    name: 'message',
    match: q => /(留言|评论|回复|建议|反馈)/.test(q),
    reply: () => ({
      text: '留言方式有两种：\n1️⃣ 在作品详情页下方的「作品留言」区直接留言\n2️⃣ 在「留言」页选择要咨询的作品后提交留言\n提交后管理员会回复，你也可以随时编辑或删除自己的留言；对平台的意见建议可以在「我的 → 意见反馈」中提交。'
    })
  },
  {
    name: 'school',
    match: q => /(新安|学校|校庆|校徽|校训|学院)/.test(q),
    reply: () => ({
      text: '新安学院校庆之际上线了「新安文创」平台，收录同学与社团的原创设计。很多作品灵感来自图书馆、老校门和樱花大道，欢迎慢慢逛～'
    })
  },
  {
    name: 'who',
    match: q => /(你是谁|你叫什么|能做什么|怎么用|帮助|help)/i.test(q),
    reply: () => ({ text: GREETING })
  }
];

// 作品名关键词映射（覆盖 17 件作品）
const PRODUCT_KEYWORDS = [
  ['笔记本', /笔记|本子/], ['手账', /手账|手帳/], ['书签', /书签/],
  ['文化衫', /文化衫|t恤|T恤|衣服|衫/], ['棒球帽', /帽|棒球帽/],
  ['托特包', /托特|帆布包|包/], ['咖啡杯', /咖啡|杯子|马克杯/],
  ['冰箱贴', /冰箱贴/], ['蜡烛', /蜡烛|香氛/], ['明信片', /明信片/],
  ['徽章', /徽章|勋章/], ['陶艺杯', /陶艺|陶瓷/], ['扎染', /扎染/],
  ['壁纸', /壁纸|屏保/], ['胶片册', /胶片|相册/]
];

const matchProducts = (q, products) =>
  products.filter(p => {
    const hay = `${p.name} ${(p.summary || '')} ${(p.tags || []).join(' ')}`;
    if (hay.indexOf(q) > -1 && q.length >= 2) return true;
    return PRODUCT_KEYWORDS.some(([, re]) => re.test(q) && re.test(hay));
  });

const answer = (question, products) => {
  const q = (question || '').trim();
  if (!q) return { text: FALLBACK, products: [], fallback: true };

  // 平台外问题：不套本地规则，交给联网大模型自由回答
  if (EXTERNAL_RE.test(q)) {
    return { text: EXTERNAL_HINT, products: [], fallback: true };
  }

  // 问「发布什么作品好」：给选题方向建议（离线时附人气作品卡片做参考）
  if (IDEA_RE.test(q)) {
    const hot = (products || [])
      .slice()
      .sort((a, b) => (b.views || 0) - (a.views || 0))
      .slice(0, 2);
    return { text: LOCAL_IDEA, products: hot, fallback: true };
  }

  // 征求意见 / 创作咨询：不套固定答案，联网开启时交给大模型给个性化建议
  if (ADVICE_RE.test(q)) {
    return { text: LOCAL_ADVICE, products: [], fallback: true };
  }

  for (const rule of RULES) {
    if (rule.match(q)) {
      const res = rule.reply(q, products || []);
      return { products: [], ...res, fallback: false };
    }
  }

  const hits = matchProducts(q, products || []);
  if (hits.length) {
    return {
      text: `为你找到 ${hits.length} 件相关作品：`,
      products: hits.slice(0, 4),
      fallback: false
    };
  }
  return { text: FALLBACK, products: [], fallback: true };
};

// ---------------- 联网搜索（OpenAI 兼容接口） ----------------
// 构建平台上下文：让大模型了解平台作品与规则
const buildSystemPrompt = (products) => {
  const list = (products || [])
    .map(p => `- ${p.name}（${(p.tags || []).join('/') || '无标签'}）：${p.summary || ''}`)
    .join('\n');
  return [
    '你是「新安学院校园文创展示平台」的智能助手「文创助手」，语气亲切自然，像一位懂文创的朋友。',
    '平台有 6 个分类：校园文具、服饰周边、生活好物、数字文创、纪念收藏、手作艺术。',
    '当前上架作品清单：\n' + (list || '（暂无）'),
    '用户问到平台内的问题时优先依据以上信息，不确定就如实说明，不要编造平台内作品。',
    '发布规则：在「我的 → 我的发布」点「+」发布，填写名称/简介/背后故事并上传图片，管理员审核通过后上架。审核关注：图片清晰、信息完整、符合校园文创主题、原创。仅当用户明确问「怎么发布/怎么投稿/发布流程」时才介绍这些，不要答非所问。',
    '用户问「发布什么作品好/什么题材受欢迎」时：这是在要选题建议。请结合校园文创场景与新安学院校庆元素（图书馆、老校门、樱花大道、毕业季等），推荐 3~5 个具体的作品方向和创意点子，说明为什么适合，并参考上架作品清单提示避免与现有作品重复。',
    '用户就自己想发布的作品征求意见（如「我想发个马克杯有什么建议」「帮我起个名」「定价多少合适」「这张图拍得怎么样」）时：给出具体、可操作的建议：作品命名思路、简介文案写法、背后故事怎么讲、拍摄角度与光线、分类选择、定价参考（校园文创常见 10~60 元区间）等，可以结合上架作品清单举例，最后鼓励用户发布。',
    '用户询问平台之外的内容（如网上的文创作品推荐、品牌、设计灵感、流行趋势、通用知识）时，请自由、丰富、有见解地回答，可以举例推荐网上的真实品牌与作品，篇幅 150~300 字。',
    '整体风格轻松友好，可用少量 emoji 点缀。'
  ].join('\n');
};

const askRemote = (question, products) =>
  new Promise((resolve, reject) => {
    const conf = (require('./config').assistant) || {};
    if (!conf.enabled || !conf.apiKey) {
      reject(new Error('未配置联网 API，请在 utils/config.js 填入 apiKey'));
      return;
    }
    wx.request({
      url: conf.apiUrl,
      method: 'POST',
      timeout: 30000, // 长回答（如选题建议）生成较慢，放宽到 30 秒
      header: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${(conf.apiKey || '').trim()}`
      },
      data: {
        model: conf.model || 'glm-4-flash',
        messages: [
          { role: 'system', content: buildSystemPrompt(products) },
          { role: 'user', content: question }
        ],
        temperature: 0.6,
        max_tokens: 500
      },
      success(res) {
        if (res.statusCode === 200 && res.data && res.data.choices && res.data.choices[0]) {
          const content = res.data.choices[0].message && res.data.choices[0].message.content;
          if (content) {
            resolve(content.trim());
            return;
          }
        }
        if (res.statusCode === 401) {
          reject(new Error('API Key 无效：请检查是否复制完整，以及账号是否已实名认证'));
          return;
        }
        if (res.statusCode === 402) {
          reject(new Error('API 余额不足：请到平台充值，或改用免费方案（如智谱 glm-4-flash）'));
          return;
        }
        const msg = res.data && res.data.error && res.data.error.message;
        reject(new Error(msg || `接口返回异常（${res.statusCode}）`));
      },
      fail(err) {
        const msg = (err && err.errMsg) || '';
        if (msg.indexOf('timeout') > -1) {
          reject(new Error('响应超时，模型生成时间较长，请稍后再试'));
        } else {
          reject(new Error('网络请求失败，请检查网络与合法域名设置'));
        }
      }
    });
  });

// 看图生成一句话简介：调用视觉模型描述 base64 图片（页面侧用 Canvas 导出 base64，绕开工具 fs 限制）
const describeBase64 = (base64) =>
  new Promise((resolve, reject) => {
    const conf = (require('./config').assistant) || {};
    if (!conf.visionApiKey) {
      reject(new Error('未配置视觉模型 Key，请在 utils/config.js 的 visionApiKey 填入'));
      return;
    }
    wx.request({
        url: conf.visionApiUrl,
        method: 'POST',
        timeout: 30000,
        header: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${(conf.visionApiKey || '').trim()}`
        },
        data: {
          model: conf.visionModel || 'glm-4v-flash',
          messages: [
            {
              role: 'user',
              content: [
                { type: 'image_url', image_url: { url: `data:image/jpeg;base64,${base64}` } },
                {
                  type: 'text',
                  text: '这是一件校园文创产品的展示图。请仔细观察图片内容，用一句不超过 40 字的话介绍这件作品，突出材质、配色与设计特点，语气亲切自然。不要出现"图片""图中""这是一张"等字样，直接输出介绍文字本身。'
                }
              ]
            }
          ],
          temperature: 0.7,
          max_tokens: 200
        },
        success(res) {
          if (res.statusCode === 200 && res.data && res.data.choices && res.data.choices[0]) {
            const content = res.data.choices[0].message && res.data.choices[0].message.content;
            if (content) {
              resolve(content.trim().slice(0, 60));
              return;
            }
          }
          if (res.statusCode === 401) {
            reject(new Error('视觉模型 Key 无效，请检查配置'));
            return;
          }
          const msg = res.data && res.data.error && res.data.error.message;
          reject(new Error(msg || `接口返回异常（${res.statusCode}）`));
        },
        fail() {
          reject(new Error('网络请求失败，请检查网络与合法域名设置'));
        }
      });
  });

module.exports = { answer, askRemote, describeBase64, GREETING, FALLBACK };
