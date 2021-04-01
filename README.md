PHP user management system
==========================

Local setup:

```shell
# TODO: This is supposed to be submodule, but not actually registered as one.
git clone git@github.com:php/web-shared.git shared

# Create database and users:
CREATE DATABASE phpmasterdb;
CREATE USER 'nobody'@'localhost';
GRANT ALL PRIVILEGES ON phpmasterdb.* TO 'nobody'@'localhost';

# Create tables
mysql -unobody phpmasterdb < users.sql

# Create user test:test
INSERT INTO users (username, svnpasswd, cvsaccess) VALUES ('test', 'd45a6c7dfc5b0dbb3567e28900d47f50', 1);

# Run server (must have mysql ext)
php -S localhost:8000 -d include_path="include/" -derror_reporting="E_ALL&~E_DEPRECATED"
```