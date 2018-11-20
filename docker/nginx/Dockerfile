FROM wodby/nginx:1.15-5.0.3

ENV NGINX_VHOST_PRESET=wioa

COPY --chown=wodby:wodby docker/nginx/vhosts.conf.tmpl /etc/gotpl/presets/
COPY --from=wioa-state-plan_php:latest --chown=nginx:nginx /var/www/html /var/www/html
