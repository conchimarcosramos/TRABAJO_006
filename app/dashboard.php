<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/Database.php';

// Verificar sesión
if (empty($_SESSION['username'])) {
    $_SESSION['error'] = 'Debes iniciar sesión para acceder al panel.';
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? 'user';

// Obtener estadísticas
$totalAlumnos = 0;
$totalCursos = 0;
$totalUsuarios = 0;
$ultimosAlumnos = [];
$cursosConAlumnos = [];
$alumnosPorMes = [];
$alumnosSinCurso = 0;

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    if (!$pdo) {
        throw new Exception('No se pudo conectar a la base de datos');
    }
    
    $totalAlumnos = (int)$pdo->query('SELECT COUNT(*) FROM alumnos')->fetchColumn();
    $totalCursos = (int)$pdo->query('SELECT COUNT(*) FROM cursos')->fetchColumn();
    $totalUsuarios = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    
    // Últimos 5 alumnos registrados
    $stmt = $pdo->query("
        SELECT id, nombre, email, TO_CHAR(created_at, 'DD-MM-YYYY') AS fecha_registro
        FROM alumnos
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $ultimosAlumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Distribución real de alumnos por curso (TODOS los cursos)
    $stmt = $pdo->query("
        SELECT c.nombre, COUNT(ac.alumno_id) AS total
        FROM cursos c
        LEFT JOIN alumnos_cursos ac ON ac.curso_id = c.id
        GROUP BY c.id, c.nombre
        ORDER BY total DESC, c.nombre
    ");
    $cursosConAlumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar alumnos sin ningún curso
    $alumnosSinCurso = (int)$pdo->query("
        SELECT COUNT(DISTINCT a.id)
        FROM alumnos a
        LEFT JOIN alumnos_cursos ac ON ac.alumno_id = a.id
        WHERE ac.curso_id IS NULL
    ")->fetchColumn();
    
    // Alumnos registrados por mes (últimos 6 meses)
    $stmt = $pdo->query("
        SELECT 
            TO_CHAR(created_at, 'Mon YYYY') AS mes,
            COUNT(*) AS total
        FROM alumnos
        WHERE created_at >= NOW() - INTERVAL '6 months'
        GROUP BY TO_CHAR(created_at, 'YYYY-MM'), TO_CHAR(created_at, 'Mon YYYY')
        ORDER BY TO_CHAR(created_at, 'YYYY-MM')
    ");
    $alumnosPorMes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Throwable $e) {
    error_log('[dashboard] Error: ' . $e->getMessage());
    $_SESSION['error'] = 'Error al cargar estadísticas: ' . $e->getMessage();
}

// Preparar datos para JavaScript
$cursoNombres = json_encode(array_column($cursosConAlumnos, 'nombre'));
$cursoTotales = json_encode(array_column($cursosConAlumnos, 'total'));
$mesesNombres = json_encode(array_column($alumnosPorMes, 'mes'));
$mesesTotales = json_encode(array_column($alumnosPorMes, 'total'));

// Calcular total de alumnos matriculados (puede haber alumnos en múltiples cursos)
$totalMatriculados = (int)$pdo->query('SELECT COUNT(*) FROM alumnos_cursos')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Panel de Control</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
.chart-container {
    position: relative;
    margin: 24px 0;
    background: var(--white);
    padding: 20px;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
}

.chart-container-tall {
    height: 400px;
}

.chart-container-medium {
    height: 320px;
}

.chart-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
    margin: 24px 0;
}

@media (min-width: 1200px) {
    .chart-grid {
        grid-template-columns: 1.2fr 1fr;
    }
}

@media (max-width: 768px) {
    .chart-container-tall,
    .chart-container-medium {
        height: 280px;
    }
}

.info-badge {
    display: inline-block;
    background: var(--light-sage);
    color: var(--dark-teal);
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    margin-left: 8px;
}
</style>
</head>
<body>
<div class="container">
    <h2>Panel de Control</h2>
    
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="message success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="message error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card">
        <p><strong>Bienvenido:</strong> <?= htmlspecialchars($username) ?></p>
        <p><strong>Rol:</strong> <?= htmlspecialchars($role) ?></p>
    </div>

    <h3>Estadísticas</h3>
    <div class="stats-grid">
        <div class="stat-card">
            <h4>Alumnos totales</h4>
            <p><?= $totalAlumnos ?></p>
        </div>
        <div class="stat-card">
            <h4>Cursos activos</h4>
            <p><?= $totalCursos ?></p>
        </div>
        <div class="stat-card">
            <h4>Usuarios sistema</h4>
            <p><?= $totalUsuarios ?></p>
        </div>
    </div>

    <!-- Gráficos -->
    <h3>
        Distribución de alumnos
        <span class="info-badge"><?= $totalMatriculados ?> matrículas totales</span>
        <?php if ($alumnosSinCurso > 0): ?>
            <span class="info-badge" style="background:#f0ad4e;color:#fff;"><?= $alumnosSinCurso ?> sin curso</span>
        <?php endif; ?>
    </h3>
    <div class="chart-grid">
        <div class="chart-container chart-container-tall">
            <canvas id="chartCursos"></canvas>
        </div>
        <div class="chart-container chart-container-medium">
            <canvas id="chartAlumnosMes"></canvas>
        </div>
    </div>

    <?php if (!empty($ultimosAlumnos)): ?>
        <h3>Últimos alumnos registrados</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ultimosAlumnos as $alumno): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$alumno['id']) ?></td>
                    <td><?= htmlspecialchars($alumno['nombre']) ?></td>
                    <td><?= htmlspecialchars($alumno['email']) ?></td>
                    <td><?= htmlspecialchars($alumno['fecha_registro']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3>Acciones rápidas</h3>
    <p style="margin-top:12px;">
        <a class="btn btn-primary" href="listar_alumnos.php">Ver alumnos</a>
        <a class="btn btn-primary" href="listar_cursos.php">Ver cursos</a>
        <a class="btn btn-success" href="registro_alumnos.php">Registrar alumno</a>
        <a class="btn btn-success" href="registro_cursos.php">Registrar curso</a>
    </p>

    <p style="margin-top:20px;">
        <a class="btn btn-outline" href="logout.php">Cerrar sesión</a>
    </p>
</div>

<script>
// Configuración global de Chart.js
Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
Chart.defaults.color = '#5C736F';

// Paleta de colores
const colors = {
    darkTeal: '#3B5159',
    mediumTeal: '#5C736F',
    sage: '#90A68A',
    lightSage: '#CCD9B8',
    cream: '#EBF2DC'
};

// Generar gradiente de colores de la paleta
function generateGradientColors(count) {
    const gradient = [];
    for (let i = 0; i < count; i++) {
        const ratio = i / Math.max(count - 1, 1);
        if (ratio < 0.33) {
            gradient.push('#3B5159'); // dark-teal
        } else if (ratio < 0.66) {
            gradient.push('#5C736F'); // medium-teal
        } else {
            gradient.push('#90A68A'); // sage
        }
    }
    return gradient;
}

const cursoLabels = <?= $cursoNombres ?>;
const cursoData = <?= $cursoTotales ?>;
const backgroundColors = generateGradientColors(cursoLabels.length);

// Gráfico 1: Distribución de alumnos por curso (Barras horizontales)
const ctxCursos = document.getElementById('chartCursos').getContext('2d');
new Chart(ctxCursos, {
    type: 'bar',
    data: {
        labels: cursoLabels,
        datasets: [{
            label: 'Alumnos matriculados',
            data: cursoData,
            backgroundColor: backgroundColors,
            borderColor: colors.mediumTeal,
            borderWidth: 0,
            borderRadius: 6,
            barThickness: 'flex',
            maxBarThickness: 40
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Alumnos por curso',
                font: {
                    size: 16,
                    weight: 'bold',
                    family: "'Poppins', sans-serif"
                },
                color: colors.darkTeal,
                padding: {
                    bottom: 20
                }
            },
            tooltip: {
                backgroundColor: colors.darkTeal,
                titleFont: {
                    size: 13,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 12
                },
                padding: 10,
                cornerRadius: 6,
                callbacks: {
                    label: function(context) {
                        const value = context.parsed.x || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return `${value} alumnos (${percentage}% del total)`;
                    }
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                    font: {
                        size: 11
                    }
                },
                grid: {
                    color: 'rgba(144, 166, 138, 0.1)'
                }
            },
            y: {
                ticks: {
                    font: {
                        size: 11
                    },
                    color: colors.mediumTeal
                },
                grid: {
                    display: false
                }
            }
        }
    }
});

// Gráfico 2: Alumnos registrados por mes (Línea con área)
const ctxMes = document.getElementById('chartAlumnosMes').getContext('2d');
new Chart(ctxMes, {
    type: 'line',
    data: {
        labels: <?= $mesesNombres ?>,
        datasets: [{
            label: 'Nuevos registros',
            data: <?= $mesesTotales ?>,
            backgroundColor: 'rgba(144, 166, 138, 0.2)',
            borderColor: colors.mediumTeal,
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: colors.darkTeal,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8,
            pointHoverBackgroundColor: colors.sage
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Registros mensuales (últimos 6 meses)',
                font: {
                    size: 16,
                    weight: 'bold',
                    family: "'Poppins', sans-serif"
                },
                color: colors.darkTeal,
                padding: {
                    bottom: 20
                }
            },
            tooltip: {
                backgroundColor: colors.darkTeal,
                titleFont: {
                    size: 13,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 12
                },
                padding: 10,
                cornerRadius: 6,
                callbacks: {
                    label: function(context) {
                        return `Nuevos alumnos: ${context.parsed.y}`;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                    font: {
                        size: 12
                    }
                },
                grid: {
                    color: 'rgba(144, 166, 138, 0.1)'
                }
            },
            x: {
                ticks: {
                    font: {
                        size: 11
                    }
                },
                grid: {
                    display: false
                }
            }
        }
    }
});
</script>
</body>
</html>