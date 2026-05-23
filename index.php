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
                <!-- Columna Izquierda: Carga de Factura -->
                <section class="card animate-fade-in" style="animation-delay: 0.1s;">
                    <div class="card-header">
                        <h2>Auditar Factura del Taller</h2>
                        <p>Sube una imagen, un PDF o digita el detalle de la factura para iniciar la auditoría automática con IA.</p>
                    </div>
                    <div class="card-body">
                        <form id="audit-form" method="POST" action="auditar.php" enctype="multipart/form-data">
                            
                            <div class="upload-zone" style="background-color: #f8fafc; border: 2px dashed var(--color-accent-blue); border-radius: var(--border-radius-md); padding: 30px 20px; transition: all 0.3s ease; text-align: center; margin-bottom: 20px;">
                                <div style="margin-bottom: 15px;">
                                    <span style="font-size: 3rem;">📄</span>
                                    <h3 style="margin-top: 10px; font-weight: 600; color: var(--color-navy-dark);">Subir Factura</h3>
                                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 15px;">Formatos soportados: PDF, JPG, PNG (Max 5MB)</p>
                                </div>
                                <input type="file" name="invoice_file" id="invoice_file" accept=".pdf,image/png,image/jpeg,image/jpg" style="max-width: 100%; margin: 0 auto; display: block;">
                            </div>

                            <div style="text-align: center; margin: 20px 0; color: var(--text-muted); font-weight: 600; font-size: 0.9rem;">
                                — O EN SU LUGAR —
                            </div>

                            <div class="manual-input-zone" style="margin-bottom: 25px;">
                                <label for="invoice_text" style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.95rem; color: var(--color-navy-dark);">Digitar datos manualmente:</label>
                                <textarea name="invoice_text" id="invoice_text" rows="5" placeholder="Ej: Taller Norte, Factura #123. Cambio de pastillas freno delanteras, 1 unidad, $65.00..." style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); font-family: inherit; resize: vertical; color: var(--text-primary);"></textarea>
                            </div>

                            <button type="submit" id="btn-auditar" class="btn btn-primary btn-block btn-lg">
                                🔍 Iniciar Auditoría Agéntica
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
