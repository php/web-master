FROM php:8.0-cli

RUN apt-get update && apt-get install -y \
        unzip \
    && docker-php-source extract \
    && php -r 'file_put_contents("mysql.zip", file_get_contents("https://github.com/php/pecl-database-mysql/archive/refs/heads/master.zip"));' \
    && unzip -q mysql.zip \
    && cd pecl-database-mysql-master \
    && phpize \
    && ./configure \
    && make -j$(nproc) install \
    && docker-php-ext-enable mysql \
    && docker-php-ext-install pdo_mysql \
    && docker-php-source delete