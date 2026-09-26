# Task Manager API

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![Laravel](https://img.shields.io/badge/Laravel-13-red)
![PHP](https://img.shields.io/badge/PHP-8.3-777bb4)

A backend API for a task manager personal platform, built with Laravel 13. This project was built as a hands-on exercise to relearn backend fundamentals after a year away from active development — focused on getting the core architecture, security, and business logic right rather than shipping every possible feature at once.

## Tech Stack

- **Framework:** Laravel 13
- **Primary database:** PostgreSQL
- **Logging database:** MongoDB (system errors + business activity audit trail)
- **Queue / Cache:** Redis
- **Authentication:** JWT (`tymon/jwt-auth`)
- **Password hashing:** Argon2id

## Architecture

This project follows a layered architecture rather than putting logic directly in controllers:

```
Route → Middleware → Controller → Request (validation) → Service (business logic)
→ Repository (database queries) → Model → back up through Resource (response shaping)
```

- **Repository + Interface** — isolates database queries behind an interface, so services depend on an abstraction rather than a concrete Eloquent implementation.
- **Service** — owns business logic and orchestrates one or more repositories/other services. Controllers stay thin.
- **Custom Exceptions** — business-rule violations (e.g. a room being unavailable, a booking that can't be cancelled) are thrown as specific exception classes, each with its own HTTP status code via a `render()` method.

## Key Features

- JWT authentication with refresh tokens, email verification, and password reset (Mailtrap-tested)
- Structured error logging to MongoDB, with a generic error message returned to clients (no internal details ever leak in API responses)
- Argon2id password hashing with automatic rehash-on-login for any legacy hashes

## Prerequisites

- PHP 8.3+
- Composer
- PostgreSQL
- MongoDB
- Redis
- WSL (if developing on Windows)

## Installation

```bash
git clone <this-repo-url>
cd task_manager_personal
make setup-packages
make install
```

Copy `.env.example` to `.env` (done automatically by `make install`) and fill in:

```env
DB_DATABASE=hotel_booking
DB_USERNAME=your_pg_user
DB_PASSWORD=your_pg_password

MONGODB_URI=mongodb://127.0.0.1:27017
MONGODB_DATABASE=hotel_booking_logs

MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxxxxxxx

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
```

Then run migrations:

```bash
make fresh-m
```

## Running the app

You need **three** processes running simultaneously during development:

```bash
make run-s   # HTTP server
make run-q   # Queue worker (required for emails and webhook side-effects)
```

## API Documentation

See the Postman collection: https://documenter.getpostman.com/view/33217412/2sBYB4Jm1p

## Testing

Automated tests are not yet part of this project (see Roadmap). All testing so far has been done manually via Postman and pest (test/feature).

## Version History

- **1.0.0** — Initial release: full CRUD for task-manager-persona, JWT auth with email verification and password reset, booking with race-condition-safe auto room assignment, reschedule, cancellation, Midtrans payment integration, review system, admin user management, and MongoDB-based error/activity logging.

-- **1.0.1** - update project status logic, when all task is completed the project status will auto update to completed, when add new task meanwhile before all task is completed, the project status will change to in-progress, when there 2 task, 1 task is completed and 1 task is in-progress and delete the task 2 (status in-progress) will change the status project to completed because all task is completed, but if there is no task (empty)—because it hasn't been created yet or was deleted, the status project will be in-progress, also add test on file TaskTest for test the logic
