# pl-devtools

[English](README.md) | [简体中文](README.zh_CN.md) | [繁體中文](README.zh_TW.md) | [日本語](README.ja.md) | [한국어](README.ko.md)

Laravel 開発者向けの診断ツール：リクエストプロファイル、SQL タイムライン、サーバー情報、セキュリティチェック、HTTP / キャッシュテレメトリ、パフォーマンスランキング、ログ要約を 1 ページに集約します。

- PHP 8.2+、Laravel 11 / 12 / 13
- 既定では無効。`APP_DEBUG=true` と明示的な有効化が必要
- フロントエンド非依存：Blade、Layui、Vue SPA
- UI は英語、簡体字中国語、繁体字中国語、日本語、韓国語に対応
- 同じセキュリティチェックを CI で実行：`php artisan devtools:audit`

## 機能

| パネル | 内容 |
| --- | --- |
| Request | メソッド/パス/ステータス、ルート、`controller@action`、ログインユーザー、Host/IP、許可ヘッダー、ミドルウェア、クエリと入力のフィールド名 |
| SQL | 件数/時間、スロークエリ、ステージ計測、N+1 呼び出し箇所のホットスポット、タイムライン、重複クエリ、明細 |
| HTTP / Cache | 外部 HTTP 呼び出し（メソッド/URL/ステータス/時間/エラー）とキャッシュの hit/miss/write/forget カウント |
| Server | PHP/SAPI/OS/負荷、Laravel のバージョンと環境、DB 接続とバージョン、cache/queue/session/fs ドライバ、ディスク、拡張、OPcache |
| Security | 12 項目の読み取り専用チェック（重大度と修正案付き） |
| Performance | リクエスト横断のランキング：遅いリクエスト、遅いクエリ、最多重複 |
| Logs | ログ末尾：レベル別件数と最新エントリ（先頭行のみ、切り詰め） |

ツールバー、単体履歴ページ、`devtools:audit` コマンドも含みます。

## 要件

PHP 8.2+、Laravel 11 / 12 / 13。

## インストール

### 1. Composer

ホストの `composer.json` に VCS リポジトリを追加します：

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/coding1688/pl-devtools.git" }
]
```

```bash
composer require --dev coding1688/pl-devtools
```

Laravel は `Coding1688\DevTools\DevToolsServiceProvider` を自動検出します。自動検出を無効にしている場合は `bootstrap/providers.php` に追加してください。

### 2. 有効化

```dotenv
APP_DEBUG=true
PL_DEVTOOLS_ENABLED=true
```

旧変数 `PL_DEBUG_ENABLED` も使用できます。

### 3. アクセス

- HTML ページの下部にツールバーが表示されます。
- 単体ページ：`http://<ホスト>/__debug/sql`。

初回は空です。ページを開くか API を呼び出してから再読み込みしてください。

### 4. 設定（任意）

既定値は `pl.devtools` にマージされます。ホストの `config/pl.php` で上書きできます：

```php
return [
    'devtools' => [
        'enabled' => env('PL_DEVTOOLS_ENABLED', false),
        'hosts' => ['b.example.local'],
        'middleware' => ['auth'],
    ],
];
```

ビュー/アセットの公開：`php artisan vendor:publish --tag=pl-devtools-views`、`--tag=pl-devtools-assets`。

## クイックスタート

1. 任意のページを開くと、下部ツールバーに SQL 件数・時間・ピークメモリが表示されます。
2. 展開して Request / SQL / HTTP-Cache を切り替えます。
3. 「全部面板与历史请求」から単体ページを開き、Server / Security / Performance / Logs と直近 50 件を確認します。
4. `php artisan devtools:audit` でセキュリティチェックを実行します。

## ツールバー

- パネルは `toolbar_collectors` で制御（既定は Request / SQL / HTTP-Cache）。
- 「查看」セレクト：`自動（最新）` は `toolbar_refresh` 秒ごとに最新へ更新。履歴を選ぶと固定され、自動に戻せます。
- 展開状態と選択項目は更新後も保持されます。
- Blade / Layui の全ページ描画ではページ更新時に再描画。Vue SPA と Layui iframe では `?toolbar=1` をポーリングし、最上位ウィンドウのみで動作します。

## 単体ページ

既定は `/__debug/sql`：

