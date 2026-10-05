# Library Management System

A PHP 8+ and MySQL starter project organized with a lightweight MVC architecture for Laragon.

## Modules

- User management: member and staff records.
- Book management: catalogue and copy availability.
- Borrow and return: borrow records, due dates, and return status.

## Run locally

1. Place this folder under Laragon's `www` directory and start Apache and MySQL.
2. Import `database/schema.sql` using phpMyAdmin or the MySQL client.
3. Copy `.env.example` to `.env` and set the database connection values there.
4. Open `http://localhost/<folder-name>/`, replacing `<folder-name>` with the
   folder's current name under Laragon's `www` directory. For example, if the
   folder is named `library-management-system`, open
   `http://localhost/library-management-system/`.

The project expects Apache `mod_rewrite` and PHP's PDO MySQL extension to be enabled. The front controller detects its URL subdirectory, so after renaming the folder, use its new name in the URL; the route links update automatically.

## MVC structure

- `app/Controllers`: request handling, input validation, and view selection.
- `app/Models`: prepared database queries and circulation transactions.
- `app/Views`: PHP templates, grouped by module.
- `app/Core`: shared controller, router, and PDO connection.
- `config`: local application and database configuration.
- `database`: schema for users, books, and borrow records.
- `public/assets`: frontend styles and JavaScript.
- `index.php`: front controller and route registration.

The first account registered becomes the administrator; later registrations create member accounts. Administrators manage users, librarians and administrators manage books and circulation, and members have a personal overview, browse, borrow history, and profile pages. Members can browse the catalogue but borrowing is recorded by staff at the desk. Passwords are hashed, forms use session CSRF tokens, and record output is escaped in templates. User and book pages support create, edit, and delete operations. Circulation supports checkout, returns, stock updates, and borrow history.

For local member-interface testing, run `database/seed_member_demo.sql` against the development database. It creates a demo member and sample borrows (currently borrowed, due soon, overdue, and returned). The demo sign-in is `member.demo@librarydesk.test` with password `MemberDemo123!`. Do not use the demo account or fixture in a production database.

The first-account bootstrap is intended for initial local setup. Disable public registration or add an invitation workflow before exposing the app to an untrusted network.