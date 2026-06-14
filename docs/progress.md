# Progress Update

## 2026-06-14 - Repair hard UML Use Case fixtures

### Summary

Repaired five newly added hard UML Use Case exercises and added them to the
validated exercise catalog.

### Changed Files

- `resources/exercises/uml/hard/use-case.json`
- `tests/Feature/ImportExercisesCommandTest.php`
- `tests/Feature/ValidateExercisesCommandTest.php`
- `docs/progress.md`

### Behavior Changes

- The hard Use Case fixture file is valid JSON and uses the supported
  `use_case` diagram type.
- Quoted PlantUML actor, system boundary, and use case labels are escaped
  correctly.
- Five hard Use Case exercises are available in the catalog.
- The catalog now contains 58 exercises, including 28 UML exercises and 9 hard
  UML exercises.
- Cached sample solution images were generated for all five new exercises.

### Testing

- `php artisan exercises:validate`: passed, 58 exercises validated.
- `php artisan exercises:import`: passed, 58 exercises imported.
- `php artisan exercises:render-uml-solutions`: passed, 5 rendered, 23 reused,
  and 0 failed.
- `php artisan test --filter=JsonExerciseProviderTest`: passed, 16 tests and
  620 assertions.
- `php artisan test --filter=ImportExercisesCommandTest`: passed, 9 tests and
  59 assertions.
- `php artisan test --filter=ValidateExercisesCommandTest`: passed, 6 tests and
  16 assertions.
- `php artisan test`: passed, 103 tests and 1103 assertions.
- `vendor/bin/pint --test tests/Unit/JsonExerciseProviderTest.php
  tests/Feature/ImportExercisesCommandTest.php
  tests/Feature/ValidateExercisesCommandTest.php`: passed.

### Follow-up Notes

- None.

## 2026-06-14 - Split UML fixtures by diagram type

### Summary

Reorganized the UML fixture catalog into one JSON file per diagram type and
difficulty while repairing the newly added medium Use Case exercises.

### Changed Files

- `resources/exercises/uml/easy/class.json`
- `resources/exercises/uml/easy/er.json`
- `resources/exercises/uml/easy/use-case.json`
- `resources/exercises/uml/medium/class.json`
- `resources/exercises/uml/medium/sequence.json`
- `resources/exercises/uml/medium/use-case.json`
- `resources/exercises/uml/hard/activity.json`
- `resources/exercises/uml/hard/class.json`
- Removed the former `exercises.json` files from the UML difficulty folders.
- `tests/Unit/JsonExerciseProviderTest.php`
- `tests/Feature/ImportExercisesCommandTest.php`
- `tests/Feature/ValidateExercisesCommandTest.php`
- `docs/progress.md`

### Behavior Changes

- Each UML fixture file now contains exercises for exactly one diagram type.
- Existing class, ER, sequence, activity, and Use Case exercises remain in the
  catalog after the restructuring.
- Five new medium Use Case exercises were repaired and added to the catalog.
- Invalid `usecase` values now use the supported `use_case` diagram type.
- Invalid JSON suffixes and unescaped PlantUML labels were removed.
- The UML catalog now contains 10 easy, 9 medium, and 4 hard exercises.
- A fixture coverage test enforces the file-to-diagram-type convention.

### Testing

- `php artisan exercises:validate`: passed, 53 exercises validated.
- `php artisan exercises:import`: passed, 53 exercises imported.
- `php artisan test --filter=JsonExerciseProviderTest`: passed, 16 tests and
  605 assertions.
- `php artisan test --filter=ImportExercisesCommandTest`: passed, 9 tests and
  59 assertions.
- `php artisan test --filter=ValidateExercisesCommandTest`: passed, 6 tests and
  15 assertions.
- `php artisan test`: passed, 103 tests and 1087 assertions.
- `vendor/bin/pint --test tests/Unit/JsonExerciseProviderTest.php
  tests/Feature/ImportExercisesCommandTest.php
  tests/Feature/ValidateExercisesCommandTest.php`: passed.

### Follow-up Notes

- Empty files are intentionally not created for diagram types that have no
  exercises at a given difficulty because empty fixture collections are
  invalid.

## 2026-06-14 - Repair the easy UML fixture collection

### Summary

Repaired the easy UML fixture file after a second JSON collection had been
appended with invalid syntax and unsupported diagram type values.

### Changed Files

- `resources/exercises/uml/easy/exercises.json`
- `docs/progress.md`

### Behavior Changes

- The original and newly added easy UML exercises now form one valid JSON
  collection containing ten exercises.
