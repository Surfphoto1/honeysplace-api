# Use official PHP image with Apache
FROM php:8.2-apache

# Enable logs directory
RUN mkdir /var/www/html/logs && chmod -R 777 /var/www/html/logs

# Copy app into container
COPY . /var/www/html/

# Expose Apache's default port
EXPOSE 80
