-- Script para limpiar caracteres UTF-8 mal codificados en la base de datos
-- Ejecutar este script en tu base de datos SQLite

-- Limpiar tabla de ingresos
UPDATE ingresos
SET descripcion = REPLACE(REPLACE(REPLACE(descripcion, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' '),
    observaciones = REPLACE(REPLACE(REPLACE(observaciones, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' '),
    referencia_bancaria = REPLACE(REPLACE(REPLACE(referencia_bancaria, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' ')
WHERE descripcion IS NOT NULL OR observaciones IS NOT NULL OR referencia_bancaria IS NOT NULL;

-- Limpiar tabla de egresos
UPDATE egresos
SET concepto = REPLACE(REPLACE(REPLACE(concepto, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' '),
    descripcion = REPLACE(REPLACE(REPLACE(descripcion, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' '),
    observaciones = REPLACE(REPLACE(REPLACE(observaciones, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' '),
    proveedor = REPLACE(REPLACE(REPLACE(proveedor, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' ')
WHERE concepto IS NOT NULL OR descripcion IS NOT NULL OR observaciones IS NOT NULL OR proveedor IS NOT NULL;

-- Limpiar tabla de propietarios
UPDATE propietarios
SET nombre = REPLACE(REPLACE(REPLACE(nombre, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' '),
    apellido = REPLACE(REPLACE(REPLACE(apellido, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' '),
    email = REPLACE(REPLACE(REPLACE(email, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' '),
    observaciones = REPLACE(REPLACE(REPLACE(observaciones, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' ')
WHERE nombre IS NOT NULL OR apellido IS NOT NULL OR email IS NOT NULL OR observaciones IS NOT NULL;

-- Limpiar tabla de categorías de ingresos
UPDATE categorias_ingresos
SET nombre = REPLACE(REPLACE(REPLACE(nombre, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' '),
    descripcion = REPLACE(REPLACE(REPLACE(descripcion, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' ')
WHERE nombre IS NOT NULL OR descripcion IS NOT NULL;

-- Limpiar tabla de categorías de egresos
UPDATE categorias_egresos
SET nombre = REPLACE(REPLACE(REPLACE(nombre, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' '),
    descripcion = REPLACE(REPLACE(REPLACE(descripcion, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' ')
WHERE nombre IS NOT NULL OR descripcion IS NOT NULL;

-- Limpiar tabla de configuración del edificio
UPDATE configuracion_edificio
SET nombre_edificio = REPLACE(REPLACE(REPLACE(nombre_edificio, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' '),
    direccion = REPLACE(REPLACE(REPLACE(direccion, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' '),
    email = REPLACE(REPLACE(REPLACE(email, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' '),
    informacion_adicional = REPLACE(REPLACE(REPLACE(informacion_adicional, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' '),
    ciudad = REPLACE(REPLACE(REPLACE(ciudad, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' '),
    provincia = REPLACE(REPLACE(REPLACE(provincia, CHAR(194), ''), CHAR(133), ''), CHAR(160), ' ')
WHERE nombre_edificio IS NOT NULL OR direccion IS NOT NULL OR email IS NOT NULL OR informacion_adicional IS NOT NULL;

-- Limpiar caracteres de comillas tipográficas y otros caracteres problemáticos
UPDATE ingresos
SET descripcion = REPLACE(REPLACE(REPLACE(REPLACE(descripcion, '"', '"'), '"', '"'), ''', '\''),
    observaciones = REPLACE(REPLACE(REPLACE(observaciones, '"', '"'), '"', '"'), ''', '\'')
WHERE descripcion IS NOT NULL OR observaciones IS NOT NULL;

UPDATE egresos
SET concepto = REPLACE(REPLACE(REPLACE(concepto, '"', '"'), '"', '"'), ''', '\''),
    descripcion = REPLACE(REPLACE(REPLACE(descripcion, '"', '"'), '"', '"'), ''', '\''),
    observaciones = REPLACE(REPLACE(REPLACE(observaciones, '"', '"'), '"', '"'), ''', '\')
WHERE concepto IS NOT NULL OR descripcion IS NOT NULL OR observaciones IS NOT NULL;
