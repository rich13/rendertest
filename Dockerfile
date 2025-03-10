FROM php:8.2-apache

# Install PostgreSQL PDO extension and other dependencies
RUN apt-get update --allow-releaseinfo-change && \
    apt-get install -y libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure PHP to use environment variables
RUN { \
    echo 'variables_order = "EGPCS"'; \
    echo 'display_errors = On'; \
    echo 'error_reporting = E_ALL'; \
} > /usr/local/etc/php/conf.d/docker-php-env.ini

# Copy application files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"] 