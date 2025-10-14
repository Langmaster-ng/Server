# ---- base runtime ----
FROM php:8.3-fpm-alpine3.20

# System deps
RUN apk add --no-cache \
    git curl bash unzip zip \
    nginx supervisor libpq-dev \
    gettext # for envsubst

# PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli opcache pgsql pdo_pgsql \
 && docker-php-ext-enable pgsql pdo_pgsql

# Composer (official static binary)
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

WORKDIR /var/www/html

# --- dependency layer (better cache) ---
# Copy only composer files first, then install
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-progress

# Now copy the rest of the app
COPY . .

# (Re)optimize autoload after full code is present
RUN composer dump-autoload --optimize

# Permissions (adjust if your app writes to storage/)
RUN mkdir -p /var/www/html/storage /run/nginx /var/log/nginx /var/cache/nginx /var/lib/nginx \
 && chown -R www-data:www-data /var/www/html

# ===================== NGINX CONFIG (template) =====================
# We keep a template that uses ${PORT}; we render it at container start with envsubst
RUN mkdir -p /etc/nginx/templates
# NOTE: If your public dir is different, change /var/www/html/public below
COPY <<'NGINXCONF' /etc/nginx/templates/app.conf.tpl
server {
    listen       ${PORT};
    server_name  _;

    root   /var/www/html/public;
    index  index.php index.html;

    access_log /dev/stdout;
    error_log  /dev/stderr;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include        fastcgi_params;
        fastcgi_pass   127.0.0.1:9000; # php-fpm default
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 300;
    }

    location ~* \.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?)$ {
        expires 7d;
        access_log off;
        try_files $uri =404;
    }
}
NGINXCONF

# ===================== SUPERVISOR =====================
RUN mkdir -p /etc/supervisor/conf.d /var/log/supervisor
COPY <<'SUPERVISOR' /etc/supervisor/conf.d/supervisord.conf
[supervisord]
nodaemon=true
user=root
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid

[program:php-fpm]
command=php-fpm -F
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0

[program:nginx]
command=/bin/sh -c "envsubst '\$PORT' < /etc/nginx/templates/app.conf.tpl > /etc/nginx/http.d/default.conf && nginx -g 'daemon off;'"
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
SUPERVISOR

# Optional healthcheck (simple TCP connect to $PORT)
HEALTHCHECK --interval=30s --timeout=3s --retries=3 CMD nc -z 127.0.0.1 "${PORT:-8080}" || exit 1

# No hard EXPOSE; Railway sets PORT. (OK to keep EXPOSE 8080 for local dev if you want)
# EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
