# Build our runtime image.
FROM wodby/drupal-php:7.1

USER wodby
ENV PHP_FPM_CLEAR_ENV=no
WORKDIR /var/www/html

COPY --chown=wodby:wodby . /var/www/html

RUN composer install
