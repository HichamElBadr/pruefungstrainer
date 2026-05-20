# Progress Update

## 2026-05-20 - Repair local MariaDB privilege tables

### Summary

Repaired corrupted local MariaDB privilege tables that prevented SQL exercise temporary database grants from being created.

### Changed Files

- `.gitignore`
- `docs/progress.md`

### Behavior Changes

- Local SQL exercise database creation can grant `SELECT` access to the runtime SQL user again.
- Local MariaDB table backups under `storage/db-backups` are ignored by Git.
- No application code was changed.

### Testing

- `CHECK TABLE` for MariaDB privilege tables: passed.
- Temporary `CREATE DATABASE`, `GRANT SELECT`, `REVOKE`, and `DROP DATABASE` probe: passed.
- Laravel `DatabaseManager` create/drop temporary database probe: passed.

### Follow-up Notes

- Local backups of the repaired MariaDB table files were saved under `storage/db-backups`.

## 2026-05-20 - Guard SQL temporary database cleanup

### Summary

Prevented SQL exercise generation from passing a missing temporary database name into the cleanup routine after early PDO failures.

### Changed Files

- `app/Services/SqlExerciseGenerator.php`
- `docs/progress.md`

### Behavior Changes

- SQL generation retries no longer fail with a type error when a PDO exception occurs before a temporary database name has been assigned.
- Temporary databases are still dropped after failed generated SQL setup attempts when a database was successfully created.

### Testing

- `php artisan test --filter=SqlExerciseGeneratorTest`: passed.

### Follow-up Notes

- None.

## 2026-05-19 - Move app navigation into sidebar

### Summary

Removed the redundant authenticated top navigation and made the existing left sidebar the primary navigation for dashboard and exercise pages.

### Changed Files

- `resources/views/layouts/app.blade.php`
- `resources/views/components/exercise-layout.blade.php`
- `resources/views/it/partials/exercise-sidebar.blade.php`
- `resources/views/components/primary-button.blade.php`
- `resources/views/dashboard.blade.php`
- `resources/views/profile/edit.blade.php`
- `resources/views/it/sql-exercise/select-difficulty.blade.php`
- `resources/views/it/calculation-exercises/index.blade.php`
- `resources/css/app.css`
- `tests/Feature/ItExerciseFlowTest.php`
- `docs/progress.md`

### Behavior Changes

- Authenticated pages no longer render the Breeze top navigation bar or Laravel logo.
- The sidebar now contains the app identity, Dashboard, SQL-Aufgaben, Rechenaufgaben, UML-Aufgaben, and the authenticated user area.
- Profile and logout actions are available from the sidebar, with logout still submitted through the existing POST route.
- Dashboard and profile pages now use the same sidebar-based shell as the exercise pages.
- Primary action buttons now use an indigo style instead of the previous dark slate style.
- Existing routes, controllers, AI generation, SQL execution, and calculation evaluation were left unchanged.

### Testing

- `php artisan test --filter=ItExerciseFlowTest`: passed.
- `php artisan test --filter=ProfileTest`: passed.
- `php artisan test`: passed.
- `npm.cmd run build`: passed.
- `vendor\bin\pint.bat --dirty --test`: passed.

### Follow-up Notes

- The old Breeze navigation Blade file remains in the project but is no longer included by the authenticated app layout.

## 2026-05-19 - Refine calculation topic cards

### Summary

Improved the calculation exercise overview cards so they match the clarity and action treatment of the SQL exercise overview.

### Changed Files

- `resources/views/it/calculation-exercises/index.blade.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `docs/progress.md`

### Behavior Changes

- Calculation topic cards now include short German descriptions for each topic.
- `Neue Aufgabe erzeugen` is now styled as a clear button-like action within each clickable topic card.
- The calculation overview header now uses `Rechenaufgabe` and `Themenauswahl` badges.
- Existing routes, form actions, AI generation, and calculation evaluation were left unchanged.

### Testing

- `php artisan test --filter=ItExerciseFlowTest`: passed.
- `npm.cmd run build`: passed.
- `vendor\bin\pint.bat --dirty --test`: passed.

### Follow-up Notes

- None.

## 2026-05-19 - Refine SQL exercise selection UI

### Summary

Refined the existing exercise navigation and SQL difficulty selection cards without changing backend behavior.

### Changed Files

- `resources/views/it/partials/exercise-sidebar.blade.php`
- `resources/views/it/sql-exercise/select-difficulty.blade.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `docs/progress.md`

