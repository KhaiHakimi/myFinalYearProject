#!/bin/bash
set -e

echo "Starting deployment process..."

# Enter maintenance mode (if the app is already down, this won't fail)
(php artisan down) || true

# Pull the latest code from the main branch
git fetch origin main
git reset --hard origin/main

# Install PHP dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Clear existing cache and optimize new cache
php artisan clear-compiled
php artisan optimize
php artisan view:cache
php artisan event:cache

# Install NPM dependencies and build Vue components for production
npm ci
npm run build

# Run any pending database migrations
php artisan migrate --force

# Restart queue workers (if running via Supervisor)
php artisan queue:restart

# Exit maintenance mode
php artisan up

echo "Deployment complete! 🎉"
