FROM php:8.2-apache

# Copy all blog files into the web server directory
COPY . /var/www/html/

# Open standard HTTP port
EXPOSE 80
