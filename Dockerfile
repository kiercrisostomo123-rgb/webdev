FROM php:8.2-apache

# Enable SQLite extensions
RUN docker-php-ext-install pdo pdo_sqlite

# Copy project files into Apache web root
COPY . /var/www/html/

# Set proper permissions for SQLite database file
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80