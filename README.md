# Pruefungstrainer

Pruefungstrainer is a Laravel-based learning application for IT exam preparation.
Exercises are loaded from version-controlled JSON files so normal operation is
fast, deterministic, and self-contained.

## Features

- SQL exercises with isolated temporary MySQL databases
- Safe learner execution restricted to single `SELECT` queries
- UML exercises with PlantUML rendering
- Calculation exercises with automatic numeric result checking
- Easy, medium, and hard difficulty levels
- Random local exercise selection through a shared provider abstraction
- Defensive JSON validation with user-facing source errors

## Exercise Architecture

Laravel resolves `App\Contracts\ExerciseProvider` to
`App\Services\Exercises\JsonExerciseProvider`.

The configured source is:

```env
EXERCISE_SOURCE=json
```

Fixtures are stored by type and difficulty:

```text
resources/exercises/
  sql/{easy,medium,hard}/*.json
  uml/{easy,medium,hard}/*.json
  calculation/{easy,medium,hard}/*.json
```

Each JSON file may contain one exercise object or a list of exercise objects.
The provider validates all files in the selected directory before choosing a
random matching exercise.

SQL fixtures contain `setup_sql` and `solution`. The setup SQL is applied to the
existing temporary exercise database, while learner input continues to use the
restricted SQL execution path.

UML fixtures contain a task and `solution_plantuml`. Learner input is still
rendered locally with Java and PlantUML.

Calculation fixtures contain `expected_result`, `unit`, `solution_steps`, and an
explanation. Existing topic selection and numeric checking remain in Laravel.

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js and npm
- MySQL or MariaDB
- Java Runtime Environment and a PlantUML JAR for UML rendering
- XAMPP or a comparable local PHP/MySQL environment

## Installation

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

Configure the main application database and the isolated SQL exercise accounts
in `.env`. The default exercise source should remain:

```env
EXERCISE_SOURCE=json
```

PlantUML paths are environment-specific:

```env
PLANTUML_JAVA_PATH=java
PLANTUML_JAR_PATH=C:\path\to\plantuml.jar
```

Do not commit local credentials or machine-specific paths.

## Local Development

Start Laravel:

```bash
php artisan serve
```

Start Vite in a second terminal when working on frontend assets:

```bash
npm run dev
```

No separate exercise generation service needs to be started.

## Testing

```bash
php artisan test
```

The test suite verifies fixture coverage, JSON validation errors, SQL fixture
execution, controller flows, PlantUML integration, and that normal exercise
loading sends no HTTP requests.

## Security Notes

- Learner SQL is treated as untrusted input.
- Only one `SELECT` statement is accepted per submission.
- SQL exercises use temporary databases separate from the application database.
- JSON fixtures are validated before use.
- PlantUML paths are configured through environment variables.
- Raw internal database errors should not be exposed to learners.

## Repository Structure

```text
app/Contracts/             Exercise provider contract
app/Services/Exercises/    JSON exercise provider
resources/exercises/       Local exercise collections
resources/views/           Blade templates
database/                  Migrations and seeders
tests/                     Unit and feature tests
docs/                      Project progress documentation
```

## License

This project is licensed under the GNU General Public License v3.0.