- The five added Use Case exercises use the supported `use_case` diagram type.
- Quoted PlantUML actor, system boundary, and use case labels are escaped
  correctly in JSON.
- All repaired exercises can be validated, rendered, loaded, and imported into
  the exercise catalog.

### Testing

- `php artisan exercises:validate`: passed, 48 exercises validated.
- `php artisan exercises:import`: passed, 48 exercises imported.
- `php artisan test --filter=JsonExerciseProviderTest`: passed, 15 tests and
  555 assertions.

### Follow-up Notes

- None.

## 2026-06-14 - Widen the shared desktop application shell

### Summary

Expanded the shared authenticated page shell so dashboard, profile, SQL,
calculation, UML, and future exercise views use wide desktop screens more
effectively.

### Changed Files

- `resources/views/components/exercise-layout.blade.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `docs/progress.md`

### Behavior Changes

- The sidebar and main content are centered together in a shared shell with a
  1600-pixel maximum width instead of the previous 1280-pixel limit.
- Existing mobile, tablet, sidebar, grid, spacing, and horizontal padding
  behavior remains unchanged.
- All current authenticated pages benefit from the wider content column through
  the shared layout component.
- Pages that need the previous narrower presentation can pass
  `:narrow="true"` to the shared exercise layout.
- Internal readability limits such as the profile form's `max-w-xl` remain in
  place.

### Testing

- Shared-shell feature test: passed, 1 test and 20 assertions across dashboard,
  profile, SQL, calculation, and UML pages.
- `php artisan test`: passed, 102 tests and 1027 assertions.
- `php artisan view:cache`: passed.
- `npm.cmd run build`: passed.
- `vendor/bin/pint --dirty`: passed.
- `git diff --check`: passed.

### Follow-up Notes

- None.

## 2026-06-14 - Refactor the UML desktop workspace

### Summary

Reorganized the UML exercise page into a compact, responsive workbench while
preserving the existing exercise content and rendering flow.

### Changed Files

- `resources/views/components/exercise-layout.blade.php`
- `resources/views/components/exercise/hints.blade.php`
- `resources/views/it/uml-exercise/index.blade.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `docs/progress.md`

### Behavior Changes

- The UML page uses a compact header and selection toolbar.
- Large screens show task content in a five-part left column and the PlantUML
  editor and preview in a seven-part right column.
- Small screens continue to stack the same content vertically.
- Hints and the sample solution remain available as compact disclosure
  elements in the task column.
- The diagram preview is always visible as a dedicated minimum-height panel
  and displays a placeholder until the learner renders a diagram.
- The PlantUML guidance is shown as muted helper text next to the render action
  instead of a separate card.
- Exercise text, request fields, routes, selection behavior, rendering, cached
  sample solutions, and validation output remain unchanged.
- Compact layout options on shared components are opt-in, so other exercise
  pages retain their current presentation.

### Testing

- `php artisan test --filter=Uml`: passed, 14 tests and 84 assertions.
- `php artisan test`: passed, 101 tests and 1007 assertions.
- `php artisan view:cache`: passed.
- `npm.cmd run build`: passed.
- `vendor/bin/pint --dirty`: passed.
- `git diff --check`: passed.

### Follow-up Notes

- None.

## 2026-06-14 - Cache rendered UML sample solutions

### Summary

Added hash-based caching for rendered UML sample-solution diagrams. The
PlantUML source remains authoritative, while web requests and a new Artisan
command reuse PNG files stored on the public Laravel disk.

### Changed Files

