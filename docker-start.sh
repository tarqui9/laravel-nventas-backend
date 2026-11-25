#!/bin/sh
# Arranque del backend. En la nube, PORT lo asigna el proveedor (Render).
set -e
cd /var/www

# Base de datos en la nube con certificado propio (por ejemplo Aiven):
# si DB_SSL_CA_PEM trae el certificado, se guarda y Laravel lo usa.
if [ -n "$DB_SSL_CA_PEM" ]; then
  printf '%s\n' "$DB_SSL_CA_PEM" > /tmp/db-ca.pem
  export MYSQL_ATTR_SSL_CA=/tmp/db-ca.pem
fi

php artisan config:clear >/dev/null 2>&1 || true

cd public
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"
exec php -S "0.0.0.0:${PORT:-8000}" ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
