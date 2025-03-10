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

# Create a script to pass environment variables to Apache
RUN echo '#!/bin/bash\n\
echo "SetEnv RENDER ${RENDER}"\n\
echo "SetEnv DEBUG ${DEBUG}"\n\
echo "SetEnv DATABASE_URL ${DATABASE_URL}"\n\
echo "SetEnv DB_HOST ${DB_HOST}"\n\
echo "SetEnv DB_PORT ${DB_PORT}"\n\
echo "SetEnv DB_NAME ${DB_NAME}"\n\
echo "SetEnv DB_USER ${DB_USER}"\n\
echo "SetEnv DB_PASSWORD ${DB_PASSWORD}"\n\
' > /usr/local/bin/generate-apache-env && \
    chmod +x /usr/local/bin/generate-apache-env

# Configure Apache to use environment variables
RUN echo 'PassEnv RENDER DEBUG DATABASE_URL DB_HOST DB_PORT DB_NAME DB_USER DB_PASSWORD\n\
<Directory /var/www/html>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/environment.conf && \
    a2enconf environment

# Copy application files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Create a custom entrypoint script
RUN echo '#!/bin/bash\n\
# Print environment variables for debugging\n\
echo "Environment variables:"\n\
echo "RENDER: ${RENDER}"\n\
echo "DATABASE_URL: ${DATABASE_URL}"\n\
echo "DB_HOST: ${DB_HOST}"\n\
echo "DB_PORT: ${DB_PORT}"\n\
echo "DB_NAME: ${DB_NAME}"\n\
echo "DB_USER: ${DB_USER}"\n\
\n\
# Start Apache\n\
apache2-foreground\n\
' > /usr/local/bin/docker-entrypoint.sh && \
    chmod +x /usr/local/bin/docker-entrypoint.sh

# Start Apache in foreground
CMD ["/usr/local/bin/docker-entrypoint.sh"] 