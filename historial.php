<?php
/**
 * PANTALLA DE HISTORIAL DE AUDITORÍAS PREVIAS
 * Auditor Agéntico de Facturación de Siniestros
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/notion.php';

$historial = [];
$error_notion = null;

try {
    $historial = notion_get_historial();
} catch (Exception $e) {
    $error_notion = $e->getMessage();
    // Fallback amigable al mock si Notion falla
    if (isset($_SESSION['mock_historial'])) {
        $historial = $_SESSION['mock_historial'];
    } else {
        $historial = MOCK_HISTORIAL;
    }
}

// Inicializar contadores acumulados
$total_auditorias = count($historial);
$acumulado_facturado = 0.0;
$acumulado_correcto = 0.0;
$acumulado_ahorro = 0.0;

foreach ($historial as $item) {
    $acumulado_facturado += (float)$item['total_facturado'];
    $acumulado_correcto += (float)$item['total_correcto'];
    $acumulado_ahorro += (float)$item['ahorro_detectado'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Auditorías - Auditor Agéntico</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .discrepancy-badge-list {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        .discrepancy-item-pill {
            background-color: var(--color-danger-soft);
            color: var(--color-danger-hover);
            border: 1px solid rgba(239, 68, 68, 0.1);
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 500;
        }
        .details-row {
            background-color: #f8fafc;
            display: none;
        }
        .details-row.active {
            display: table-row;
        }
        .details-box {
            padding: 15px 20px;
            border-left: 4px solid var(--color-accent-blue);
        }
        .toggle-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            color: var(--color-accent-blue);
            padding: 4px 8px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .toggle-btn:hover {
            background-color: var(--color-accent-blue-soft);
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Encabezado -->
        <header class="main-header animate-fade-in">
            <div class="header-logo">
                <span class="logo-icon">🛡️</span>
                <div class="logo-text">
                    <h1>Auditor Agéntico</h1>
                    <p>Facturación de Siniestros</p>
                </div>
            </div>
            
            <div class="header-nav">
                <a href="index.php" class="nav-link">🔍 Nueva Auditoría</a>
                <a href="historial.php" class="nav-link active">📋 Historial de Auditorías</a>
                <?php if (!DEMO_MODE): ?>
                    <a href="cargar_tarifario.php" class="nav-link">⚙️ Ajustes de Tarifario</a>
                <?php endif; ?>
            </div>

            <div class="connection-badge <?php echo DEMO_MODE ? 'demo' : 'connected'; ?>">
                <span class="badge-dot"></span>
                <span><?php echo DEMO_MODE ? 'Modo Demo Activo (Mock)' : 'Notion Conectado'; ?></span>
            </div>
        </header>

        <main class="main-content">
            <!-- Mensaje de error de Notion -->
            <?php if ($error_notion !== null && !DEMO_MODE): ?>
                <div class="alert alert-danger animate-fade-in">
                    <span class="alert-icon">⚠️</span>
                    <div>
                        <strong>Error al conectar con Notion Historial DB:</strong> <?php echo htmlspecialchars($error_notion); ?>.
                        <br><span style="font-size: 0.85em;">Mostrando historial acumulado en sesión local (modo de contingencia).</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- KPIs Acumulados -->
            <div class="metrics-row animate-fade-in" style="animation-delay: 0.05s; margin-bottom: 25px;">
                <div class="metric-card">
                    <span class="metric-label">Auditorías Realizadas</span>
                    <span class="metric-val"><?php echo $total_auditorias; ?></span>
                </div>

                <div class="metric-card">
                    <span class="metric-label">Monto Total Auditado</span>
                    <span class="metric-val">$<?php echo number_format($acumulado_facturado, 2); ?></span>
                </div>

                <div class="metric-card">
                    <span class="metric-label">Monto Aprobado Acumulado</span>
                    <span class="metric-val" style="color: var(--color-accent-blue);">$<?php echo number_format($acumulado_correcto, 2); ?></span>
                </div>

                <div class="metric-card savings-highlight">
                    <span class="metric-label">Ahorro Neto Detectado</span>
                    <span class="metric-val savings-amount">$<?php echo number_format($acumulado_ahorro, 2); ?></span>
                </div>
            </div>

            <!-- Tabla de Historial -->
            <section class="card animate-fade-in" style="animation-delay: 0.1s;">
                <div class="card-header">
                    <h2>Historial de Auditorías (Lectura Notion DB)</h2>
                    <p>Registro histórico de facturas procesadas por el sistema auditor con su respectivo análisis y dictamen.</p>
                </div>
                <div class="card-body" style="padding-top: 15px;">
                    <?php if ($total_auditorias === 0): ?>
                        <div class="text-center" style="padding: 40px 0; color: var(--text-muted);">
                            <span style="font-size: 3rem;">📂</span>
                            <p class="margin-top-md" style="font-size: 1rem; font-weight: 500;">No se registran auditorías en el historial.</p>
                            <a href="index.php" class="btn btn-primary margin-top-md">Comenzar a Auditar</a>
                        </div>
                    <?php else: ?>
                        <div class="table-container" style="max-height: none; overflow: visible;">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 50px; text-align: center;">Detalle</th>
                                        <th>Factura</th>
                                        <th>Taller</th>
                                        <th>Fecha Auditoría</th>
                                        <th>Total Facturado</th>
                                        <th>Total Correcto</th>
                                        <th>Ahorro Detectado</th>
                                        <th>Confianza</th>
                                        <th>Resultado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historial as $index => $row): ?>
                                        <?php 
                                            // Parsear discrepancias
                                            $discrepancias_arr = json_decode($row['discrepancias'], true);
                                            if (!is_array($discrepancias_arr)) {
                                                $discrepancias_arr = [];
                                            }
                                            
                                            // Filtrar solo las que tienen estado rojo o amarillo
                                            $anomalias = array_filter($discrepancias_arr, function($i) {
                                                return isset($i['estado']) && ($i['estado'] === 'rojo' || $i['estado'] === 'amarillo');
                                            });

                                            // Formatear Fecha
                                            $fecha_formateada = 'N/A';
                                            if (!empty($row['fecha'])) {
                                                $dt = new DateTime($row['fecha']);
                                                // Ajustar zona horaria si es necesario, mostrar en español amigable
                                                $fecha_formateada = $dt->format('d/m/Y H:i');
                                            }
                                        ?>
                                        <!-- Fila Principal -->
                                        <tr>
                                            <td style="text-align: center;">
                                                <button class="toggle-btn" onclick="toggleDetails(<?php echo $index; ?>)" title="Ver Desglose de Anomalías">
                                                    👁️
                                                </button>
                                            </td>
                                            <td style="font-weight: 700; color: var(--color-navy-dark);"><?php echo htmlspecialchars($row['id_factura']); ?></td>
                                            <td style="font-weight: 500;"><?php echo htmlspecialchars($row['taller']); ?></td>
                                            <td><?php echo htmlspecialchars($fecha_formateada); ?></td>
                                            <td class="price">$<?php echo number_format($row['total_facturado'], 2); ?></td>
                                            <td class="price" style="color: var(--color-accent-blue);">$<?php echo number_format($row['total_correcto'], 2); ?></td>
                                            <td class="price" style="color: <?php echo $row['ahorro_detectado'] > 0 ? 'var(--color-danger)' : 'var(--color-success)'; ?>;">
                                                $<?php echo number_format($row['ahorro_detectado'], 2); ?>
                                            </td>
                                            <td>
                                                <span class="badge" style="background-color: #f1f5f9; color: var(--color-navy-light); border: 1px solid #cbd5e1;">
                                                    <?php echo $row['score_confianza']; ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                    $res_class = 'mano-de-obra'; // verde
                                                    if (strcasecmp($row['resultado'], 'Revisar') === 0) {
                                                        $res_class = 'repuesto'; // amarillo/naranja
                                                    } elseif (strcasecmp($row['resultado'], 'Rechazado') === 0) {
                                                        $res_class = 'danger'; // rojo (custom class needed or use style inline)
                                                    }
                                                    
                                                    $style_inline = '';
                                                    if ($res_class === 'danger') {
                                                        $style_inline = 'background-color: var(--color-danger-soft); color: var(--color-danger); border: 1px solid rgba(239, 68, 68, 0.2);';
                                                    }
                                                ?>
                                                <span class="badge badge-category <?php echo $res_class; ?>" style="<?php echo $style_inline; ?>">
                                                    <?php echo htmlspecialchars($row['resultado']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <!-- Fila Desplegable de Detalles -->
                                        <tr id="details-row-<?php echo $index; ?>" class="details-row">
                                            <td colspan="9">
                                                <div class="details-box">
                                                    <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 10px; color: var(--color-navy-dark);">
                                                        🔍 Detalle de Anomalías y Discrepancias Detectadas
                                                    </h4>
                                                    <?php if (empty($anomalias)): ?>
                                                        <p style="font-size: 0.85rem; color: var(--color-success); font-weight: 500;">
                                                            ✅ Esta factura está alineada al 100% con el tarifario de referencia. No se hallaron discrepancias.
                                                        </p>
                                                    <?php else: ?>
                                                        <div class="table-container" style="background-color: #ffffff; border-color: #cbd5e1; max-height: none;">
                                                            <table style="font-size: 0.8rem;">
                                                                <thead>
                                                                    <tr style="background-color: #f1f5f9;">
                                                                        <th>Código</th>
                                                                        <th>Descripción</th>
                                                                        <th>Cantidad</th>
                                                                        <th>Precio Unit. Facturado</th>
                                                                        <th>Precio Unit. Acordado</th>
                                                                        <th>Desviación</th>
                                                                        <th>Tipo de Error</th>
                                                                        <th>Detalles del Hallazgo</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($anomalias as $anom): ?>
                                                                        <tr>
                                                                            <td><span class="badge badge-code"><?php echo htmlspecialchars($anom['codigo']); ?></span></td>
                                                                            <td><?php echo htmlspecialchars($anom['descripcion']); ?></td>
                                                                            <td><?php echo $anom['cantidad']; ?> <span style="font-size: 0.75em; text-transform: lowercase; color: var(--text-muted);"><?php echo htmlspecialchars($anom['unidad']); ?></span></td>
                                                                            <td class="price">$<?php echo number_format($anom['precio_facturado'], 2); ?></td>
                                                                            <td class="price" style="color: var(--color-accent-blue);">$<?php echo number_format($anom['precio_acordado'], 2); ?></td>
                                                                            <td class="price" style="color: var(--color-danger); font-weight: 700;">
                                                                                $<?php echo number_format(($anom['subtotal_facturado'] - $anom['subtotal_correcto']), 2); ?>
                                                                            </td>
                                                                            <td>
                                                                                <span class="badge" style="background-color: var(--color-danger); color: #ffffff; padding: 1px 4px; font-size: 0.7rem;">
                                                                                    <?php echo htmlspecialchars($anom['tipo_error']); ?>
                                                                                </span>
                                                                            </td>
                                                                            <td><?php echo htmlspecialchars($anom['detalles']); ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <div class="text-center margin-top-md" style="margin-bottom: 20px;">
                <a href="index.php" class="btn btn-primary btn-lg">🔍 Iniciar Nueva Auditoría</a>
            </div>
        </main>

        <footer class="main-footer">
            <p>&copy; 2026 Auditor Agéntico de Facturación de Siniestros. Hackathon Edition.</p>
        </footer>
    </div>

    <script>
        function toggleDetails(index) {
            const detailsRow = document.getElementById('details-row-' + index);
            if (detailsRow) {
                detailsRow.classList.toggle('active');
            }
        }
    </script>
</body>
</html>
