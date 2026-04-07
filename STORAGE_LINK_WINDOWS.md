# Storage Link en Windows - Solución a Problemas con Comprobantes

## 🎯 Problema

Los comprobantes de pagos de expensas no se pueden visualizar porque el `storage link` está mal configurado en Windows.

**Síntomas:**
- ❌ Error: "El archivo del comprobante no existe"
- ✅ Los archivos existen en `storage/app/public/caja/...`
- ❌ Pero Laravel no los encuentra

**Causa:** Git Bash crea symlinks UNIX que no funcionan correctamente en Windows.

---

## 🔍 Diagnóstico

Ejecutar este comando para verificar si el link está roto:

```bash
ls -la public/storage
```

**Si ves esto (INCORRECTO):**
```
lrwxrwxrwx 1 user group 59 Jan 01 12:00 public/storage -> /c/laragon-6.0.0/www/...
```
→ Ruta UNIX `/c/...` ❌ **NO FUNCIONA EN WINDOWS**

**Debería ser un Junction de Windows:**
```
lrwxrwxrwx 1 user group 59 Jan 01 12:00 public/storage -> ...\storage\app\public
```
→ Ruta Windows válida ✅

---

## ✅ Solución

### Paso 1: Eliminar el link roto
```bash
# Desde la raíz del proyecto
rm -f public/storage
```

### Paso 2: Crear Junction de Windows (CORRECTO)

**Opción A: Usar CMD directamente**
```cmd
mklink /J public\storage storage\app\public
```

**Opción B: Desde Git Bash**
```bash
cmd //c "mklink /J public\\storage ..\\storage\\app\\public"
```

**Opción C: Copiar archivos (solución temporal, NO recomendada)**
```bash
cp -r storage/app/public/caja public/storage/
```

### Paso 3: Verificar
```bash
ls public/storage/caja/pagos-expensas/2026/04/
```

Deberías ver los archivos `.webp`.

---

## 🎯 Resultado Esperado

```
public/storage/ → Es un Junction (enlace simbólico de Windows)
                       ↓
           apunta a: storage/app/public/
```

Ahora Laravel puede encontrar los archivos con:
```php
Storage::disk('public')->exists('caja/pagos-expensas/...')
Storage::disk('public')->url('caja/pagos-expensas/...')
```

---

## ⚠️ IMPORTANTE

### Si actualizas archivos:
- Los Junctions de Windows **SÍ se mantienen sincronizados** ✅
- No necesitas volver a crear el link
- Los cambios en `storage/app/public/` se reflejan en `public/storage/`

### Si borras y clonas el repo:
- Vuelve a ejecutar: `mklink /J public\storage storage\app\public`

### En servidores Linux:
- El comando normal funciona: `php artisan storage:link`
- Solo en Windows necesitamos `mklink /J`

---

## 📝 Comandos Rápidos

### Verificar estado actual:
```bash
ls -la public/storage
```

### Recrear storage link (Windows):
```bash
rm -f public/storage
mklink /J public\storage storage\app\public
```

### Ver archivos de comprobantes:
```bash
ls public/storage/caja/pagos-expensas/2026/04/
```

---

## 🔧 En el Código

**Siempre especificar el disco 'public' para comprobantes:**

```php
// ❌ INCORRECTO (busca en disco 'local' por defecto)
Storage::exists($ruta)
Storage::url($ruta)

// ✅ CORRECTO (busca en disco 'public')
Storage::disk('public')->exists($ruta)
Storage::disk('public')->url($ruta)
```

**Por qué:**
- Disco `local` → `storage/app/private/` (privado, no accesible vía web)
- Disco `public` → `storage/app/public/` (público, accesible vía web)

---

## 🚨 Troubleshooting

### Si sigue sin funcionar después de crear el junction:

1. **Verificar que el archivo existe:**
   ```bash
   ls storage/app/public/caja/pagos-expensas/2026/04/
   ```

2. **Verificar que sea accesible vía web:**
   ```
   http://tudominio.com/storage/caja/pagos-expensas/2026/04/archivo.webp
   ```

3. **Verificar permisos:**
   ```bash
   # El directorio debe tener permisos de lectura
   chmod -R 755 storage/app/public/
   chmod 644 storage/app/public/caja/pagos-expensas/2026/04/*
   ```

4. **Limpiar caché:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

---

## 📚 Referencias

- [Laravel Filesystem Documentation](https://laravel.com/docs/filesystem)
- [Windows Junctions](https://docs.microsoft.com/en-us/windows/win32/Win32FSJT)

---

**Última actualización:** Abril 2026
**Aplicable a:** Laravel 10+, Windows 10/11
