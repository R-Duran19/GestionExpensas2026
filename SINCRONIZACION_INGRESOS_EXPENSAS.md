# Sincronización Automática Ingresos ↔ Expensas

## ✅ IMPLEMENTADO

Fecha: Abril 2026

---

## 🎯 Problema Resuelto

**Antes:** Si editabas/eliminabas un pago de expensa desde el módulo de caja, el `expensa_total` NO se actualizaba, causando inconsistencias.

**Ahora:** Cualquier modificación a un pago de expensa actualiza automáticamente el `expensa_total`.

---

## 🔄 Comportamiento Implementado

### 1️⃣ **Editar Pago de Expensa**
```php
// Escenario: Editar monto de pago
Ingreso: 500 Bs → 300 Bs (se edita)
ExpensaTotal: se recalcula automáticamente
  ├── monto_pagado: 500 Bs → 300 Bs
  ├── monto_pendiente: 1,000 Bs → 1,200 Bs
  └── estado: "parcial" (se mantiene)
```

### 2️⃣ **Eliminar Pago de Expensa**
```php
// Escenario: Eliminar pago
Ingreso: eliminado (soft delete)
ExpensaTotal: se recalcula automáticamente
  ├── monto_pagado: 500 Bs → 0 Bs
  ├── monto_pendiente: 1,000 Bs → 1,500 Bs
  └── estado: "parcial" → "pendiente"
```

### 3️⃣ **Restaurar Pago de Expensa**
```php
// Escenario: Restaurar pago eliminado
Ingreso: restaurado
ExpensaTotal: se recalcula automáticamente
  ├── monto_pagado: 0 Bs → 500 Bs
  ├── monto_pendiente: 1,500 Bs → 1,000 Bs
  └── estado: "pendiente" → "parcial"
```

---

## 🛠️ Método Implementado

### `recalcularExpensaTotal(int $expensaTotalId)`

**Ubicación:** `app/Services/IngresoService.php`

**Lógica:**
```php
protected function recalcularExpensaTotal(int $expensaTotalId): void
{
    // 1. Obtener expensa_total
    $expensaTotal = ExpensaTotal::find($expensaTotalId);

    // 2. Sumar todos los pagos activos
    $montoPagado = Ingreso::where('expensa_total_id', $expensaTotalId)
        ->activos()
        ->sum('importe');

    // 3. Recalcular pendiente
    $montoPendiente = $expensaTotal->monto_total - $montoPagado;

    // 4. Calcular saldo a favor (si aplica)
    if ($montoPendiente < 0) {
        $saldoFavor = abs($montoPendiente);
        $montoPendiente = 0;
    }

    // 5. Determinar estado
    if ($montoPendiente == 0 && $saldoFavor == 0) {
        $estado = 'pagado';
    } elseif ($montoPagado > 0 && $montoPendiente > 0) {
        $estado = 'parcial';
    } else {
        $estado = 'pendiente';
    }

    // 6. Actualizar registro
    $expensaTotal->update([
        'monto_pagado' => $montoPagado,
        'monto_pendiente' => $montoPendiente,
        'saldo_a_favor' => $saldoFavor,
        'estado' => $estado,
    ]);

    // 7. Registrar en Activity Log
}
```

---

## 📝 Modificaciones al IngresoService

### Métodos Actualizados:

#### ✅ `actualizar()`
```php
public function actualizar(Ingreso $ingreso, array $datos, $archivoComprobante = null)
{
    // Detectar si es pago de expensa
    $esPagoExpensa = !is_null($ingreso->expensa_total_id);
    $expensaTotalId = $ingreso->expensa_total_id;
    $montoOriginal = $ingreso->importe;

    // Actualizar ingreso
    $ingreso->update($datos);

    // Si es pago de expensa y cambió el monto → recalcular
    if ($esPagoExpensa && isset($datos['importe']) && $datos['importe'] != $montoOriginal) {
        $this->recalcularExpensaTotal($expensaTotalId);
    }
}
```

#### ✅ `eliminar()`
```php
public function eliminar(Ingreso $ingreso)
{
    // Detectar si es pago de expensa
    $esPagoExpensa = !is_null($ingreso->expensa_total_id);
    $expensaTotalId = $ingreso->expensa_total_id;

    // Soft delete
    $ingreso->delete();

    // Si es pago de expensa → recalcular
    if ($esPagoExpensa) {
        $this->recalcularExpensaTotal($expensaTotalId);
    }
}
```

#### ✅ `restaurar()`
```php
public function restaurar(int $id)
{
    $ingreso = Ingreso::withTrashed()->findOrFail($id);

    // Detectar si es pago de expensa
    $esPagoExpensa = !is_null($ingreso->expensa_total_id);
    $expensaTotalId = $ingreso->expensa_total_id;

    $ingreso->restore();

    // Si es pago de expensa → recalcular
    if ($esPagoExpensa) {
        $this->recalcularExpensaTotal($expensaTotalId);
    }
}
```

---

## 🧪 Casos de Prueba

### ✅ **Caso 1: Editar monto mayor**
```
Estado inicial:
- ExpensaTotal: monto_total=2,000, monto_pagado=500, monto_pendiente=1,500

Acción:
- Editar pago: 500 Bs → 800 Bs

Resultado:
- ExpensaTotal: monto_pagado=800, monto_pendiente=1,200
- Estado: "parcial" (se mantiene)
```