### Behavior Changes

- The left exercise navigation now uses the same exercise order as the top navigation: SQL, Rechenaufgaben, UML.
- SQL difficulty cards now include learner-friendly descriptions for `Einfach`, `Mittel`, and `Schwer`.
- The card action now presents `Aufgabe erzeugen` as a clear button-like element while keeping the full card clickable.
- The SQL selection header now uses `Übungsstufe` and `Beispieldatenbank`.
- Existing routes, form actions, AI generation, SQL execution, and controller logic were left unchanged.

### Testing

- `php artisan test --filter=ItExerciseFlowTest`: passed.
- `npm.cmd run build`: passed.
- `vendor\bin\pint.bat --dirty --test`: passed.

### Follow-up Notes

- None.

## 2026-05-19 - Refresh exercise page UI

### Summary

Improved the exercise area with a shared layout, left sidebar navigation, consistent cards, badges, typography, and styled SQL/table displays.

### Changed Files

- `app/Http/Controllers/CalculationExerciseController.php`
- `app/Http/Controllers/SqlExerciseController.php`
- `resources/css/app.css`
- `resources/views/components/exercise-layout.blade.php`
- `resources/views/it/partials/exercise-sidebar.blade.php`
- `resources/views/it/sql-exercise/index.blade.php`
- `resources/views/it/sql-exercise/select-difficulty.blade.php`
- `resources/views/it/calculation-exercises/index.blade.php`
- `resources/views/it/uml-exercise/index.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/navigation.blade.php`
- `tailwind.config.js`
- `tests/Feature/ItExerciseFlowTest.php`
- `docs/progress.md`

### Behavior Changes

- Exercise pages now share a professional two-column layout with a left sidebar for SQL, UML, and calculation exercises.
- The active exercise section is highlighted in the sidebar and main navigation.
- SQL tables, query results, SQL input, and solution code now use consistent learning-oriented styling.
- Calculation exercises now separate the task, answer input, feedback, expected result, and sample solution into distinct cards.
- Fixture-backed exercises now display `Beispielaufgabe`; generated exercises display `KI-generiert`.
- Existing routes, form actions, request methods, AI generation, SQL execution, and calculation checking were left unchanged.

### Testing

- `php artisan test --filter=ItExerciseFlowTest`: passed.
- `php artisan test`: passed.
- `npm.cmd run build`: passed.
- `vendor\bin\pint.bat --dirty --test`: passed after formatting dirty PHP files with Pint.

### Follow-up Notes

- No Aufgabenverlauf route currently exists, so no history link is shown in the sidebar.

## 2026-05-19 - Add exercise source badges

### Summary

Added persisted source tracking for SQL and calculation exercises and displayed a German source badge on exercise pages.

### Changed Files

