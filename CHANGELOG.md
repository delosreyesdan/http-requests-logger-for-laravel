# Changelog

## Unreleased

### Added
- Redis/Valkey buffer layer using `ext-redis` directly for performance
- Configurable buffer key prefix (`redis.prefix`)
- Valkey support — same protocol as Redis, labelled via `redis.driver` config
- Graceful fallback to direct storage writes when the buffer is unavailable (`fallback_on_buffer_error`)
- `DatabaseLogRepository` — batch inserts for any Laravel-supported DB (MySQL, PostgreSQL, SQLite, etc.)
- `DynamoDbLogRepository` — batch writes via `batchWriteItem` (chunks of 25, single unprocessed retry)
- `RedisLogRepository` — permanent Redis/Valkey list storage with configurable `max_entries` cap
- `NullLogRepository` — no-op driver for disabling logging without removing middleware
- `HttpRequestLoggerMiddleware` — logs `direction`, `method`, `url`, `status`, `ip`, `user_agent`, `headers`, `body`, `duration_ms`
- Configurable field filtering via `exclude.fields`
- Configurable path filtering via `exclude.paths` (wildcard support)
- Outgoing HTTP request logging via Laravel's `ResponseReceived` event (opt-in, `log_outgoing`)
- `logs:flush` Artisan command — manually drain the full buffer to storage
- `logs:clear` Artisan command — discard the buffer without persisting
- `logs:status` Artisan command — display buffer key, buffered count, batch size, and fill percentage
- `logs:prune --days=30` Artisan command — delete database records older than N days
- Auto-schedule option — registers `logs:flush` on a configurable cron via `schedule.enabled` and `schedule.cron`
- `HttpRequestLog` Eloquent model with named query scopes: `incoming`, `outgoing`, `failed`, `successful`, `slow`, `withStatus`, `fromIp`, `forUrl`, `within`
- `LogMasker` utility — recursive masking of sensitive header and body fields before storage
- Response body logging for both incoming and outgoing requests (`log_response_body`, `max_body_size`)
- `HttpRequestLogger` service class and `HttpRequestLogger` Facade
- Missing `illuminate/http`, `illuminate/console`, `illuminate/database`, `illuminate/events` added to `require`
- Facade alias registered via `extra.laravel.aliases` in `composer.json`
- `enabled` config flag — master on/off switch without removing middleware registration
- `sample_rate` config — log a fraction of requests (0.0–1.0) to reduce storage cost at scale
- `log_user_id` config — optionally capture `auth()->id()` on each log entry
- `connection` config — point database writes at a dedicated Laravel DB connection
- `correlation_id_header` config — read or generate a request ID and store it as `request_id`; shared across incoming and outgoing log entries in the same request cycle
- `request_id` and `user_id` columns added to migration and in-memory test schema
- `forUser` and `withRequestId` query scopes on `HttpRequestLog`

### Fixed
- Default table name in config was `'r'`; corrected to `'http_request_logs'`
- `flush()` previously drained only one `batch_size` chunk; now loops until the buffer is empty
- `created_at`/`updated_at` are now stamped by `DatabaseLogRepository` before insert
- Redis connection is now cached statically in `RequestLogBufferAction` — one connection per process instead of one per call
- `TestCase` now creates the `http_request_logs` table in-memory for feature tests
- Fixed typo `DynamoDBLogRepository` → `DynamoDbLogRepository` in the service provider
- Replaced removed `getUserAgent()` (Symfony 6) with `$request->userAgent()`
- Path exclusion patterns now normalize leading slashes to match `$request->is()` behaviour
