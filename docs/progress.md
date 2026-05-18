# Progress Update

## 2026-05-18 - Regenerate invalid SQL exercises automatically

### Summary

Added bounded regeneration for AI-generated SQL exercises when MariaDB rejects the generated setup SQL.

### Changed Files

- `app/Services/SqlExerciseGenerator.php`
- `app/Services/DatabaseManager.php`
- `app/Http/Controllers/SqlExerciseController.php`
- `config/exercises.php`
- `.env.example`
- `tests/Unit/SqlExerciseGeneratorTest.php`
- `docs/progress.md`

### Behavior Changes

- SQL exercise generation now retries up to a configurable number of attempts when generated setup SQL is invalid.
- Failed generated attempts are rejected before the learner sees them, logged, and their temporary databases are dropped immediately.
- Retry prompts now include the previous MariaDB error and rejected SQL so the model can correct the failed setup instead of repeating it unchanged.
- If all live attempts fail, the app now falls back to the local SQL fixture so the exercise page still opens instead of returning an error.
- The retry limit can be configured with `SQL_EXERCISE_GENERATION_ATTEMPTS`.

### Testing

- `php artisan test --filter=SqlExerciseGeneratorTest`: passed.
- `php artisan test --filter=ItExerciseFlowTest`: passed.
- `php artisan test --filter=QueryHandlerTest`: passed.
- Live SQL route check with Ollama running: invalid live generations were rejected and the page still loaded successfully through the fixture fallback.

### Follow-up Notes

- The retry path only handles generated SQL execution failures; unrelated gateway or application errors still surface normally.

## 2026-05-18 - Add SQL exercise error protocol

### Summary

Added a dedicated SQL exercise error log so generated SQL failures and learner-query execution failures can be reviewed after they occur.

### Changed Files

- `config/logging.php`
- `app/Http/Controllers/SqlExerciseController.php`
- `app/Services/DatabaseManager.php`
- `app/Services/QueryHandler.php`
- `docs/progress.md`

### Behavior Changes

- SQL exercise failures are now written to `storage/logs/sql-exercise.log`.
- Failed generated SQL statements are logged with the failing statement number and SQL text.
- SQL exercise flow failures are logged with request context, and learner-query execution exceptions are logged separately.

### Testing

- `php artisan test --filter=ItExerciseFlowTest`: passed.
- `php artisan test --filter=QueryHandlerTest`: passed.
- Live SQL route failure test: confirmed the generated SQL error was written to `storage/logs/sql-exercise.log`.

### Follow-up Notes

- The protocol is intended for troubleshooting the SQL exercise flow and does not change UML, Scan, auth, or UI behavior.

## 2026-05-18 - Harden SQL AI JSON generation

### Summary

Tightened the SQL exercise prompt and enabled Ollama JSON mode for SQL generation to prevent malformed AI JSON responses in the SQL exercise flow.

### Changed Files

- `ai-gateway/prompts/sql/sql_v1.txt`
- `ai-gateway/app/clients/ollama_client.py`
- `ai-gateway/app/services/sql_service.py`
- `docs/progress.md`

### Behavior Changes

- SQL generation now instructs the local model to return exactly one plain JSON object with the existing `task`, `mysqlstatement`, and `solution` string fields.
- SQL generation now requests Ollama JSON mode while keeping non-SQL AI flows unchanged.

### Testing

- `php artisan test --filter=ItExerciseFlowTest`: passed.
- Manual POST to the local AI gateway `/generate/sql` endpoint with Ollama running: returned a valid JSON response with the expected SQL fields.
- Authenticated local SQL navigation flow through the Laravel app: `/it/sql-uebung` loaded successfully without an `AI-Gateway error`.

### Follow-up Notes

- A later live SQL-page retry exposed a separate model-generated foreign-key issue in the SQL content; that is unrelated to malformed JSON formatting and was not changed here.

## Completed Improvements

### Exercise ownership and route protection
- Added `user_id` ownership to exercises and linked `User` and `Exercise` models.
- Replaced global `Exercise::latest()` lookups with session-bound current exercise ids for SQL and Scan flows.
- Protected all `/it/*` exercise routes with `auth` and `verified` middleware.
- Fixed active navigation states and added the IT links to the mobile navigation.

### SQL exercise hardening
- Moved SQL exercise database settings into `config/exercises.php`.
- Added support for separate admin and runtime SQL users.
- Created unique temporary database names with a timestamp plus random suffix.
- Moved stale temporary database cleanup out of controller construction into a scheduled Artisan command.
- Added structured table loading to `DatabaseManager`.
- Restricted submitted SQL to one `SELECT` statement only.
- Replaced raw HTML query output with structured, escaped rendering in the Blade view.
- Added local environment settings for a dedicated `sql_exercise_reader` runtime user.

### AI integration cleanup
- Unified the Laravel AI abstraction around `AiResponseProvider`.
- Added `getScan()` support and shared response validation logic.
- Removed the old direct `OllamaService` path from the Laravel app.
- Completed Scan support in the Python AI gateway:
  - Added `/generate/scan`
  - Added Scan request/response schema
  - Added Scan prompt template and service

### Scan exercise improvements
- Reworked the Scan controller to use structured AI payloads.
- Stored the current Scan exercise in the session.
- Added numeric input validation before solution comparison.
- Escaped generated task and solution output in the Blade view.
- Completed `SolutionEvaluator::compareText()` and made numeric comparison tolerant of non-numeric input.

### UML / PlantUML improvements
- Moved PlantUML paths and behavior flags into `config/plantuml.php`.
- Removed the hardcoded Windows Java path from the service.
- Switched process execution to argument-array based `Process` usage.
- Added cleanup for stale temporary PlantUML artifacts.
- Deleted generated PNG files after embedding them in the response.
- Disabled raw PlantUML directives by default and tightened accepted simplified UML syntax.
- Updated the UML help text to match the safer input model.

### General cleanup
- Replaced `dd()`-style failure handling in the exercised flows with normal HTTP or validation errors.
- Removed stale imports and unfinished legacy dependencies.
- Improved fixture service documentation wording.

## Verification Performed

- Ran the Laravel migration that adds `exercise.user_id`.
- Created a dedicated local SQL runtime account and updated the local environment to use it.
- Cleared Laravel configuration cache.
- Ran `php artisan test`.
  - Result: `32 passed`, `93 assertions`
- Ran `php artisan route:list --path=it -v`.
  - Confirmed all IT exercise routes now use `auth` and `verified`.

## Added Test Coverage

- Added feature tests for:
  - IT route protection
  - SQL exercise creation and session scoping
  - Scan exercise creation and answer checking
  - UML rendering flow
  - Rejection of raw PlantUML directives
- Added unit tests for:
  - Allowed `SELECT` queries
  - Rejection of non-`SELECT` queries

## Remaining Note

- Python bytecode compilation for the AI gateway could not be run in this environment because the Windows Python launcher is blocked by an access-denied error. The FastAPI changes were reviewed in code, but not executed locally here.
