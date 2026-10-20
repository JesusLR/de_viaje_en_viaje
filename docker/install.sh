#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
command -v docker >/dev/null || { echo 'Instala Docker Engine y Docker Compose antes de continuar.' >&2; exit 1; }
docker compose version >/dev/null
command -v openssl >/dev/null || { echo 'Se requiere openssl para generar las claves.' >&2; exit 1; }

if [ ! -f .env.docker ]; then
    umask 077
    cp .env.docker.example .env.docker
    sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" .env.docker
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$(openssl rand -hex 24)|" .env.docker
    sed -i "s|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=$(openssl rand -hex 24)|" .env.docker
    echo 'Se creó .env.docker con claves únicas.'
    echo 'Edita APP_URL, APP_BIND, APP_PORT y la configuración HTTPS; después ejecuta este instalador otra vez.'
    exit 0
fi

docker compose --env-file .env.docker config --quiet
docker compose --env-file .env.docker up -d --build --wait --wait-timeout 300
echo 'Aplicación iniciada. Crea el administrador con:'
echo 'docker compose --env-file .env.docker exec --user www-data app php artisan agencia:crear-administrador'
