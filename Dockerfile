FROM php:8.2-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Activer mod_rewrite Apache
RUN a2enmod rewrite

# Configurer le document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Installer msmtp pour relayer les emails vers MailHog
RUN apt-get update && apt-get install -y msmtp && rm -rf /var/lib/apt/lists/*

# Configurer PHP
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN echo "upload_max_filesize = 10M" >> "$PHP_INI_DIR/php.ini"
RUN echo "post_max_size = 12M" >> "$PHP_INI_DIR/php.ini"
RUN echo "sendmail_path = /usr/bin/msmtp -t" >> "$PHP_INI_DIR/php.ini"

# Configurer msmtp pour MailHog
RUN echo "account default\nhost mailhog\nport 1025\nfrom no-reply@marketplace.local\nauth off\ntls off" > /etc/msmtprc

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
