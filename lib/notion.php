<?php
/**
 * LIBRERÍA DE INTEGRACIÓN CON NOTION API
 * Auditor Agéntico de Facturación de Siniestros
 */

require_once __DIR__ . '/../config.php';

// Iniciar sesión para soportar persistencia en el modo Demo
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Realiza una petición cURL genérica a la API de Notion
 */
function notion_api_request($endpoint, $method = 'GET', $body = null) {
    $url = "https://api.notion.com/v1" . $endpoint;
    $ch = curl_init();

    $headers = [
        'Authorization: Bearer ' . NOTION_TOKEN,
        'Notion-Version: 2022-06-28',
        'Content-Type: application/json'
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);


    if ($error) {
        throw new Exception("Error cURL al contactar a Notion: " . $error);
    }

    $data = json_decode($response, true);
    
    if ($httpCode >= 400) {
        $msg = isset($data['message']) ? $data['message'] : 'Error desconocido';
        throw new Exception("Notion API Error [Status {$httpCode}]: " . $msg);
    }

    return $data;
}

/**
 * Obtiene la lista de ítems del Tarifario acordado
 */
function notion_get_tarifario() {
    if (DEMO_MODE) {
        return MOCK_TARIFARIO;
    }

    try {
        $endpoint = "/databases/" . NOTION_TARIFARIO_DB_ID . "/query";
        $response = notion_api_request($endpoint, 'POST', new stdClass());
        
        $tarifario = [];
        if (!empty($response['results'])) {
            foreach ($response['results'] as $page) {
                $props = $page['properties'];
                
                // Extraer Código (título)
                $codigo = '';
                if (!empty($props['Código']['title'])) {
                    $codigo = $props['Código']['title'][0]['plain_text'];
                }
                
                // Extraer Descripción (rich_text)
                $descripcion = '';
                if (!empty($props['Descripción']['rich_text'])) {
                    $descripcion = $props['Descripción']['rich_text'][0]['plain_text'];
                }
                
                // Extraer Categoría (select)
                $categoria = '';
                if (!empty($props['Categoría']['select'])) {
                    $categoria = $props['Categoría']['select']['name'];
                }
                
                // Extraer Precio Acordado (number)
                $precio = 0.0;
                if (isset($props['Precio acordado']['number'])) {
                    $precio = (float)$props['Precio acordado']['number'];
                }
                
                // Extraer Unidad (rich_text o select)
                $unidad = '';
                if (!empty($props['Unidad']['rich_text'])) {
                    $unidad = $props['Unidad']['rich_text'][0]['plain_text'];
                } elseif (!empty($props['Unidad']['select'])) {
                    $unidad = $props['Unidad']['select']['name'];
                }

                if (!empty($codigo)) {
                    $tarifario[] = [
                        'codigo' => $codigo,
                        'descripcion' => $descripcion,
                        'categoria' => $categoria,
                        'precio' => $precio,
                        'unidad' => $unidad
                    ];
                }
            }
        }
        
        // Si la base de datos real está vacía, retornar el mock de auxilio
        if (empty($tarifario)) {
            return MOCK_TARIFARIO;
        }

        return $tarifario;

    } catch (Exception $e) {
        // En caso de fallo con credenciales reales, loggear y caer a modo seguro mockeado
        error_log("Fallo cargando tarifario real de Notion: " . $e->getMessage());
        // Propagar el error o hacer fallback amigable. Dejemos que la UI muestre la alerta
        throw $e;
    }
}

/**
 * Guarda los resultados de una auditoría en la Base de Datos de Historial
 */
function notion_save_audit($invoiceId, $workshop, $totalInvoiced, $totalCorrect, $savings, $confidenceScore, $resultStatus, $discrepanciesArray) {
    $discrepanciesJson = json_encode($discrepanciesArray, JSON_UNESCAPED_UNICODE);

    if (DEMO_MODE) {
        // En modo demo, guardamos en la sesión para dar dinamismo a la interfaz
        if (!isset($_SESSION['mock_historial'])) {
            $_SESSION['mock_historial'] = MOCK_HISTORIAL;
        }
        
        // Verificar si ya existe para no duplicar en múltiples pruebas seguidas de la misma factura
        foreach ($_SESSION['mock_historial'] as $key => $item) {
            if ($item['id_factura'] === $invoiceId) {
                // Actualizarlo
                $_SESSION['mock_historial'][$key] = [
                    'id_factura' => $invoiceId,
                    'taller' => $workshop,
                    'fecha' => date('c'),
                    'total_facturado' => (float)$totalInvoiced,
                    'total_correcto' => (float)$totalCorrect,
                    'ahorro_detectado' => (float)$savings,
                    'score_confianza' => (int)$confidenceScore,
                    'resultado' => $resultStatus,
                    'discrepancias' => $discrepanciesJson
                ];
                return true;
            }
        }

        // Agregar nuevo registro al inicio
        array_unshift($_SESSION['mock_historial'], [
            'id_factura' => $invoiceId,
            'taller' => $workshop,
            'fecha' => date('c'),
            'total_facturado' => (float)$totalInvoiced,
            'total_correcto' => (float)$totalCorrect,
            'ahorro_detectado' => (float)$savings,
            'score_confianza' => (int)$confidenceScore,
            'resultado' => $resultStatus,
            'discrepancias' => $discrepanciesJson
        ]);
        return true;
    }

    // Petición Real a Notion
    $body = [
        'parent' => ['database_id' => NOTION_HISTORIAL_DB_ID],
        'properties' => [
            'ID Factura' => [
                'title' => [
                    [
                        'text' => [
                            'content' => $invoiceId
                        ]
                    ]
                ]
            ],
            'Taller' => [
                'rich_text' => [
                    [
                        'text' => [
                            'content' => $workshop
                        ]
                    ]
                ]
            ],
            'Fecha auditoría' => [
                'date' => [
                    'start' => date('c') // ISO 8601 (ej: 2026-05-22T10:15:30-05:00)
                ]
            ],
            'Total facturado' => [
                'number' => (float)$totalInvoiced
            ],
            'Total correcto según tarifario' => [
                'number' => (float)$totalCorrect
            ],
            'Ahorro detectado' => [
                'number' => (float)$savings
            ],
            'Score de confianza' => [
                'number' => (int)$confidenceScore
            ],
            'Resultado' => [
                'select' => [
                    'name' => $resultStatus
                ]
            ],
            'Discrepancias' => [
                'rich_text' => array_map(function($chunk) {
                    return [
                        'text' => [
                            'content' => $chunk
                        ]
                    ];
                }, str_split($discrepanciesJson, 2000))
            ]
        ]
    ];

    $response = notion_api_request('/pages', 'POST', $body);
    return !empty($response['id']);
}

