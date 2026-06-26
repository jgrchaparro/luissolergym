# ---------------------------------------------------------------------------
# Dockerfile de PRODUCCIÓN (Render)
# No se usa en local: local sigue corriendo sobre WAMP con APP_ENV=dev.
# ---------------------------------------------------------------------------
FROM php:8.3-apache AS base

ENV DEBIAN_FRONTEND=noninteractive \
    APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

# ---- Dependencias del sistema ---------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        ca-certificates \
        libicu-dev \
        libzip-dev \
        libssl-dev \
        libxml2-dev \
        libonig-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libwebp-dev \
        pkg-config \
        zlib1g-dev \
    && rm -rf /var/lib/apt/lists/*

# ---- Extensiones PHP ------------------------------------------------------
# gd requiere configurarse con las libs de imagen antes de instalarse
# (lo necesita gregwar/captcha-bundle para generar los captchas).
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        intl \
        opcache \
        zip \
        pdo_mysql

# MongoDB (requerido por ext-mongodb ^2.0 en composer.json)
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# ---- Configuración PHP para producción -----------------------------------
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/apache/php.ini $PHP_INI_DIR/conf.d/zz-app.ini

# ---- Apache ---------------------------------------------------------------
RUN a2enmod rewrite headers expires
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# ---- Composer -------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiamos primero solo los archivos necesarios para composer
COPY composer.json composer.lock symfony.lock ./
COPY scripts ./scripts

# Desactivamos explícitamente el bloqueo por alertas de seguridad de Composer
RUN composer config policy.advisories.block false

# Ahora sí ejecutamos el update sin que censure a Symfony 7.3
RUN composer update \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-progress \
        --ignore-platform-reqs

# Ahora sí copiamos el resto del proyecto
# Ahora sí copiamos el resto del proyecto
COPY . .

# ---- Variables de entorno dummies para compilar el contenedor ----
ENV MONGODB_URL=mongodb://localhost:27017 \
    MONGODB_DB=build \
    APP_SECRET=build_dummy_secret \
    APP_DEBUG=1 \
    MAILER_DSN=null://null \
    MAIL_SENDER=build@localhost \
    DEFAULT_URI=http://localhost \
    MOSTRAR_CAPTCHA=0 \
    MOSTRAR_NOTIFICACION_PREGUNTAS_SEGURIDAD=0

# Optimizamos el Autoload diciéndole que ignore los checks estrictos de versión PHP
RUN composer dump-autoload --classmap-authoritative --no-dev --ignore-platform-reqs

# Ejecutamos el post-install ignorando también la verificación de PHP
RUN composer run-script post-install-cmd --no-dev --ignore-platform-reqs

# ---- Permisos y carpetas de caché ----------------------------------------
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var public

# ---- Entrypoint -----------------------------------------------------------
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Render inyecta el puerto en la variable $PORT (por defecto 10000)
ENV PORT=10000
EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
