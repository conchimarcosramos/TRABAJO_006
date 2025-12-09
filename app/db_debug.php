<?php
require_once __DIR__ . '/config/Database.php';
echo '<pre>';
try {
    $db = new Database();
    $pdo = $db->getConnection();
    if (!$pdo) { echo "No DB connection.\n"; exit; }
    echo "Connected OK.\n";

    $tables = ['users','cursos','alumnos'];
    foreach ($tables as $t) {
        $exists = $pdo->query("SELECT to_regclass('public.{$t}')")->fetchColumn();
        echo "Table {$t}: " . ($exists ? "exists" : "NO") . "\n";
        if ($exists) {
            $count = $pdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
            echo "  rows = {$count}\n";
            echo "  columns: \n";
            $cols = $pdo->query("
                SELECT column_name, data_type
                FROM information_schema.columns
                WHERE table_schema = current_schema() AND table_name = '{$t}'
            ")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $c) {
                echo "    - {$c['column_name']} ({$c['data_type']})\n";
            }
        }
    }
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    error_log('[db_debug] ' . $e->getMessage());
}
echo '</pre>';
?>