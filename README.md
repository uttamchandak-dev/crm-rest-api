# CRM REST API

A REST API for a lightweight CRM, built with **CodeIgniter 4** (PHP) and **MySQL**. Supports user auth, customers, and notes, with role-based access control: agents only see and manage the customers they own, while admins have full visibility.

## Features

- Token-based authentication (register/login/logout), tokens stored as SHA-256 hashes with expiry
- Role-based access control (`admin` vs `agent`) enforced in a request filter and in controllers
- Customers CRUD with per-owner data isolation for agents
- Notes on customers (append-only activity log)
- Input validation via CodeIgniter's model validation rules
- Auto-migrating on container start — no manual setup step

## Tech Stack

PHP 8.2 · CodeIgniter 4 · MySQL 8 · Docker

## Getting Started

```bash
docker compose up -d --build
```

This builds the PHP app image, starts MySQL, runs all migrations, and starts the CodeIgniter dev server on `http://localhost:8080`.

To run outside Docker, you'll need PHP 8.1+ with the `intl`, `mysqli`, and `pdo_mysql` extensions, plus Composer:

```bash
composer install
cp .env.example .env   # adjust database.default.* to point at your MySQL instance
php spark migrate --all
php spark serve
```

## Project Structure

```
app/
  Config/Routes.php       route definitions
  Config/Filters.php      registers the tokenAuth filter
  Controllers/            AuthController, CustomerController, NoteController
  Filters/                TokenAuthFilter (bearer token auth)
  Libraries/AuthContext.php  holds the authenticated user for the request
  Models/                 UserModel, AuthTokenModel, CustomerModel, NoteModel
  Database/Migrations/    schema for users, auth_tokens, customers, notes
```

## API Reference

| Method | Endpoint                       | Auth        | Description                              |
|--------|----------------------------------|-------------|--------------------------------------------|
| GET    | `/health`                        | No          | Health check                               |
| POST   | `/api/auth/register`             | No          | Create a user (`role`: `admin` or `agent`) |
| POST   | `/api/auth/login`                | No          | Authenticate, get a bearer token           |
| POST   | `/api/auth/logout`                | Yes         | Revoke the current token                   |
| GET    | `/api/auth/me`                    | Yes         | Get the current user                       |
| GET    | `/api/customers`                  | Yes         | List customers (own only, unless admin)    |
| POST   | `/api/customers`                  | Yes         | Create a customer (owned by caller)        |
| GET    | `/api/customers/:id`               | Yes         | Get one customer (own only, unless admin)  |
| PUT    | `/api/customers/:id`               | Yes         | Update a customer (own only, unless admin) |
| DELETE | `/api/customers/:id`               | Yes (admin) | Delete a customer                          |
| GET    | `/api/customers/:id/notes`         | Yes         | List notes on a customer                   |
| POST   | `/api/customers/:id/notes`         | Yes         | Add a note to a customer                   |

## Example: full flow

```bash
BASE=http://localhost:8080/api

# Register an agent
curl -s -X POST $BASE/auth/register -H "Content-Type: application/json" \
  -d '{"email":"agent@crm.test","password":"agentpass1","fullName":"Alex Agent"}'
# => { "token": "...", "user": { ... } }

TOKEN="<paste token from above>"

# Create a customer (owned by this agent)
curl -s -X POST $BASE/customers -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"name":"Globex Inc","email":"contact@globex.test","status":"lead"}'

# List customers — only ones this agent owns
curl -s $BASE/customers -H "Authorization: Bearer $TOKEN"

# Add a note
curl -s -X POST $BASE/customers/1/notes -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"body":"Initial call went well, follow up next week."}'
```

Register a second user with `"role":"admin"` to see cross-agent visibility: an admin's `GET /api/customers` returns every customer, while an agent's only returns their own — a 404 (not a 403) is returned if an agent requests a customer they don't own, to avoid leaking which IDs exist.

## Design Notes

- Tokens are opaque random strings; only their SHA-256 hash is stored, so a database leak doesn't expose usable tokens (similar in spirit to Laravel Sanctum).
- `TokenAuthFilter` resolves the caller once per request and stores it in `AuthContext`, a simple static holder, so controllers don't need to re-resolve or pass the user around manually.
- RBAC is enforced at the query/controller level (`WHERE owner_id = ?` for agents) rather than via row-level DB permissions, which keeps the authorization logic visible and testable in PHP rather than split across the DB layer.