/**
 * Obtiene el historial de auditorías
 */
function notion_get_historial() {
    if (DEMO_MODE) {
        if (!isset($_SESSION['mock_historial'])) {
            $_SESSION['mock_historial'] = MOCK_HISTORIAL;
        }
        return $_SESSION['mock_historial'];
    }

    try {
        $endpoint = "/databases/" . NOTION_HISTORIAL_DB_ID . "/query";
        $body = [
            'sorts' => [
                [
                    'property' => 'Fecha auditoría',
                    'direction' => 'descending'
                ]
            ]
        ];
        
        $response = notion_api_request($endpoint, 'POST', $body);
        $historial = [];
        
        if (!empty($response['results'])) {
            foreach ($response['results'] as $page) {
                $props = $page['properties'];
                
                $idFactura = !empty($props['ID Factura']['title']) ? $props['ID Factura']['title'][0]['plain_text'] : 'S/N';
                $taller = !empty($props['Taller']['rich_text']) ? $props['Taller']['rich_text'][0]['plain_text'] : 'Desconocido';
                
                $fecha = '';
                if (!empty($props['Fecha auditoría']['date'])) {
                    $fecha = $props['Fecha auditoría']['date']['start'];
                }
                
                $totalFacturado = isset($props['Total facturado']['number']) ? (float)$props['Total facturado']['number'] : 0.0;
                $totalCorrecto = isset($props['Total correcto según tarifario']['number']) ? (float)$props['Total correcto según tarifario']['number'] : 0.0;
                $ahorro = isset($props['Ahorro detectado']['number']) ? (float)$props['Ahorro detectado']['number'] : 0.0;
                $score = isset($props['Score de confianza']['number']) ? (int)$props['Score de confianza']['number'] : 0;
                
                $resultado = 'Revisar';
                if (!empty($props['Resultado']['select'])) {
                    $resultado = $props['Resultado']['select']['name'];
                }
                
                $discrepancias = '[]';
                if (!empty($props['Discrepancias']['rich_text'])) {
                    $discrepanciasText = '';
                    foreach ($props['Discrepancias']['rich_text'] as $rt) {
                        $discrepanciasText .= $rt['plain_text'];
                    }
                    if (!empty($discrepanciasText)) {
                        $discrepancias = $discrepanciasText;
                    }
                }
                
                $historial[] = [
                    'id_factura' => $idFactura,
                    'taller' => $taller,
                    'fecha' => $fecha,
                    'total_facturado' => $totalFacturado,
                    'total_correcto' => $totalCorrecto,
                    'ahorro_detectado' => $ahorro,
                    'score_confianza' => $score,
                    'resultado' => $resultado,
                    'discrepancias' => $discrepancias
                ];
            }
        }
        
        return $historial;
    } catch (Exception $e) {
        error_log("Fallo cargando historial real de Notion: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Inserta un ítem en la base de datos de tarifario (usado para inicialización en Notion)
 */
function notion_insert_tarifario_item($code, $description, $category, $price, $unit) {
    if (DEMO_MODE) {
        return false;
    }

    $body = [
        'parent' => ['database_id' => NOTION_TARIFARIO_DB_ID],
        'properties' => [
            'Código' => [
                'title' => [
                    [
                        'text' => [
                            'content' => $code
                        ]
                    ]
                ]
            ],
            'Descripción' => [
                'rich_text' => [
                    [
                        'text' => [
                            'content' => $description
                        ]
                    ]
                ]
            ],
            'Categoría' => [
                'select' => [
                    'name' => $category
                ]
            ],
            'Precio acordado' => [
                'number' => (float)$price
            ],
            'Unidad' => [
                'rich_text' => [
                    [
                        'text' => [
                            'content' => $unit
                        ]
                    ]
                ]
            ]
        ]
    ];

    $response = notion_api_request('/pages', 'POST', $body);
    return !empty($response['id']);
}
