<?php
/**
 * CONTROLADOR Y DASHBOARD DE RESULTADOS DE AUDITORÍA
 * Auditor Agéntico de Facturación de Siniestros
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/notion.php';
require_once __DIR__ . '/lib/gemini.php';

// Validar que se reciba un archivo o texto
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$invoiceData = null;

// Prioridad 1: Archivo subido
if (isset($_FILES['invoice_file']) && $_FILES['invoice_file']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['invoice_file']['tmp_name'];
    $mimeType = $_FILES['invoice_file']['type'];
    $fileData = file_get_contents($fileTmpPath);
    
    $invoiceData = [
        'type' => 'file',
        'mime' => $mimeType,
        'base64' => base64_encode($fileData)
    ];
} 
// Prioridad 2: Texto digitado
elseif (!empty($_POST['invoice_text'])) {
    $invoiceData = [
        'type' => 'text',
        'content' => trim($_POST['invoice_text'])
    ];
} 
else {
    die("Error: Debes subir un archivo o digitar la factura.");
}

$audit_result = null;
$error_ia = null;
$error_notion_save = null;
$saved_in_notion = false;

// 1. Obtener tarifario de referencia
try {
    $tarifario = notion_get_tarifario();
} catch (Exception $e) {
    // Si Notion falla al traer el tarifario, usamos el Mock local como contingencia
    $tarifario = MOCK_TARIFARIO;
}

// 2. Ejecutar Auditoría con la IA (Google Gemini)
try {
    $audit_result = gemini_audit_invoice($invoiceData, $tarifario);
} catch (Exception $e) {
    $error_ia = $e->getMessage();
}

// 3. Guardar resultados en Notion si no hubo fallos en la IA
if ($audit_result !== null) {
    try {
        $saved_in_notion = notion_save_audit(
            $audit_result['id_factura'] ?? 'FACT-DESCONOCIDA',
            $audit_result['taller_nombre'] ?? 'Taller Desconocido',
            $audit_result['total_facturado'],
            $audit_result['total_correcto'],
            $audit_result['ahorro_detectado'],
            $audit_result['score_confianza'],
            $audit_result['resultado'],
            $audit_result['items']
        );
    } catch (Exception $e) {
        $error_notion_save = $e->getMessage();
        $saved_in_notion = false;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Auditoría Agéntica</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                <a href="historial.php" class="nav-link">📋 Historial de Auditorías</a>
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
            <!-- Si la IA falló por completo -->
            <?php if ($error_ia !== null): ?>
                <section class="card animate-fade-in">
                    <div class="card-header">
                        <h2 style="color: var(--color-danger);">❌ Error en el análisis de IA</h2>
                    </div>
                    <div class="card-body text-center" style="padding: 40px 20px;">
                        <div class="alert alert-danger" style="display: inline-flex; text-align: left;">
                            <span class="alert-icon">⚠️</span>
                            <div>
                                <strong>La API de Google Gemini ha fallado:</strong><br>
                                <code><?php echo htmlspecialchars($error_ia); ?></code>
                            </div>
                        </div>
                        <p class="margin-top-md" style="color: var(--text-secondary);">
                            Por favor verifica que la clave <code>GEMINI_API_KEY</code> en tu archivo <code>.env</code> sea correcta y tenga saldo disponible.
                        </p>
                        <div class="margin-top-md">
                            <a href="index.php" class="btn btn-primary">⬅️ Regresar e intentar de nuevo</a>
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <!-- Auditoría Exitosa -->
                <div class="results-dashboard">
                    <!-- Fila Superior de Métricas -->
                    <div class="metrics-row animate-fade-in" style="animation-delay: 0.05s;">
                        <div class="metric-card">
                            <span class="metric-label">Factura Auditada</span>
                            <span class="metric-val" style="font-size: 1.5rem; color: var(--color-navy-light);"><?php echo htmlspecialchars($audit_result['id_factura'] ?? 'FACT-DESCONOCIDA'); ?></span>
                            <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 5px;">🏢 <?php echo htmlspecialchars($audit_result['taller_nombre'] ?? 'Taller Desconocido'); ?></span>
                        </div>

                        <div class="metric-card">
                            <span class="metric-label">Total Facturado</span>
                            <span class="metric-val">$<?php echo number_format($audit_result['total_facturado'], 2); ?></span>
                        </div>

                        <div class="metric-card">
                            <span class="metric-label">Total Correcto según Tarifario</span>
                            <span class="metric-val" style="color: var(--color-accent-blue);">$<?php echo number_format($audit_result['total_correcto'], 2); ?></span>
                        </div>

                        <div class="metric-card savings-highlight">
                            <span class="metric-label">Ahorro Detectado</span>
                            <span class="metric-val savings-amount">$<?php echo number_format($audit_result['ahorro_detectado'], 2); ?></span>
                        </div>
                    </div>

                    <!-- Fila Intermedia: Decisión del Agente y Score de Confianza -->
                    <div class="grid-2col animate-fade-in" style="animation-delay: 0.1s;">
                        <!-- Decisión Destacada -->
                        <?php 
                            $resultado = $audit_result['resultado'];
                            $alert_class = 'decision-alert-revisar';
                            $alert_icon = '⚠️';
                            $alert_title = 'Auditoría Pendiente de Revisión';
                            
                            if (strcasecmp($resultado, 'Aprobado') === 0) {
                                $alert_class = 'decision-alert-aprobado';
                                $alert_icon = '✅';
                                $alert_title = 'Factura Aprobada para Pago';
                            } elseif (strcasecmp($resultado, 'Rechazado') === 0) {
                                $alert_class = 'decision-alert-rechazado';
                                $alert_icon = '🚫';
                                $alert_title = 'Factura Rechazada';
                            }
                        ?>
                        <div class="decision-alert <?php echo $alert_class; ?>">
                            <div class="decision-icon"><?php echo $alert_icon; ?></div>
                            <div class="decision-body">
                                <h3>Dictamen de la IA: <?php echo htmlspecialchars($resultado); ?> (<?php echo $alert_title; ?>)</h3>
                                <p><?php echo htmlspecialchars($audit_result['recomendacion']); ?></p>
                            </div>
                        </div>

                        <!-- Score de Confianza -->
                        <div class="card" style="padding: 20px 25px; flex-direction: row; align-items: center; justify-content: space-between;">
                            <div>
                                <h3 style="font-size: 1.1rem; font-weight: 700;">Score de Confianza de Auditoría</h3>
                                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">Fiabilidad calculada para la detección de anomalías.</p>
                            </div>
                            <div class="text-center" style="position: relative; width: 90px; height: 90px; display: flex; justify-content: center; align-items: center;">
                                <?php 
                                    $score = (int)$audit_result['score_confianza'];
                                    $score_color = 'var(--color-success)';
                                    if ($score < 70) {
                                        $score_color = 'var(--color-danger)';
                                    } elseif ($score < 90) {
                                        $score_color = 'var(--color-warning)';
                                    }
                                ?>
                                <svg width="90" height="90" viewBox="0 0 36 36" style="transform: rotate(-90deg);">
                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e2e8f0" stroke-width="3" />
                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="<?php echo $score_color; ?>" stroke-dasharray="<?php echo $score; ?>, 100" stroke-width="3.2" stroke-linecap="round" />
                                </svg>
                                <span style="position: absolute; font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 700; color: var(--color-navy-dark);"><?php echo $score; ?>%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Alertas de Guardado en Notion -->
                    <?php if ($error_notion_save !== null): ?>
                        <div class="alert alert-danger animate-fade-in" style="margin-bottom: 0;">
                            <span class="alert-icon">⚠️</span>
                            <div>
                                <strong>La auditoría no pudo ser grabada en el Historial de Notion:</strong> <?php echo htmlspecialchars($error_notion_save); ?>.
                                <br><span style="font-size: 0.85em;">El análisis se muestra completo a continuación, pero no quedará registrado en el historial en la nube.</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="animate-fade-in text-right" style="text-align: right;">
                            <span class="notion-save-status <?php echo $saved_in_notion ? 'saved' : 'not-saved'; ?>">
                                <span class="badge-dot"></span>
                                <span><?php echo $saved_in_notion ? 'Auditoría Guardada en Notion Historial DB ✔️' : 'Modo Demo: Auditoría Simulada en Sesión Local'; ?></span>
                            </span>
                        </div>
                    <?php endif; ?>

                    <!-- Desglose por Ítem en Tabla -->
                    <section class="card animate-fade-in" style="animation-delay: 0.2s;">
                        <div class="card-header">
                            <h2>Desglose Ítem por Ítem Auditado</h2>
                            <p>Comparación analítica entre los ítems facturados por el taller y el tarifario autorizado.</p>
                        </div>
                        <div class="card-body" style="padding-top: 15px;">
                            <div class="table-container" style="max-height: none;">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Estado</th>
                                            <th>Código</th>
                                            <th>Descripción</th>
                                            <th>Cantidad</th>
                                            <th>Precio Unit. Facturado</th>
                                            <th>Precio Unit. Acordado</th>
                                            <th>Subtotal Facturado</th>
                                            <th>Subtotal Correcto</th>
                                            <th>Discrepancia / Comentarios</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($audit_result['items'] as $item): ?>
                                            <?php 
                                                $row_class = '';
                                                if ($item['estado'] === 'rojo') {
                                                    $row_class = 'style="background-color: var(--color-danger-soft);"';
                                                } elseif ($item['estado'] === 'amarillo') {
                                                    $row_class = 'style="background-color: var(--color-warning-soft);"';
                                                }
                                            ?>
                                            <tr <?php echo $row_class; ?>>
                                                <td>
                                                    <span class="status-indicator <?php echo htmlspecialchars($item['estado']); ?>">
                                                        <span class="dot <?php echo htmlspecialchars($item['estado']); ?>"></span>
                                                    </span>
                                                </td>
                                                <td><span class="badge badge-code"><?php echo htmlspecialchars($item['codigo']); ?></span></td>
                                                <td style="font-weight: 500; color: var(--color-navy-dark);"><?php echo htmlspecialchars($item['descripcion']); ?></td>
                                                <td><?php echo $item['cantidad']; ?> <span style="font-size: 0.8em; color: var(--text-muted); text-transform: lowercase;"><?php echo htmlspecialchars($item['unidad']); ?></span></td>
                                                <td class="price">$<?php echo number_format($item['precio_facturado'], 2); ?></td>
                                                <td class="price" style="color: var(--color-accent-blue);">$<?php echo number_format($item['precio_acordado'], 2); ?></td>
                                                <td class="price">$<?php echo number_format($item['subtotal_facturado'], 2); ?></td>
                                                <td class="price" style="color: var(--color-success-hover); font-weight: 700;">$<?php echo number_format($item['subtotal_correcto'], 2); ?></td>
                                                <td>
                                                    <?php if ($item['tipo_error'] !== null): ?>
                                                        <span class="badge" style="background-color: var(--color-danger); color: #ffffff; padding: 2px 6px; font-size: 0.7rem; margin-right: 5px;">
                                                            <?php echo htmlspecialchars($item['tipo_error']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span style="font-size: 0.85rem; font-weight: 500;"><?php echo htmlspecialchars($item['detalles']); ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <!-- Acciones Finales -->
                    <div class="animate-fade-in text-center margin-top-md" style="margin-bottom: 20px;">
                        <a href="index.php" class="btn btn-secondary btn-lg" style="margin-right: 15px;">🔍 Auditar Otra Factura</a>
                        <a href="historial.php" class="btn btn-primary btn-lg">📋 Ver Historial de Auditorías</a>
                    </div>
                </div>
            <?php endif; ?>
        </main>

        <footer class="main-footer">
            <p>&copy; 2026 Auditor Agéntico de Facturación de Siniestros. Hackathon Edition.</p>
        </footer>
    </div>
</body>
</html>
