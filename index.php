<?php
/**
 * PANTALLA PRINCIPAL: SELECCIÓN DE FACTURA Y TARIFARIO
 * Auditor Agéntico de Facturación de Siniestros
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/notion.php';

$tarifario = [];
$error_notion = null;

// Intentar cargar el tarifario desde Notion
try {
    $tarifario = notion_get_tarifario();
} catch (Exception $e) {
    $error_notion = $e->getMessage();
    // Fallback de seguridad al mock en caso de excepción
    $tarifario = MOCK_TARIFARIO;
}

// Calcular la cantidad de ítems
$tarifario_count = count($tarifario);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditor Agéntico de Facturación de Siniestros</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <!-- Encabezado Principal -->
        <header class="main-header animate-fade-in">
            <div class="header-logo">
                <span class="logo-icon">🛡️</span>
                <div class="logo-text">
                    <h1>Auditor Agéntico</h1>
                    <p>Facturación de Siniestros</p>
                </div>
            </div>
            
            <div class="header-nav">
                <a href="index.php" class="nav-link active">🔍 Nueva Auditoría</a>
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
            <!-- Mensaje de error amigable de Notion si existe -->
            <?php if ($error_notion !== null && !DEMO_MODE): ?>
                <div class="alert alert-danger animate-fade-in">
                    <span class="alert-icon">⚠️</span>
                    <div>
                        <strong>Error al conectar con Notion:</strong> <?php echo htmlspecialchars($error_notion); ?>. 
                        <br><span style="font-size: 0.85em;">El sistema ha activado el <strong>modo de contingencia local (datos mock)</strong> para que la aplicación siga funcionando.</span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (DEMO_MODE): ?>
                <div class="alert alert-info animate-fade-in">
                    <span class="alert-icon">💡</span>
                    <div>
                        <strong>Presentación en Hackathon (Modo Demo):</strong> Las credenciales de API no están configuradas. 
                        El sistema operará con bases de datos y respuestas de IA simuladas (mock) para garantizar la fluidez de la presentación.
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid-2col">
                <!-- Columna Izquierda: Selección de Facturas -->
                <section class="card animate-fade-in" style="animation-delay: 0.1s;">
                    <div class="card-header">
                        <h2>Facturas de Taller Recibidas</h2>
                        <p>Selecciona una de las facturas enviadas por los talleres mecánicos para iniciar la auditoría automática con IA.</p>
                    </div>
                    <div class="card-body">
                        <div class="invoices-selector">
                            <?php foreach (FACTURAS_EJEMPLO as $key => $factura): ?>
                                <?php 
                                    // Calcular el total de la factura
                                    $total_fac = 0;
                                    foreach ($factura['items'] as $item) {
                                        $total_fac += $item['cantidad'] * $item['precio_unitario'];
                                    }
                                ?>
                                <div class="invoice-option" data-invoice="<?php echo htmlspecialchars(json_encode($factura)); ?>">
                                    <input type="radio" name="invoice_id_radio" value="<?php echo $key; ?>" class="hidden-radio">
                                    <div class="invoice-option-header">
                                        <span class="invoice-option-title"><?php echo htmlspecialchars($factura['id']); ?></span>
                                        <span class="price">$<?php echo number_format($total_fac, 2); ?></span>
                                    </div>
                                    <div class="invoice-option-taller">🏢 Taller: <?php echo htmlspecialchars($factura['taller']); ?></div>
                                    <div style="font-size: 0.8em; color: var(--text-secondary); margin-top: 5px;">📅 Fecha: <?php echo htmlspecialchars($factura['fecha']); ?></div>
                                    <div class="invoice-option-error" style="margin-top: 10px;">
                                        <strong>Falla de control sembrada:</strong> <?php echo htmlspecialchars($factura['descripcion_error']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Visor de Factura Seleccionada -->
                        <div class="active-invoice-viewer">
                            <div class="viewer-header">
                                <span class="viewer-title">Detalle de Factura Seleccionada</span>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">Vista Previa</span>
                            </div>
                            <div id="active-invoice-items" class="viewer-items">
                                <p style="color: var(--text-muted); text-align: center; font-size: 0.9rem; padding: 20px 0;">
                                    Ninguna factura seleccionada. Elige una de arriba.
                                </p>
                            </div>
                            <div class="viewer-total-row">
                                <span>Total Facturado:</span>
                                <span id="active-invoice-total">$0.00</span>
                            </div>
                        </div>

                        <!-- Formulario de Envío a Auditoría -->
                        <form id="audit-form" method="POST" action="auditar.php">
                            <input type="hidden" name="invoice_id" id="selected-invoice-id" value="">
                            <button type="submit" id="btn-auditar" class="btn btn-primary btn-block btn-lg" disabled>
                                🔍 Selecciona una factura
                            </button>
                        </form>
                    </div>
                </section>

                <!-- Columna Derecha: Listado de Tarifario Acordado -->
                <section class="card animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <h2>Tarifario Acordado (Notion DB)</h2>
                            <p>Lista de códigos y precios máximos negociados con la red de talleres.</p>
                        </div>
                        <input type="text" id="tarifario-search" placeholder="Filtrar tarifario..." class="btn btn-secondary" style="font-weight: normal; font-size: 0.85rem; padding: 6px 12px; margin: 0; width: 180px;">
                    </div>
                    <div class="card-body" style="padding-top: 15px;">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Descripción</th>
                                        <th>Categoría</th>
                                        <th>Precio Acordado</th>
                                        <th>Unidad</th>
                                    </tr>
                                </thead>
                                <tbody id="tarifario-table-body">
                                    <?php foreach ($tarifario as $item): ?>
                                        <tr>
                                            <td><span class="badge badge-code"><?php echo htmlspecialchars($item['codigo']); ?></span></td>
                                            <td style="font-weight: 500; color: var(--color-navy-light);"><?php echo htmlspecialchars($item['descripcion']); ?></td>
                                            <td>
                                                <span class="badge badge-category <?php echo strtolower(str_replace(' ', '-', $item['categoria'])); ?>">
                                                    <?php echo htmlspecialchars($item['categoria']); ?>
                                                </span>
                                            </td>
                                            <td class="price">$<?php echo number_format($item['precio'], 2); ?></td>
                                            <td style="text-transform: lowercase; font-style: italic; color: var(--text-muted);"><?php echo htmlspecialchars($item['unidad']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top: 15px; font-size: 0.8rem; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center;">
                            <span>Total de ítems cargados: <strong><?php echo $tarifario_count; ?></strong></span>
                            <?php if (DEMO_MODE): ?>
                                <span style="color: var(--color-accent-blue);">ℹ️ Utilizando base de datos local simulada</span>
                            <?php else: ?>
                                <a href="cargar_tarifario.php" style="font-weight: 600;">⚙️ Administrar base de datos</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <!-- Overlay del Cargador de Auditoría (Holográfico Scanner) -->
        <div id="audit-loader" class="audit-loader-overlay">
            <div class="scanner-box">
                <div class="scanner-line"></div>
                <div class="scanner-grid"></div>
            </div>
            <div class="loader-text">🤖 Iniciando Auditoría Agéntica...</div>
            <div id="loader-subtext-msgs" class="loader-subtext"><strong>Iniciando agente...</strong></div>
        </div>

        <footer class="main-footer">
            <p>&copy; 2026 Auditor Agéntico de Facturación de Siniestros. Hackathon Edition.</p>
        </footer>
    </div>

    <script src="assets/app.js"></script>
</body>
</html>
