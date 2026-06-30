# V2Board API 接口文档

## 📋 目录

### 📊 数据说明
- [数据字段说明](#数据字段说明)
- [枚举值说明](#枚举值说明)

### 🔐 认证相关
- [认证方式](#认证方式)

### 👤 用户端 API (`/api/v1/user/`)
- [用户信息管理](#用户信息管理)
  - [获取用户信息](#获取用户信息)
  - [获取订阅信息](#获取订阅信息)
  - [更新用户信息](#更新用户信息)
  - [修改密码](#修改密码)
  - [检查登录状态](#检查登录状态)
- [订单管理](#订单管理)
  - [创建订单](#创建订单)
  - [获取订单列表](#获取订单列表)
  - [订单详情](#订单详情)
  - [取消订单](#取消订单)
  - [获取支付方式](#获取支付方式)
- [支付处理](#支付处理)
  - [订单支付](#订单支付)
  - [检查支付状态](#检查支付状态)
- [套餐管理](#套餐管理)
  - [获取套餐列表](#获取套餐列表)
- [工单管理](#工单管理)
  - [创建工单](#创建工单)
  - [获取工单列表](#获取工单列表)
  - [回复工单](#回复工单)
  - [关闭工单](#关闭工单)
- [礼品卡兑换](#礼品卡兑换)
  - [兑换礼品卡](#兑换礼品卡)
- [邀请管理](#邀请管理)
  - [获取邀请信息](#获取邀请信息)
  - [生成邀请码](#生成邀请码)
  - [邀请详情](#邀请详情)
- [统计信息](#统计信息)
  - [获取流量统计](#获取流量统计)
  - [获取流量日志](#获取流量日志)
- [服务器管理](#服务器管理)
  - [获取服务器列表](#获取服务器列表)
- [公告管理](#公告管理)
  - [获取公告列表](#获取公告列表)
- [优惠券管理](#优惠券管理)
  - [验证优惠券](#验证优惠券)
- [知识库](#知识库)
  - [获取知识库列表](#获取知识库列表)
  - [获取知识库分类](#获取知识库分类)

### 👨‍💼 管理端 API (`/api/v1/{admin_path}/`)
- [用户管理](#用户管理)
  - [获取用户列表](#获取用户列表)
  - [创建用户](#创建用户)
  - [更新用户](#更新用户)
  - [删除用户](#删除用户)
- [套餐管理](#套餐管理)
  - [获取套餐列表](#获取套餐列表)
  - [创建套餐](#创建套餐)
  - [更新套餐](#更新套餐)
  - [删除套餐](#删除套餐)
- [服务器管理](#服务器管理)
  - [获取服务器组列表](#获取服务器组列表)
  - [创建服务器组](#创建服务器组)
  - [获取路由规则列表](#获取路由规则列表)
  - [创建路由规则](#创建路由规则)
- [订单管理](#订单管理)
  - [获取订单列表](#获取订单列表)
  - [订单详情](#订单详情)
  - [更新订单状态](#更新订单状态)
  - [手动确认支付](#手动确认支付)
  - [取消订单](#取消订单)
- [支付管理](#支付管理)
  - [获取支付方式列表](#获取支付方式列表)
  - [获取可用支付网关](#获取可用支付网关)
  - [创建支付方式](#创建支付方式)
  - [更新支付方式](#更新支付方式)
  - [删除支付方式](#删除支付方式)
  - [启用/禁用支付方式](#启用禁用支付方式)
  - [获取支付配置表单](#获取支付配置表单)
  - [支付方式排序](#支付方式排序)
- [工单管理](#工单管理)
  - [获取工单列表](#获取工单列表)
  - [回复工单](#回复工单)
  - [关闭工单](#关闭工单)
- [优惠券管理](#优惠券管理)
  - [获取优惠券列表](#获取优惠券列表)
  - [生成优惠券](#生成优惠券)
  - [删除优惠券](#删除优惠券)
- [礼品卡管理](#礼品卡管理)
  - [获取礼品卡列表](#获取礼品卡列表)
  - [生成礼品卡](#生成礼品卡)
- [公告管理](#公告管理)
  - [获取公告列表](#获取公告列表)
  - [创建公告](#创建公告)
- [知识库管理](#知识库管理)
  - [获取知识库列表](#获取知识库列表)
  - [创建知识库](#创建知识库)
- [系统配置](#系统配置)
  - [获取系统配置](#获取系统配置)
  - [保存系统配置](#保存系统配置)
- [统计分析](#统计分析)
  - [获取订单统计](#获取订单统计)
  - [获取用户统计](#获取用户统计)
  - [获取排行榜](#获取排行榜)

### 👨‍💻 员工端 API (`/api/v1/staff/`)
- [用户管理](#用户管理)
  - [获取用户信息](#获取用户信息)
  - [更新用户信息](#更新用户信息)
  - [发送邮件](#发送邮件)
  - [封禁用户](#封禁用户)
- [工单管理](#工单管理)
  - [获取工单列表](#获取工单列表)
  - [回复工单](#回复工单)
  - [关闭工单](#关闭工单)

### 🌐 访客端 API (`/api/v1/guest/`)
- [套餐查询](#套餐查询)
  - [获取公开套餐列表](#获取公开套餐列表)
- [客户端版本信息](#客户端版本信息)
  - [获取客户端版本](#获取客户端版本)
- [公共配置](#公共配置)
  - [获取公共配置](#获取公共配置)
- [支付回调](#支付回调)
  - [支付回调接口](#支付回调接口)

### 🔐 认证端 API (`/api/v1/passport/`)
- [用户认证](#用户认证)
  - [用户注册](#用户注册)
  - [用户登录](#用户登录)
  - [Token转登录](#token转登录)
  - [忘记密码](#忘记密码)
  - [邮件链接登录](#邮件链接登录)
- [邮件验证](#邮件验证)
  - [发送邮件验证码](#发送邮件验证码)
- [统计](#统计)
  - [页面访问统计](#页面访问统计)

### 💻 客户端 API (`/api/v1/client/`)
- [客户端配置](#客户端配置)
  - [获取客户端配置](#获取客户端配置)
  - [获取客户端版本](#获取客户端版本)
- [订阅链接](#订阅链接)
  - [获取订阅链接](#获取订阅链接)

### 🖥️ 服务端 API (`/api/v1/server/`)
- [用户管理](#用户管理)
  - [获取用户列表](#获取用户列表)
  - [获取节点配置](#获取节点配置)
  - [用户流量上报](#用户流量上报)

### ❌ 错误处理
- [错误码说明](#错误码说明)
- [注意事项](#注意事项)
- [更新日志](#更新日志)

---

## 📊 数据字段说明

### 👤 用户字段 (User)

| 字段名 | 类型 | 说明 | 示例值 |
|--------|------|------|--------|
| `id` | int | 用户ID | 1 |
| `email` | string | 用户邮箱 | "user@example.com" |
| `password` | string | 密码哈希 | "hashed_password" |
| `password_algo` | string | 密码算法 | "bcrypt" |
| `password_salt` | string | 密码盐值 | "salt123" |
| `balance` | int | 账户余额(分) | 1000 |
| `commission_balance` | int | 佣金余额(分) | 500 |
| `commission_type` | tinyint | 佣金类型 | 0=系统, 1=周期, 2=一次性 |
| `commission_rate` | int | 佣金比例(%) | 10 |
| `plan_id` | int | 当前套餐ID | 1 |
| `group_id` | int | 用户组ID | 1 |
| `transfer_enable` | bigint | 总流量限制(字节) | 107374182400 |
| `u` | bigint | 已上传流量(字节) | 1073741824 |
| `d` | bigint | 已下载流量(字节) | 2147483648 |
| `device_limit` | int | 设备限制数量 | 3 |
| `speed_limit` | int | 速度限制(KB/s) | 1024 |
| `expired_at` | bigint | 订阅过期时间戳 | 1640995200 |
| `banned` | tinyint | 是否封禁 | 0=正常, 1=封禁 |
| `is_admin` | tinyint | 是否管理员 | 0=否, 1=是 |
| `is_staff` | tinyint | 是否员工 | 0=否, 1=是 |
| `invite_user_id` | int | 邀请人ID | 1 |
| `telegram_id` | bigint | Telegram ID | 123456789 |
| `uuid` | string | 用户UUID | "uuid-string" |
| `token` | string | 订阅Token | "token123" |
| `auto_renewal` | tinyint | 自动续费 | 0=关闭, 1=开启 |
| `remind_expire` | tinyint | 过期提醒 | 0=关闭, 1=开启 |
| `remind_traffic` | tinyint | 流量提醒 | 0=关闭, 1=开启 |
| `last_login_at` | int | 最后登录时间戳 | 1640995200 |
| `last_login_ip` | int | 最后登录IP | 192168001001 |
| `remarks` | text | 备注信息 | "用户备注" |
| `created_at` | int | 创建时间戳 | 1640995200 |
| `updated_at` | int | 更新时间戳 | 1640995200 |

### 📦 套餐字段 (Plan)

| 字段名 | 类型 | 说明 | 示例值 |
|--------|------|------|--------|
| `id` | int | 套餐ID | 1 |
| `name` | string | 套餐名称 | "基础套餐" |
| `content` | text | 套餐描述 | "适合个人用户" |
| `group_id` | int | 服务器组ID | 1 |
| `transfer_enable` | int | 流量限制(GB) | 100 |
| `device_limit` | int | 设备限制 | 3 |
| `speed_limit` | int | 速度限制(KB/s) | 1024 |
| `month_price` | int | 月付价格(分) | 1000 |
| `quarter_price` | int | 季付价格(分) | 2500 |
| `half_year_price` | int | 半年价格(分) | 4500 |
| `year_price` | int | 年付价格(分) | 8000 |
| `two_year_price` | int | 两年价格(分) | 15000 |
| `three_year_price` | int | 三年价格(分) | 21000 |
| `onetime_price` | int | 一次性价格(分) | 10000 |
| `reset_price` | int | 重置流量价格(分) | 1000 |
| `reset_traffic_method` | tinyint | 重置方式 | 0=按月, 1=按年, 2=不重置 |
| `capacity_limit` | int | 容量限制 | 1000 |
| `show` | tinyint | 是否显示 | 0=隐藏, 1=显示 |
| `renew` | tinyint | 是否可续费 | 0=不可, 1=可续费 |
| `sort` | int | 排序权重 | 1 |

### 📋 订单字段 (Order)

| 字段名 | 类型 | 说明 | 示例值 |
|--------|------|------|--------|
| `id` | int | 订单ID | 1 |
| `user_id` | int | 用户ID | 1 |
| `plan_id` | int | 套餐ID | 1 |
| `invite_user_id` | int | 邀请人ID | 1 |
| `coupon_id` | int | 优惠券ID | 1 |
| `payment_id` | int | 支付方式ID | 1 |
| `type` | int | 订单类型 | 1=新购, 2=续费, 3=升级 |
| `period` | string | 计费周期 | "month_price", "quarter_price", "half_year_price", "year_price", "two_year_price", "three_year_price", "onetime_price", "reset_price" |
| `trade_no` | string | 订单号 | "ORDER123456789" |
| `callback_no` | string | 回调订单号 | "CALLBACK123" |
| `total_amount` | int | 总金额(分) | 1000 |
| `handling_amount` | int | 手续费(分) | 50 |
| `discount_amount` | int | 优惠金额(分) | 100 |
| `surplus_amount` | int | 剩余价值(分) | 500 |
| `refund_amount` | int | 退款金额(分) | 0 |
| `balance_amount` | int | 使用余额(分) | 200 |
| `surplus_order_ids` | text | 折抵订单ID列表 | "[1,2,3]" |
| `status` | tinyint | 订单状态 | 0=待支付, 1=开通中, 2=已取消, 3=已完成, 4=已折抵 |
| `commission_status` | tinyint | 佣金状态 | 0=待确认, 1=发放中, 2=有效, 3=无效 |
| `commission_balance` | int | 佣金余额(分) | 100 |
| `actual_commission_balance` | int | 实际支付佣金(分) | 80 |
| `paid_at` | int | 支付时间戳 | 1640995200 |
| `created_at` | int | 创建时间戳 | 1640995200 |
| `updated_at` | int | 更新时间戳 | 1640995200 |

### 🎫 工单字段 (Ticket)

| 字段名 | 类型 | 说明 | 示例值 |
|--------|------|------|--------|
| `id` | int | 工单ID | 1 |
| `user_id` | int | 用户ID | 1 |
| `subject` | string | 工单主题 | "问题标题" |
| `level` | tinyint | 工单级别 | 1=低, 2=中, 3=高 |
| `status` | tinyint | 工单状态 | 0=已开启, 1=已关闭 |
| `reply_status` | tinyint | 回复状态 | 0=待回复, 1=已回复 |
| `created_at` | int | 创建时间戳 | 1640995200 |
| `updated_at` | int | 更新时间戳 | 1640995200 |

### 🎫 优惠券字段 (Coupon)

| 字段名 | 类型 | 说明 | 示例值 |
|--------|------|------|--------|
| `id` | int | 优惠券ID | 1 |
| `code` | string | 优惠券代码 | "DISCOUNT10" |
| `name` | string | 优惠券名称 | "新用户优惠券" |
| `type` | tinyint | 优惠券类型 | 1=固定金额, 2=百分比折扣 |
| `value` | int | 优惠券面值 | 1000(分) 或 10(%) |
| `show` | tinyint | 是否显示 | 0=隐藏, 1=显示 |
| `limit_use` | int | 使用次数限制 | 100 |
| `limit_use_with_user` | int | 每用户使用限制 | 1 |
| `limit_plan_ids` | string | 限制套餐ID | "1,2,3" |
| `limit_period` | string | 限制计费周期 | "month_price,quarter_price" |
| `started_at` | int | 开始时间戳 | 1640995200 |
| `ended_at` | int | 结束时间戳 | 1641081600 |
| `created_at` | int | 创建时间戳 | 1640995200 |
| `updated_at` | int | 更新时间戳 | 1640995200 |

### 🎁 礼品卡字段 (Giftcard)

| 字段名 | 类型 | 说明 | 示例值 |
|--------|------|------|--------|
| `id` | int | 礼品卡ID | 1 |
| `code` | string | 礼品卡代码 | "GIFT123456" |
| `name` | string | 礼品卡名称 | "礼品卡" |
| `type` | tinyint | 礼品卡类型 | 1=余额, 2=套餐 |
| `value` | int | 礼品卡面值 | 1000(分) |
| `plan_id` | int | 套餐ID(类型2时) | 1 |
| `limit_use` | int | 使用次数限制 | 10 |
| `used_user_ids` | text | 已使用用户ID | "[1,2,3]" |
| `started_at` | int | 开始时间戳 | 1640995200 |
| `ended_at` | int | 结束时间戳 | 1641081600 |
| `created_at` | int | 创建时间戳 | 1640995200 |
| `updated_at` | int | 更新时间戳 | 1640995200 |

### 📢 公告字段 (Notice)

| 字段名 | 类型 | 说明 | 示例值 |
|--------|------|------|--------|
| `id` | int | 公告ID | 1 |
| `title` | string | 公告标题 | "系统维护通知" |
| `content` | text | 公告内容 | "系统将于今晚进行维护" |
| `show` | tinyint | 是否显示 | 0=隐藏, 1=显示 |
| `img_url` | string | 图片URL | "https://example.com/image.jpg" |
| `tags` | string | 标签 | "维护,通知" |
| `created_at` | int | 创建时间戳 | 1640995200 |
| `updated_at` | int | 更新时间戳 | 1640995200 |

### 📚 知识库字段 (Knowledge)

| 字段名 | 类型 | 说明 | 示例值 |
|--------|------|------|--------|
| `id` | int | 知识库ID | 1 |
| `language` | string | 语言代码 | "zh-CN" |
| `category` | string | 分类名称 | "教程" |
| `title` | string | 标题 | "使用教程" |
| `body` | text | 内容 | "详细的使用说明" |
| `sort` | int | 排序权重 | 1 |
| `show` | tinyint | 是否显示 | 0=隐藏, 1=显示 |
| `created_at` | int | 创建时间戳 | 1640995200 |
| `updated_at` | int | 更新时间戳 | 1640995200 |

### 🖥️ 服务器字段 (Server)

| 字段名 | 类型 | 说明 | 示例值 |
|--------|------|------|--------|
| `id` | int | 服务器ID | 1 |
| `group_id` | string | 服务器组ID | "1,2,3" |
| `route_id` | string | 路由规则ID | "1,2" |
| `name` | string | 服务器名称 | "香港节点1" |
| `parent_id` | int | 父节点ID | 1 |
| `host` | string | 主机地址 | "hk.example.com" |
| `port` | string | 连接端口 | "443" |
| `server_port` | int | 服务端口 | 443 |
| `tags` | string | 标签 | "香港,高速" |
| `rate` | string | 倍率 | "1.0" |
| `show` | tinyint | 是否显示 | 0=隐藏, 1=显示 |
| `sort` | int | 排序权重 | 1 |
| `created_at` | int | 创建时间戳 | 1640995200 |
| `updated_at` | int | 更新时间戳 | 1640995200 |

### 💳 支付方式字段 (Payment)

| 字段名 | 类型 | 说明 | 示例值 |
|--------|------|------|--------|
| `id` | int | 支付方式ID | 1 |
| `uuid` | string | 支付方式UUID | "payment-uuid" |
| `payment` | string | 支付方式标识 | "alipay" |
| `name` | string | 支付方式名称 | "支付宝" |
| `icon` | string | 图标URL | "https://example.com/icon.png" |
| `config` | text | 配置信息 | "{\"app_id\":\"123\"}" |
| `notify_domain` | string | 回调域名 | "api.example.com" |
| `handling_fee_fixed` | int | 固定手续费(分) | 50 |
| `handling_fee_percent` | decimal | 百分比手续费 | 2.50 |
| `enable` | tinyint | 是否启用 | 0=禁用, 1=启用 |
| `sort` | int | 排序权重 | 1 |
| `created_at` | int | 创建时间戳 | 1640995200 |
| `updated_at` | int | 更新时间戳 | 1640995200 |

---

## 🔢 枚举值说明

### 📋 订单相关枚举

#### 订单类型 (Order Type)
| 值 | 说明 | 描述 |
|---|------|------|
| `1` | 新购 | 用户首次购买套餐 |
| `2` | 续费 | 用户续费现有套餐 |
| `3` | 升级 | 用户升级到更高级套餐 |

#### 订单状态 (Order Status)
| 值 | 说明 | 描述 |
|---|------|------|
| `0` | 待支付 | 订单已创建，等待用户支付 |
| `1` | 开通中 | 支付成功，正在开通服务 |
| `2` | 已取消 | 订单被取消或支付超时 |
| `3` | 已完成 | 订单完成，服务已开通 |
| `4` | 已折抵 | 订单被其他订单折抵 |

#### 佣金状态 (Commission Status)
| 值 | 说明 | 描述 |
|---|------|------|
| `0` | 待确认 | 佣金待确认 |
| `1` | 发放中 | 佣金正在发放 |
| `2` | 有效 | 佣金有效 |
| `3` | 无效 | 佣金无效 |

#### 计费周期 (Period)
| 值 | 说明 | 描述 |
|---|------|------|
| `month_price` | 月付 | 按月计费 |
| `quarter_price` | 季付 | 按季度计费 |
| `half_year_price` | 半年付 | 按半年计费 |
| `year_price` | 年付 | 按年计费 |
| `two_year_price` | 两年付 | 按两年计费 |
| `three_year_price` | 三年付 | 按三年计费 |
| `onetime_price` | 一次性 | 一次性付费 |
| `reset_price` | 重置流量 | 重置流量包 |

### 🎫 工单相关枚举

#### 工单级别 (Ticket Level)
| 值 | 说明 | 描述 |
|---|------|------|
| `1` | 低 | 低优先级工单 |
| `2` | 中 | 中优先级工单 |
| `3` | 高 | 高优先级工单 |

#### 工单状态 (Ticket Status)
| 值 | 说明 | 描述 |
|---|------|------|
| `0` | 已开启 | 工单已开启，等待处理 |
| `1` | 已关闭 | 工单已关闭 |

#### 回复状态 (Reply Status)
| 值 | 说明 | 描述 |
|---|------|------|
| `0` | 待回复 | 等待回复 |
| `1` | 已回复 | 已回复 |

### 🎫 优惠券相关枚举

#### 优惠券类型 (Coupon Type)
| 值 | 说明 | 描述 |
|---|------|------|
| `1` | 固定金额 | 固定金额折扣 |
| `2` | 百分比折扣 | 按百分比折扣 |

### 🎁 礼品卡相关枚举

#### 礼品卡类型 (Giftcard Type)
| 值 | 说明 | 描述 |
|---|------|------|
| `1` | 余额 | 充值到账户余额 |
| `2` | 套餐 | 直接开通指定套餐 |

### 👤 用户相关枚举

#### 佣金类型 (Commission Type)
| 值 | 说明 | 描述 |
|---|------|------|
| `0` | 系统 | 系统佣金 |
| `1` | 周期 | 周期性佣金 |
| `2` | 一次性 | 一次性佣金 |

#### 重置方式 (Reset Traffic Method)
| 值 | 说明 | 描述 |
|---|------|------|
| `0` | 按月重置 | 每月重置流量 |
| `1` | 按年重置 | 每年重置流量 |
| `2` | 不重置 | 不自动重置流量 |

#### 布尔值枚举 (Boolean Values)
| 值 | 说明 | 描述 |
|---|------|------|
| `0` | 否/关闭/隐藏/禁用 | 否定状态 |
| `1` | 是/开启/显示/启用 | 肯定状态 |

### 🖥️ 服务器相关枚举

#### 节点类型 (Node Type)
| 值 | 说明 | 描述 |
|---|------|------|
| `vmess` | VMess | VMess协议节点 |
| `vless` | VLESS | VLESS协议节点 |
| `trojan` | Trojan | Trojan协议节点 |
| `shadowsocks` | Shadowsocks | SS协议节点 |
| `hysteria` | Hysteria | Hysteria协议节点 |
| `tuic` | TUIC | TUIC协议节点 |
| `anytls` | AnyTLS | AnyTLS协议节点 |

#### 传输方式 (Network)
| 值 | 说明 | 描述 |
|---|------|------|
| `tcp` | TCP | TCP传输 |
| `kcp` | KCP | KCP传输 |
| `ws` | WebSocket | WebSocket传输 |
| `http` | HTTP | HTTP传输 |
| `domainsocket` | Domain Socket | 域套接字传输 |
| `quic` | QUIC | QUIC传输 |
| `grpc` | gRPC | gRPC传输 |

### 💳 支付方式枚举

#### 支付方式标识 (Payment Method)
| 值 | 说明 | 描述 |
|---|------|------|
| `alipay` | 支付宝 | 支付宝支付 |
| `wechat` | 微信支付 | 微信支付 |
| `stripe` | Stripe | Stripe支付 |
| `paypal` | PayPal | PayPal支付 |
| `manual` | 手动支付 | 手动确认支付 |

---

## 🔐 认证方式

### JWT Token 认证
- **Header**: `Authorization: Bearer {token}`
- **Token有效期**: 1小时
- **自动续期**: 前端会自动刷新token
- **Base URL**: `/api/v1/`

---

## 👤 用户端 API (`/api/v1/user/`)

### 🔑 用户信息管理

#### 获取用户信息
```http
GET /api/v1/user/info
Authorization: Bearer {token}
```

**响应**:
```json
{
  "data": {
    "id": 1,
    "email": "user@example.com",
    "balance": 1000,
    "commission_balance": 500,
    "plan_id": 1,
    "expired_at": 1640995200,
    "transfer_enable": 107374182400,
    "u": 1073741824,
    "d": 2147483648,
    "device_limit": 3,
    "telegram_id": null,
    "discount": 0,
    "invite_user_id": null,
    "invite_user_email": null
  }
}
```

#### 获取订阅信息
```http
GET /api/v1/user/getSubscribe
Authorization: Bearer {token}
```

**响应**:
```json
{
  "data": {
    "plan_id": 1,
    "token": "uuid-string",
    "expired_at": 1640995200,
    "u": 1073741824,
    "d": 2147483648,
    "transfer_enable": 107374182400,
    "device_limit": 3,
    "email": "user@example.com",
    "uuid": "uuid-string",
    "plan": {
      "id": 1,
      "name": "基础套餐",
      "transfer_enable": 100,
      "device_limit": 3,
      "reset_price": 1000,
      "reset_traffic_method": 0
    },
    "alive_ip": 2,
    "subscribe_url": "https://domain.com/api/v1/client/subscribe?token=xxx",
    "reset_day": 5
  }
}
```

#### 更新用户信息
```http
POST /api/v1/user/update
Authorization: Bearer {token}
Content-Type: application/json

{
  "email": "new@example.com",
  "password": "newpassword"
}
```

#### 修改密码
```http
POST /api/v1/user/changePassword
Authorization: Bearer {token}
Content-Type: application/json

{
  "old_password": "oldpass",
  "new_password": "newpass"
}
```

#### 检查登录状态
```http
GET /api/v1/user/checkLogin
Authorization: Bearer {token}
```

**响应**:
```json
{
  "data": {
    "is_login": true,
    "is_admin": false
  }
}
```

### 💰 订单管理

#### 创建订单
```http
POST /api/v1/user/order/save
Authorization: Bearer {token}
Content-Type: application/json

{
  "plan_id": 1,
  "period": "month_price",
  "coupon_code": "DISCOUNT10"
}
```

**参数说明**:
- `plan_id`: 套餐ID
- `period`: 计费周期 (`month_price`, `quarter_price`, `half_year_price`, `year_price`, `two_year_price`, `three_year_price`, `onetime_price`, `reset_price`)
- `coupon_code`: 优惠券代码 (可选)

**响应**:
```json
{
  "data": "ORDER123456789"
}
```

#### 获取订单列表
```http
GET /api/v1/user/order/fetch?current=1&pageSize=10
Authorization: Bearer {token}
```

**查询参数**:
- `current`: 当前页码 (默认: 1)
- `pageSize`: 每页数量 (默认: 10)

---
#### 获取订单列表（标准化）

- API: `GET /api/v1/user/order/fetch`
- 入参（Query）:
  - `current` int 可选: 页码，默认1
  - `pageSize` int 可选: 每页数量，默认10
- 出参:
  - `data` array<Order>: 订单数组
  - `total` int: 总数量（若后端返回）
- 字段说明: 见[订单字段](#-订单字段-order)

---
#### 订单详情（标准化）

- API: `GET /api/v1/user/order/detail`
- 入参（Query）:
  - `id` int 必填: 订单ID
- 出参:
  - `data` Order: 订单详情
- 字段说明: 见[订单字段](#-订单字段-order)

---
#### 取消订单（标准化，用户端）

- API: `POST /api/v1/user/order/cancel`
- 入参:
  - `trade_no` string 必填: 订单号
- 出参:
  - `data` bool: 是否成功
- 字段说明:
  - 仅可取消`status=0`待支付订单

---
#### 获取支付方式（标准化，用户端）

- API: `GET /api/v1/user/order/getPaymentMethod`
- 入参: 无
- 出参:
  - `data` array<Payment>: 可用支付方式数组
- 字段说明: 见[支付方式字段](#-支付方式字段-payment)

---
#### 获取套餐列表（标准化，用户端）

- API: `GET /api/v1/user/plan/fetch`
- 入参: 无
- 出参:
  - `data` array<Plan>: 套餐数组
- 字段说明: 见[套餐字段](#-套餐字段-plan)

---
#### 创建工单（标准化）

- API: `POST /api/v1/user/ticket/save`
- 入参:
  - `subject` string 必填: 工单主题
  - `level` int 必填: 工单级别（1/2/3）
  - `message` string 必填: 工单内容
- 出参:
  - `data` bool: 是否成功
- 字段说明: 见[工单字段](#-工单字段-ticket)

---
#### 获取工单列表（标准化）

- API: `GET /api/v1/user/ticket/fetch`
- 入参（Query）:
  - `current` int 可选: 页码
  - `pageSize` int 可选: 每页数量
- 出参:
  - `data` array<Ticket>: 工单数组
  - `total` int: 总数（若后端返回）
- 字段说明: 见[工单字段](#-工单字段-ticket)

---
#### 回复工单（标准化）

- API: `POST /api/v1/user/ticket/reply`
- 入参:
  - `id` int 必填: 工单ID
  - `message` string 必填: 回复内容
- 出参:
  - `data` bool: 是否成功
- 字段说明:
  - 回复成功后工单`reply_status`将更新

---
#### 关闭工单（标准化）

- API: `POST /api/v1/user/ticket/close`
- 入参:
  - `id` int 必填: 工单ID
- 出参:
  - `data` bool: 是否成功

---
#### 兑换礼品卡（标准化）

- API: `POST /api/v1/user/redeemgiftcard`
- 入参:
  - `giftcard_code` string 必填: 礼品卡代码
- 出参:
  - `data.balance` int: 当前余额（分）
  - `data.giftcard_balance` int: 本次礼品卡贡献余额（分）
- 字段说明: 见[礼品卡字段](#-礼品卡字段-giftcard)

---
#### 获取邀请信息（标准化）

- API: `GET /api/v1/user/invite/fetch`
- 入参: 无
- 出参:
  - `data` object: 邀请相关信息（邀请链接、统计等）

---
#### 生成邀请码（标准化）

- API: `GET /api/v1/user/invite/save`
- 入参: 无
- 出参:
  - `data` string: 新的邀请码或生成结果

---
#### 邀请详情（标准化）

- API: `GET /api/v1/user/invite/details`
- 入参: 无
- 出参:
  - `data` array: 被邀请用户或明细数组

---
#### 获取用户信息（标准化）

- API: `GET /api/v1/user/info`
- 入参: 无
- 出参:
  - `data` User: 用户信息
- 字段说明: 见[用户字段](#-用户字段-user)

---
#### 获取订阅信息（标准化）

- API: `GET /api/v1/user/getSubscribe`
- 入参: 无
- 出参:
  - `data` object: 订阅信息（含`plan`与`reset_day`等）
- 字段说明:
  - `reset_day` int|null: 距离重置天数；null表示不重置或无效

---
#### 修改密码（标准化）

- API: `POST /api/v1/user/changePassword`
- 入参:
  - `old_password` string 必填: 旧密码
  - `new_password` string 必填: 新密码
- 出参:
  - `data` bool: 是否成功
- 说明:
  - 成功后将清除所有登录会话

---
#### 检查登录状态（标准化）

- API: `GET /api/v1/user/checkLogin`
- 入参: 无
- 出参:
  - `data.is_login` bool: 是否已登录
  - `data.is_admin` bool 可选: 是否管理员

### 💳 支付处理

#### 订单支付
```http
POST /api/v1/user/order/checkout
Authorization: Bearer {token}
Content-Type: application/json

{
  "trade_no": "ORDER123456789",
  "method": 1,
  "token": "stripe_token"
}
```

**参数说明**:
- `trade_no`: 订单号
- `method`: 支付方式ID
- `token`: Stripe支付token (可选)

**响应**:
```json
{
  "type": 1,
  "data": {
    "url": "https://qr.alipay.com/xxx",
    "qr_code": "data:image/png;base64,xxx"
  }
}
```

**支付类型说明**:
- `type: -1`: 免费订单，直接完成
- `type: 0`: 跳转支付页面
- `type: 1`: 显示二维码支付
- `type: 2`: 显示支付链接

#### 检查支付状态
```http
POST /api/v1/user/order/check
Authorization: Bearer {token}
Content-Type: application/json

{
  "trade_no": "ORDER123456789"
}
```

**响应**:
```json
{
  "data": 3
}
```

**订单状态说明**:
- `0`: 待支付
- `1`: 开通中
- `2`: 已取消
- `3`: 已完成
- `4`: 已折抵

### 📦 套餐管理

#### 获取套餐列表
```http
GET /api/v1/user/plan/fetch
Authorization: Bearer {token}
```

### 🎫 工单管理

#### 创建工单
```http
POST /api/v1/user/ticket/save
Authorization: Bearer {token}
Content-Type: application/json

{
  "subject": "问题标题",
  "level": 1,
  "message": "问题描述"
}
```

**参数说明**:
- `subject`: 工单主题
- `level`: 工单级别 (1=低, 2=中, 3=高)
- `message`: 问题描述

#### 获取工单列表
```http
GET /api/v1/user/ticket/fetch?current=1&pageSize=10
Authorization: Bearer {token}
```

#### 回复工单
```http
POST /api/v1/user/ticket/reply
Authorization: Bearer {token}
Content-Type: application/json

{
  "id": 1,
  "message": "回复内容"
}
```

#### 关闭工单
```http
POST /api/v1/user/ticket/close
Authorization: Bearer {token}
Content-Type: application/json

{
  "id": 1
}
```

### 🎁 礼品卡兑换

#### 兑换礼品卡
```http
POST /api/v1/user/redeemgiftcard
Authorization: Bearer {token}
Content-Type: application/json

{
  "giftcard_code": "GIFT123456"
}
```

**响应**:
```json
{
  "data": {
    "balance": 1000,
    "giftcard_balance": 500
  }
}
```

### 🔗 邀请管理

#### 获取邀请信息
```http
GET /api/v1/user/invite/fetch
Authorization: Bearer {token}
```

#### 生成邀请码
```http
GET /api/v1/user/invite/save
Authorization: Bearer {token}
```

#### 邀请详情
```http
GET /api/v1/user/invite/details
Authorization: Bearer {token}
```

### 📊 统计信息

#### 获取流量统计
```http
GET /api/v1/user/getStat
Authorization: Bearer {token}
```

#### 获取流量日志
```http
GET /api/v1/user/stat/getTrafficLog?current=1&pageSize=10
Authorization: Bearer {token}
```

### 🖥️ 服务器管理

#### 获取服务器列表
```http
GET /api/v1/user/server/fetch
Authorization: Bearer {token}
```

### 📢 公告管理

#### 获取公告列表
```http
GET /api/v1/user/notice/fetch?current=1&pageSize=10
Authorization: Bearer {token}
```

### 🎫 优惠券管理

#### 验证优惠券
```http
POST /api/v1/user/coupon/check
Authorization: Bearer {token}
Content-Type: application/json

{
  "coupon_code": "DISCOUNT10"
}
```

### 📚 知识库

#### 获取知识库列表
```http
GET /api/v1/user/knowledge/fetch?current=1&pageSize=10
Authorization: Bearer {token}
```

#### 获取知识库分类
```http
GET /api/v1/user/knowledge/getCategory
Authorization: Bearer {token}
```

---

## 👨‍💼 管理端 API (`/api/v1/{admin_path}/`)

### 👥 用户管理

#### 获取用户列表
```http
GET /api/v1/{admin_path}/user/fetch?current=1&pageSize=20&email=test@example.com
Authorization: Bearer {admin_token}
```

**查询参数**:
- `current`: 当前页码
- `pageSize`: 每页数量
- `email`: 邮箱筛选 (可选)
- `plan_id`: 套餐筛选 (可选)

#### 创建用户
```http
POST /api/v1/{admin_path}/user/save
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "plan_id": 1,
  "expired_at": 1640995200,
  "transfer_enable": 107374182400,
  "device_limit": 3,
  "balance": 0,
  "commission_rate": 0
}
```

#### 更新用户
```http
POST /api/v1/{admin_path}/user/update
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "id": 1,
  "email": "new@example.com",
  "plan_id": 2,
  "expired_at": 1640995200,
  "transfer_enable": 214748364800,
  "device_limit": 5,
  "balance": 1000,
  "commission_rate": 10
}
```

#### 删除用户
```http
POST /api/v1/{admin_path}/user/drop
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "id": 1
}
```

### 📦 套餐管理

#### 获取套餐列表
```http
GET /api/v1/{admin_path}/plan/fetch
Authorization: Bearer {admin_token}
```

#### 创建套餐
```http
POST /api/v1/{admin_path}/plan/save
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "name": "高级套餐",
  "content": "套餐描述",
  "group_id": [1],
  "transfer_enable": 500,
  "device_limit": 5,
  "month_price": 2000,
  "quarter_price": 5000,
  "half_year_price": 9000,
  "year_price": 16000,
  "two_year_price": 30000,
  "three_year_price": 42000,
  "onetime_price": 10000,
  "reset_price": 1000,
  "reset_traffic_method": 0,
  "show": 1,
  "renew": 1,
  "sort": 1
}
```

#### 更新套餐
```http
POST /api/v1/{admin_path}/plan/update
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "id": 1,
  "name": "更新后的套餐名",
  "month_price": 2500
}
```

#### 删除套餐
```http
POST /api/v1/{admin_path}/plan/drop
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "id": 1
}
```

### 🖥️ 服务器管理

#### 获取服务器组列表
```http
GET /api/v1/{admin_path}/server/group/fetch
Authorization: Bearer {admin_token}
```

#### 创建服务器组
```http
POST /api/v1/{admin_path}/server/group/save
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "name": "服务器组1"
}
```

#### 获取路由规则列表
```http
GET /api/v1/{admin_path}/server/route/fetch
Authorization: Bearer {admin_token}
```

#### 创建路由规则
```http
POST /api/v1/{admin_path}/server/route/save
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "remarks": "规则备注",
  "match": ["domain:example.com"],
  "action": "block",
  "action_value": ""
}
```

### 📊 订单管理

#### 获取订单列表
```http
GET /api/v1/{admin_path}/order/fetch?current=1&pageSize=20
Authorization: Bearer {admin_token}
```

#### 订单详情
```http
GET /api/v1/{admin_path}/order/detail?id=1
Authorization: Bearer {admin_token}
```

#### 更新订单状态
```http
POST /api/v1/{admin_path}/order/update
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "id": 1,
  "status": 3
}
```

**状态说明**:
- `0`: 待支付
- `1`: 开通中
- `2`: 已取消
- `3`: 已完成
- `4`: 已折抵

#### 手动确认支付
```http
POST /api/v1/{admin_path}/order/paid
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "trade_no": "ORDER123456789"
}
```

#### 取消订单
```http
POST /api/v1/{admin_path}/order/cancel
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "trade_no": "ORDER123456789"
}
```

### 💳 支付管理

#### 获取支付方式列表
```http
GET /api/v1/{admin_path}/payment/fetch
Authorization: Bearer {admin_token}
```

**响应**:
```json
{
  "data": [
    {
      "id": 1,
      "uuid": "payment-uuid",
      "name": "支付宝",
      "payment": "alipay",
      "icon": "https://example.com/alipay.png",
      "config": "{\"app_id\":\"123\"}",
      "notify_domain": "https://api.example.com",
      "notify_url": "https://api.example.com/api/v1/guest/payment/notify/alipay/payment-uuid",
      "handling_fee_fixed": 0,
      "handling_fee_percent": 0,
      "enable": 1,
      "sort": 1,
      "created_at": 1640995200,
      "updated_at": 1640995200
    }
  ]
}
```

#### 获取可用支付网关
```http
GET /api/v1/{admin_path}/payment/getPaymentMethods
Authorization: Bearer {admin_token}
```

**响应**:
```json
{
  "data": [
    "Alipay",
    "WechatPay", 
    "Stripe",
    "PayPal",
    "Manual"
  ]
}
```

#### 创建支付方式
```http
POST /api/v1/{admin_path}/payment/save
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "name": "支付宝",
  "icon": "https://example.com/alipay.png",
  "payment": "Alipay",
  "config": "{\"app_id\":\"123\",\"private_key\":\"xxx\"}",
  "notify_domain": "https://api.example.com",
  "handling_fee_fixed": 0,
  "handling_fee_percent": 0
}
```

**参数说明**:
- `name`: 支付方式显示名称
- `icon`: 支付方式图标URL
- `payment`: 支付网关标识
- `config`: 支付配置JSON字符串
- `notify_domain`: 自定义回调域名
- `handling_fee_fixed`: 固定手续费(分)
- `handling_fee_percent`: 百分比手续费

#### 更新支付方式
```http
POST /api/v1/{admin_path}/payment/save
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "id": 1,
  "name": "支付宝支付",
  "config": "{\"app_id\":\"456\"}"
}
```

#### 删除支付方式
```http
POST /api/v1/{admin_path}/payment/drop
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "id": 1
}
```

#### 启用/禁用支付方式
```http
POST /api/v1/{admin_path}/payment/show
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "id": 1
}
```

#### 获取支付配置表单
```http
GET /api/v1/{admin_path}/payment/getPaymentForm?payment=Alipay&id=1
Authorization: Bearer {admin_token}
```

**查询参数**:
- `payment`: 支付网关标识
- `id`: 支付方式ID (可选)

**响应**:
```json
{
  "data": {
    "form": [
      {
        "name": "app_id",
        "label": "应用ID",
        "type": "text",
        "required": true
      },
      {
        "name": "private_key", 
        "label": "私钥",
        "type": "textarea",
        "required": true
      }
    ]
  }
}
```

#### 支付方式排序
```http
POST /api/v1/{admin_path}/payment/sort
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "ids": [1, 2, 3]
}
```

### 🎫 工单管理

#### 获取工单列表
```http
GET /api/v1/{admin_path}/ticket/fetch?current=1&pageSize=20
Authorization: Bearer {admin_token}
```

#### 回复工单
```http
POST /api/v1/{admin_path}/ticket/reply
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "id": 1,
  "message": "管理员回复"
}
```

#### 关闭工单
```http
POST /api/v1/{admin_path}/ticket/close
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "id": 1
}
```

### 🎫 优惠券管理

#### 获取优惠券列表
```http
GET /api/v1/{admin_path}/coupon/fetch?current=1&pageSize=20
Authorization: Bearer {admin_token}
```

#### 生成优惠券
```http
POST /api/v1/{admin_path}/coupon/generate
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "name": "新用户优惠券",
  "type": 1,
  "value": 1000,
  "limit_plan_ids": [1, 2],
  "limit_period": ["month_price", "quarter_price"],
  "limit_use": 100,
  "limit_use_with_user": 1,
  "expired_at": 1640995200
}
```

#### 删除优惠券
```http
POST /api/v1/{admin_path}/coupon/drop
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "id": 1
}
```

### 🎁 礼品卡管理

#### 获取礼品卡列表
```http
GET /api/v1/{admin_path}/giftcard/fetch?current=1&pageSize=20
Authorization: Bearer {admin_token}
```

#### 生成礼品卡
```http
POST /api/v1/{admin_path}/giftcard/generate
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "name": "礼品卡",
  "value": 1000,
  "count": 10,
  "expired_at": 1640995200
}
```

### 📢 公告管理

#### 获取公告列表
```http
GET /api/v1/{admin_path}/notice/fetch?current=1&pageSize=20
Authorization: Bearer {admin_token}
```

#### 创建公告
```http
POST /api/v1/{admin_path}/notice/save
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "title": "系统维护通知",
  "content": "系统将于今晚进行维护",
  "img_url": "https://example.com/image.jpg",
  "show": 1
}
```

### 📚 知识库管理

#### 获取知识库列表
```http
GET /api/v1/{admin_path}/knowledge/fetch?current=1&pageSize=20
Authorization: Bearer {admin_token}
```

#### 创建知识库
```http
POST /api/v1/{admin_path}/knowledge/save
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "title": "使用教程",
  "content": "详细的使用说明",
  "category": "教程",
  "show": 1,
  "sort": 1
}
```

### ⚙️ 系统配置

#### 获取系统配置
```http
GET /api/v1/{admin_path}/config/fetch
Authorization: Bearer {admin_token}
```

#### 保存系统配置
```http
POST /api/v1/{admin_path}/config/save
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "app_name": "V2Board",
  "app_url": "https://example.com",
  "app_description": "V2Board 管理面板",
  "register_limit_by_ip_enable": 1,
  "register_limit_count": 3,
  "email_whitelist_enable": 0,
  "recaptcha_enable": 0
}
```

### 📊 统计分析

#### 获取订单统计
```http
GET /api/v1/{admin_path}/stat/getOrder?start=1640995200&end=1641081600
Authorization: Bearer {admin_token}
```

#### 获取用户统计
```http
GET /api/v1/{admin_path}/stat/getStatUser?start=1640995200&end=1641081600
Authorization: Bearer {admin_token}
```

#### 获取排行榜
```http
GET /api/v1/{admin_path}/stat/getRanking?start=1640995200&end=1641081600
Authorization: Bearer {admin_token}
```

---

## 👨‍💻 员工端 API (`/api/v1/staff/`)

### 👥 用户管理

#### 获取用户信息
```http
GET /api/v1/staff/user/getUserInfoById?id=1
Authorization: Bearer {staff_token}
```

#### 更新用户信息
```http
POST /api/v1/staff/user/update
Authorization: Bearer {staff_token}
Content-Type: application/json

{
  "id": 1,
  "email": "new@example.com",
  "plan_id": 2,
  "expired_at": 1640995200
}
```

#### 发送邮件
```http
POST /api/v1/staff/user/sendMail
Authorization: Bearer {staff_token}
Content-Type: application/json

{
  "user_ids": [1, 2, 3],
  "subject": "邮件主题",
  "template_name": "custom",
  "template_value": {
    "content": "邮件内容"
  }
}
```

#### 封禁用户
```http
POST /api/v1/staff/user/ban
Authorization: Bearer {staff_token}
Content-Type: application/json

{
  "id": 1,
  "ban": 1
}
```

### 🎫 工单管理

#### 获取工单列表
```http
GET /api/v1/staff/ticket/fetch?current=1&pageSize=20
Authorization: Bearer {staff_token}
```

#### 回复工单
```http
POST /api/v1/staff/ticket/reply
Authorization: Bearer {staff_token}
Content-Type: application/json

{
  "id": 1,
  "message": "员工回复"
}
```

#### 关闭工单
```http
POST /api/v1/staff/ticket/close
Authorization: Bearer {staff_token}
Content-Type: application/json

{
  "id": 1
}
```

---

## 🌐 访客端 API (`/api/v1/guest/`)

### 📦 套餐查询

#### 获取公开套餐列表
```http
GET /api/v1/guest/plan/fetch
```

**响应**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "基础套餐",
      "content": "适合个人用户",
      "transfer_enable": 100,
      "device_limit": 3,
      "month_price": 1000,
      "quarter_price": 2500,
      "half_year_price": 4500,
      "year_price": 8000,
      "show": 1,
      "sort": 1
    }
  ]
}
```

### 📢 公开公告

#### 获取公开公告列表
```http
GET /api/v1/guest/notice/fetch
```

**查询参数**:
| 参数 | 类型 | 说明 |
|------|------|------|
| id | number | 可选，获取单条公告 |
| current | number | 页码，默认 1 |
| pageSize | number | 每页条数，默认 5，最大 100 |

**响应**:
```json
{
  "data": [
    {
      "id": 1,
      "title": "公告标题",
      "content": "公告内容",
      "show": 1,
      "img_url": null,
      "tags": ["购买页"],
      "created_at": 1234567890,
      "updated_at": 1234567890
    }
  ],
  "total": 1
}
```

### 📱 客户端版本信息

#### 获取客户端版本
```http
GET /api/v1/guest/app/getVersion
```

**响应**:
```json
{
  "data": {
    "windows_version": "1.0.0",
    "windows_download_url": "https://example.com/windows.exe",
    "macos_version": "1.0.0",
    "macos_download_url": "https://example.com/macos.dmg",
    "android_version": "1.0.0",
    "android_download_url": "https://example.com/android.apk",
    "ios_version": "1.0.0",
    "ios_download_url": "https://example.com/ios.ipa",
    "linux_version": "1.0.0",
    "linux_download_url": "https://example.com/linux.AppImage"
  }
}
```

### ⚙️ 公共配置

#### 获取公共配置
```http
GET /api/v1/guest/comm/config
```

**响应**:
```json
{
  "data": {
    "tos_url": "https://example.com/tos",
    "is_email_verify": 1,
    "is_invite_force": 0,
    "email_whitelist_suffix": ["gmail.com", "qq.com"],
    "is_recaptcha": 1,
    "recaptcha_site_key": "6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI",
    "app_name": "V2Board",
    "app_description": "V2Board is best!",
    "app_url": "https://example.com",
    "logo": "https://example.com/logo.png"
  }
}
```

**字段说明**:
- `tos_url`: 服务条款URL
- `is_email_verify`: 是否启用邮箱验证 (0=否, 1=是)
- `is_invite_force`: 是否强制邀请注册 (0=否, 1=是)
- `email_whitelist_suffix`: 邮箱白名单后缀数组
- `is_recaptcha`: 是否启用reCAPTCHA验证 (0=否, 1=是)
- `recaptcha_site_key`: reCAPTCHA站点密钥
- `app_name`: 站点名称
- `app_description`: 站点描述
- `app_url`: 站点URL
- `logo`: 站点Logo URL

### 💳 支付回调

#### 支付回调接口
```http
POST /api/v1/guest/payment/notify/{method}/{uuid}
Content-Type: application/x-www-form-urlencoded
```

**路径参数**:
- `method`: 支付方式标识 (alipay, wechat, stripe等)
- `uuid`: 支付方式UUID

**请求体**: 支付平台回调的原始数据

**响应**:
- 成功: `success` 或自定义响应内容
- 失败: HTTP 500 错误

**处理流程**:
1. 验证支付平台签名
2. 查找对应订单
3. 更新订单状态为已支付
4. 触发订单开通流程
5. 发送Telegram通知(如果配置)

---

## 🔐 认证端 API (`/api/v1/passport/`)

### 🔑 用户认证

#### 用户注册
```http
POST /api/v1/passport/auth/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "invite_code": "INVITE123",
  "recaptcha_data": "recaptcha_token"
}
```

**参数说明**:
- `email`: 邮箱地址
- `password`: 密码
- `invite_code`: 邀请码 (可选)
- `recaptcha_data`: reCAPTCHA验证码 (可选)

#### 用户登录
```http
POST /api/v1/passport/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "recaptcha_data": "recaptcha_token"
}
```

**响应**:
```json
{
  "data": {
    "token": "jwt_token_string",
    "expired_at": 1640995200
  }
}
```

#### Token转登录
```http
GET /api/v1/passport/auth/token2Login?token=temp_token
```

#### 忘记密码
```http
POST /api/v1/passport/auth/forget
Content-Type: application/json

{
  "email": "user@example.com",
  "recaptcha_data": "recaptcha_token"
}
```

#### 邮件链接登录
```http
POST /api/v1/passport/auth/getQuickLoginUrl
Content-Type: application/json

{
  "email": "user@example.com",
  "redirect": "/dashboard"
}
```

### 📧 邮件验证

#### 发送邮件验证码
```http
POST /api/v1/passport/comm/sendEmailVerify
Content-Type: application/json

{
  "email": "user@example.com",
  "recaptcha_data": "recaptcha_token"
}
```

### 📊 统计

#### 页面访问统计
```http
POST /api/v1/passport/comm/pv
Content-Type: application/json

{
  "path": "/dashboard",
  "user_agent": "Mozilla/5.0..."
}
```

---

## 💻 客户端 API (`/api/v1/client/`)

### 📱 客户端配置

#### 获取客户端配置
```http
GET /api/v1/client/app/getConfig
Authorization: Bearer {client_token}
```

#### 获取客户端版本
```http
GET /api/v1/client/app/getVersion
Authorization: Bearer {client_token}
```

### 📡 订阅链接

#### 获取订阅链接
```http
GET /api/v1/client/subscribe?token={user_token}
Authorization: Bearer {client_token}
```

**响应**: 返回订阅配置文件内容

---

## 🖥️ 服务端 API (`/api/v1/server/`)

### 👥 用户管理

#### 获取用户列表
```http
POST /api/v1/server/UniProxy/user
Content-Type: application/json

{
  "token": "server_token",
  "node_type": "vmess",
  "node_id": 1
}
```

**参数说明**:
- `token`: 服务器认证token
- `node_type`: 节点类型 (vmess, vless, trojan, shadowsocks, hysteria, tuic)
- `node_id`: 节点ID

#### 获取节点配置
```http
POST /api/v1/server/UniProxy/config
Content-Type: application/json

{
  "token": "server_token",
  "node_type": "vmess",
  "node_id": 1
}
```

#### 用户流量上报
```http
POST /api/v1/server/UniProxy/push
Content-Type: application/json

{
  "token": "server_token",
  "node_type": "vmess",
  "node_id": 1,
  "data": [
    {
      "user_id": 1,
      "u": 1073741824,
      "d": 2147483648
    }
  ]
}
```

---

## ❌ 错误码说明

### HTTP 状态码
- `200`: 请求成功
- `400`: 请求参数错误
- `401`: 未授权/Token无效
- `403`: 权限不足
- `404`: 资源不存在
- `500`: 服务器内部错误

### 业务错误码
- `500`: 通用错误
- `500`: 用户不存在
- `500`: 订阅计划不存在
- `500`: 订单不存在
- `500`: 优惠券无效
- `500`: 余额不足
- `500`: 订阅已过期
- `500`: 设备数量超限
- `500`: 流量已用尽

### 错误响应格式
```json
{
  "message": "错误描述信息",
  "errors": {
    "field": ["具体错误信息"]
  }
}
```

---

## 📝 注意事项

1. **认证**: 所有需要认证的接口都需要在Header中携带有效的JWT Token
2. **频率限制**: 部分接口有频率限制，请合理控制请求频率
3. **参数验证**: 所有POST请求的参数都会进行严格验证
4. **时区**: 所有时间戳均为Unix时间戳（秒）
5. **流量单位**: 流量相关字段单位为字节（Byte）
6. **金额单位**: 金额相关字段单位为分（Cent）

---

## 🔄 更新日志

- **v1.0.0**: 初始版本API文档
- **v1.1.0**: 新增iOS和Linux客户端版本支持
- **v1.2.0**: 新增重置流量功能API说明

---

*本文档最后更新时间: 2024年12月*
