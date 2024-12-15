#!/bin/bash

# Wait for the database to be ready
DB_HOST="${DB_HOST:-mysql_db}"
DB_PORT="${DB_PORT:-3306}"
timeout=60

echo "Waiting for database connection at $DB_HOST:$DB_PORT..."
while ! mysqladmin ping -h "$DB_HOST" --silent -P "$DB_PORT"; do
  sleep 1
  timeout=$((timeout - 1))
  if [ $timeout -le 0 ]; then
    echo "Database connection timed out. Exiting."
    exit 1
  fi
done

echo "Database is ready!"

# Run Laravel commands
echo "Running Laravel setup commands..."
php artisan key:generate || exit 1
php artisan config:cache || exit 1
php artisan route:cache || exit 1
php artisan view:cache || exit 1
php artisan migrate --force || exit 1

if [ "${RUN_SEED:-false}" = "true" ]; then
  echo "Seeding database..."
  php artisan db:seed --force || exit 1
fi

# Start Apache
echo "Starting Apache..."
exec apache2-foreground
