# HTTPS con Docker y Caddy

Requiere Compose 2.24.4 o posterior, dominio apuntando al servidor y puertos TCP 80/443 permitidos y disponibles para Caddy. SITE_DOMAIN en .env.docker permite cambiar el dominio (predeterminado de-viaje-en-viaje.com). Si existe un registro DNS AAAA, debe apuntar también al servidor correcto.

La aplicación y el proxy tienen IP fija diferente en la red HTTPS para evitar que Docker asigne a la aplicación la IP del proxy. La subred 172.29.240.0/29 debe estar disponible. Si se modifica, actualizar ambas IP y TRUSTED_PROXIES.

## Activar o reintentar tras la configuración anterior

Subir los tres archivos de esta configuración. Desde /de_viaje_en_viaje:

```bash
sudo docker compose --env-file .env.docker -f compose.yaml -f compose.https.yaml config --quiet
sudo docker compose --env-file .env.docker -f compose.yaml -f compose.https.yaml rm -s -f proxy
sudo docker compose --env-file .env.docker -f compose.yaml -f compose.https.yaml up -d --no-deps --force-recreate app
sudo docker compose --env-file .env.docker -f compose.yaml -f compose.https.yaml up -d --no-deps proxy
sudo docker compose --env-file .env.docker -f compose.yaml -f compose.https.yaml exec app php artisan config:clear
sudo docker compose --env-file .env.docker -f compose.yaml -f compose.https.yaml logs --tail=80 proxy
curl -I https://de-viaje-en-viaje.com
```

La eliminación del contenedor proxy conserva los certificados en el volumen. Estos comandos no ejecutan migraciones ni seeders ni recrean MySQL. La recreación de app interrumpe brevemente el servicio.

Si persiste un conflicto, revisar `sudo ss -ltnp '( sport = :80 or sport = :443 )'` y `sudo docker network inspect de-viaje_https_proxy`; no detener otros servicios sin identificar primero qué está usando la dirección o puerto.

La configuración sobrescribe APP_URL, SESSION_SECURE_COOKIE y TRUSTED_PROXIES del contenedor. Mantener ambos argumentos -f en futuros despliegues HTTPS. No usar down -v: elimina volúmenes persistentes.

## Volver a HTTP

En .env.docker: APP_BIND=0.0.0.0, APP_PORT=80, APP_URL=http://de-viaje-en-viaje.com, SESSION_SECURE_COOKIE=false y TRUSTED_PROXIES vacío.

```bash
sudo docker stop de-viaje-proxy-1
sudo docker compose --env-file .env.docker -f compose.yaml up -d --no-deps --force-recreate app
sudo docker compose --env-file .env.docker -f compose.yaml exec app php artisan config:clear
```

Referencias: https://caddyserver.com/docs/automatic-https y https://docs.docker.com/reference/compose-file/merge/