- `app/Models/Exercise.php`
- `app/Services/AI/AiResponseProvider.php`
- `app/Services/SqlExerciseGenerator.php`
- `app/Http/Controllers/SqlExerciseController.php`
- `app/Http/Controllers/CalculationExerciseController.php`
- `database/migrations/2026_05_19_000004_add_source_to_exercises_table.php`
- `resources/views/it/sql-exercise/index.blade.php`
- `resources/views/it/calculation-exercises/index.blade.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `docs/progress.md`

### Behavior Changes

- New exercises now store `source` as `generated` for AI gateway responses or `fixture` for prepared fixture responses.
- SQL exercises display `KI-generierte Aufgabe` or `Vorbereitete Aufgabe`.
- Calculation exercises display `KI-generierte Aufgabe` or `Vorbereitete Aufgabe`.
- Existing routes, authentication, difficulty selection, and UML behavior were left unchanged.

### Testing

- `php artisan test --filter=ItExerciseFlowTest`: passed.
- `php artisan test --filter=SqlExerciseGeneratorTest`: passed.
- `php artisan test --filter=AiResponseProviderTest`: passed.

### Follow-up Notes

- Run `php artisan migrate` locally to add the nullable `source` column before using the new badge data outside the test database.

## 2026-05-19 - Add SQL difficulty selection and exercise fixtures

### Summary

Changed the SQL exercise entry point so it opens a difficulty selection page before generating an exercise. Added difficulty-aware SQL generation, fixture fallback lookup, and broader SQL and calculation fixture collections for tests, demos, and local development.

### Changed Files

- `routes/web.php`
- `app/Http/Controllers/SqlExerciseController.php`
- `app/Http/Controllers/CalculationExerciseController.php`
- `app/Models/Exercise.php`
- `app/Services/AI/AiFixtureService.php`
- `app/Services/AI/AiResponseProvider.php`
- `app/Services/SqlExerciseGenerator.php`
- `database/migrations/2026_05_19_000003_add_difficulty_to_exercises_table.php`
- `resources/views/it/sql-exercise/select-difficulty.blade.php`
- `resources/views/it/sql-exercise/index.blade.php`
- `resources/views/layouts/navigation.blade.php`
- `resources/ai-fixtures/sql.json`
- `resources/ai-fixtures/calculation.json`
- `ai-gateway/app/schemas/sql.py`
- `ai-gateway/app/services/sql_service.py`
- `ai-gateway/prompts/sql/sql_v1.txt`
- `ai-gateway/tests/test_sql_service.py`
- `tests/Unit/AiFixtureServiceTest.php`
- `tests/Unit/AiResponseProviderTest.php`
- `tests/Unit/SqlExerciseGeneratorTest.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `docs/progress.md`

### Behavior Changes

- Opening `SQL-Aufgaben` now shows `Einfach`, `Mittel`, and `Schwer` instead of generating immediately.
- SQL generation now accepts only `easy`, `medium`, or `hard`; invalid values return 404.
- SQL exercise prompts and AI gateway prompts now include explicit difficulty rules.
- Generated SQL exercises store and display the selected difficulty with German labels.
- SQL fixtures now include at least three exercises per difficulty and are used for matching fallback.
- Calculation fixtures now include at least two exercises per supported topic and can be used as fallback by topic and difficulty.
- Calculation AI failures now fall back to matching fixtures without showing raw gateway errors.
- UML behavior was not changed.

### Testing

- `php artisan test --filter=AiFixtureServiceTest`: passed.
- `php artisan test --filter=AiResponseProviderTest`: passed.
- `php artisan test --filter=SqlExerciseGeneratorTest`: passed.
- `php artisan test --filter=ItExerciseFlowTest`: passed.
- `php artisan test`: passed.
- `php artisan route:list --path=it`: verified SQL, calculation, and UML routes.
- `C:\Users\hicha\AppData\Local\Programs\Python\Python313\python.exe -m unittest discover -s ai-gateway\tests` with `PYTHONPATH` including `ai-gateway` and `.venv\Lib\site-packages`: passed.
- `C:\Users\hicha\AppData\Local\Programs\Python\Python313\python.exe -m compileall ai-gateway\app`: passed.

### Follow-up Notes

- The existing `ai-gateway\.venv\Scripts\python.exe` launcher points to an inaccessible WindowsApps Python shim in this environment, so gateway tests were run with the installed Python executable and the venv site-packages on `PYTHONPATH`.
- Run `php artisan migrate` locally to add the nullable `difficulty` column before using stored difficulties outside the test database.

## 2026-05-19 - Harden calculation exercise generation

### Summary

Improved calculation exercise generation quality by requiring richer AI output and validating generated exercises before returning them to Laravel.

### Changed Files

