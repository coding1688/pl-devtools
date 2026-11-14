# pl-devtools

[English](README.md) | [简体中文](README.zh_CN.md) | [繁體中文](README.zh_TW.md) | [日本語](README.ja.md) | [한국어](README.ko.md)

[Moonsea](https://moonsea.dev) · [GitHub](https://github.com/coding1688/pl-devtools) · [Issues](https://github.com/coding1688/pl-devtools/issues)

## Laravel のリクエスト分析・診断ツール

SQL タイムライン、リクエスト情報、HTTP／キャッシュ計測、サーバー情報、性能集計、ログ要約、設定可能なセキュリティチェックを提供します。業務モデルやデータベースのマイグレーションは不要です。Blade ではツールバー、API ではレスポンスヘッダーと履歴画面を利用できます。

## 要件とインストール

宣言上の対応範囲は PHP `^8.2`、Laravel コンポーネント `^11 | ^12 | ^13` です。選択した Laravel によっては、より新しい PHP が必要です。この文書は現在のソースを説明しています。対応するリリースを使用してください。サービスプロバイダーは自動検出されます。使用する環境には開発用依存関係もインストールしてください。

```bash
composer require --dev coding1688/pl-devtools
php artisan vendor:publish --tag=devtools-config
```

Composer のレジストリで見つからない場合は、先にソースリポジトリを追加してください。

```bash
composer config repositories.pl-devtools vcs https://github.com/coding1688/pl-devtools.git
```

## ローカルで有効化

初期状態では無効です。許可された開発環境で両方の設定を有効にします。履歴の既定パスは `/__debug/sql` です。

```dotenv
APP_DEBUG=true
PL_DEVTOOLS_ENABLED=true
```

## 設定

`config/devtools.php` と `devtools.*` を使用します。優先順位は、パッケージ既定値 → 旧 `pl.devtools` → `devtools` です。マップは再帰的に統合し、リストは空リストも含めて置き換えます。旧設定の取り込みはプロバイダー登録時のみです。実行時の変更には新しいキーを使用してください。旧公開タグ `pl-devtools-config` も別名として利用できます。

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

`collect` は HTTP 計測、`store_cache` は履歴保存、`log` は分析要約のログ出力を制御します。永続化しない場合は後者の二つを無効にしてください。`toolbar=false` は HTML 注入のみを無効にし、計測とヘッダーは残します。有効化、debug、環境、ホスト、認可の条件は常に適用されます。計測を無効にすると履歴コントローラーへのアクセスも拒否されます。空の `hosts` は、許可された環境内でホストを制限しません。

## 共有環境と認可

診断エンドポイントを一般公開しないでください。環境とホストを制限し、アプリケーション側の Gate を設定します。次の例では、ホスト側で実装する権限を利用します。

```php
use Illuminate\Support\Facades\Gate;

Gate::define('view-devtools', fn ($user) => $user->can('inspect-diagnostics'));

// config/devtools.php
'authorization_gate' => 'view-devtools',
```

Gate は計測、ツールバー、履歴アクセスに共通して適用されます。必要に応じて `devtools.middleware` に認証ミドルウェアを追加してください。Gate 名の設定だけではログイン状態の確立や Bearer Token の検証は行いません。履歴ルートは `web` ミドルウェアを使用し、消去操作は POST です。配備設定を変更したらルートと設定のキャッシュを再生成してください。

## 言語

UI は `en`、`zh_CN`、`zh_TW`、`ja`、`ko` に対応します。明示した `devtools.locale` が優先され、その後はブラウザー言語、アプリケーション言語、フォールバック言語を参照します。英語のコンソール出力と技術識別子は、すべてが翻訳されているわけではありません。

## データ処理と障害の分離

対応する詳細レコードにはリクエスト入力値と SQL バインド値を保存しません。Referer、HTTP 失敗メッセージ、ログ要約では URL 認証情報、クエリー、フラグメント、一般的な秘密キーの値をマスクします。`devtools.redact_keys` でキーを追加できます。URL パス、SQL リテラル、自由記述の業務テキスト内の秘密は確実に識別できません。マスク処理はアクセス制御の代わりにはなりません。

計測やツールバーの失敗は、正常な業務レスポンスや元の例外を置き換えません。診断失敗を監視対象のロガーへ再帰的に記録しません。共有環境では履歴件数と TTL を制限し、キャッシュとログへのアクセスを管理してください。

## CLI 診断

読み取り専用の監査コマンドは HTTP 計測を有効にせず実行できます。`--fresh` はレポートキャッシュを無視し、`--json` は JSON を出力します。`--fail-on` は `none`、`info`、`low`、`medium`、`high` に対応し、既定値は `high` です。しきい値に達する失敗項目があると非ゼロの終了コードを返せます。完全なセキュリティ評価を代替するものではありません。

```bash
php artisan devtools:audit
php artisan devtools:audit --json --fresh --fail-on=high
```

## 拡張

`Collectors\Collector` を実装し `devtools.collectors` に登録します。独自チェックは `Diagnostics\Security\Check` を実装し `devtools.security.checks` に登録します。現在のバージョンの契約に従ってください。特定の管理画面フレームワークには依存しません。ビューは `pl-devtools-views`、スタイルは `pl-devtools-assets` で公開できます。

## 開発とアップグレード

テストは設定互換性、既定の登録、アクセス制御、障害分離、マスク処理、5 言語のキー整合性を対象とします。CI には PHP／Laravel の互換性ジョブがあります。利用するリリースの結果を確認してください。アップグレード時は実行時の `pl.devtools.*` 書き込みを `devtools.*` に移し、認可とホストのキャッシュを見直してください。

```bash
composer install
composer test
```

## ライセンス

[MIT License](LICENSE). Copyright © 2026 [coding1688 · Moonsea](https://moonsea.dev).
