ARG CI_COMMIT_REF_SLUG=master
ARG CI_SERVER_HOST
ARG CI_PROJECT_PATH
ARG PHP_VER
ARG DOCKER_REGISTRY_PORT

FROM ${CI_SERVER_HOST}${DOCKER_REGISTRY_PORT}/$CI_PROJECT_PATH/php${PHP_VER}-composer:$CI_COMMIT_REF_SLUG

RUN apk add --no-cache gnupg
#RUN pecl install xdebug
#RUN docker-php-ext-enable xdebug
RUN pecl install ast && docker-php-ext-enable ast
RUN pecl install pcov && docker-php-ext-enable pcov

RUN rm -rf /var/cache/apk/* /var/tmp/* /tmp/*

WORKDIR /app

RUN pwd

RUN ls -la

RUN chown -R www-data /app/tests/reports