- `README.md`
- `app/Console/Commands/RenderUmlSolutions.php`
- `app/Http/Controllers/UmlExerciseController.php`
- `app/Services/PlantUmlRenderCache.php`
- `resources/views/it/uml-exercise/index.blade.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `tests/Feature/RenderUmlSolutionsCommandTest.php`
- `tests/Unit/PlantUmlRenderCacheTest.php`
- `docs/progress.md`

### Behavior Changes

- UML sample-solution PNG files are named with a SHA-256 hash of
  `solution_plantuml`.
- Existing cache files are returned without invoking PlantUML again.
- Changed PlantUML source produces a different cache path automatically.
- The UML sample-solution section displays the cached diagram, source code,
  expected elements, and explanation.
- Cached images are served through an authenticated Laravel route instead of
  relying on the public-disk `APP_URL`, so XAMPP subdirectory installations
  generate working image URLs.
- Cache failures do not prevent the exercise page or source solution from
  loading.
- `php artisan exercises:render-uml-solutions` processes all UML JSON fixtures,
  continues after individual failures, and reports rendered, reused, and failed
  counts.
- Learner-submitted PlantUML continues to use the existing dynamic rendering
  path.

### Testing

- Focused cache, command, and UML-flow tests: passed, 22 tests and 229
  assertions.
- First real cache command run: 12 rendered, 1 reused, 0 failed.
- Second real cache command run: 0 rendered, 13 reused, 0 failed.
- `php artisan test`: passed, 101 tests and 1000 assertions.
- `php artisan view:cache`: passed.
- Cache filesystem check: 13 SHA-256-named PNG files and an active public
  storage link.
- Local XAMPP HTTP check reached the image route under
  `/pruefungstrainer/public` and correctly applied authentication.
- Laravel Pint and `git diff --check`: passed.

### Follow-up Notes

- None.

## 2026-06-14 - Add reusable progressive exercise hints

### Summary

Added optional progressive hints as a shared exercise feature for SQL, UML,
calculation, and future catalog types. Centralized hint normalization and added
one reusable collapsible Blade component across the existing exercise views.

### Changed Files

- `README.md`
- `app/Http/Controllers/CalculationExerciseController.php`
- `app/Http/Controllers/SqlExerciseController.php`
- `app/Http/Controllers/UmlExerciseController.php`
- `app/Models/Exercise.php`
- `app/Services/Exercises/DatabaseExerciseProvider.php`
- `app/Services/Exercises/ExerciseFixtureImporter.php`
- `app/Services/Exercises/ExerciseHintNormalizer.php`
- `app/Services/Exercises/JsonExerciseProvider.php`
- `database/migrations/2026_06_14_000001_add_hints_to_exercises_table.php`
- `resources/exercises/sql/easy/exercises.json`
- `resources/exercises/uml/hard/exercises.json`
- `resources/views/components/exercise/hints.blade.php`
- `resources/views/it/calculation-exercises/index.blade.php`
- `resources/views/it/sql-exercise/index.blade.php`
- `resources/views/it/uml-exercise/index.blade.php`
- `tests/Feature/ImportExercisesCommandTest.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `tests/Unit/ExerciseHintNormalizerTest.php`
- `tests/Unit/JsonExerciseProviderTest.php`
- `docs/progress.md`

### Behavior Changes

- Every exercise payload now contains a normalized `hints` array.
- Missing or non-array hints become an empty array.
- Incomplete entries, invalid levels, and duplicate levels are ignored without
  rejecting the exercise.
- Valid hints are sorted by levels `1`, `2`, and `3` and limited to three.
- SQL, UML, and calculation pages use the same collapsed hint component before
  the learner input area.
- Hint usage is not stored and does not reveal or affect sample solutions.
- One SQL fixture and one UML fixture include minimal example hints.

### Testing

- Focused hint, provider, import, and exercise-flow tests: passed, 44 tests and
  808 assertions.
- `php artisan exercises:validate`: passed for all 43 fixtures with SQL and
  PlantUML execution enabled.
- `php artisan exercises:import`: passed; 43 existing catalog exercises
  updated.
- `php artisan test`: passed, 96 tests and 963 assertions.
- `php artisan view:cache`: passed.
- Laravel Pint and `git diff --check`: passed.

### Follow-up Notes

- The component accepts a `mode` value for future behavior changes, but practice
  and exam-mode differences are intentionally not implemented yet.

## 2026-06-14 - Support structured multi-type UML exercises

### Summary

Refactored the UML exercise flow to load structured catalog fixtures for class,
ER/data-model, use-case, sequence, and activity diagrams. Replaced the
class-specific input normalizer with neutral PlantUML wrapping and kept the
selected catalog exercise stable during rendering.

### Changed Files

