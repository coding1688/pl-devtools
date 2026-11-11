# pl-devtools

[English](README.md) | [简体中文](README.zh_CN.md) | [繁體中文](README.zh_TW.md) | [日本語](README.ja.md) | [한국어](README.ko.md)

Laravel 개발 진단 도구: 요청 프로파일, SQL 타임라인, 서버 정보, 보안 점검, HTTP/캐시 텔레메트리, 성능 순위, 로그 요약을 한 페이지에 모았습니다.

- PHP 8.2+, Laravel 11 / 12 / 13
- 기본 비활성화. `APP_DEBUG=true` 와 명시적 활성화 필요
- 프런트엔드 독립적: Blade, Layui, Vue SPA
- UI는 영어, 중국어 간체, 중국어 번체, 일본어, 한국어 지원
- 동일한 보안 점검을 CI에서 실행: `php artisan devtools:audit`

## 기능

| 패널 | 내용 |
| --- | --- |
| Request | 메서드/경로/상태, 라우트, `controller@action`, 로그인 사용자, Host/IP, 허용 헤더, 미들웨어, 쿼리·입력 필드 이름 |
| SQL | 개수/시간, 느린 쿼리, 단계 계측, N+1 호출 위치 핫스팟, 타임라인, 중복 쿼리 그룹, 상세 |
| HTTP / Cache | 외부 HTTP 호출(메서드/URL/상태/시간/오류)과 캐시 hit/miss/write/forget 카운트 |
| Server | PHP/SAPI/OS/부하, Laravel 버전·환경, DB 연결·버전, cache/queue/session/fs 드라이버, 디스크, 확장, OPcache |
| Security | 12개 읽기 전용 점검(심각도와 수정 안내 포함) |
| Performance | 요청 간 순위: 가장 느린 요청·쿼리, 가장 많이 반복된 문장 |
| Logs | 로그尾部: 레벨별 카운트와 최근 항목(첫 줄만, 잘림) |

도구 모음, 단독 기록 페이지, `devtools:audit` 명령도 포함합니다.

## 요구 사항

PHP 8.2+, Laravel 11 / 12 / 13.

## 설치

### 1. Composer

호스트 `composer.json`에 VCS 저장소를 추가합니다:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/coding1688/pl-devtools.git" }
]
```

```bash
composer require --dev coding1688/pl-devtools
```

Laravel이 `Coding1688\DevTools\DevToolsServiceProvider`를 자동 발견합니다. 자동 발견을 끈 경우 `bootstrap/providers.php`에 추가하세요.

### 2. 활성화

```dotenv
APP_DEBUG=true
PL_DEVTOOLS_ENABLED=true
```

이전 변수 `PL_DEBUG_ENABLED`도 사용할 수 있습니다.

### 3. 접속

- 모든 HTML 페이지 하단에 도구 모음이 표시됩니다.
- 단독 페이지: `http://<호스트>/__debug/sql`.

처음에는 비어 있습니다. 페이지를 열거나 API를 호출한 뒤 새로고침하세요.

### 4. 설정(선택)

기본값은 `pl.devtools`로 병합됩니다. 호스트의 `config/pl.php`에서 덮어쓸 수 있습니다:

```php
return [
    'devtools' => [
        'enabled' => env('PL_DEVTOOLS_ENABLED', false),
        'hosts' => ['b.example.local'],
        'middleware' => ['auth'],
    ],
];
```

뷰/에셋 게시: `php artisan vendor:publish --tag=pl-devtools-views`, `--tag=pl-devtools-assets`.

## 빠른 시작

1. 아무 페이지나 열면 하단 도구 모음에 SQL 개수·시간·피크 메모리가 표시됩니다.
2. 펼쳐서 Request / SQL / HTTP-Cache를 전환합니다.
3. 「全部面板与历史请求」로 단독 페이지를 열어 Server / Security / Performance / Logs와 최근 50건을 확인합니다.
4. `php artisan devtools:audit`로 보안 점검을 실행합니다.

## 도구 모음

- 패널은 `toolbar_collectors`로 제어(기본 Request / SQL / HTTP-Cache).
- 「查看」 선택: `自動（最新）`는 `toolbar_refresh`초마다 최신 요청으로 갱신합니다. 기록을 선택하면 고정되고 자동으로 되돌릴 수 있습니다.
- 펼침 상태와 선택 항목은 갱신 후에도 유지됩니다.
- Blade / Layui 전체 페이지는 페이지 갱신 시 다시 렌더링됩니다. Vue SPA와 Layui iframe은 `?toolbar=1`을 폴링하며 최상위 창에서만 동작합니다.

## 단독 페이지

기본 경로는 `/__debug/sql`입니다:

