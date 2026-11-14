# pl-devtools

[English](README.md) | [简体中文](README.zh_CN.md) | [繁體中文](README.zh_TW.md) | [日本語](README.ja.md) | [한국어](README.ko.md)

[Moonsea](https://moonsea.dev) · [GitHub](https://github.com/coding1688/pl-devtools) · [Issues](https://github.com/coding1688/pl-devtools/issues)

## Laravel 请求分析与诊断工具

集中查看 SQL 时间线、请求信息、HTTP／缓存遥测、服务器信息、性能汇总、日志摘要和可配置的安全检查。无需业务模型或数据库迁移。Blade 页面可使用注入工具栏，API 项目可使用响应头和历史记录页面。

## 环境要求与安装

声明支持 PHP `^8.2`、Laravel 组件 `^11 | ^12 | ^13`；具体 Laravel 版本可能要求更新的 PHP。本文描述当前源码，请使用对应版本。Laravel 会自动发现服务提供者。需要工具的环境必须安装开发依赖。

```bash
composer require --dev coding1688/pl-devtools
php artisan vendor:publish --tag=devtools-config
```

如果 Composer 仓库无法找到包，先添加源码仓库，再执行安装命令：

```bash
composer config repositories.pl-devtools vcs https://github.com/coding1688/pl-devtools.git
```

## 本地启用

默认关闭。在允许的开发环境中同时开启以下配置。默认历史记录入口为 `/__debug/sql`。

```dotenv
APP_DEBUG=true
PL_DEVTOOLS_ENABLED=true
```

## 配置约定

使用 `config/devtools.php` 和 `devtools.*`。优先级为：包默认值 → 旧 `pl.devtools` → `devtools`。映射递归合并，列表整体替换，包括空列表。旧配置仅在服务提供者注册时导入；运行时修改应使用新路径。旧发布标签 `pl-devtools-config` 保留为别名。

```php
// config/devtools.php
'enabled' => env('PL_DEVTOOLS_ENABLED', false),
'environments' => ['local', 'testing'],
'hosts' => [],
'collect' => true,
'store_cache' => true,
'log' => false,
'toolbar' => true,
'authorization_gate' => null,
'locale' => null,
```

`collect` 控制 HTTP 采集；`store_cache` 保存请求历史；`log` 独立控制分析摘要日志。需要不持久保存分析数据时，同时关闭后两项。`toolbar=false` 只关闭 HTML 注入，保留采集与响应头。启用、debug、环境、域名与授权检查仍然生效。关闭采集也会阻止访问分析历史控制器。`hosts` 为空表示在允许环境内不限制域名。

## 共享环境与授权

不要将诊断入口公开暴露。配置允许的环境、域名，并定义宿主 Gate。以下示例依赖你在项目中实现的权限：

```php
use Illuminate\Support\Facades\Gate;

Gate::define('view-devtools', fn ($user) => $user->can('inspect-diagnostics'));

// config/devtools.php
'authorization_gate' => 'view-devtools',
```

Gate 同时保护采集、工具栏和历史访问。必要时通过 `devtools.middleware` 添加登录认证中间件。配置 Gate 名称本身不会建立登录状态，也不会验证 Bearer Token。历史路由使用 `web` 中间件组；清空操作为 POST。部署配置变化后重新生成路由和配置缓存。

## 语言

界面语言包括 `en`、`zh_CN`、`zh_TW`、`ja`、`ko`。显式 `devtools.locale` 优先，否则依次参考浏览器语言、应用语言及回退语言。控制台英文提示和技术标识并未全部翻译。

## 数据处理与故障隔离

对应详情记录不保存请求输入值和 SQL 绑定值。Referer、HTTP 失败消息和日志摘要经过脱敏，移除 URL 凭据、查询参数、片段及常见敏感键值。可用 `devtools.redact_keys` 扩展敏感键名。URL 路径、原始 SQL 字面量或任意业务文本里的秘密无法可靠识别；脱敏不能替代访问控制。

采集和工具栏失败不会覆盖正常业务响应或原始业务异常。诊断故障不会递归写入被监测的日志器。共享环境应限制历史容量和 TTL，并控制缓存、日志的访问。

## 命令行诊断

只读检查命令无需启用 HTTP 分析。`--fresh` 跳过报告缓存，`--json` 输出 JSON。`--fail-on` 支持 `none`、`info`、`low`、`medium`、`high`，默认 `high`；达到配置阈值的失败项可令命令返回非零状态。它检查配置风险，不替代完整安全评估。

```bash
php artisan devtools:audit
php artisan devtools:audit --json --fresh --fail-on=high
```

## 扩展

实现 `Collectors\Collector`，在 `devtools.collectors` 注册采集器；实现 `Diagnostics\Security\Check`，在 `devtools.security.checks` 注册宿主检查。以当前版本接口为准，不依赖特定后台框架。使用 `pl-devtools-views` 发布视图，使用 `pl-devtools-assets` 发布样式资源。

## 开发与升级

测试覆盖配置兼容、默认注册、访问控制、故障隔离、脱敏和五语键一致性。CI 定义了 PHP／Laravel 兼容任务，实际结果请查看对应版本。升级时将运行时 `pl.devtools.*` 写入改为 `devtools.*`，检查访问控制并重新生成宿主缓存。

```bash
composer install
composer test
```

## 许可证

[MIT License](LICENSE). Copyright © 2026 [coding1688 · Moonsea](https://moonsea.dev).