- `.env.example`
- `README.md`
- `app/Http/Controllers/UmlExerciseController.php`
- `app/Models/UmlExerciseDetail.php`
- `app/Services/PlantUmlInput.php`
- `app/Services/PlantUmlService.php`
- `app/Services/Exercises/DatabaseExerciseProvider.php`
- `app/Services/Exercises/ExerciseFixtureImporter.php`
- `app/Services/Exercises/JsonExerciseProvider.php`
- `config/exercises.php`
- `config/plantuml.php`
- `database/migrations/2026_06_14_000000_add_structured_fields_to_uml_exercise_details.php`
- `resources/exercises/uml/*/exercises.json`
- `resources/views/it/uml-exercise/index.blade.php`
- `tests/Feature/ImportExercisesCommandTest.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `tests/Feature/ValidateExercisesCommandTest.php`
- `tests/Unit/JsonExerciseProviderTest.php`
- `tests/Unit/PlantUmlInputTest.php`

### Behavior Changes

- UML fixtures now validate and persist `diagram_type`, `scenario`,
  `requirements`, `starter_plantuml`, `solution_plantuml`, and
  `expected_elements`.
- Supported diagram types are `class`, `er`, `use_case`, `sequence`, and
  `activity`.
- The UML page provides German diagram-type navigation and displays the full
  structured task data.
- Rendering resolves the submitted exercise ID instead of relying on session
  state, preserving the selected exercise and learner input on render errors.
- PlantUML input is passed through unchanged when it contains `@startuml`;
  otherwise only the standard start and end markers are added.
- `PlantUmlService` now focuses exclusively on rendering complete PlantUML
  source and no longer injects class-diagram directives.
- Existing class fixtures use the new schema, with four minimal fixtures added
  to cover the other diagram types.

### Testing

- `php artisan exercises:validate`: passed for all 43 fixtures with SQL and
  PlantUML execution enabled.
- `php artisan exercises:import`: passed; 43 catalog exercises imported.
- `php artisan test`: passed, 92 tests and 916 assertions.
- Focused final UML tests: passed, 33 tests and 710 assertions.
- `php artisan view:cache`: passed.
- Laravel Pint and `git diff --check`: passed.

### Follow-up Notes

- Replace the four minimal non-class placeholders with the final AP2-style UML
  fixture collection in a later task.

## 2026-06-13 - Germanize SQL fixtures and add cyclic navigation

### Summary

Converted all SQL fixture schemas, sample data, tasks, and solutions to German
domain terminology. Added deterministic next-exercise navigation that stays
within the current SQL difficulty and rotates after the final exercise.

### Changed Files

- `README.md`
- `agents.md`
- `app/Http/Controllers/SqlExerciseController.php`
- `app/Models/CalculationExerciseDetail.php`
- `app/Models/SqlExerciseDetail.php`
- `app/Models/UmlExerciseDetail.php`
- `resources/exercises/sql/easy/exercises.json`
- `resources/exercises/sql/medium/exercises.json`
- `resources/exercises/sql/hard/exercises.json`
- `resources/views/it/sql-exercise/index.blade.php`
- `routes/web.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `tests/Feature/ImportExercisesCommandTest.php`
- `tests/Unit/JsonExerciseProviderTest.php`
- `docs/progress.md`

### Behavior Changes

- All nine SQL fixtures use German table and column names.
- SQL tasks, sample values, explanations, and solution queries use German
  domain terminology.
- Existing `easy`, `medium`, and `hard` fixture directories retain three SQL
  exercises each.
- SQL exercise pages display a clearly visible `Nächste Aufgabe` button.
- The next exercise is selected by stable `external_id` order within the same
  category and difficulty.
- Navigation from the final matching exercise rotates to the first one.
- SQL execution, expected result rendering, and request-scoped sandbox cleanup
  remain unchanged.
- Type-specific detail models now use their actual `exercise_id` primary key so
  changed fixtures update correctly on MySQL.

### Testing

- `php artisan test --filter="JsonExerciseProviderTest|ItExerciseFlowTest"`:
  passed, 27 tests and 679 assertions.
- `php artisan exercises:import`: updated all 39 catalog exercises twice
  without duplicates.
- Catalog check: three published SQL exercises exist for each difficulty.
- `php artisan test`: passed, 86 tests and 878 assertions.
- `php artisan exercises:validate`: passed for all 39 fixtures with SQL and
  PlantUML checks enabled.
- `php artisan view:cache`: passed.
- Laravel Pint and `git diff --check`: passed.
- Final MariaDB residue check: zero temporary SQL schemas and zero
  database-specific runtime grants.

### Follow-up Notes

- None.

## 2026-06-13 - Harden SQL provisioning and internal error boundaries

### Summary

Closed the remaining SQL sandbox privilege leaks and removed administrative
execution of fixture setup SQL. Added strict setup statement validation,
recoverable catalog migration guards, and controlled UML rendering errors.

### Changed Files