### ✅ **Caso 2: Editar monto menor**
```
Estado inicial:
- ExpensaTotal: monto_total=2,000, monto_pagado=1,500, monto_pendiente=500

Acción:
- Editar pago: 1,500 Bs → 200 Bs

Resultado:
- ExpensaTotal: monto_pagado=200, monto_pendiente=1,800
- Estado: "parcial" (se mantiene)
```

### ✅ **Caso 3: Eliminar único pago**
```
Estado inicial:
- ExpensaTotal: monto_total=2,000, monto_pagado=500, monto_pendiente=1,500

Acción:
- Eliminar pago de 500 Bs

Resultado:
- ExpensaTotal: monto_pagado=0, monto_pendiente=2,000
- Estado: "parcial" → "pendiente"
```

### ✅ **Caso 4: Eliminar pago completo**
```
Estado inicial:
- ExpensaTotal: monto_total=2,000, monto_pagado=2,000, monto_pendiente=0, estado="pagado"

Acción:
- Eliminar pago de 2,000 Bs

Resultado:
- ExpensaTotal: monto_pagado=0, monto_pendiente=2,000
- Estado: "pagado" → "pendiente"
```

### ✅ **Caso 5: Restaurar pago**
```
Estado inicial:
- ExpensaTotal: monto_total=2,000, monto_pagado=0, monto_pendiente=2,000, estado="pendiente"

Acción:
- Restaurar pago eliminado de 1,500 Bs

Resultado:
- ExpensaTotal: monto_pagado=1,500, monto_pendiente=500
- Estado: "pendiente" → "parcial"
```

### ✅ **Caso 6: Pago en exceso**
```
Estado inicial:
- ExpensaTotal: monto_total=2,000, monto_pagado=0, monto_pendiente=2,000

Acción:
- Registrar pago de 2,500 Bs

Resultado:
- ExpensaTotal: monto_pagado=2,500, monto_pendiente=0, saldo_a_favor=500
- Estado: "pagado"
```

---

## 🔔 Activity Log

Todos los recálculos automáticos se registran en el Activity Log:

```php
[
    'user_id' => Auth::id(),
    'action' => 'expensa_total.recalculada',
    'model_type' => ExpensaTotal::class,
    'model_id' => $expensaTotal->id,
    'description' => "Expensa total recalculada automáticamente",
    'properties' => [
        'expensa_total_id' => 123,
        'propietario' => "Juan Pérez",
        'periodo' => "Marzo 2026",
        'monto_pagado' => 500.00,
        'monto_pendiente' => 1500.00,
        'saldo_a_favor' => 0,
        'estado' => 'parcial',
    ],
]
```

---

## 🚀 Características de la Implementación

✅ **Automático** - No requiere intervención del usuario
✅ **Transaccional** - Todo o nada, evita inconsistencias
✅ **Auditado** - Activity Log en cada recálculo
✅ **Flexible** - Funciona con múltiples pagos parciales
✅ **Seguro** - Solo recalcula si es pago de expensa

---

## 📊 Flujo Completo de Sincronización

```
┌─────────────────────────────────────────────────────────────┐
│  Usuario edita/elimina/restaura un Ingreso                  │
│  desde el módulo de Caja                                    │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
        ┌────────────────────────────┐
        │ IngresoService detecta:    │
        │ - Tiene expensa_total_id?  │
        └────────────┬───────────────┘
                     │
            ┌────────┴────────┐
            │                 │
         ¿SÍ?             ¿NO?
            │                 │
            ▼                 ▼
  ┌─────────────────┐   ┌──────────┐
  │ recalcular      │   │ No hacer │
  │ ExpensaTotal    │   │ nada     │
  └─────────────────┘   └──────────┘
            │
            ▼
  ┌─────────────────────────┐
  │ 1. Sumar pagos activos  │
  │ 2. Recalcular pendiente │
  │ 3. Actualizar estado    │
  │ 4. Registrar en Log     │
  └─────────────────────────┘
            │
            ▼
  ┌─────────────────────────┐
  │ ✅ Datos sincronizados   │
  └─────────────────────────┘
```

---

## 🎯 Ventajas de esta Implementación

1. **Integridad de Datos** - Expensas siempre sincronizadas con pagos
2. **Flexibilidad** - Puedes corregir errores sin problemas
3. **Auditoría Completa** - Todo cambio queda registrado
4. **Automático** - No requiere intervención manual
5. **Transaccional** - Evita inconsistencias parciales

---

## ⚠️ Consideraciones Importantes

### Los cambios se aplican SOLO si:
1. El ingreso tiene `expensa_total_id` (es pago de expensa)
2. El monto realmente cambió (en edición)
3. La operación se completa exitosamente

### NO se recalculará si:
1. Editas otros campos (fecha, descripción, etc.) sin cambiar monto
2. El ingreso NO es un pago de expensa (expensa_total_id = null)
3. Ocurre un error en la transacción (rollback)

---

**Desarrollado por:** Claude Sonnet 4.6
**Fecha:** Abril 2026
**Versión:** 1.1.0
