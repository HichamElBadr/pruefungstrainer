# Pruefungstrainer

Pruefungstrainer is a Laravel-based learning application for IT exam preparation.
Version-controlled JSON fixtures are imported into a reusable database catalog,
so normal exercise loading is fast, deterministic, and self-contained.

## Features

- SQL exercises with isolated temporary MySQL databases
- Safe learner execution restricted to single `SELECT` queries
- UML exercises with PlantUML rendering
- Calculation exercises with automatic numeric result checking
- Easy, medium, and hard difficulty levels
- Random catalog exercise selection through a shared provider abstraction
- Defensive JSON validation with user-facing source errors

## Exercise Architecture

Laravel resolves `App\Contracts\ExerciseProvider` to
`App\Services\Exercises\DatabaseExerciseProvider` during normal operation.
`JsonExerciseProvider` remains responsible for validating fixture files during
imports and fixture validation.

The configured source is:

```env
EXERCISE_SOURCE=database
```

Fixtures are stored by type and difficulty:

```text
resources/exercises/
  sql/{easy,medium,hard}/*.json
  uml/{easy,medium,hard}/*.json
  calculation/{easy,medium,hard}/*.json
```

Each JSON file may contain one exercise object or a list of exercise objects.
Import fixtures idempotently with:

```bash
php artisan exercises:import
```

`external_id` prevents duplicates and causes changed fixture content to update
the existing catalog entry. Common content is stored in `exercises`; SQL,
calculation, and UML fields are stored in one-to-one detail tables.

SQL fixtures contain `setup_sql` and `solution`. Each learner submission creates
a fresh temporary database, applies the catalog exercise setup, runs the
restricted query, and drops the database before the request finishes. SQL
database names and selected SQL exercises are not stored in session state, so
parallel browser tabs remain independent. The exercise preview uses the same
request-scoped cleanup pattern when it reads the sample tables.

SQL fixture tables, columns, tasks, sample data, and solutions use German
domain names such as `kunden`, `bestellungen`, `produkte`, `preis`, and
`menge`. The exercise page provides a next button that advances in stable
`external_id` order within the same SQL category and difficulty and rotates
back to the first matching exercise.

Fixture setup accepts only unqualified `CREATE TABLE` and
`INSERT INTO ... VALUES` statements. Setup runs through the dedicated runtime
account with temporary `CREATE` and `INSERT` permissions; those permissions are
revoked before learner SQL is executed. The provisioning account never executes
fixture SQL.

Learner results are row-limited. Failed queries show a sanitized database
exception message and a learning hint without exposing stack traces, file
paths, credentials, or connection details. Unsafe delay, file, locking,
system-schema, and variable access constructs are rejected.

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
php artisan exercises:import
npm run build
```

Configure the main application database and the isolated SQL exercise accounts
in `.env`. The default exercise source should remain:

```env
EXERCISE_SOURCE=database
SQL_EXERCISE_QUERY_TIMEOUT_MS=3000
SQL_EXERCISE_RESULT_ROW_LIMIT=200
```

`SQL_EXERCISE_RUNTIME_USERNAME` must be a dedicated account and must differ
from `SQL_EXERCISE_ADMIN_USERNAME`. The admin account creates and drops sandbox
schemas and grants or revokes temporary sandbox permissions. The runtime
account starts without global privileges and is used for setup and learner
queries. Do not configure the application database account as the runtime
account.

For a disposable local database, the catalog can also be created and imported
through the seeders:

```bash
php artisan migrate:fresh --seed
```

The reusable-catalog migration is non-destructive for existing installations.
It renames the previous AI-era tables to `legacy_exercises` and
`legacy_categories`. Review and archive or remove those tables manually only
after confirming that their historical data is no longer needed.

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

Temporary SQL databases are normally removed immediately in the request
cleanup path. The scheduled `sql-exercises:cleanup` command is only a fallback
for databases left behind by process termination, database outages, or failed
cleanup calls. It recognizes both the current
`sql_exercise_<timestamp>_<random>` format and the legacy
`sql_exercise_<timestamp>` format. It also revokes orphaned runtime grants for
temporary databases that no longer exist.

Run a one-off fallback cleanup manually with:

```bash
php artisan sql-exercises:cleanup
```

For local development, keep Laravel's scheduler running in a separate terminal:

```bash
php artisan schedule:work
```

On a Linux server, add the standard Laravel scheduler cron entry. Replace the
project path and PHP binary when necessary:

```cron
* * * * * cd /var/www/pruefungstrainer && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

On Windows, create a Task Scheduler task that runs the following command every
minute from the project directory:

```powershell
php artisan schedule:run
```

Laravel schedules the fallback SQL cleanup hourly. The configured
`SQL_EXERCISE_MAX_AGE_SECONDS` determines how old a matching temporary database
must be before it is removed.

## Testing

```bash
php artisan test
```

Validate every exercise collection against the configured local services:

```bash
php artisan exercises:validate
```

This checks JSON structure, minimum collection size, unique IDs, calculation
topic coverage, SQL setup and solution execution in temporary MariaDB
databases, and PlantUML rendering. Structural validation can skip those local
service checks when necessary:

```bash
php artisan exercises:validate --skip-sql-execution --skip-uml-render
```

The test suite verifies fixture coverage, JSON validation errors, SQL fixture
execution, idempotent catalog imports, controller flows, PlantUML integration,
German SQL identifiers, cyclic next-exercise navigation, and that normal
exercise loading sends no HTTP requests.

## Security Notes

- Learner SQL is treated as untrusted input.
- Only one `SELECT` statement is accepted per submission.
- Learner queries have an execution timeout and result-row limit.
- Delay, file, locking, system schema, and variable access is blocked.
- Each SQL request uses a fresh temporary database separate from the application
  database and attempts cleanup in a `finally` path.
- SQL sandbox names and selected SQL exercises are not kept in session state.
- JSON fixtures are validated before use.
- PlantUML paths are configured through environment variables.
- SQL errors may show the sanitized database exception message, but never stack
  traces, file paths, credentials, or connection details.

## Repository Structure

```text
app/Contracts/             Exercise provider contract
app/Services/Exercises/    Database provider, JSON validator, and fixture importer
app/Models/                Catalog and type-specific detail models
resources/exercises/       Local exercise collections
resources/views/           Blade templates
database/                  Migrations and seeders
tests/                     Unit and feature tests
docs/                      Project progress documentation
```

## License

This project is licensed under the GNU General Public License v3.0.
