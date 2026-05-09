# Changelog

## Unreleased

### Added
- Redis/Valkey buffer layer using `ext-redis` directly for performance
- Configurable buffer key prefix (`redis.prefix`)
- Valkey support — same protocol as Redis, labelled via `redis.driver` config
- Graceful fallback to direct storage writes when the buffer is unavailable (`fallback_on_buffer_error`)
- `DatabaseLogRepository` — batch inserts for any Laravel-supported DB (MySQL, PostgreSQL, SQLite, etc.)
- `DynamoDbLogRepository` — batch writes via `batchWriteItem` (chunks of 25, single unprocessed retry)
- `RedisLogRepository` — permanent Redis/Valkey list storage
- `NullLogRepository` — no-op driver for disabling logging without removing middleware
- `HttpRequestLoggerMiddleware` — logs `direction`, `method`, `url`, `status`, `ip`, `user_agent`, `headers`, `body`, `duration_ms`
- Configurable field filtering via `exclude.fields`
- Configurable path filtering via `exclude.paths` (wildcard support)
- Outgoing HTTP request logging via Laravel's `ResponseReceived` event (opt-in, `log_outgoing`)
- `logs:flush` Artisan command — manually drain the full buffer to storage
- `logs:clear` Artisan command — discard the buffer without persisting
- Auto-schedule option — registers `logs:flush` on a configurable cron via `schedule.enabled` and `schedule.cron`

### Fixed
- Default table name in config was `'r'`; corrected to `'http_request_logs'`
- `flush()` previously drained only one `batch_size` chunk; now loops until the buffer is empty
- `created_at`/`updated_at` are now stamped by `DatabaseLogRepository` before insert
- Redis connection is now cached statically in `RequestLogBufferAction` — one connection per process instead of one per call
- `TestCase` now creates the `http_request_logs` table in-memory for feature tests
- Fixed typo `DynamoDBLogRepository` → `DynamoDbLogRepository` in the service provider