- `README.md`
- `agents.md`
- `app/Http/Controllers/UmlExerciseController.php`
- `app/Services/DatabaseManager.php`
- `app/Services/Exercises/JsonExerciseProvider.php`
- `app/Services/Exercises/SqlSetupValidator.php`
- `config/exercises.php`
- `database/migrations/2026_06_13_000000_create_reusable_exercise_catalog.php`
- `routes/console.php`
- `tests/Feature/ImportExercisesCommandTest.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `tests/Unit/DatabaseManagerTest.php`
- `tests/Unit/JsonExerciseProviderTest.php`
- `tests/Unit/SqlSetupValidatorTest.php`
- `docs/progress.md`

### Behavior Changes

- Fixture setup SQL is limited to unqualified `CREATE TABLE` and
  `INSERT INTO ... VALUES` statements.
- Setup SQL executes as the dedicated runtime user instead of the provisioning
  account.
- Runtime `CREATE` and `INSERT` permissions are revoked before learner queries.
- Dropping a sandbox also revokes all database-specific runtime grants.
- Fallback cleanup removes orphaned grants for databases that no longer exist
  and reports both schema and grant cleanup counts.
- SQL admin and runtime usernames must be different.
- The catalog migration can resume when its new tables already exist after a
  partial MySQL DDL failure.
- Unexpected PlantUML process exceptions are logged but replaced with a generic
  learner-facing error.

### Testing

- Focused security and exercise flow tests: passed, 50 tests and 448 assertions.
- Real MariaDB fixture validation without PlantUML rendering: passed for all 39
  exercises.
- Fresh testing migration and seed: passed.
- Runtime privilege probe: setup writes passed, learner-time writes were
  rejected, SELECT remained available, and teardown removed both schema and
  grant.
- `php artisan sql-exercises:cleanup`: removed 28 stale schemas and 64 orphaned
  runtime grants from the previous implementation.
- `php artisan test`: passed, 83 tests and 557 assertions.
- `php artisan exercises:validate`: passed for all 39 fixtures with SQL and
  PlantUML checks enabled.
- `php artisan view:cache`: passed.
- Laravel Pint: passed for all changed PHP files.
- Final MariaDB residue check: zero temporary SQL schemas and zero
  database-specific runtime grants.

### Follow-up Notes

- None.

## 2026-06-13 - Support legacy SQL sandbox cleanup

### Summary

Extended fallback cleanup to recognize both current and legacy temporary SQL
database names while retaining strict schema-name validation. Expanded startup
documentation for manual, local scheduler, Linux cron, and Windows Task
Scheduler operation.

### Changed Files

- `README.md`
- `agents.md`
- `app/Services/DatabaseManager.php`
- `tests/Unit/DatabaseManagerTest.php`
- `docs/progress.md`

### Behavior Changes

- Stale cleanup accepts current
  `{prefix}{timestamp}_{ten-character-random-suffix}` database names.
- Stale cleanup also accepts legacy `{prefix}{timestamp}` database names.
- Malformed or merely similar schema names are ignored.
- Explicit temporary database drops use the same strict format validation.
- Documentation now identifies scheduled cleanup as a fallback and explains
  local and server execution.

### Testing

- `php artisan test --filter=DatabaseManagerTest`: passed, 2 tests and 9
  assertions.
- `php artisan test`: passed, 71 tests and 524 assertions.
- `php artisan exercises:validate`: passed for all 39 fixtures with SQL and
  PlantUML checks enabled.
- `php artisan schedule:list`: confirmed hourly fallback cleanup registration.
- Laravel Pint: passed for the changed PHP files.

### Follow-up Notes

- None.

## 2026-06-13 - Make SQL sandboxes request-scoped

### Summary

Replaced session-owned SQL exercise databases with disposable request-scoped
sandboxes. SQL submissions now identify the reusable catalog exercise in the
route, rebuild its setup in a fresh database, and clean up that database before
the response completes.

### Changed Files

- `README.md`
- `agents.md`
- `app/Http/Controllers/SqlExerciseController.php`
- `app/Services/DatabaseManager.php`
- `resources/views/it/sql-exercise/index.blade.php`
- `routes/web.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `docs/progress.md`

### Behavior Changes

- SQL exercise and temporary database identifiers are no longer stored as
  session state.
- The SQL form submits directly to the selected reusable catalog exercise.
- Every learner query receives a fresh database with the exercise `setup_sql`.
- Temporary databases are dropped from a `finally` path after successful and
  failed learner queries.
- Exercise previews also use a short-lived sandbox when reading sample tables.
- Parallel browser tabs cannot replace each other's selected SQL exercise.
- Partial database creation failures attempt immediate cleanup.
- The hourly stale-database cleanup remains available only as a recovery
  fallback.

### Testing

- `php artisan test --filter=ItExerciseFlowTest`: passed, 15 tests and 145
  assertions.
- `php artisan test`: passed, 69 tests and 515 assertions.
- `php artisan exercises:validate`: passed for all 39 fixtures with SQL and
  PlantUML checks enabled.
- `php artisan view:cache`: passed.
- Laravel Pint: passed for the changed PHP files.

### Follow-up Notes

- None.

## 2026-06-13 - Introduce reusable database exercise catalog

### Summary

Replaced temporary per-user exercise copies with a reusable database catalog.
Added type-specific detail tables and an idempotent importer that synchronizes
validated JSON fixtures by their stable external IDs.

