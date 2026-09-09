# Computerized Child Abuse Database Management System (PHP / MySQL)

A role-based, audit-logged case-management prototype, implementing the design from
*"Design and Implementation of a Computerized Child Abuse Database Management System"*
(Kazeem, 2026) in PHP + MySQL.

> **This is a teaching/demonstration prototype, not a production-ready system.**
> See "Before you touch a single real case" below before using it with any real names
> or real data.

## Features

- Username/password authentication with salted `password_hash()` credential storage
- Role-based access control (Administrator, Caseworker, Intake/Reporting Officer),
  enforced on the server on every request, not just hidden in the UI
- Child record + linked case registration, with duplicate-child search before creation
- Case lifecycle tracking (Reported → Under Review → Referred → Under Investigation → Closed)
- Referral tracking to external bodies (law enforcement, medical, court, etc.)
- Dated case notes
- Append-only audit trail: every login, view, create, update, and referral action is
  logged with the acting user, action, target record, and timestamp
- Anonymized, aggregate-only statistical reporting for administrators
- CSRF protection on all state-changing forms
- Session idle-timeout (default 30 minutes)

## Requirements

- PHP 8.1+ with the `pdo_mysql` extension
- MySQL 8.0+ (or MariaDB 10.4+)
- A web server (Apache/Nginx) or PHP's built-in server for local development

## Local Setup

```bash
# 1. Clone the repository
git clone <your-repo-url> child-protection-cms
cd child-protection-cms

# 2. Create your local environment file
cp .env.example .env
# then edit .env with your local MySQL credentials

# 3. Create the database (schema.sql applies its own tables)
mysql -u root -p -e "CREATE DATABASE child_protection CHARACTER SET utf8mb4;"

# 4. Apply the schema and create demo accounts
php seed.php

# 5. Start PHP's built-in development server
php -S 127.0.0.1:8000

# 6. Open http://127.0.0.1:8000 in your browser
```

## Demonstration Accounts

| Role           | Username      | Password      |
|----------------|---------------|---------------|
| Administrator  | `admin`       | `Admin@12345` |
| Caseworker     | `caseworker1` | `Case@12345`  |
| Intake Officer | `intake1`     | `Intake@12345`|

**Change every one of these passwords (and remove the demo accounts) before any
deployment that will hold real information.**

## Demonstration Walkthrough

1. Log in as `intake1`, search for a child (no match will be found initially), and
   register a new case. Note the generated case reference number.
2. Log in as `admin`, confirm the account list, observe the new case in "Reported"
   status on the overview page, and assign the case to `caseworker1`.
3. Log in as `caseworker1`, open the newly assigned case, add a case note, update the
   status to "Under Review", and create a referral (the case status automatically
   advances to "Referred").
4. Log back in as `admin` and open the Audit Log: every action from the preceding
   steps appears with the acting username and a timestamp. Open Reports to confirm
   the same activity is reflected only as anonymized aggregate counts.
5. Log in again as `intake1` and open the case directly: the case is visible (because
   `intake1` registered it) but the status/notes/referral controls are hidden, and a
   direct POST to those endpoints returns a logged 403 rather than succeeding —
   demonstrating that the access-control boundary is enforced by the server, not
   merely hidden in the interface.

## Project Structure

```
.
├── admin/              Administrator-only pages (users, audit log, reports, assignment)
├── caseworker/          Caseworker workspace / all-cases listing (admin)
├── case/                Shared case-detail page and its POST action handlers
├── intake/               Intake officer dashboard, child search, case registration
├── includes/            Shared helpers: functions.php (auth/RBAC/audit), header/footer, flash
├── assets/style.css     Application stylesheet
├── config.php           Env loading, PDO connection, session hardening
├── schema.sql           MySQL schema
├── seed.php              CLI script: applies schema + creates demo accounts
├── index.php             Entry point, redirects by role
├── login.php / logout.php
└── error.php             Shared 403/404 page
```

## Deploying (GitHub + a host)

1. Push this repository to GitHub. `.env` is git-ignored — never commit real
   credentials; only `.env.example` should be in the repo.
2. Connect the repo to a host that supports PHP + a managed MySQL database and
   auto-deploys from GitHub, e.g. **Render**, **Railway**, or **DigitalOcean App
   Platform**.
3. On the host, set the environment variables from `.env.example` (`DB_HOST`,
   `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_SECRET`,
   `SESSION_TIMEOUT_MINUTES`) to point at the host's managed MySQL instance.
4. Run `php seed.php` once against the production database (most hosts let you run
   a one-off command/console, or you can temporarily expose a protected setup
   script) to create the schema and initial admin account — then **immediately
   change that admin password**.
5. Confirm the site is served over HTTPS; `config.php` automatically marks session
   cookies `Secure` when `$_SERVER['HTTPS']` is set.

## Before You Touch a Single Real Case

This prototype implements authentication, role-based access control, and audit
logging as core requirements, but it has **not** undergone independent security
auditing, penetration testing, or formal data-protection compliance review. Per the
accompanying thesis (Chapter 1.8, Limitations; Chapter 5.4, Recommendations), a real
deployment handling actual child-protection data would additionally need, at minimum:

- Independent security audit and penetration testing
- Legal / data-protection compliance review for the deploying jurisdiction
- Multi-factor authentication
- Field-level encryption of highly sensitive fields
- A formal, technically enforced data-retention and deletion policy
- Periodic, auditable permission recertification
- Independent, immutable (write-once) audit-log storage
- Staff training and institutional governance policies
- Load testing appropriate to the target agency's caseload
- A documented incident-response plan
- Automated, encrypted, tested backups and disaster recovery

This project is an academic prototype demonstrating the architecture and access-
control approach described in the thesis — not a certified, production-ready system.

## License

Provided for academic/demonstration purposes. Add a license of your choosing before
any public redistribution.
