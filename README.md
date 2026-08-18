# Shared Calendar

A simple shared calendar web app built as a layered PHP monolith with MariaDB persistence and JavaScript-enhanced UI.

## Stack

- PHP 8.3+ (targeting PHP 8.4 style without framework dependencies)
- MariaDB
- Vanilla JavaScript

## Structure

- `/public` - front controller and static assets
- `/src/Application` - application services and app bootstrap
- `/src/Domain` - entities, view periods, repository interfaces, shared exceptions
- `/src/Infrastructure` - HTTP, persistence, security, and logging adapters
- `/templates` - server-rendered views
- `/database/schema.sql` - schema and seed data
- `/tests/run.php` - lightweight service and domain tests

## Features

- Day, week, month, and N-week views
- Add, edit, inspect, and delete events
- Shared calendar roles: owner, editor, viewer
- CSRF protection for writes
- Output escaping in templates
- UTC event storage with calendar timezone conversion
- Transaction-wrapped writes and file logging

## Local setup

1. Create a MariaDB database.
2. Import `/home/runner/work/sand/sand/database/schema.sql`.
3. Configure environment variables:

```bash
export APP_NAME="Shared Calendar"
export APP_TIMEZONE="UTC"
export APP_DEFAULT_VIEW="month"
export APP_DEFAULT_WEEKS="4"
export DB_HOST="127.0.0.1"
export DB_PORT="3306"
export DB_DATABASE="shared_calendar"
export DB_USERNAME="root"
export DB_PASSWORD=""
```

4. Start the built-in server from the repository root:

```bash
php -S 127.0.0.1:8000 -t public
```

5. Open `http://127.0.0.1:8000`.

## Tests

Run:

```bash
composer test
```

## SFTP deployment

Upload the repository contents to your PHP hosting account so that the web root points at `/public`, then:

1. Import `database/schema.sql` into MariaDB.
2. Set the environment variables supported in `config/app.php` and `config/database.php`.
3. Ensure the `storage/logs` directory is writable by PHP.

No container, queue worker, or build pipeline is required.
