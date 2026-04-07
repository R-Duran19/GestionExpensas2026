# 🚀 Tutorial Rápido Docker - GestionExpensas2026

## 📦 Archivos Necesarios

Ya tienes creados estos archivos en tu proyecto:
- `Dockerfile` 
- `docker-compose.yml`
- `.env.docker.example`
- `Makefile` (opcional, para comandos rápidos)

## 🎯 5 Pasos para Deployment

### 1️⃣ Configurar variables
```bash
# Copiar y editar
cp .env.docker.example .env.docker

# Editar estas líneas mínimas:
nano .env.docker
```
**Cambiar:**
- `DB_PASSWORD=tu_contraseña_segura`
- `DB_ROOT_PASSWORD=tu_contraseña_root_segura`

### 2️⃣ Subir al VPS
```bash
# Hacer commit de los cambios
git add .
git commit -m "Add Docker config"
git push origin main
```

### 3️⃣ En el VPS - Levantar contenedores
```bash
# Entrar al VPS
ssh usuario@tu-vps-ip

# Ir al proyecto
cd /ruta/del/proyecto

# Actualizar código
git pull origin main

# Build y up (CONSTRUIR Y LEVANTAR)
docker-compose build
docker-compose up -d

# Verificar que estén corriendo
docker-compose ps
```

### 4️⃣ Configurar base de datos
```bash
# Esperar 30 segundos a que MySQL inicie

# Ejecutar migraciones
docker-compose exec app php artisan migrate --force

# Crear storage link
docker-compose exec app php artisan storage:link

# Optimizar
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
```

### 5️⃣ Configurar Nginx Proxy Manager
**Panel:** `http://tu-vps-ip:81`

**Nuevo Proxy Host:**
- **Domain:** `expensas.tu-dominio.com`
- **Forward Hostname:** `gestion_expensas_app`
- **Forward Port:** `80`
- **SSL:** Let's Encrypt ✅
- **Force SSL:** ✅

## ✅ Listo!

Tu aplicación está funcionando en: `https://expensas.tu-dominio.com`

## 🔄 Para Actualizaciones Futuras

```bash
# En el VPS
git pull origin main
docker-compose build
docker-compose down && docker-compose up -d
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
```

## 🛠️ Comandos Útiles (con Makefile)

```bash
make help                # Ver todos los comandos
make logs                # Ver logs en tiempo real
make shell               # Entrar al contenedor
make status              # Ver estado de contenedores
make clear-cache         # Limpiar caché de Laravel
make backup              # Backup de base de datos
```

## 🛠️ Comandos Útiles (sin Makefile)

```bash
docker-compose ps                    # Ver estado
docker-compose logs -f app           # Ver logs app
docker-compose logs -f mysql         # Ver logs MySQL
docker-compose restart app           # Reiniciar app
docker-compose exec app bash         # Entrar al contenedor
docker-compose down                  # Detener todo
docker-compose up -d                 # Iniciar todo
```

## 🐛 Si hay problemas

```bash
# Verificar contenedores
docker-compose ps

# Ver logs
docker-compose logs app

# Verificar red
docker network inspect geopricer_365soft_app-network

# Reiniciar todo
docker-compose down
docker-compose up -d
```

## 💾 Backup

```bash
# Backup base de datos
docker-compose exec mysql mysqldump -u expensas_user -p expensas_db > backup.sql
```

---

**Eso es todo!** Para más detalles ver [DOCKER_MANUAL.md](DOCKER_MANUAL.md)