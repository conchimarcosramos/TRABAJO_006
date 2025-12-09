<?php
// ...existing code...
$stmt = $pdo->prepare("
    SELECT id, username, email, role,
           TO_CHAR(created_at, 'DD-MM-YYYY') AS fecha_registro
    FROM users
    ORDER BY created_at DESC
    LIMIT :limit OFFSET :offset
");
// ...existing code...