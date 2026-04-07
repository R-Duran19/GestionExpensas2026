# 🐳 Guía de Deployment con Docker - GestionExpensas2026

## 📋 Requisitos Previos

- Docker y Docker Compose instalados en el VPS
- La red `geopricer_365soft_app-network` ya creada (✅ verificada)
- Nginx Proxy Manager funcionando en el VPS

## 🚀 Deployment Paso a Paso

### 1. Preparar Archivos

```bash
# Copiar el repositorio al VPS
git clone <tu-repositorio> /ruta/al/proyecto
cd /ruta/al/proyecto

# Crear archivo .env para Docker
cp .env.docker.example .env.docker
```

### 2. Configurar Variables de Entorno

Edita `.env.docker` según tus necesidades:

```bash
# Configuración básica
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Base de datos
DB_DATABASE=expensas_db
DB_USERNAME=expensas_user
DB_PASSWORD=tu_contraseña_segura
DB_ROOT_PASSWORD=tu_contraseña_root_segura

# Puerto de acceso (opcional, por defecto 8001)
APP_PORT=8001
DB_PORT_HOST=3307

# Ejecutar migraciones al inicio (cuidado en producción)
RUN_MIGRATIONS=false
RUN_SEEDERS=false
```

### 3. Construir y Levantar Contenedores

```bash
# Construir imágenes
docker-compose build

# Levantar contenedores
docker-compose up -d

# Ver logs
docker-compose logs -f app
```

### 4. Configurar Nginx Proxy Manager

1. **Ingresar al panel de NPM:** `http://tu-vps-ip:81`
2. **Crear nuevo Proxy Host:**
   - **Domain Names:** `tu-dominio.com`
   - **Scheme:** `http`
   - **Forward Hostname/IP:** `gestion_expensas_app`
   - **Forward Port:** `80`
   - **Enable SSL:** ✅ (Let's Encrypt)
   - **Force SSL:** ✅

### 5. Ejecutar Migraciones Manualmente (Primera vez)

```bash
# Entrar al contenedor
docker-compose exec app bash

# Ejecutar migraciones
php artisan migrate --force

# Ejecutar seeders (si es necesario)
php artisan db:seed --force

# Crear storage link
php artisan storage:link

# Limpiar caché
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Salir del contenedor
exit
```

## 🔧 Gestión de Contenedores

```bash
# Ver estado de los contenedores
docker-compose ps

# Reiniciar contenedores
docker-compose restart

# Ver logs en tiempo real
docker-compose logs -f app
docker-compose logs -f mysql

# Actualizar la aplicación
git pull
docker-compose build
docker-compose up -d

# Entrar al contenedor
docker-compose exec app bash
```

## 🗄️ Gestión de Base de Datos

### Backup

```bash
# Backup de la base de datos
docker-compose exec mysql mysqldump -u expensas_user -p expensas_db > backup_$(date +%Y%m%d).sql

# Backup desde el host
docker exec gestion_expensas_mysql mysqldump -u expensas_user -p expensas_db > backup.sql
```

### Restaurar

```bash
# Restaurar base de datos
docker-compose exec -T mysql mysql -u expensas_user -p expensas_db < backup.sql
```

### Acceso directo a MySQL

```bash
# Conectar a MySQL desde el host
mysql -h 127.0.0.1 -P 3307 -u expensas_user -p expensas_db

# O desde dentro del contenedor
docker-compose exec mysql mysql -u expensas_user -p expensas_db
```

## 📊 Monitoreo

```bash
# Ver uso de recursos
docker stats gestion_expensas_app gestion_expensas_mysql

# Ver logs de Laravel
docker-compose exec app tail -f storage/logs/laravel.log

# Ver espacio en disco
docker system df
```

## 🔐 Seguridad

- La base de datos MySQL NO está expuesta públicamente, solo accesible internamente
- El contenedor `app` expone el puerto 8001 (opcional) y se conecta a la red compartida
- Solo Nginx Proxy Manager maneja las conexiones HTTPS públicas
- Los archivos `.env` deben estar protegidos y no incluirse en git

## 🆘 Solución de Problemas

### Error de conexión a MySQL

```bash
# Verificar que MySQL esté corriendo
docker-compose ps mysql

# Ver logs de MySQL
docker-compose logs mysql

# Verificar la red
docker network inspect geopricer_365soft_app-network
```

### Error de permisos en storage

```bash
# Dentro del contenedor app
docker-compose exec app bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 755 storage bootstrap/cache
```

### Assets no cargan correctamente

```bash
# Dentro del contenedor app
docker-compose exec app bash
php artisan storage:link
php artisan view:clear
```

## 🔄 Actualización de la Aplicación

```bash
# Actualizar código
git pull origin main

# Reconstruir contenedor si es necesario
docker-compose build

# Reiniciar contenedores
docker-compose down
docker-compose up -d

# Ejecutar migraciones si hay nuevas
docker-compose exec app php artisan migrate --force
```

## 📝 Notas Importantes

- **Backup antes de actualizar:** Siempre hace backup de la base de datos antes de cambios grandes
- **Storage Link:** El enlace simbólico `public/storage` debe existir para que funcionen las subidas de archivos
- **SSL:** Configurar siempre SSL en Nginx Proxy Manager para producción
- **Caché:** Después de cambios en configuración, limpiar la caché de Laravel