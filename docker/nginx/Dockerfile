FROM wodby/nginx:1.15-5.0.3

COPY --chown=nginx:nginx docker/nginx/zz-wioa-extras.conf /etc/nginx/conf.d/
COPY --from=wioa-state-plan_php:latest --chown=nginx:nginx /var/www/html /var/www/html
