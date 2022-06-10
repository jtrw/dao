FROM brdnlsrg/baseimage:php-light as backend

# pdo_pgsql
RUN apk add --no-cache postgresql-dev
RUN docker-php-ext-install pdo_pgsql
RUN docker-php-ext-enable pdo_pgsql

WORKDIR /app

#USER www-data