### Changed Files

- `.env`
- `.env.example`
- `README.md`
- `agents.md`
- `app/Console/Commands/ImportExercises.php`
- `app/Http/Controllers/CalculationExerciseController.php`
- `app/Http/Controllers/SqlExerciseController.php`
- `app/Http/Controllers/UmlExerciseController.php`
- `app/Models/CalculationExerciseDetail.php`
- `app/Models/Category.php`
- `app/Models/Exercise.php`
- `app/Models/SqlExerciseDetail.php`
- `app/Models/UmlExerciseDetail.php`
- `app/Models/User.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/Exercises/DatabaseExerciseProvider.php`
- `app/Services/Exercises/ExerciseCollectionValidator.php`
- `app/Services/Exercises/ExerciseFixtureImporter.php`
- `config/exercises.php`
- `database/migrations/2026_06_13_000000_create_reusable_exercise_catalog.php`
- `database/seeders/CategorySeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/ExerciseCatalogSeeder.php`
- `resources/views/it/calculation-exercises/index.blade.php`
- `routes/console.php`
- `tests/Feature/ImportExercisesCommandTest.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `tests/Unit/JsonExerciseProviderTest.php`
- `docs/progress.md`

### Behavior Changes

- Normal exercise loading now uses `DatabaseExerciseProvider`.
- JSON fixtures remain the authoritative import and validation source.
- `php artisan exercises:import` creates or updates catalog entries by
  `external_id` without creating duplicates.
- SQL, calculation, and UML data is stored in dedicated one-to-one detail
  tables.
- Opening an exercise stores only its reusable catalog ID in the session and no
  longer inserts an exercise row.
- The daily cleanup no longer deletes reusable exercises; only temporary SQL
  databases remain scheduled for cleanup.
- Existing exercise and category tables were preserved as `legacy_exercises`
  and `legacy_categories`.
- The local MySQL database now contains 39 catalog exercises while retaining all
  139 historical exercise rows in the legacy table.

### Testing

- `php artisan test`: passed, 68 tests and 483 assertions.
- `php artisan exercises:validate`: passed for all 39 fixtures with SQL and
  PlantUML checks enabled.
- `php artisan exercises:import`: passed twice; the second run updated 39 rows
  and created no duplicates.
- `php artisan view:cache`: passed.
- Laravel Pint: passed for the changed catalog files.

### Follow-up Notes

- Legacy rows were intentionally not converted because most do not contain
  complete reusable fixture data. Review `legacy_exercises` and
  `legacy_categories` manually before deciding whether to archive or remove
  them.
- Removing a fixture from JSON does not automatically delete or archive its
  existing catalog row.

## 2026-06-13 - Improve learner-facing SQL error handling

### Summary

Refactored learner SQL execution to return structured success and error data.
Added a controlled error display that preserves realistic database messages
while removing framework traces, filesystem paths, credentials, and connection
details.

### Changed Files

- `README.md`
- `AGENTS.md`
- `agents.md`
- `app/Http/Controllers/SqlExerciseController.php`
- `app/Services/Exercises/ExerciseCollectionValidator.php`
- `app/Services/QueryHandler.php`
- `resources/views/it/sql-exercise/index.blade.php`
- `resources/views/it/sql-exercise/partials/query-result-table.blade.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `tests/Unit/JsonExerciseProviderTest.php`
- `tests/Unit/QueryHandlerTest.php`
- `docs/progress.md`

### Behavior Changes

- Successful SQL queries return structured columns, rows, and an optional
  informational message.
- Failed SQL queries return a user-facing explanation, a sanitized database
  exception message, and a context-specific learning hint.
- SQL errors appear directly below the learner input and all error content is
  escaped by Blade.
- Submitted SQL remains in the textarea after an error.
- Stack traces, local paths, credentials, connection details, and Laravel debug
  output are not displayed.
- The expected solution query and result table are shown only after a successful
  learner query.

### Testing

- `php artisan test`: passed, 61 tests and 445 assertions.
- `php artisan exercises:validate`: passed for 39 exercises with SQL and
  PlantUML checks enabled.
- `php artisan view:cache`: passed.
- Laravel Pint: passed for all changed PHP files.

### Follow-up Notes

- None.

## 2026-06-13 - Harden SQL execution and add fixture validation command

### Summary

Hardened learner SQL execution with configurable resource limits and additional
query restrictions. Added an `exercises:validate` Artisan command for validating
all JSON collections and their runtime behavior before deployment.

### Changed Files