| 파라미터 | 설명 |
| --- | --- |
| `?type=all\|html\|ajax` | 요청 유형 필터 |
| `?request=<uuid>` | 단일 프로파일만 표시 |
| `?refresh=2\|5` | 자동 새로고침 간격(초) |
| `?fresh=1` | Server / Security / Performance / Logs 캐시 삭제 |
| `?export=json` | 현재 필터 결과를 JSON으로 다운로드 |
| `?toolbar=1` | 도구 모음 폴링용 프래그먼트 |

## 패널

- **Request**: 메서드, 상태, 라우트, 컨트롤러, 사용자, Host/IP, 허용 헤더, 미들웨어, 필드 이름만 기록합니다. 값·쿠키·세션은 저장하지 않습니다.
- **SQL**: 느린 쿼리, `measure()` 단계 계측, N+1 핫스팟, 타임라인, 중복 그룹, 상세.
- **HTTP / Cache**: `Http::` 호출만 집계하며 HTTP와 캐시를 각각 끌 수 있습니다.
- **Server**: 환경, 드라이버, 디스크, 확장, OPcache. `diagnostics_ttl`로 캐시됩니다.
- **Security**: `APP_DEBUG`, `APP_KEY`, HTTPS, 도구 노출, 쓰기 권한, `public/` 내 민감 파일, 업로드 확장자, 의존성 취약점(선택).
- **Performance**: 느린 요청, 느린 쿼리, 최다 반복.
- **Logs**:尾部만, 첫 줄만, 스택 트레이스 없음.

## 보안 감사

```bash
php artisan devtools:audit
php artisan devtools:audit --fail-on=medium
php artisan devtools:audit --fail-on=none
php artisan devtools:audit --json --fresh
```

기준에 도달하면 종료 코드 `1`, 아니면 `0`. 의존성 취약점 점검은 기본 비활성(`security.composer_audit = true`로 활성).

## 설정(요약)

| 키 | 기본값 | 설명 |
| --- | --- | --- |
| `enabled` | `env('PL_DEVTOOLS_ENABLED', env('PL_DEBUG_ENABLED', false))` | 전체 스위치 |
| `environments` / `hosts` | `['local','testing']` / `[]` | 허용 환경·호스트 |
| `path` / `route_name` | `__debug/sql` / `debug.sql` | 경로·라우트 이름 |
| `middleware` | `[]` | 디버그 라우트 추가 미들웨어 |
| `locale` | `null` | UI 언어: `en`, `zh_CN`, `zh_TW`, `ja`, `ko`. `null`이면 앱 locale 사용 |
| `slow_ms` / `max_queries` | `100` / `500` | 느린 쿼리 기준 / 요청당 한도 |
| `history_limit` / `ttl_seconds` | `50` / `1800` | 기록 개수 / 보존 시간 |
| `toolbar_refresh` / `toolbar_history` | `2` / `20` | 폴링 간격 / 선택 항목 수 |
| `toolbar_in_iframes` | `true` | iframe 내 표시 |
| `ignore_routes` / `ignore_paths` | `[]` | 수집 제외 라우트 / 경로 |
| `diagnostics_ttl` / `security_ttl` | `300` / `300` | 스냅샷 캐시 |
| `telemetry.*` | 활성 | HTTP / 캐시 계측 |
| `performance.top` | `10` | 순위 개수 |
| `logs.levels` / `logs.limit` | 오류 레벨 / `50` | 로그 수집 |
| `security.composer_audit` | `false` | `composer audit` 실행 |

## 명령

| 명령 | 설명 |
| --- | --- |
| `php artisan devtools:audit` | 보안 점검. `--json`, `--fresh`, `--fail-on` |

## 문제 해결

| 증상 | 확인 |
| --- | --- |
| 도구 모음이 없음 | `APP_DEBUG`, `PL_DEVTOOLS_ENABLED`, `environments`, `hosts`. `</body>`가 있는 HTML인지, `ignore_*` 설정 확인 |
| AJAX에 도구 모음 없음 | 설계상 정상. 단독 페이지 `?type=ajax` 또는 `X-Debug-SQL-Url` 헤더 사용 |
| SPA / iframe 갱신 안 됨 | `toolbar_refresh`와 Network의 `?toolbar=1` 확인 |
| 데이터 없음 | 먼저 요청을 발생시키고 `cache_store` 쓰기 권한 확인 |
| 페이지가 느려짐 | `diagnostics_ttl` 늘리기, `telemetry` 끄기, `ignore_*`로 제외 |
| 운영 환경 | 활성화하지 말고 `devtools:audit` 사용 |

## 호환성

Laravel 11 / 12 / 13, PHP 8.2+. 버전 기록은 [Tags](https://github.com/coding1688/pl-devtools/tags) 참고.

## 관련 패키지

`coding1688/pl-files`, `coding1688/pl-fields`, `coding1688/pl-devtools`.

## 라이선스

MIT
