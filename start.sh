#!/bin/bash
set -e

# Clear any broken caches first
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Fix storage permissions
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Run migrations
php artisan migrate --force

# Now cache after everything is ready
php artisan config:cache
php artisan route:cache

apache2-foreground