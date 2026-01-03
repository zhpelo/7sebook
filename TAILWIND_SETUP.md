# Tailwind CSS 设置说明

## 已完成的配置

1. ✅ Tailwind CSS 已安装（在 `package.json` 中`）
2. ✅ Vite 已配置 Tailwind CSS 插件
3. ✅ `resources/css/app.css` 已配置 Tailwind
4. ✅ 所有视图文件已更新为使用 Tailwind CSS 类

## 运行步骤

### 1. 安装依赖
```bash
cd laravel-7sebook
npm install
```

### 2. 开发模式（自动编译）
```bash
npm run dev
```

### 3. 生产模式（构建）
```bash
npm run build
```

## 注意事项

- 开发时运行 `npm run dev`，Vite 会自动监听文件变化并重新编译
- 生产环境需要先运行 `npm run build` 生成编译后的文件
- 确保 Laravel 的 `APP_ENV` 和 `APP_DEBUG` 配置正确

## 视图文件更新

所有视图文件已更新为使用 Tailwind CSS 类：
- `layout.blade.php` - 主布局
- `search.blade.php` - 搜索页面
- `detail.blade.php` - 书籍详情
- `login.blade.php` - 登录页面
- `register.blade.php` - 注册页面
- `latest.blade.php` - 最近更新
- 其他视图文件也已更新

## 自定义样式

如果需要自定义 Tailwind 配置，可以编辑 `resources/css/app.css` 文件中的 `@theme` 部分。
