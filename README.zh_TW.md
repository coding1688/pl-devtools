# pl-devtools

[English](README.md) | [简体中文](README.zh_CN.md) | [繁體中文](README.zh_TW.md) | [日本語](README.ja.md) | [한국어](README.ko.md)

Laravel 本機開發診斷工具：請求畫像、SQL 時間軸、伺服器資訊、安全性檢查、HTTP / 快取遙測、效能排行與日誌摘要，集中在同一頁面。

- PHP 8.2+，Laravel 11 / 12 / 13
- 預設關閉，需 `APP_DEBUG=true` 且明確開啟
- 與前端框架無關：Blade、Layui、Vue SPA
- 介面支援英文、簡體中文、繁體中文、日文、韓文
- 同一套安全檢查可跑 CI：`php artisan devtools:audit`

## 功能

| 面板 | 內容 |
| --- | --- |
| Request | 方法/路徑/狀態、路由、`controller@action`、登入使用者、Host/IP、白名單請求標頭、中介層、查詢與提交欄位名稱 |
| SQL | 數量/耗時、慢查詢、階段耗時、N+1 呼叫位置熱點、時間軸、重複查詢分組、查詢明細 |
| HTTP / Cache | 外部 HTTP 呼叫（方法/網址/狀態/耗時/錯誤）與快取 hit/miss/write/forget 計數 |
| Server | PHP/SAPI/OS/負載、Laravel 版本與環境、DB 連線與版本、cache/queue/session/fs 驅動、磁碟、擴充功能、OPcache |
| Security | 12 項唯讀安全檢查，含等級與修復建議 |
| Performance | 跨請求排行：最慢請求、最慢查詢、重複最多的語句 |
| Logs | 日誌尾部：依等級計數與最近項目（僅保留首行並截斷） |

另含：底部工具列、獨立歷史頁、`devtools:audit` 指令。

本套件不依賴宿主應用的模型、資料表、Service 或設定檔；只使用自己的 `pl.devtools` 預設值（啟動時合併）、設定的 cache store、日誌檔與資料庫版本查詢，因此可放入任何 Laravel 11–13 專案。

## 需求

PHP 8.2+，Laravel 11 / 12 / 13。

## 安裝

### 1. 透過 Composer

在宿主 `composer.json` 加入 VCS 倉庫：

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/coding1688/pl-devtools.git" }
]
```

```bash
composer require --dev coding1688/pl-devtools
```

Laravel 會自動發現 `Coding1688\DevTools\DevToolsServiceProvider`；若停用自動發現，請加入 `bootstrap/providers.php`。

### 2. 開啟

```dotenv
APP_DEBUG=true
PL_DEVTOOLS_ENABLED=true
```

相容舊變數 `PL_DEBUG_ENABLED`。

### 3. 開啟頁面

- 任何 HTML 頁面底部會出現工具列；
- 獨立頁面：`http://<你的網域>/__debug/sql`。

第一次開啟沒有資料是正常的：先拜訪頁面或呼叫介面，再重新整理。

### 4. 設定（可選）

預設值合併到 `pl.devtools`，建議在 `config/pl.php` 覆寫：

```php
return [
    'devtools' => [
        'enabled' => env('PL_DEVTOOLS_ENABLED', false),
        'hosts' => ['b.example.local'],
        'middleware' => ['auth'],
    ],
];
```

發佈視圖 / 資源：`php artisan vendor:publish --tag=pl-devtools-views`、`--tag=pl-devtools-assets`。

## 快速開始

1. 開啟任一頁面，底部工具列顯示本次請求的 SQL 數量、耗時、峰值記憶體；
2. 展開後切換 Request / SQL / HTTP-Cache 面板；
3. 點「全部面板與歷史請求」進入獨立頁，查看 Server / Security / Performance / Logs 與最近 50 筆請求；
4. 執行 `php artisan devtools:audit` 做安全檢查。

## 工具列

- 面板切換由 `toolbar_collectors` 控制（預設 Request / SQL / HTTP-Cache）；
- 「查看」下拉選單：`自動（最新）` 每 `toolbar_refresh` 秒跟隨最新請求；選擇歷史請求即鎖定，可切回自動；
- 重新整理或切換後保留展開狀態與選中項；
- Blade / Layui 整頁渲染會隨頁面更新；Vue SPA 與 Layui iframe 透過 `?toolbar=1` 輪詢，僅在頂層視窗執行。

## 獨立頁面

預設 `/__debug/sql`：