- `ai-gateway/prompts/calculation/calculation_v1.txt`
- `ai-gateway/app/schemas/calculation.py`
- `ai-gateway/app/services/calculation_service.py`
- `ai-gateway/app/api/routes.py`
- `ai-gateway/tests/test_calculation_service.py`
- `app/Http/Controllers/CalculationExerciseController.php`
- `app/Services/AI/AiResponseProvider.php`
- `app/Models/Exercise.php`
- `database/migrations/2026_05_19_000002_add_calculation_metadata_to_exercises_table.php`
- `resources/ai-fixtures/calculation.json`
- `resources/views/it/calculation-exercises/index.blade.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `docs/progress.md`

### Behavior Changes

- Calculation AI responses now include `title`, `task`, `expected_result`, `expected_unit`, and `sample_solution`.
- The AI gateway rejects vague, too-short, under-specified, or non-numeric calculation outputs with HTTP 422.
- The AI gateway retries calculation generation up to three times and records the successful attempt in metadata.
- Calculation exercises now display a German title and expected unit in the UI.
- The expected result remains numeric internally, while the unit is displayed separately.
- SQL and UML flows were left unchanged.

### Testing

- `ai-gateway\.venv\Scripts\python.exe -m unittest discover -s tests`: passed.
- `ai-gateway\.venv\Scripts\python.exe -m compileall ai-gateway\app`: passed.
- `php artisan migrate`: passed.
- `php artisan test --filter=ItExerciseFlowTest`: passed.
- `php artisan test`: passed.
- `php artisan route:list --path=it`: verified calculation, SQL, and UML routes.

### Follow-up Notes

- The live model can still fail all three attempts if it repeatedly ignores the schema, but the user will no longer receive vague or incomplete calculation exercises from those invalid generations.

## 2026-05-19 - Improve Ollama connection errors

### Summary

Converted unavailable Ollama connections in the AI gateway into clear HTTP errors.

### Changed Files

- `ai-gateway/app/clients/ollama_client.py`
- `docs/progress.md`

### Behavior Changes

- If Ollama is not reachable, the gateway now returns HTTP 503 with a clear message instead of logging a full unhandled traceback.
- Ollama timeouts now return HTTP 504 with the configured timeout value.
- Other Ollama request failures still return HTTP 502.

### Testing

- `ai-gateway\.venv\Scripts\python.exe -m compileall ai-gateway\app`: passed.
- `Invoke-RestMethod http://127.0.0.1:11434/api/tags`: initially failed while Ollama was unavailable, then passed after `ollama list` woke the local service.

### Follow-up Notes

- A direct `deepseek-r1:7b` test generation exceeded 30 seconds during local probing; keep `OLLAMA_TIMEOUT_SEC` high enough for cold model starts.

## 2026-05-19 - Refactor Scan exercises into calculation exercises

### Summary

Replaced the Scan exercise flow with a calculation exercise feature named "Rechenaufgaben" in the UI.

### Changed Files

- `app/Http/Controllers/CalculationExerciseController.php`
- `app/Services/CalculationExerciseTopicCatalog.php`
- `app/Services/AI/AiGatewayClient.php`
- `app/Services/AI/AiResponseProvider.php`
- `app/Services/SolutionEvaluator.php`
- `app/Models/Exercise.php`
- `routes/web.php`
- `resources/views/it/calculation-exercises/index.blade.php`
- `resources/views/layouts/navigation.blade.php`
- `resources/ai-fixtures/calculation.json`
- `database/migrations/2026_05_19_000000_add_sample_solution_to_exercises_table.php`
- `database/migrations/2026_05_19_000001_rename_scan_category_to_calculation.php`
- `database/seeders/CategorySeeder.php`
- `ai-gateway/app/api/routes.py`
- `ai-gateway/app/schemas/calculation.py`
- `ai-gateway/app/services/calculation_service.py`
- `ai-gateway/prompts/calculation/calculation_v1.txt`
- `tests/Feature/ItExerciseFlowTest.php`

### Behavior Changes

- The navigation now links to "Rechenaufgaben" instead of "Scan".
- The calculation exercise overview shows selectable topic buttons for Prozentrechnung, Dreisatz, Multiplikation, Division, Speichergrößen, Stromverbrauch and Hardwarekosten.
- Selecting a topic generates one calculation exercise for that topic through the local AI gateway abstraction.
- Calculation exercises now store the expected result separately from a step-by-step German sample solution.
- Unknown calculation topics return a clean 404 response.
- The old Scan controller, route, view, fixture and gateway endpoint were replaced by calculation exercise naming.

### Testing

- `php artisan migrate`: passed.
- `php artisan test --filter=ItExerciseFlowTest`: passed.
- `php artisan test`: passed.
- `ai-gateway\.venv\Scripts\python.exe -m compileall ai-gateway\app`: passed.

### Follow-up Notes

- Live Ollama quality still depends on the local model following the requested topic and numeric `expected_result` format.

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

- The Windows Python launcher is still blocked by an access-denied error, but AI gateway bytecode compilation now works through the project virtualenv at `ai-gateway\.venv\Scripts\python.exe`.
