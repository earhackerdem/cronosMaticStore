-- Script de inicialización para MariaDB
-- CronosMatic Store Database

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS cronosmatic CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Usar la base de datos
USE cronosmatic;

-- Configurar variables de sesión
SET sql_mode = "STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO";

-- Configurar timezone
SET time_zone = "+00:00";

-- Configurar caracteres
SET character_set_client = utf8mb4;
SET character_set_connection = utf8mb4;
SET character_set_database = utf8mb4;
SET character_set_results = utf8mb4;
SET character_set_server = utf8mb4;
SET collation_connection = utf8mb4_unicode_ci;
SET collation_database = utf8mb4_unicode_ci;
SET collation_server = utf8mb4_unicode_ci;
