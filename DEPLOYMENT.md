# Guía de Despliegue en Servidor - GestionExpensas2026

## 🚀 Despliegue Rápido (Recomendado)

### Opción 1: Usar el script automatizado

```bash
# Clonar el repositorio
git clone <tu-repositorio> GestionExpensas2026
cd GestionExpensas2026

# Dar permisos de ejecución al script
chmod +x deploy-server.sh

# Ejecutar el script de despliegue
./deploy-server.sh
```

Este script automáticamente:
1. Limpia contenedores y volúmenes anteriores
2. Crea el archivo `.env` con configuraciones correctas
3. Levanta los contenedores Docker
4. Ejecuta las migraciones
5. Optimiza la aplicación para producción

### Opción 2: Despliegue Manual Paso a Paso

```bash
# 1. Clonar el repositorio
git clone <tu-repositorio> GestionExpensas2026
cd GestionExpensas2026

# 2. Crear el archivo .env desde la plantilla
cp .env.production .env

# 3. Verificar que el archivo .env tenga estas líneas importantes:
cat .env | grep -E "DB_USERNAME|DB_PASSWORD"

# Debe mostrar:
# DB_USERNAME=AdminUserExpe2026
# DB_PASSWORD=AdminExpe8119

# 4. Limpiar cualquier instalación anterior
docker compose down -v
docker volume rm gestion_expensas_mysql_data

# 5. Levantar los contenedores
docker compose up -d

# 6. Esperar a que MySQL inicie (aproximadamente 15 segundos)
sleep 15

# 7. Verificar que los contenedores estén corriendo
docker compose ps

# 8. Limpiar cachés de Laravel
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear

# 9. Ejecutar las migraciones
docker compose exec app php artisan migrate --force

# 10. Optimizar para producción
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

## 🔧 Solución de Problemas

### Error: "SQLSTATE[HY000] [1045] Access denied for user"

**Causa**: Las credenciales en `.env` no coinciden con las configuradas en Docker.

**Solución**:
```bash
# Asegúrate que tu .env tenga exactamente estos valores:
DB_USERNAME=AdminUserExpe2026
DB_PASSWORD=AdminExpe8119
DB_ROOT_PASSWORD=AdminExpe8119

# Luego reinicia todo:
docker compose down -v
docker compose up -d
```

### Error: "Authentication plugin 'caching_sha2_password' cannot be loaded"

**Causa**: MySQL 8.0 usa un plugin de autenticación incompatible por defecto.

**Solución**:
```bash
# El comando de MySQL en docker-compose.yml ya soluciona esto con:
# --default-authentication-plugin=mysql_native_password

# Si persiste el problema, elimina el volumen y reinicia:
docker compose down -v
docker compose up -d
```

## 📊 Monitoreo y Logs

```bash
# Ver todos los logs
docker compose logs -f

# Ver solo logs de MySQL
docker compose logs -f mysql

# Ver solo logs de la aplicación
docker compose logs -f app

# Verificar conexión a la base de datos
docker compose exec app php artisan db:show

# Entrar en el contenedor de la aplicación
docker compose exec app bash

# Entrar en el contenedor de MySQL
docker compose exec mysql bash
```

## 🔐 Configuración de Producción

El archivo `.env.production` incluye:

- ✅ `APP_DEBUG=false` - No mostrar errores en pantalla
- ✅ `MYSQL_ATTR_SSL_CA=null` - Desactiva SSL para MySQL
- ✅ Credenciales seguras preconfiguradas
- ✅ Optimizado para nginx-proxy-manager

## 🌐 Acceso a la Aplicación

Después del despliegue exitoso:

- URL principal: `https://expensas.acmanalytics.online`
- La aplicación se conecta automáticamente a nginx-proxy-manager
- Los contenedores se reinician automáticamente si fallan

## 🛠️ Mantenimiento

```bash
# Actualizar la aplicación
git pull origin main
docker compose exec app composer install --no-dev
docker compose exec app php artisan migrate --force
docker compose restart app

# Hacer backup de la base de datos
docker compose exec mysql mysqldump -u root -pAdminExpe8119 expensas_db > backup_$(date +%Y%m%d).sql

# Restaurar backup
docker compose exec -T mysql mysql -u root -pAdminExpe8119 expensas_db < backup_20260407.sql
```

## ⚠️ Errores Comunes y Sus Soluciones

| Error | Causa | Solución |
|-------|-------|----------|
| Access denied for user | Credenciales incorrectas | Verifica `.env` y reinicia contenedores |
| SSL connection error | SSL activado en MySQL | Verifica `--ssl=0` en docker-compose.yml |
| Authentication plugin error | Plugin incompatible | Verifica `--default-authentication-plugin=mysql_native_password` en docker-compose.yml |
| Permission denied | Permisos de archivos | Ejecuta `chmod -R 775 storage bootstrap/cache` |
| Port already in use | Puerto ocupado | Cambia `APP_PORT` en `.env` o detén servicios que usen el puerto |

## 📞 Soporte

Si encuentras problemas no documentados:

1. Verifica los logs: `docker compose logs -f`
2. Verifica que los contenedores estén corriendo: `docker compose ps`
3. Verifica la configuración de red: `docker network ls`
4. Revisa este archivo actualizado en el repositorio