#!/bin/bash
set -e

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
while ! mysqladmin ping -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" --silent; do
    echo "Waiting for MySQL connection..."
    sleep 2
done

echo "MySQL is ready!"

# Check if database exists and has tables
RESULT=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema='$DB_NAME';" --batch --skip-column-names 2>/dev/null || echo "0")

if [ "$RESULT" -eq "0" ]; then
    echo "Database is empty or doesn't exist. Initializing..."
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < /var/www/html/inv_system\ \(2\).sql
    echo "Database initialized successfully!"
else
    echo "Database already contains $RESULT tables. Skipping initialization."
fi

echo "Database setup complete!"