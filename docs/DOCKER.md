# Instalar De Viaje con Docker

La instalación incluye PHP 8.3 con Apache, MySQL 8.4 y los recursos de Vite
compilados. El servidor solo necesita Docker Engine, el complemento Docker
Compose v2 (con soporte de `up --wait`), Bash y OpenSSL. No necesita Laragon,
PHP, Composer ni Node instalados en el servidor. Se necesita Internet para
construir las imágenes y para los recursos externos que usa la interfaz
(Bootstrap, Font Awesome, Select2 y Google Fonts).

## Primera instalación en Linux

1. Copia el proyecto al servidor y entra en su carpeta. Puedes usar el paquete
   `de-viaje-docker.tar.gz`, que no contiene credenciales ni datos locales:

   ```bash
   mkdir -p de-viaje
   tar -xzf de-viaje-docker.tar.gz -C de-viaje
   cd de-viaje
   bash docker/install.sh
   ```

2. La primera ejecución crea `.env.docker` con una clave de aplicación y dos
   contraseñas aleatorias. Edita ese archivo antes de continuar:

   ```bash
   nano .env.docker
   ```

   Para probar por IP, usa `APP_URL=http://IP_DEL_SERVIDOR:8080`,
   `APP_BIND=0.0.0.0`, `APP_PORT=8080` y `SESSION_SECURE_COOKIE=false`.

   Para producción detrás de un proxy HTTPS, usa
   `APP_URL=https://viajes.tudominio.com`, `APP_BIND=127.0.0.1` y
   `SESSION_SECURE_COOKIE=true`. El proxy debe enviar `Host`,
   `X-Forwarded-Proto` y `X-Forwarded-For`. En `TRUSTED_PROXIES` coloca la IP
   desde la que el contenedor recibe al proxy (puede ser la puerta de enlace
   de la red Docker), o sus rangos CIDR, separados por comas. No uses `*`.
   Si el proxy corre en otro contenedor, conecta ambos a una red compartida
   y dirige el tráfico al puerto 80 de `app`; adapta Compose a esa red.
   Esta configuración no instala certificados ni configura el proxy externo.

3. Ejecuta otra vez el instalador y crea el administrador:

   ```bash
   bash docker/install.sh
   docker compose --env-file .env.docker exec --user www-data app php artisan agencia:crear-administrador
   ```

   El comando solicita nombre, correo y contraseña oculta de al menos 12
   caracteres. No modifica usuarios existentes. No ejecutes `db:seed` para
   instalar: el seeder de desarrollo contiene una cuenta de demostración.

4. Abre el dominio o la IP configurados. La landing y `/catalogo` son públicos;
   la administración se abre en `/login`. Configura logo, carrusel, contactos
   y servicios desde el sistema.

La instalación nueva comienza sin clientes, ventas ni servicios de prueba.
Las migraciones crean las tablas y los roles. El arranque espera a MySQL,
ejecuta migraciones pendientes y prepara las cachés. Si falla, el contenedor
no inicia Apache; consulta los registros.

## Operación y actualizaciones

Usa siempre `--env-file .env.docker` para que Compose y Laravel usen los mismos
valores. La `.env` de Laragon no se copia a la imagen.

```bash
docker compose --env-file .env.docker ps
docker compose --env-file .env.docker logs --tail=100 app db
docker compose --env-file .env.docker stop
docker compose --env-file .env.docker start
```

Para actualizar: respalda primero, reemplaza el código conservando `.env.docker`
y ejecuta `bash docker/install.sh`. El comando reconstruye la imagen y aplica
solo las migraciones pendientes. Cambiar el código local no cambia una imagen
ya construida. Cambios en `.env.docker` también se aplican con el instalador.

Los volúmenes `de-viaje_base_datos` y `de-viaje_archivos` conservan MySQL y todo
`storage/app`: imágenes públicas, logo, carrusel y fotos privadas de perfil.
No cambies el nombre de proyecto de Compose en una instalación existente.
`docker compose down` conserva los volúmenes; **`down -v` los elimina**.
No regeneres `APP_KEY` ni cambies las contraseñas de MySQL en el archivo
esperando que se actualicen las cuentas de una base ya creada.

Las colas usan `sync`; no se requiere un worker para las funciones actuales.
Si se incorporan trabajos en segundo plano, será necesario agregar su worker.
La base no publica el puerto 3306 hacia el servidor.

## Respaldos (desde el servidor Linux)

Detén las escrituras durante el respaldo para mantener la base y archivos
consistentes:

```bash
mkdir -p backups
chmod 700 backups
umask 077
docker compose --env-file .env.docker exec --user www-data app php artisan down
docker compose --env-file .env.docker exec -T db sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysqldump --user="$MYSQL_USER" --single-transaction --no-tablespaces "$MYSQL_DATABASE"' > backups/base.sql
docker compose --env-file .env.docker exec -T app tar -C /var/www/html/storage/app -czf - . > backups/archivos.tar.gz
cp .env.docker backups/env.docker
docker compose --env-file .env.docker exec --user www-data app php artisan up
```

Comprueba que los comandos terminaron correctamente antes de considerar válido
el respaldo. Conserva también una copia protegida fuera del servidor.

## Migrar los datos que ya tienes en Laragon

El paquete de instalación no exporta los datos actuales automáticamente.
Necesitas un respaldo SQL de la base actual, una copia de `storage/app` y
la `APP_KEY` original. Conserva las rutas relativas de los archivos y la misma
clave si quieres mantener los datos cifrados. No subas tu `.env` al repositorio.

Después de iniciar una instalación vacía, ponla en mantenimiento, importa el SQL
y copia los archivos. No crees un administrador nuevo si ya viene en el respaldo:

```bash
docker compose --env-file .env.docker exec --user www-data app php artisan down
docker compose --env-file .env.docker exec -T db sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysql --user="$MYSQL_USER" "$MYSQL_DATABASE"' < respaldo.sql
docker compose --env-file .env.docker cp ./storage-app/. app:/var/www/html/storage/app/
docker compose --env-file .env.docker exec app chown -R www-data:www-data /var/www/html/storage/app
docker compose --env-file .env.docker exec --user www-data app php artisan migrate --force
docker compose --env-file .env.docker exec --user www-data app php artisan up
```

Estos comandos importan en la base configurada y pueden reemplazar datos: úsalos
en la instalación nueva, conservando el respaldo original. Configura antes la
`APP_KEY` original en `.env.docker` y vuelve a ejecutar el instalador.

## Verificación de la entrega

Los scripts y la estructura se validan localmente. La construcción y arranque
reales requieren un equipo con Docker; no están verificados en el equipo
de desarrollo actual porque Docker no está instalado. Al instalar, verifica
`ps`, `/up`, `/catalogo`, el ingreso, la carga de imágenes y los comprobantes PDF.

Referencias: [despliegue Laravel 12](https://laravel.com/docs/12.x/deployment),
[orden de inicio de Compose](https://docs.docker.com/compose/how-tos/startup-order/).
