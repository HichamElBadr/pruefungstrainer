# AGENTS.md

## Project Overview

This project is a Laravel-based web application for an AI-supported exam trainer for IT-related training and study contexts.

The application provides browser-based exercises such as SQL exercises, UML exercises and calculation-style tasks. Exercises and sample solutions are generated through a locally running AI service.

The project is intended as a maintainable prototype and foundation for future extensions.

## Tech Stack

- Laravel
- PHP
- Blade templates
- MySQL
- Local AI gateway / Ollama integration
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
- Do not use external AI APIs.
- Keep AI processing local unless explicitly requested otherwise.
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

## AI Gateway and Ollama Rules

- The Laravel application may call a local AI gateway or local Ollama service.
- Keep AI-related HTTP calls encapsulated in service classes.
- Use clear timeouts and error handling for AI requests.
- Validate and sanitize AI responses before using them.
- If the AI returns JSON, parse it defensively and handle invalid JSON gracefully.
- Do not assume that AI output is always valid, safe or complete.
- Keep prompts explicit and structured.
- Prefer English developer/system prompts while keeping user-facing exercise text in German when appropriate.

## SQL Exercise Rules

- Treat user-submitted SQL as potentially unsafe.
- Keep SQL execution isolated from the main application database.
- Do not allow destructive SQL commands unless explicitly required and safely isolated.
- Prefer allowing only safe query types such as `SELECT` for learner input.
- Return understandable error messages for invalid SQL.
- Do not expose raw database errors unnecessarily to users.
- Keep temporary exercise databases clearly separated from the main application database.

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
## 2026-05-18 – Improve AI gateway error handling

### Summary

Improved handling for unavailable AI gateway responses.

### Changed Files

- `app/Services/AiGatewayService.php`
- `app/Http/Controllers/SqlExerciseController.php`
- `resources/views/sql/index.blade.php`

### Behavior Changes

- Users now see a clear error message if the AI gateway is unavailable.
- Raw cURL exceptions are no longer shown in the browser.

### Testing

- Manual test with gateway running: passed.
- Manual test with gateway stopped: passed.
- `php artisan test`: not run.

### Follow-up Notes

- A future improvement could add a health check endpoint for the AI gateway.