# AGRI-SED

## Database setup

For a fresh install, import [`database_setup.sql`](database_setup.sql) into
MySQL. It creates the `agrised` database and all tables, including the review,
appeal, report, attachment, comment, and submission-pause features. Do not run
the migration files on a database created from this setup script; those files
are retained for upgrading older installations.

The PHP app currently connects to MySQL using the settings in
[`system1/db.php`](system1/db.php). Update those settings to match the host's
database credentials before deployment, and configure PHP to persist uploaded
files in `system1/uploads/`.
