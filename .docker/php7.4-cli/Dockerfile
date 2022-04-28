ARG PHP_VER

FROM php:${PHP_VER}-cli-alpine

RUN apk update
RUN apk add --no-cache build-base
RUN apk add --no-cache autoconf
RUN apk add --no-cache automake

# php settings
ADD conf.ini /usr/local/etc/php/conf.d/
RUN chmod +r /usr/local/etc/php/conf.d/conf.ini

# curl for cmd
RUN apk add --no-cache curl

# php extensions

# mcrypt
RUN apk add --no-cache libmcrypt-dev

# bcmath
RUN docker-php-ext-install bcmath
RUN docker-php-ext-enable bcmath

# sockets
RUN docker-php-ext-install sockets
RUN docker-php-ext-enable sockets

# intl
RUN apk add --no-cache icu-dev
RUN docker-php-ext-install intl
RUN docker-php-ext-enable intl

# opcache
RUN docker-php-ext-install opcache
RUN docker-php-ext-enable opcache

# amqp
#RUN apk add --no-cache rabbitmq-c-dev
#RUN pecl install amqp
#RUN docker-php-ext-enable amqp

# redis
#RUN pecl install redis
#RUN docker-php-ext-enable redis

# zip
#RUN apk add --no-cache libzip-dev
#RUN docker-php-ext-install zip
#RUN docker-php-ext-enable zip

# mysql
RUN docker-php-ext-install mysqli
RUN docker-php-ext-enable mysqli

# pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-enable pdo_mysql

# pdo_pgsql
#RUN apk add --no-cache postgresql-dev
#RUN docker-php-ext-install pdo_pgsql
#RUN docker-php-ext-enable pdo_pgsql

# pcntl
RUN docker-php-ext-install pcntl
RUN docker-php-ext-enable pcntl