- `.env`
- `.env.example`
- `README.md`
- `agents.md`
- `app/Console/Commands/ValidateExercises.php`
- `app/Contracts/ExerciseProvider.php`
- `app/Services/Exercises/ExerciseCollectionValidator.php`
- `app/Services/Exercises/ExerciseValidationReport.php`
- `app/Services/Exercises/JsonExerciseProvider.php`
- `app/Services/QueryHandler.php`
- `bootstrap/app.php`
- `config/exercises.php`
- `tests/Feature/ValidateExercisesCommandTest.php`
- `tests/Unit/QueryHandlerTest.php`
- `docs/progress.md`

### Behavior Changes

- Learner SQL results are limited to a configurable number of rows.
- MariaDB and MySQL learner queries receive a configurable execution deadline.
- Delay functions, file access, locking clauses, system schemas, executable
  database comments, and server or session variables are rejected.
- SQL exceptions are logged but replaced with a generic learner-facing message.
- `php artisan exercises:validate` checks fixture directories, required fields,
  minimum collection sizes, unique IDs, calculation topic coverage, safe and
  executable SQL solutions, and PlantUML rendering.
- The command returns a failing exit code when a collection is invalid and
  supports explicit flags for skipping local service integration checks.

### Testing

- `php artisan test`: passed, 59 tests and 415 assertions.
- `php artisan exercises:validate`: passed for 39 exercises with MariaDB and
  PlantUML checks enabled.
- `php artisan test --filter="QueryHandlerTest|ValidateExercisesCommandTest"`:
  passed, 13 tests and 38 assertions.
- MariaDB `SET STATEMENT max_statement_time` runtime-user probe: passed.

### Follow-up Notes

- None.

## 2026-06-13 - Align project documentation with JSON exercise sources

### Summary

Updated the project README and agent instructions to describe the current
JSON-only exercise architecture instead of the retired gateway workflow.

### Changed Files

- `README.md`
- `agents.md`
- `docs/progress.md`

### Behavior Changes

- Development instructions no longer mention a Python gateway, Ollama, or the
  historical gateway directory.
- Agent guidance now requires the shared exercise provider, validated JSON
  fixtures, supported types and difficulties, and no live-generation fallback.

### Testing

- Documentation reference scan for gateway and Ollama terms: passed.

### Follow-up Notes

- None.

## 2026-06-13 - Replace live exercise generation with JSON sources

### Summary

Introduced a reusable exercise provider abstraction and migrated SQL, UML, and
calculation exercises to validated local JSON collections. Removed the Laravel
AI gateway clients, live response provider, SQL generation retry service, and
legacy AI fixture path from the normal application.

### Changed Files

