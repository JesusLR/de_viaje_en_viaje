# HTTPS en el servidor

Configuración opcional con Caddy: obtiene y renueva el certificado y redirige HTTP a HTTPS. No ejecuta migraciones ni seeders. Conserva los volúmenes de MySQL y archivos.

## Preparación

- Subir `compose.https.yaml` y `docker/Caddyfile` al servidor junto a `compose.yaml`.
- El dominio `de-viaje-en-viaje.com` debe apuntar a la IP pública del servidor. Si existe un registro AAAA, también debe apuntar al servidor correcto.
- Permitir conexiones entrantes TCP 80 y 443 en el firewall del servidor y del proveedor.
- Los puertos 80 y 443 deben estar disponibles para el proxy; el contenedor app libera el 80 al recrearse.
- Requiere Docker Compose 2.24.4 o posterior (`sudo docker compose version`). La subred `172.29.240.0/29` debe estar libre; si se cambia, cambiar también la IP del proxy y TRUSTED_PROXIES.
- Para usar otro dominio, agregar `SITE_DOMAIN=otro-dominio.com` en `.env.docker` (solo el nombre, sin protocolo ni ruta).

## Activar y probar

Desde `/de_viaje_en_viaje`:

```bash
sudo docker compose --env-file .env.docker -f compose.yaml -f compose.https.yaml config --quiet
sudo docker compose --env-file .env.docker -f compose.yaml -f compose.https.yaml up -d --no-deps app proxy
sudo docker compose --env-file .env.docker -f compose.yaml -f compose.https.yaml exec app php artisan config:clear
sudo docker compose --env-file .env.docker -f compose.yaml -f compose.https.yaml logs --tail=80 proxy
curl -I http://de-viaje-en-viaje.com
curl -I https://de-viaje-en-viaje.com
```

La emisión puede tardar unos minutos. HTTP debe redirigir a HTTPS y HTTPS debe responder con certificado válido. Probar también iniciar sesión y enviar una cotización desde el celular. Si el certificado falla, revisar DNS, registros AAAA, puertos y logs del proxy.

La configuración HTTPS sobrescribe APP_URL, SESSION_SECURE_COOKIE y TRUSTED_PROXIES del contenedor; no hace falta modificar esos valores en `.env.docker`. La app solo recibe tráfico del proxy por la red Docker y confía en su IP específica.

**En los siguientes despliegues, incluir siempre ambos `-f`**. Para reconstruir la app:

```bash
sudo docker compose --env-file .env.docker -f compose.yaml -f compose.https.yaml up -d --build --no-deps app
```

No usar `down -v`: elimina volúmenes persistentes, incluidos datos y certificados.

## Volver temporalmente a HTTP

Con `.env.docker` configurado con APP_BIND=0.0.0.0, APP_PORT=80, APP_URL=http://de-viaje-en-viaje.com y SESSION_SECURE_COOKIE=false:

```bash
sudo docker compose --env-file .env.docker -f compose.yaml -f compose.https.yaml stop proxy
sudo docker compose --env-file .env.docker -f compose.yaml up -d --no-deps --force-recreate app
sudo docker compose --env-file .env.docker exec app php artisan config:clear
```

Referencias: https://caddyserver.com/docs/automatic-https y https://docs.docker.com/reference/compose-file/merge/