| 參數 | 說明 |
| --- | --- |
| `?type=all\|html\|ajax` | 依請求類型過濾 |
| `?request=<uuid>` | 只顯示單一畫像 |
| `?refresh=2\|5` | 自動重新整理秒數 |
| `?fresh=1` | 清除 Server / Security / Performance / Logs 快照 |
| `?export=json` | 匯出目前篩選結果（附件下載） |
| `?toolbar=1` | 工具列輪詢片段介面 |

## 面板

- **Request**：請求、狀態、路由、控制器、使用者、Host/IP、白名單標頭、中介層、查詢與提交欄位名稱；不記錄參數值、Cookie 或 Session。
- **SQL**：慢查詢、階段耗時（`measure()`）、N+1 呼叫位置熱點、時間軸、重複分組、查詢明細。
- **HTTP / Cache**：僅統計 `Http::` 發出的請求；可分別關閉 HTTP 與快取擷取。
- **Server**：環境、驅動、磁碟、擴充功能、OPcache；依 `diagnostics_ttl` 快取。
- **Security**：高風險含 `APP_DEBUG`、`APP_KEY`、HTTPS、工具暴露、可寫入權限、`public/` 敏感檔、上傳副檔名、依賴漏洞（可選）。
- **Performance**：最慢請求、最慢查詢、重複最多語句。
- **Logs**：只讀尾部、只留首行、不顯示堆疊。

## 安全稽核

```bash
php artisan devtools:audit
php artisan devtools:audit --fail-on=medium
php artisan devtools:audit --fail-on=none
php artisan devtools:audit --json --fresh
```

命中門檻回傳 `1`，否則 `0`。依賴漏洞檢查預設關閉，可用 `security.composer_audit = true` 開啟。

## 設定（常用）

| 鍵 | 預設 | 說明 |
| --- | --- | --- |
| `enabled` | `env('PL_DEVTOOLS_ENABLED', env('PL_DEBUG_ENABLED', false))` | 總開關 |
| `environments` / `hosts` | `['local','testing']` / `[]` | 允許的環境與網域 |
| `path` / `route_name` | `__debug/sql` / `debug.sql` | 路徑與路由名 |
| `middleware` | `[]` | 偵錯路由的額外中介層 |
| `locales` | `null` | Server 面板顯示的語言清單（選填） |
| `locale` | `null` | 介面語言：`en`、`zh_CN`、`zh_TW`、`ja`、`ko`；`null` 依序取瀏覽器 Accept-Language、應用 locale、英文 |
| `slow_ms` / `max_queries` | `100` / `500` | 慢查詢門檻、單請求上限 |
| `history_limit` / `ttl_seconds` | `50` / `1800` | 歷史筆數與保留時間 |
| `toolbar_refresh` / `toolbar_history` | `2` / `20` | 輪詢秒數、下拉選單筆數 |
| `toolbar_in_iframes` | `true` | iframe 內是否顯示 |
| `ignore_routes` / `ignore_paths` | `[]` | 不採集的路由名 / 路徑 |
| `diagnostics_ttl` / `security_ttl` | `300` / `300` | 快照快取秒數 |
| `telemetry.*` | 開啟 | HTTP / 快取遙測 |
| `performance.top` | `10` | 排行筆數 |
| `logs.levels` / `logs.limit` | 錯誤級別 / `50` | 日誌擷取 |
| `security.composer_audit` | `false` | 執行 `composer audit` |

## 指令

| 指令 | 說明 |
| --- | --- |
| `php artisan devtools:audit` | 執行安全檢查；`--json`、`--fresh`、`--fail-on` |

## 疑難排解

| 現象 | 排查 |
| --- | --- |
| 工具列沒出現 | `APP_DEBUG`、`PL_DEVTOOLS_ENABLED`、`environments`、`hosts`；回應需為含 `</body>` 的 HTML；檢查 `ignore_*` |
| AJAX 沒有工具列 | 設計如此；用獨立頁 `?type=ajax` 或回應標頭 `X-Debug-SQL-Url` |
| SPA / iframe 不更新 | 確認 `toolbar_refresh`，Network 是否有 `?toolbar=1` |
| 沒有資料 | 先產生請求；檢查 `cache_store` 寫入權限 |
| 頁面變慢 | 調大 `diagnostics_ttl`、關閉 `telemetry`、用 `ignore_*` |
| 生產環境 | 不要開啟，改用 `devtools:audit` |

## 相容性

Laravel 11 / 12 / 13，PHP 8.2+；版本記錄見 [Tags](https://github.com/coding1688/pl-devtools/tags)。

## 相關套件

`coding1688/pl-files`、`coding1688/pl-fields`、`coding1688/pl-devtools`。

## 授權

MIT