| パラメータ | 説明 |
| --- | --- |
| `?type=all\|html\|ajax` | リクエスト種別で絞り込み |
| `?request=<uuid>` | 1 件のみ表示 |
| `?refresh=2\|5` | 自動更新の秒数 |
| `?fresh=1` | Server / Security / Performance / Logs のキャッシュを破棄 |
| `?export=json` | 現在の絞り込み結果を JSON でダウンロード |
| `?toolbar=1` | ツールバー用フラグメント |

## パネル

- **Request**：メソッド、ステータス、ルート、コントローラ、ユーザー、Host/IP、許可ヘッダー、ミドルウェア、フィールド名のみ。値・Cookie・セッションは保存しません。
- **SQL**：スロークエリ、`measure()` によるステージ計測、N+1 ホットスポット、タイムライン、重複グループ、明細。
- **HTTP / Cache**：`Http::` 経由のみ集計。HTTP とキャッシュは個別に無効化できます。
- **Server**：環境、ドライバ、ディスク、拡張、OPcache。`diagnostics_ttl` でキャッシュ。
- **Security**：`APP_DEBUG`、`APP_KEY`、HTTPS、ツールの公開、書き込み権限、`public/` の機密ファイル、アップロード拡張子、依存脆弱性（任意）。
- **Performance**：遅いリクエスト、遅いクエリ、最多重複。
- **Logs**：末尾のみ、先頭行のみ、スタックトレースなし。

## セキュリティ監査

```bash
php artisan devtools:audit
php artisan devtools:audit --fail-on=medium
php artisan devtools:audit --fail-on=none
php artisan devtools:audit --json --fresh
```

しきい値に達すると終了コード `1`、それ以外は `0`。依存脆弱性チェックは既定で無効（`security.composer_audit = true` で有効）。

## 設定（抜粋）

| キー | 既定値 | 説明 |
| --- | --- | --- |
| `enabled` | `env('PL_DEVTOOLS_ENABLED', env('PL_DEBUG_ENABLED', false))` | マスタースイッチ |
| `environments` / `hosts` | `['local','testing']` / `[]` | 許可する環境とホスト |
| `path` / `route_name` | `__debug/sql` / `debug.sql` | パスとルート名 |
| `middleware` | `[]` | デバッグルートの追加ミドルウェア |
| `locale` | `null` | UI 言語：`en`、`zh_CN`、`zh_TW`、`ja`、`ko`。`null` はブラウザの Accept-Language → アプリの locale → 英語の順 |
| `slow_ms` / `max_queries` | `100` / `500` | スロークエリ閾値 / 上限 |
| `history_limit` / `ttl_seconds` | `50` / `1800` | 履歴件数 / 保持時間 |
| `toolbar_refresh` / `toolbar_history` | `2` / `20` | ポーリング秒数 / 選択肢数 |
| `toolbar_in_iframes` | `true` | iframe 内の表示 |
| `ignore_routes` / `ignore_paths` | `[]` | 収集しないルート / パス |
| `diagnostics_ttl` / `security_ttl` | `300` / `300` | スナップショットのキャッシュ |
| `telemetry.*` | 有効 | HTTP / キャッシュの計測 |
| `performance.top` | `10` | ランキング件数 |
| `logs.levels` / `logs.limit` | エラーレベル / `50` | ログ収集 |
| `security.composer_audit` | `false` | `composer audit` の実行 |

## コマンド

| コマンド | 説明 |
| --- | --- |
| `php artisan devtools:audit` | セキュリティチェック。`--json`、`--fresh`、`--fail-on` |

## トラブルシューティング

| 症状 | 確認事項 |
| --- | --- |
| ツールバーが出ない | `APP_DEBUG`、`PL_DEVTOOLS_ENABLED`、`environments`、`hosts`。`</body>` を含む HTML か。`ignore_*` を確認 |
| AJAX にツールバーがない | 仕様です。単体ページの `?type=ajax` か `X-Debug-SQL-Url` ヘッダーを使用 |
| SPA / iframe が更新されない | `toolbar_refresh` と Network の `?toolbar=1` を確認 |
| データがない | まずリクエストを発生させる。`cache_store` の書き込み権限を確認 |
| 遅くなった | `diagnostics_ttl` を増やす、`telemetry` を無効化、`ignore_*` で除外 |
| 本番環境 | 有効化しない。`devtools:audit` を使用 |

## 互換性

Laravel 11 / 12 / 13、PHP 8.2+。バージョン履歴は [Tags](https://github.com/coding1688/pl-devtools/tags) を参照。

## 関連パッケージ

`coding1688/pl-files`、`coding1688/pl-fields`、`coding1688/pl-devtools`。

## ライセンス

MIT
