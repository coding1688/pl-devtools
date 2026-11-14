# pl-devtools

[English](README.md) | [简体中文](README.zh_CN.md) | [繁體中文](README.zh_TW.md) | [日本語](README.ja.md) | [한국어](README.ko.md)

[Moonsea](https://moonsea.dev) · [GitHub](https://github.com/coding1688/pl-devtools) · [Issues](https://github.com/coding1688/pl-devtools/issues)

## Laravel 請求分析與診斷工具

集中檢視 SQL 時間線、請求資訊、HTTP／快取遙測、伺服器資訊、效能彙總、日誌摘要及可設定的安全檢查。無需業務模型或資料庫遷移。Blade 頁面可使用注入工具列，API 專案可使用回應標頭及歷史紀錄頁面。

## 環境需求與安裝

宣告支援 PHP `^8.2`、Laravel 元件 `^11 | ^12 | ^13`；特定 Laravel 版本可能需要更新的 PHP。本文描述目前原始碼，請使用對應版本。Laravel 會自動探索服務提供者。需要工具的環境必須安裝開發依賴。

```bash
composer require --dev coding1688/pl-devtools
php artisan vendor:publish --tag=devtools-config
```

若 Composer 套件來源找不到套件，請先加入原始碼儲存庫，再安裝：

```bash
composer config repositories.pl-devtools vcs https://github.com/coding1688/pl-devtools.git
```

## 本機啟用

預設關閉。請在允許的開發環境中同時啟用以下設定。預設歷史紀錄入口為 `/__debug/sql`。

```dotenv
APP_DEBUG=true
PL_DEVTOOLS_ENABLED=true
```

## 設定慣例

使用 `config/devtools.php` 與 `devtools.*`。優先順序：套件預設值 → 舊 `pl.devtools` → `devtools`。對應表遞迴合併，清單整體取代，包含空清單。舊設定僅在服務提供者註冊時匯入；執行期間修改應使用新路徑。舊發佈標籤 `pl-devtools-config` 保留為別名。

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

`collect` 控制 HTTP 蒐集；`store_cache` 儲存請求歷史；`log` 獨立控制分析摘要日誌。若不需要持久保存分析資料，請同時關閉後兩項。`toolbar=false` 僅關閉 HTML 注入，保留蒐集及回應標頭。啟用、debug、環境、網域及授權檢查仍有效。關閉蒐集也會阻止存取分析歷史控制器。`hosts` 為空表示在允許的環境內不限制網域。

## 共用環境與授權

請勿公開暴露診斷入口。設定允許的環境、網域，並定義宿主 Gate。以下範例依賴你在專案內實作的權限：

```php
use Illuminate\Support\Facades\Gate;

Gate::define('view-devtools', fn ($user) => $user->can('inspect-diagnostics'));

// config/devtools.php
'authorization_gate' => 'view-devtools',
```

Gate 同時保護蒐集、工具列與歷史存取。必要時透過 `devtools.middleware` 加入登入驗證中介層。設定 Gate 名稱本身不會建立登入狀態，也不會驗證 Bearer Token。歷史路由使用 `web` 中介層群組；清除操作為 POST。部署設定變更後，請重新產生路由與設定快取。

## 語言

介面語言包含 `en`、`zh_CN`、`zh_TW`、`ja`、`ko`。明確設定的 `devtools.locale` 優先，否則依序參考瀏覽器語言、應用程式語言與備援語言。主控台英文提示及技術識別字尚未全部翻譯。

## 資料處理與故障隔離

對應明細不儲存請求輸入值與 SQL 綁定值。Referer、HTTP 失敗訊息及日誌摘要會遮蔽 URL 憑證、查詢參數、片段與常見敏感鍵值。可透過 `devtools.redact_keys` 擴充敏感鍵名。URL 路徑、原始 SQL 字面值或任意業務文字中的機密無法可靠識別；遮蔽不能取代存取控制。

蒐集與工具列失敗不會覆蓋正常業務回應或原始例外。診斷故障不會遞迴寫入受監測的日誌器。共用環境應限制歷史容量與 TTL，並管理快取、日誌存取。

## 命令列診斷

唯讀檢查命令無需啟用 HTTP 分析。`--fresh` 略過報告快取，`--json` 輸出 JSON。`--fail-on` 支援 `none`、`info`、`low`、`medium`、`high`，預設為 `high`；達到門檻的失敗項目可讓命令回傳非零狀態。此功能檢查設定風險，不取代完整安全評估。

```bash
php artisan devtools:audit
php artisan devtools:audit --json --fresh --fail-on=high
```

## 擴充

實作 `Collectors\Collector` 並在 `devtools.collectors` 註冊；實作 `Diagnostics\Security\Check` 並在 `devtools.security.checks` 註冊宿主檢查。請依循目前版本介面，不依賴特定後台框架。使用 `pl-devtools-views` 發佈檢視，使用 `pl-devtools-assets` 發佈樣式資源。

## 開發與升級

測試涵蓋設定相容、預設註冊、存取控制、故障隔離、遮蔽與五語鍵一致性。CI 定義了 PHP／Laravel 相容工作，實際結果請查看對應版本。升級時將執行期間的 `pl.devtools.*` 寫入改為 `devtools.*`，檢查存取控制並重新產生宿主快取。

```bash
composer install
composer test
```

## 授權條款

[MIT License](LICENSE). Copyright © 2026 [coding1688 · Moonsea](https://moonsea.dev).
