# Core Webber

A PHP 8.1+ / MySQL starter portal for Core Webber, designed for cPanel hosting.

## Included
- Responsive business website with construction, roads, housing, civil engineering, mining, farming, logistics, equipment, solar, materials, repairs, sourcing and small services.
- Client and partner registration, login and session-based dashboards.
- Project/service enquiry form and enquiry history.
- Admin role can view all enquiries.
- Password hashing with PHP password_hash/password_verify and PDO prepared statements.

## cPanel installation
1. Create a MySQL database and database user in cPanel. Assign the user to the database with required privileges.
2. Open phpMyAdmin and import `database.sql` (if your host does not allow CREATE DATABASE, remove the first two SQL statements and import into the database you created).
3. Upload the repository contents to the domain's document root (usually public_html).
4. Copy `config.example.php` to `config.php`; enter the database name, username and password supplied by cPanel. Keep config.php private and never commit it.
5. Visit the website and create your account. To enable the first administrator, run the commented UPDATE statement in phpMyAdmin with your email.
6. Enable HTTPS/SSL in cPanel before accepting real customer information.

## Important launch notes
This is the first working foundation, not a fully finished production marketplace. Partner business profiles, partner verification, admin status editing, email notifications, password reset, CSRF protection, rate limiting, privacy/terms pages and file uploads should be added before public launch. Do not use real customer data until those controls and HTTPS are configured.
