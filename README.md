# LibraryDesk

LibraryDesk is a lightweight library management application built with PHP and MySQL. It provides separate admin, librarian, and member experiences for managing the catalogue, user accounts, and book borrows and returns.

## Features

- **Admin:** manage user accounts, books, and circulation.
- **Librarian:** manage books and process borrows and returns.
- **Member:** view a personal overview, browse and search the catalogue, check active borrows and due dates, review borrow history, and update profile details.
- **Catalogue:** filter books by category and availability, with paginated results.
- **Responsive interface:** shared desktop layout, mobile hamburger navigation, and role-aware mobile quick actions.
- **Application safeguards:** role checks on protected routes, prepared database queries, password hashing, CSRF-protected forms, server-side validation, and escaped template output.

Book borrowing is recorded by library staff; members can browse the catalogue but cannot directly create or modify borrow records.

## Requirements

- PHP 8.0 or later
- MySQL with the PDO MySQL PHP extension
- Apache with `mod_rewrite` enabled when using Laragon/Apache

## Local setup

### 1. Place the project

For Laragon, place this directory under `D:\laragon\www` and start Apache and MySQL.

### 2. Configure the database connection

Copy the example environment file to `.env` in the project root:

```powershell
Copy-Item .env.example .env
```

Edit `.env` with the credentials for your local MySQL server:

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=library_management
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

The local `.env` file is excluded from Git. Do not commit database passwords or other secrets.

### 3. Run the one-time installer

Start Laragon or PHP's built-in server, then open `http://localhost/librarymanagement/setup.php` (or `http://localhost:8000/setup.php`). The installer creates the configured database if needed, installs the tables from [`database/schema.sql`](database/schema.sql), and creates the first admin account. It is restricted to localhost and will not run if user accounts already exist.

After setup completes, remove `setup.php` from the web root. A local `.setup.lock` file also prevents rerunning the installer and is excluded from Git.

### 4. Open the application

With Laragon, open the project at `http://localhost/librarymanagement/`.

Alternatively, from the project directory, start PHP's built-in development server:

```powershell
php -S localhost:8000 index.php
```

Then open `http://localhost:8000/`. If PowerShell cannot find `php`, use Laragon's Terminal or invoke `php.exe` from your Laragon PHP installation.

## Accounts and roles

Register an account from the sign-up page. **The first account registered in an empty database is assigned the admin role; subsequent self-registrations create member accounts.** Admins can create user accounts and assign roles from User Management.

| Role | Permissions |
| --- | --- |
| Admin | Manage users, books, and borrow/return operations |
| Librarian | Manage books and process borrow/return operations |
| Member | Browse books, view only their own borrow records, and edit their name or password |

Protected pages enforce permissions on the server; hiding a navigation link is not the only access control.

## Demo member data

To populate a local development database with a test member, books, and example borrow records, run [`database/seed_member_demo.sql`](database/seed_member_demo.sql) after importing the schema:

```text
Email:    member.demo@librarydesk.test
Password: MemberDemo123!
```

The fixture includes active, due-soon, overdue, and returned borrow records. It is for local testing only; do not use the demo account or fixture in a production database.

## Project structure

```text
app/
  Controllers/   Request handling, validation, and authorization
  Core/          Router, base controller, database connection, helpers
  Models/        Prepared database queries and data operations
  Views/         PHP templates and shared layout
config/          Environment-backed application configuration
database/        Database schema and local demo fixture
public/assets/   CSS and JavaScript
routes/          Application route definitions
index.php        Front controller
```

## Security and deployment notes

- Keep `.env` private; `.env.example` contains only configuration placeholders.
- The initial admin assignment is intended for first-time local setup. Disable public registration or replace it with an invitation/administrator-managed workflow before exposing the application to an untrusted network.
- Use a dedicated MySQL account with only the permissions the application needs rather than a privileged root account in production.
- Deploy behind HTTPS and configure production-grade session and server settings before public use.
