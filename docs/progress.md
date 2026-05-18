# Progress Update

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
