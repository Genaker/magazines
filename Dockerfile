# Dev + PHPUnit: PHP 8.4 FPM with GD, phpredis, and pdo_sqlite.
FROM webdevops/php-dev:8.4

WORKDIR /var/www/html

USER root

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY . .

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
