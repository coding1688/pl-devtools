# pl-devtools

[English](README.md) | [简体中文](README.zh_CN.md) | [繁體中文](README.zh_TW.md) | [日本語](README.ja.md) | [한국어](README.ko.md)

[Moonsea](https://moonsea.dev) · [GitHub](https://github.com/coding1688/pl-devtools) · [Issues](https://github.com/coding1688/pl-devtools/issues)

## Laravel 요청 분석 및 진단 도구

SQL 타임라인, 요청 정보, HTTP·캐시 계측, 서버 정보, 성능 요약, 로그 요약과 설정 가능한 보안 점검을 제공합니다. 업무 모델이나 데이터베이스 마이그레이션은 필요하지 않습니다. Blade에서는 도구 모음을, API에서는 응답 헤더와 기록 화면을 사용할 수 있습니다.

## 요구 사항과 설치

선언된 지원 범위는 PHP `^8.2`, Laravel 구성 요소 `^11 | ^12 | ^13`입니다. 선택한 Laravel 버전에 따라 더 최신 PHP가 필요할 수 있습니다. 이 문서는 현재 소스를 설명하므로 해당 기능이 포함된 릴리스를 사용하세요. 서비스 공급자는 자동으로 검색됩니다. 도구를 사용하는 환경에는 개발 의존성도 설치해야 합니다.

```bash
composer require --dev coding1688/pl-devtools
php artisan vendor:publish --tag=devtools-config
```

Composer 저장소에서 패키지를 찾을 수 없다면 설치 전에 소스 저장소를 추가하세요.

```bash
composer config repositories.pl-devtools vcs https://github.com/coding1688/pl-devtools.git
```

## 로컬에서 활성화

기본값은 비활성화입니다. 허용된 개발 환경에서 다음 두 설정을 모두 활성화하세요. 기본 기록 경로는 `/__debug/sql`입니다.

```dotenv
APP_DEBUG=true
PL_DEVTOOLS_ENABLED=true
```

## 설정 규칙

`config/devtools.php`와 `devtools.*`를 사용합니다. 우선순위는 패키지 기본값 → 기존 `pl.devtools` → `devtools`입니다. 맵은 재귀적으로 병합하고 목록은 빈 목록을 포함해 교체합니다. 기존 설정은 공급자 등록 시에만 가져옵니다. 실행 중 변경은 새 키를 사용하세요. 기존 배포 태그 `pl-devtools-config`도 별칭으로 유지됩니다.

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

`collect`는 HTTP 계측, `store_cache`는 요청 기록 저장, `log`는 분석 요약 로그를 제어합니다. 영구 저장을 원하지 않으면 뒤의 두 항목을 모두 끄세요. `toolbar=false`는 HTML 삽입만 끄고 계측과 응답 헤더는 유지합니다. 활성화·debug·환경·호스트·권한 조건은 계속 적용됩니다. 수집을 끄면 분석 기록 컨트롤러 접근도 거부됩니다. `hosts`가 비어 있으면 허용된 환경 안에서 호스트를 제한하지 않습니다.

## 공유 환경과 권한

진단 엔드포인트를 공개하지 마세요. 허용 환경과 호스트를 설정하고 호스트 애플리케이션의 Gate를 정의하세요. 아래 예제는 애플리케이션에서 구현할 권한에 위임합니다.

```php
use Illuminate\Support\Facades\Gate;

Gate::define('view-devtools', fn ($user) => $user->can('inspect-diagnostics'));

// config/devtools.php
'authorization_gate' => 'view-devtools',
```

Gate는 계측, 도구 모음, 기록 접근을 모두 보호합니다. 필요하면 `devtools.middleware`에 인증 미들웨어를 추가하세요. Gate 이름을 설정하는 것만으로 로그인 상태를 만들거나 Bearer Token을 검증하지는 않습니다. 기록 라우트는 `web` 미들웨어 그룹을 사용하며 비우기 작업은 POST입니다. 배포 설정을 바꾼 후에는 라우트와 설정 캐시를 다시 생성하세요.

## 언어

UI는 `en`, `zh_CN`, `zh_TW`, `ja`, `ko`를 지원합니다. 명시한 `devtools.locale`이 우선이며, 이후 브라우저 언어·애플리케이션 언어·대체 언어를 참조합니다. 영문 콘솔 출력과 기술 식별자가 모두 번역된 것은 아닙니다.

## 데이터 처리와 장애 격리

해당 상세 기록에는 요청 입력값과 SQL 바인딩 값을 저장하지 않습니다. Referer, HTTP 실패 메시지, 로그 요약에서 URL 인증 정보·쿼리·프래그먼트와 일반적인 비밀 키 값을 가립니다. `devtools.redact_keys`로 민감한 키 이름을 추가할 수 있습니다. URL 경로, 원시 SQL 리터럴, 자유 형식 업무 텍스트의 비밀은 확실히 식별할 수 없습니다. 마스킹은 접근 제어를 대체하지 않습니다.

계측과 도구 모음의 실패는 정상 업무 응답이나 원래 예외를 덮어쓰지 않습니다. 진단 실패를 계측 대상 로거에 재귀적으로 기록하지 않습니다. 공유 환경에서는 기록 수와 TTL을 제한하고 캐시·로그 접근을 관리하세요.

## CLI 진단

읽기 전용 감사 명령은 HTTP 분석을 활성화하지 않고도 실행할 수 있습니다. `--fresh`는 보고서 캐시를 건너뛰고 `--json`은 JSON을 출력합니다. `--fail-on`은 `none`, `info`, `low`, `medium`, `high`를 지원하며 기본값은 `high`입니다. 기준에 도달한 실패 항목은 0이 아닌 종료 코드를 만들 수 있습니다. 이 점검은 전체 보안 평가를 대체하지 않습니다.

```bash
php artisan devtools:audit
php artisan devtools:audit --json --fresh --fail-on=high
```

## 확장

`Collectors\Collector`를 구현해 `devtools.collectors`에 등록하세요. 사용자 정의 점검은 `Diagnostics\Security\Check`를 구현해 `devtools.security.checks`에 등록합니다. 현재 버전의 인터페이스를 따르며 특정 관리자 프레임워크에 의존하지 않습니다. 뷰는 `pl-devtools-views`, 스타일 리소스는 `pl-devtools-assets` 태그로 배포할 수 있습니다.

## 개발과 업그레이드

테스트는 설정 호환성, 기본 등록, 접근 제어, 장애 격리, 마스킹과 5개 언어 키 일관성을 다룹니다. CI에 PHP·Laravel 호환성 작업이 정의되어 있으므로 사용할 릴리스의 결과를 확인하세요. 업그레이드 시 실행 중 `pl.devtools.*` 쓰기를 `devtools.*`로 옮기고 권한 설정과 호스트 캐시를 점검하세요.

```bash
composer install
composer test
```

## 라이선스

[MIT License](LICENSE). Copyright © 2026 [coding1688 · Moonsea](https://moonsea.dev).
