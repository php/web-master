PHP user management system
==========================

#### Docker

```shell
docker-compose up --build
```

You can reset the data volumes using `docker-compose down -v`.

#### Manual

```shell
git submodule update --init

# Create database and users:
CREATE DATABASE phpmasterdb;
CREATE USER 'nobody'@'localhost';
GRANT ALL PRIVILEGES ON phpmasterdb.* TO 'nobody'@'localhost';

# Create tables
mysql -unobody phpmasterdb < schema.sql

# Create user test:test
INSERT INTO users (username, svnpasswd, cvsaccess) VALUES ('test', '$2y$10$iGHyxmfHI62Xyr3DPf8faOPCvmU1UMVMlhJQ/FqooqgPJ3STMHTyG', 1);

# Run server (must have mysql ext)
php -S localhost:8000 -d include_path="$PWD/include/" -derror_reporting="E_ALL&~E_DEPRECATED" -t public
```
