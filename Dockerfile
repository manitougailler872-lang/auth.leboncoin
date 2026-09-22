
FROM php:8.3-apache

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install intl zip opcache \
    && rm -rf /var/lib/apt/lists/*

# Activer la réécriture des URL
RUN a2enmod rewrite

RUN sed -i 's/Listen 80/Listen 10000/' \
    /etc/apache2/ports.conf



# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configurer Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Définir le dossier de travail
WORKDIR /var/www/html

# Copier les fichiers du projet
COPY . .

# Installer les dépendances Symfony
ENV APP_ENV=prod
ENV APP_DEBUG=0

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

RUN php bin/console importmap:install \
    && php bin/console asset-map:compile

# Préparer les dossiers nécessaires
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 775 var


# Configurer Apache
RUN printf '%s\n' \
    '<VirtualHost *:10000>' \
    'DocumentRoot /var/www/html/public' \
    '<Directory /var/www/html/public>' \
    'AllowOverride All' \
    'Require all granted' \
    'Options -Indexes +FollowSymLinks' \
    'FallbackResource /index.php' \
    '</Directory>' \
    'ErrorLog /proc/self/fd/2' \
    'CustomLog /proc/self/fd/1 combined' \
    '</VirtualHost>' \
    > /etc/apache2/sites-available/000-default.conf

# Render utilise le port 10000 par défaut
EXPOSE 10000

CMD ["apache2-foreground"]