FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libssl-dev libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Apache sert le dossier /public (les sources ne sont pas exposées)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Réécriture d'URL (.htaccess), pas de listing de dossiers, versions masquées
COPY docker/php/apache.conf /etc/apache2/conf-available/app.conf
RUN a2enconf app && echo "expose_php=Off" > /usr/local/etc/php/conf.d/security.ini

# Fuseau horaire de l'application (dates de prestation, horodatage des commandes)
RUN echo "date.timezone=Europe/Paris" > /usr/local/etc/php/conf.d/timezone.ini
