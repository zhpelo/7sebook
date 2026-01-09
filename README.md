# 七秒电子书

一个基于 Laravel 12 构建的现代化电子书管理系统，提供电子书搜索、下载、管理等功能。

## 📋 项目简介

七秒电子书是一个功能完整的电子书资源管理平台，支持用户搜索、浏览、下载电子书，并提供完善的后台管理系统。系统采用 Laravel 12 框架开发，使用 Tailwind CSS 构建现代化响应式界面。

## ✨ 主要功能

### 前台功能

- **搜索功能**
  - 支持按书名、作者、描述搜索
  - 支持按文件格式筛选（PDF、EPUB、MOBI、AZW3、TXT、ZIP等）
  - 搜索历史记录
  - 热门搜索关键词（支持按今天/本周/本月/全部筛选）
  - 分页显示搜索结果

- **书籍管理**
  - 书籍详情页（封面、简介、标签等）
  - 相关推荐书籍
  - 标签分类浏览
  - 下载排行
  - 最新书籍列表

- **用户功能**
  - 用户注册/登录
  - 忘记密码/重置密码
  - 个人中心
  - 点赞和收藏功能
  - 我的点赞/收藏列表

- **下载功能**
  - 登录后下载
  - 每日下载限制（5次）
  - 防重复点击保护
  - 下载日志记录

- **其他功能**
  - Sitemap 自动生成
  - 响应式设计（支持移动端）
  - 深色模式支持

### 后台管理

- **仪表盘**
  - 统计信息（用户数、书籍数、下载次数等）
  - 数据可视化图表
  - 系统信息展示

- **用户管理**
  - 用户列表（支持搜索）
  - 添加/编辑/删除用户
  - 批量删除用户

- **书籍管理**
  - 书籍列表（支持搜索）
  - 添加/编辑/删除书籍
  - 批量删除书籍
  - 标签管理

- **数据统计**
  - 下载记录管理
  - 搜索历史管理
  - 邮件发送记录

- **标签管理**
  - 标签列表
  - 添加/编辑/删除标签
  - 标签合并功能

- **系统设置**
  - 管理员信息修改

## 🛠️ 技术栈

### 后端
- **PHP**: ^8.2
- **Laravel**: ^12.0
- **数据库**: SQLite（可切换为 MySQL/PostgreSQL）

### 前端
- **Tailwind CSS**: ^4.0.0
- **Vite**: ^7.0.7
- **Font Awesome**: 6.4.0（图标库）
- **Chart.js**: （后台图表）

### 开发工具
- **Laravel Pint**: 代码格式化
- **PHPUnit**: 单元测试
- **Laravel Sail**: Docker 开发环境

## 📦 安装部署

### 环境要求

- PHP >= 8.2
- Composer
- Node.js >= 18.x
- npm 或 yarn

### 快速开始

1. **克隆项目**
```bash
git clone <repository-url>
cd 7sebook
```

2. **安装 PHP 依赖**
```bash
composer install
```

3. **配置环境变量**
```bash
cp .env.example .env
php artisan key:generate
```

编辑 `.env` 文件，配置数据库连接等信息：
```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

4. **运行数据库迁移**
```bash
php artisan migrate
```

5. **安装前端依赖**
```bash
npm install
```

6. **构建前端资源**
```bash
npm run build
```

7. **启动开发服务器**
```bash
# 使用 Laravel 内置服务器
php artisan serve

# 或使用开发脚本（包含 Vite 热重载）
composer run dev
```

访问 `http://localhost:8000` 查看应用。

### 生产环境部署

1. **优化配置**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

2. **构建前端资源**
```bash
npm run build
```

3. **设置存储链接**
```bash
php artisan storage:link
```

4. **配置 Web 服务器**
   - Apache/Nginx 指向 `public` 目录
   - 确保 `storage` 和 `bootstrap/cache` 目录可写

## 📁 项目结构

```
7sebook/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # 控制器
│   │   │   ├── Admin/            # 后台管理控制器
│   │   │   └── ...
│   │   └── Middleware/           # 中间件
│   ├── Models/                   # 数据模型
│   ├── Services/                 # 业务逻辑服务
│   └── Helpers/                  # 辅助函数
├── database/
│   ├── migrations/               # 数据库迁移
│   └── seeders/                  # 数据填充
├── resources/
│   ├── views/                    # Blade 模板
│   │   ├── admin/                # 后台视图
│   │   └── ...                   # 前台视图
│   ├── css/                      # CSS 文件
│   └── js/                       # JavaScript 文件
├── routes/
│   └── web.php                   # 路由定义
├── storage/
│   └── app/                      # 存储文件（书籍文件等）
└── public/                       # 公共资源
```

## 🔧 配置说明

### 数据库配置

默认使用 SQLite，如需切换为 MySQL 或 PostgreSQL，修改 `.env` 文件：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 文件存储

书籍文件存储在 `storage/app/private/` 目录，确保该目录有写入权限。

### 邮件配置

如需启用邮件功能（如密码重置），配置 `.env` 中的邮件设置：

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
```

## 🎯 核心功能说明

### 下载限制

- 用户每日最多下载 5 次
- 10 秒内禁止重复下载同一本书
- 前端按钮防重复点击保护

### 搜索功能

- 支持全文搜索（书名、作者、描述）
- 搜索历史自动记录
- 热门搜索支持时间范围筛选（今天/本周/本月/全部）

### 安全特性

- CSRF 保护
- SQL 注入防护
- XSS 防护
- 文件下载权限验证
- 管理员权限中间件

## 📝 开发指南

### 代码规范

项目使用 Laravel Pint 进行代码格式化：

```bash
./vendor/bin/pint
```

### 运行测试

```bash
php artisan test
```

### 数据库迁移

创建新迁移：
```bash
php artisan make:migration create_example_table
```

运行迁移：
```bash
php artisan migrate
```

回滚迁移：
```bash
php artisan migrate:rollback
```

## 🔐 默认管理员

首次部署后，需要创建管理员账户。可以通过以下方式：

1. 使用数据库迁移创建默认管理员
2. 通过注册功能注册，然后在数据库中手动设置为管理员
3. 使用 Tinker 创建：

```bash
php artisan tinker
```

```php
$user = new App\Models\User();
$user->username = 'admin';
$user->email = 'admin@example.com';
$user->password = Hash::make('your_password');
$user->is_admin = 1;
$user->save();
```

## 📄 许可证

MIT License

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📞 联系方式

如有问题或建议，请通过 Issue 反馈。

## 🔄 更新日志

### 最新功能

- ✅ 下载按钮防重复点击优化
- ✅ 后端短时间内重复请求限制
- ✅ 热门搜索支持时间范围筛选
- ✅ 编辑书籍时禁止修改文件相关字段

---

**七秒电子书** - 让阅读更简单 🚀

