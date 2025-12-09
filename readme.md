- CREACIÓN DE PROYECTO

- docker-compose.yml
- app/
    index.php
    api.php
    css/style.css
    config/Database.php
- config/apache.conf
- init-db/init.sql

// Modificaciones en el yml con nueva bd y usuario y passw.
// BD = formulario_db
// User = admin
// Password = 123456789

// Modificaciones y revision en Database.php

// Modificaciones en el fichero index, exportando a un css los estilos del documento y creando tambien una carpeta para guardalos y enlazarlos

// Revisamos api.php
// Revisamos config/apache.conf
// Comprobación fichero init.sql con las tablas a crear en la bd

-------------------------------------

// Arrancamos el Docker Desktop
// Al arrancar docker, debemos docker compose up -d
// La diferencia entre docker-compose up -d y docker compose up -d radica principalmente en la versión y el comando utilizado, no en la funcionalidad.
// Indicamos docker compose ps , para comprobar que inició correctamente, tanto php como postgres
// Accedemos a http://localhost:8080
// Insertamos datos , todo ok

// Ejecutamos database, extension
// 127.0.0.1 - servidor
// admin -- user
// 123456789 / admin123  -- password
// formulario_db  -- base de datos

.-----
// SQLTools PostgreSQL/Redshift Driver