- `.env`
- `.env.example`
- `README.md`
- `app/Contracts/ExerciseProvider.php`
- `app/Exceptions/ExerciseSourceException.php`
- `app/Services/Exercises/JsonExerciseProvider.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/SqlExerciseController.php`
- `app/Http/Controllers/UmlExerciseController.php`
- `app/Http/Controllers/CalculationExerciseController.php`
- `config/exercises.php`
- `config/filesystems.php`
- `config/services.php`
- `resources/exercises/sql/{easy,medium,hard}/exercises.json`
- `resources/exercises/uml/{easy,medium,hard}/exercises.json`
- `resources/exercises/calculation/{easy,medium,hard}/exercises.json`
- `resources/views/it/sql-exercise/index.blade.php`
- `resources/views/it/sql-exercise/select-difficulty.blade.php`
- `resources/views/it/uml-exercise/index.blade.php`
- `resources/views/it/calculation-exercises/index.blade.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `tests/Unit/JsonExerciseProviderTest.php`
- Removed `app/Services/AI/*`, `app/Services/SqlExerciseGenerator.php`,
  `resources/ai-fixtures/*`, and their obsolete unit tests.
- `docs/progress.md`

### Behavior Changes

- `EXERCISE_SOURCE=json` is now the default and only configured exercise source.
- SQL, UML, and calculation exercises load randomly from
  `resources/exercises/{type}/{difficulty}/*.json`.
- Every exercise type has multiple easy, medium, and hard fixtures.
- Every existing calculation topic has a fixture at each difficulty.
- SQL fixtures still create isolated temporary databases through `setup_sql`,
  and learner queries still use the existing SELECT-only execution flow.
- UML tasks now come from JSON while learner diagrams still render through
  PlantUML.
- Calculation exercises retain topic selection, numeric checking, solution
  steps, and explanations.
- Missing directories, empty directories, invalid JSON, missing fields, and
  unmatched criteria produce clear source errors without a live fallback.
- Normal exercise loading performs no HTTP request and requires no Python or
  Ollama process.
- The README now documents Laravel-only startup and the JSON fixture structure.

### Testing

- `php artisan test`: passed, 49 tests and 385 assertions.
- `php artisan test --filter="JsonExerciseProviderTest|ItExerciseFlowTest"`:
  passed, 21 tests and 316 assertions.
- `php artisan view:cache`: passed.
- Targeted `vendor/bin/pint --test` for all changed PHP files: passed.
- Repository-wide `vendor/bin/pint --test`: still reports pre-existing style and
  line-ending issues in unrelated files.
- JSON parsing check for all nine exercise collection files: passed.

### Follow-up Notes

- The historical `ai-gateway/` directory remains in the repository but is not
  referenced or required by the Laravel exercise flow. It can be archived or
  removed in a separate cleanup task.

## 2026-05-21 - Preserve submitted calculation answer

### Summary

Kept the learner's submitted calculation answer visible after checking the solution.

### Changed Files

- `app/Http/Controllers/CalculationExerciseController.php`
- `resources/views/it/calculation-exercises/index.blade.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `docs/progress.md`

### Behavior Changes

- After clicking "Lösung prüfen", the submitted answer remains in the answer input.
- Learners can compare their own answer directly with the expected result and sample solution.

### Testing

- `php artisan test --filter=ItExerciseFlowTest`: passed.

### Follow-up Notes

- None.

## 2026-05-21 - Increase Laravel execution timeout

### Summary

Increased the Laravel request execution timeout to support slower local AI generation requests.

### Changed Files

- `.env`
- `.env.example`
- `app/Providers/AppServiceProvider.php`
- `config/app.php`
- `docs/progress.md`

### Behavior Changes

- Laravel now reads `APP_MAX_EXECUTION_TIME` and applies it during application bootstrap.
- Local requests can run for up to 180 seconds instead of being stopped by PHP after 60 seconds.

### Testing

- `php artisan config:clear`: passed.
- `php artisan config:show app`: confirmed `max_execution_time` is `180`.
- `php artisan test`: passed.

### Follow-up Notes

- If Apache/FastCGI has a separate timeout below 180 seconds, that server-level value may also need to be increased outside the project.

## 2026-05-21 - Render SQL learner and expected result tables

### Summary

Improved SQL exercise submission rendering so learner query output and expected solution output are displayed after a submitted SELECT query.

### Changed Files

- `app/Http/Controllers/SqlExerciseController.php`
- `app/Services/QueryHandler.php`
- `resources/views/it/sql-exercise/index.blade.php`
- `resources/views/it/sql-exercise/partials/query-result-table.blade.php`
- `tests/Feature/ItExerciseFlowTest.php`
- `tests/Unit/QueryHandlerTest.php`
- `docs/progress.md`

### Behavior Changes

- Submitted SQL queries now render under a dedicated "Deine Ausgabe" section.
- The stored sample solution query is executed safely through the same SELECT-only query handler and rendered under "Erwartete Ausgabe".
- SELECT queries that return no rows now keep their column headers, allowing the result table shape to remain visible.

### Testing

- `php artisan test --filter=QueryHandlerTest`: passed.
- `php artisan test --filter=ItExerciseFlowTest`: passed.

### Follow-up Notes

- None.

## 2026-05-21 - Stabilize AI gateway environment loading

### Summary

Updated the AI gateway settings so it loads environment variables from the Laravel project `.env` and then allows `ai-gateway/.env` to override them.

### Changed Files

- `ai-gateway/app/core/settings.py`
- `ai-gateway/tests/test_settings.py`
- `docs/progress.md`

### Behavior Changes

- The gateway now resolves `OLLAMA_MODEL` consistently regardless of whether it is started from the Laravel root or the `ai-gateway` directory.
- Local gateway startup now uses the existing project-level `OLLAMA_MODEL=deepseek-r1:32b` when `ai-gateway/.env` is empty, avoiding the previous fallback to the missing `deepseek-r1:7b` model.
- Extra Laravel `.env` keys are ignored by the Python settings loader.

### Testing

- `ai-gateway\.venv\Scripts\python.exe -m unittest discover -s ai-gateway\tests`: failed from the Laravel root because the gateway package was not on `PYTHONPATH`.
- `ai-gateway\.venv\Scripts\python.exe -m unittest discover -s tests` from `ai-gateway`: passed.
- `ai-gateway\.venv\Scripts\python.exe -m compileall app` from `ai-gateway`: passed.
- Manual POST to `http://127.0.0.1:8001/generate/sql`: passed with `deepseek-r1:32b`.
- Manual POST to `http://127.0.0.1:8001/generate/calculation`: passed with `deepseek-r1:32b`.

### Follow-up Notes

- None.

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
