<?php
/**
 * SCRIPT PARA POBLAR EL TARIFARIO EN NOTION
 * Auditor Agéntico de Facturación de Siniestros
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/notion.php';

$mensaje = "";
$detalles_carga = [];
$cargado = false;

// Procesar carga de tarifario cuando se envíe el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['poblar'])) {
    if (DEMO_MODE) {
        $mensaje = "El sistema está corriendo en MODO DEMO. No es necesario poblar Notion.";
    } else {
        try {
            $tarifario_ejemplo = MOCK_TARIFARIO;
            $exitos = 0;
            $errores = 0;

            foreach ($tarifario_ejemplo as $item) {
                try {
                    $res = notion_insert_tarifario_item(
                        $item['codigo'],
                        $item['descripcion'],
                        $item['categoria'],
                        $item['precio'],
                        $item['unidad']
                    );
                    if ($res) {
                        $detalles_carga[] = ['codigo' => $item['codigo'], 'estado' => 'success', 'msg' => 'Insertado con éxito'];
                        $exitos++;
                    } else {
                        $detalles_carga[] = ['codigo' => $item['codigo'], 'estado' => 'error', 'msg' => 'Fallo al insertar (respuesta vacía)'];
                        $errores++;
                    }
                } catch (Exception $e) {
                    $detalles_carga[] = ['codigo' => $item['codigo'], 'estado' => 'error', 'msg' => $e->getMessage()];
                    $errores++;
                }
                // Pequeña pausa para no saturar el rate limit de Notion (3 peticiones por segundo recomendado)
                usleep(300000); // 300 ms
            }

            $cargado = true;
            if ($errores === 0) {
                $mensaje = "¡Tarifario inicial cargado con éxito! Se insertaron {$exitos} ítems en Notion.";
            } else {
                $mensaje = "Proceso terminado con algunos inconvenientes: {$exitos} insertados, {$errores} errores.";
            }

        } catch (Exception $e) {
            $mensaje = "Error crítico durante la carga: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poblar Tarifario Notion - Auditor Agéntico</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <!-- Encabezado -->
        <header class="main-header">
            <div class="header-logo">
                <span class="logo-icon">🛡️</span>
                <div class="logo-text">
                    <h1>Auditor Agéntico</h1>
                    <p>Facturación de Siniestros</p>
                </div>
            </div>
            <div class="connection-badge <?php echo DEMO_MODE ? 'demo' : 'connected'; ?>">
                <span class="badge-dot"></span>
                <span><?php echo DEMO_MODE ? 'Modo Demo Activo (Mock)' : 'Notion Conectado'; ?></span>
            </div>
        </header>

        <main class="main-content">
            <section class="card setup-card animate-fade-in">
                <div class="card-header">
                    <h2>Poblar Tarifario en Notion</h2>
                    <p>Este script te permite poblar automáticamente tu base de datos de Notion con los 15 ítems de referencia acordados para la demo de forma masiva.</p>
                </div>

                <div class="card-body">
                    <?php if (!empty($mensaje)): ?>
                        <div class="alert <?php echo $cargado && !isset($errores) ? 'alert-success' : 'alert-info'; ?>">
                            <span class="alert-icon">ℹ️</span>
                            <p><?php echo htmlspecialchars($mensaje); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (DEMO_MODE): ?>
                        <div class="demo-notice">
                            <h3>⚠️ Modo Demo Detectado</h3>
                            <p>Las credenciales en el archivo <code>.env</code> están incompletas o vacías. El sistema está simulando toda la base de datos de manera local.</p>
                            <p><strong>No necesitas realizar esta carga en Notion.</strong> Si deseas probar la conexión real, por favor edita el archivo <code>.env</code> con tus claves.</p>
                        </div>
                    <?php else: ?>
                        <div class="database-schema-preview">
                            <h3>Datos de Ejemplo a Cargar (15 ítems):</h3>
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
                                    <tbody>
                                        <?php foreach (MOCK_TARIFARIO as $item): ?>
                                            <tr>
                                                <td><span class="badge badge-code"><?php echo htmlspecialchars($item['codigo']); ?></span></td>
                                                <td><?php echo htmlspecialchars($item['descripcion']); ?></td>
                                                <td>
                                                    <span class="badge badge-category <?php echo strtolower(str_replace(' ', '-', $item['categoria'])); ?>">
                                                        <?php echo htmlspecialchars($item['categoria']); ?>
                                                    </span>
                                                </td>
                                                <td class="price">$<?php echo number_format($item['precio'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($item['unidad']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if (!$cargado): ?>
                            <form method="POST" action="" class="text-center margin-top-md">
                                <button type="submit" name="poblar" class="btn btn-primary btn-lg">
                                    🚀 Poblar Base de Datos en Notion Now
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($detalles_carga)): ?>
                        <div class="logs-container margin-top-md">
                            <h3>Registro de inserción:</h3>
                            <ul class="logs-list">
                                <?php foreach ($detalles_carga as $log): ?>
                                    <li class="log-item <?php echo $log['estado']; ?>">
                                        <span class="log-code"><?php echo htmlspecialchars($log['codigo']); ?></span>:
                                        <span class="log-status"><?php echo $log['estado'] === 'success' ? '✔️' : '❌'; ?></span>
                                        <span class="log-msg"><?php echo htmlspecialchars($log['msg']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="action-footer text-center margin-top-md">
                        <a href="index.php" class="btn btn-secondary">⬅️ Volver a la pantalla principal</a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="main-footer">
            <p>&copy; 2026 Auditor Agéntico de Facturación de Siniestros. Hackathon Edition.</p>
        </footer>
    </div>
</body>
</html>
