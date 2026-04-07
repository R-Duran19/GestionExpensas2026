# 🐳 Tutorial Manual Docker - GestionExpensas2026

## 📋 Paso 1: Preparar Archivos

### En tu máquina local:
```bash
# Copiar archivo de entorno
cp .env.docker.example .env.docker

# Editar .env.docker con tus datos:
nano .env.docker
```

**Configuraciones mínimas necesarias en `.env.docker`:**
```bash
DB_DATABASE=expensas_db
DB_USERNAME=expensas_user
DB_PASSWORD=tu_contraseña_segura_aqui
DB_ROOT_PASSWORD=tu_contraseña_root_segura_aqui
```

### Subir archivos al VPS:
```bash
# Opción A: Usar git
git add .
git commit -m "Add Docker configuration"
git push origin main

# Opción B: Subir manualmente con scp
scp -r /ruta/al/proyecto/* usuario@tu-vps-ip:/ruta/destino/
```

## 🚀 Paso 2: Deployment en VPS

### Conectarte al VPS:
```bash
ssh usuario@tu-vps-ip
cd /ruta/donde/esta/el/proyecto
```

### Verificar que la red Docker existe:
```bash
docker network ls | grep geopricer_365soft_app-network
```

Si NO existe, crearla:
```bash
docker network create geopricer_365soft_app-network
```

### Construir y levantar contenedores:
```bash
# Construir las imágenes
docker-compose build

# Levantar contenedores en segundo plano
docker-compose up -d

# Verificar que estén corriendo
docker-compose ps
```

## 🗄️ Paso 3: Configurar Base de Datos

### Esperar a que MySQL esté listo (30 segundos):
```bash
# Verificar logs de MySQL
docker-compose logs mysql

# Cuando veas "ready for connections", continuar
```

### Ejecutar migraciones:
```bash
# Entrar al contenedor
docker-compose exec app bash

# Ejecutar migraciones
php artisan migrate --force

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

## 🔌 Paso 4: Configurar Nginx Proxy Manager

### Acceder al panel:
```
http://tu-vps-ip:81
```

### Crear nuevo Proxy Host:

**Detalles del Proxy:**
- **Domain Names:** `expensas.tu-dominio.com` (o tu subdominio)
- **Scheme:** `http`
- **Forward Hostname/IP:** `gestion_expensas_app`
- **Forward Port:** `80`

**Opciones:**
- ✅ Cache Assets
- ✅ Block Common Exploits
- ✅ Websockets Support (si lo necesitas)

**SSL:**
- **SSL Certificate:** Let's Encrypt
- **Force SSL:** ✅
- **HTTP/2 Support:** ✅
- **HSTS Enabled:** ✅

## ✅ Paso 5: Verificar Funcionamiento

### Verificar contenedores:
```bash
docker-compose ps
```

Deberías ver algo como:
```
NAME                            STATUS
gestion_expensas_app            Up (healthy)
gestion_expensas_mysql          Up (healthy)
```

### Ver logs si hay problemas:
```bash
# Logs de la aplicación
docker-compose logs app

# Logs de MySQL
docker-compose logs mysql

# Logs en tiempo real
docker-compose logs -f
```

## 📊 Comandos de Gestión Manual

### Ver estado:
```bash
docker-compose ps
```

### Ver logs:
```bash
# Todos los logs
docker-compose logs

# Solo app
docker-compose logs app

# Solo mysql
docker-compose logs mysql

# En tiempo real
docker-compose logs -f app
```

### Reiniciar contenedores:
```bash
# Reiniciar todos
docker-compose restart

# Reiniciar solo app
docker-compose restart app

# Reiniciar solo mysql
docker-compose restart mysql
```

### Detener/Iniciar:
```bash
# Detener todo
docker-compose down

# Iniciar todo
docker-compose up -d
```

### Actualizar la aplicación:
```bash
# Actualizar código
git pull origin main

# Reconstruir imágenes (si hubo cambios en Dockerfile)
docker-compose build

# Reiniciar contenedores
docker-compose down
docker-compose up -d

# Ejecutar nuevas migraciones
docker-compose exec app php artisan migrate --force

# Limpiar caché
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Optimizar
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

## 💾 Backup y Restauración

### Backup de base de datos:
```bash
# Crear carpeta de backups
mkdir -p backups

# Backup
docker-compose exec mysql mysqldump -u expensas_user -p expensas_db > backups/backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restaurar backup:
```bash
docker-compose exec -T mysql mysql -u expensas_user -p expensas_db < backups/archivo_backup.sql
```

## 🐛 Troubleshooting

### La aplicación no responde:
```bash
# Verificar contenedores
docker-compose ps

# Ver logs
docker-compose logs app

# Verificar que el contenedor app esté en la red correcta
docker network inspect geopricer_365soft_app-network | grep gestion_expensas_app
```

### Error de conexión a MySQL:
```bash
# Verificar que MySQL esté corriendo
docker-compose ps mysql

# Ver logs de MySQL
docker-compose logs mysql

# Entrar a MySQL manualmente
docker-compose exec mysql mysql -u expensas_user -p expensas_db
```

### Assets no cargan (imágenes, CSS, JS):
```bash
# Entrar al contenedor
docker-compose exec app bash

# Recrear storage link
php artisan storage:link

# Limpiar caché
php artisan view:clear

# Salir
exit
```

### Performance lenta:
```bash
# Optimizar para producción
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

### Problemas de permisos:
```bash
# Entrar al contenedor
docker-compose exec app bash

# Arreglar permisos
chown -R www-data:www-data storage bootstrap/cache
chmod -R 755 storage bootstrap/cache

exit
```

## 📝 Comandos Útiles del Contenedor

### Ejecutar comandos de Artisan:
```bash
# Ver rutas
docker-compose exec app php artisan route:list

# Limpiar caché
docker-compose exec app php artisan cache:clear

# Ejecutar migraciones
docker-compose exec app php artisan migrate

# Verificar configuración
docker-compose exec app php artisan config:show app.name
```

### Acceder a la consola del contenedor:
```bash
# Shell bash
docker-compose exec app bash

# Consola de MySQL
docker-compose exec mysql mysql -u expensas_user -p expensas_db
```

### Acceder a Tinker:
```bash
docker-compose exec app php artisan tinker
```

## 🎯 Resumen Rápido

**Primer deployment:**
```bash
# 1. Configurar .env.docker
nano .env.docker

# 2. Build y up
docker-compose build
docker-compose up -d

# 3. Migraciones
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan storage:link
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# 4. Configurar Nginx Proxy Manager
# (Forward Hostname: gestion_expensas_app, Port: 80)
```

**Actualizaciones futuras:**
```bash
git pull
docker-compose build
docker-compose down && docker-compose up -d
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
```

## 🔐 Seguridad

**IMPORTANTE:**
- Nunca incluir `.env.docker` en git
- Usar contraseñas fuertes en producción
- Mantener siempre SSL activo en Nginx Proxy Manager
- Los contenedores se reinician automáticamente (`restart: unless-stopped`)
- MySQL NO está expuesto públicamente (solo acceso interno)

## 📞 Ayuda

Para ver todos los comandos disponibles del Makefile:
```bash
make help
```

Logs útiles para diagnóstico:
```bash
docker-compose logs app | grep ERROR
docker-compose logs mysql | grep ERROR
```