# AGENTS.md

## Project Overview

This project is a Laravel-based web application for an exam trainer for IT-related training and study contexts.

The application provides browser-based SQL, UML and calculation exercises. Exercises and sample solutions are loaded from validated, version-controlled JSON fixtures.

The project is intended as a maintainable prototype and foundation for future extensions.

## Tech Stack

- Laravel
- PHP
- Blade templates
- MySQL
- Local JSON exercise fixtures
- PlantUML for UML diagram generation
- Git and GitHub for version control
- XAMPP/local development environment on Windows

## General Rules

- Keep the project simple and maintainable.
- Do not introduce unrelated features.
- Do not rewrite large parts of the application unless explicitly requested.
- Do not change the architecture without explaining why.
- Preserve existing behavior unless the task explicitly asks for a behavior change.
- Prefer small, focused changes over large refactorings.
- Do not add live exercise generation or external AI APIs unless explicitly requested.
- Keep the normal exercise flow independent from external or local generation services.
- Do not add complex role or permission systems unless explicitly requested.
- Do not add mobile optimization or full redesigns unless explicitly requested.
- Do not introduce unnecessary frontend frameworks.
- Do not commit secrets, credentials, API keys or local machine-specific paths.

## Laravel Conventions

- Keep controllers as thin as reasonably possible.
- Move reusable business logic into services.
- Use Laravel validation for user input.
- Use Eloquent models and relationships where appropriate.
- Use migrations for database schema changes.
- Use configuration files and `.env` variables for environment-specific values.
- Do not hardcode absolute local Windows paths if they can be configured.
- Do not use `dd()`, `dump()` or temporary debug output in final code.
- Handle errors with user-friendly messages where possible.

## JSON Exercise Source Rules

- Use `App\Contracts\ExerciseProvider` for exercise loading.
- Keep JSON source logic encapsulated in `App\Services\Exercises\JsonExerciseProvider`.
- Store fixtures under `resources/exercises/{type}/{difficulty}/*.json`.
- Supported exercise types are `sql`, `uml` and `calculation`.
- Supported difficulties are `easy`, `medium` and `hard`.
- Validate JSON syntax, required fields, exercise type and difficulty before use.
- Provide understandable errors for missing directories, empty directories, invalid JSON and missing fields.
- Do not add live generation as an automatic fallback.
- Keep user-facing exercise text in German where appropriate.
- Add or update fixture coverage tests when changing exercise schemas or collections.

## SQL Exercise Rules

- Treat user-submitted SQL as potentially unsafe.
- Keep SQL execution isolated from the main application database.
- Do not allow destructive SQL commands unless explicitly required and safely isolated.
- Prefer allowing only safe query types such as `SELECT` for learner input.
- Return understandable error messages for invalid SQL.
- Do not expose raw database errors unnecessarily to users.
- Keep temporary exercise databases clearly separated from the main application database.
- SQL fixtures must provide valid `setup_sql` and one safe solution query.

## PlantUML Rules

- Keep PlantUML execution encapsulated in a dedicated service.
- Make Java and PlantUML paths configurable through `.env` or config files.
- Do not hardcode local absolute paths in final code.
- Handle missing Java, missing PlantUML JAR files and generation errors clearly.

## Documentation Rules

For every completed non-trivial task, update `docs/progress.md` in English.

Do not document the user prompt itself. Document the implemented result.

Add new entries at the top of the file.

Each progress entry should include:

- Date
- Short title
- Summary
- Changed files
- Behavior changes
- Testing notes
- Follow-up notes, if relevant

Skip progress entries for:

- Typo fixes
- Pure formatting changes
- Commit message help
- Temporary debugging notes
- Changes that do not affect code, behavior, configuration, tests or documentation

### Example Progress Entry

```md
## 2026-05-18 - Improve JSON exercise validation

### Summary

Improved validation and error handling for local JSON exercises.

### Changed Files

- `app/Services/Exercises/JsonExerciseProvider.php`
- `resources/exercises/sql/easy/exercises.json`
- `tests/Unit/JsonExerciseProviderTest.php`

### Behavior Changes

- Invalid JSON fixtures now produce a clear source error.
- Missing required SQL fields are rejected before database setup.

### Testing

- `php artisan test --filter=JsonExerciseProviderTest`: passed.
- `php artisan test`: passed.

### Follow-up Notes

- None.
```
