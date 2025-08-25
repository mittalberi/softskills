# BMIET Learning Portal (Starter)

**Stack:** PHP 8+, MySQL 8 (or MariaDB), Bootstrap 5, vanilla JS.

## Quick Setup
1. Create DB & user, then import schema and seed:
   ```sql
   SOURCE db/schema.sql;
   SOURCE db/seed.sql;
   ```
2. Edit `includes/config.php` (DB creds, `$portal_url`, emails).
3. Deploy to your subdomain docroot (e.g., `/var/www/learn`).
4. Ensure PHP `pdo_mysql` is enabled.
5. Visit the site and login as admin:  
   - **Email:** `admin@example.com`  
   - **Password:** `Admin@123` (change immediately).

## CSV Import (Instructor)
- Go to `/admin/` → “Upload Questions (CSV)”.  
- Required header:  
  `company_tag,topic,question_type,question_text,option_a,option_b,option_c,option_d,correct_answer,explanation,difficulty`

## Notes
- Mobile-first, responsive UI with Bootstrap 5. Light/Dark toggle included.
- CSRF protection, prepared statements, password hashing out of the box.
- Starter content: 1 course, 1 module, 1 lesson, and a published quiz.

## Setup
- PHP: 8.x, extensions: mysqli, intl, mbstring
- Create DB: softskills (utf8mb4)
- Import: db/schema.sql
- Copy includes/config.sample.php -> includes/config.php and fill env vars

## Structure
- /db
- /includes
- /public (index.php, assets)
- /admin
- /api

