-- Inicialización de la base de datos
-- Este script se ejecuta automáticamente cuando el contenedor MySQL se inicia por primera vez

SET CHARACTER SET utf8mb4;
SET NAMES utf8mb4;

-- Otorgar privilegios necesarios
GRANT ALL PRIVILEGES ON expensas_db.* TO 'expensas_user'@'%';
FLUSH PRIVILEGES;