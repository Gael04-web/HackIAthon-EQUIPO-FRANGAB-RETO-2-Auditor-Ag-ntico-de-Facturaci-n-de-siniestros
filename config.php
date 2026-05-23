<?php
/**
 * ARCHIVO DE CONFIGURACIÓN Y MODO DEMO
 * Auditor Agéntico de Facturación de Siniestros
 */

// 1. Cargar variables de entorno de un archivo .env si existe (útil en desarrollo local)
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Ignorar comentarios
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Helper para obtener variables de entorno con fallback
function get_env_var($key, $default = '') {
    $val = getenv($key);
    if ($val !== false) return $val;
    if (isset($_ENV[$key])) return $_ENV[$key];
    if (isset($_SERVER[$key])) return $_SERVER[$key];
    return $default;
}

// 2. Definición de Constantes
define('GEMINI_API_KEY', get_env_var('GEMINI_API_KEY', ''));
define('GEMINI_MODEL', get_env_var('GEMINI_MODEL', 'gemini-1.5-pro'));
define('NOTION_TOKEN', get_env_var('NOTION_TOKEN', ''));
define('NOTION_TARIFARIO_DB_ID', get_env_var('NOTION_TARIFARIO_DB_ID', ''));
define('NOTION_HISTORIAL_DB_ID', get_env_var('NOTION_HISTORIAL_DB_ID', ''));

// Determinar si corre en MODO DEMO (falta alguna credencial clave)
$isDemo = (empty(GEMINI_API_KEY) || empty(NOTION_TOKEN) || empty(NOTION_TARIFARIO_DB_ID) || empty(NOTION_HISTORIAL_DB_ID));
define('DEMO_MODE', $isDemo);

// 3. DATOS MOCK (Para presentación en Hackathon sin credenciales)

// 15 Ítems de Tarifario Acordado
$mock_tarifario = [
    ['codigo' => 'MO-001', 'descripcion' => 'Cambio de aceite de motor y filtro', 'categoria' => 'Mano de obra', 'precio' => 30.00, 'unidad' => 'hora'],
    ['codigo' => 'MO-002', 'descripcion' => 'Alineación de dirección y balanceo de 4 ruedas', 'categoria' => 'Mano de obra', 'precio' => 45.00, 'unidad' => 'unidad'],
    ['codigo' => 'MO-003', 'descripcion' => 'Mano de obra para reemplazo de pastillas de freno delanteras', 'categoria' => 'Mano de obra', 'precio' => 40.00, 'unidad' => 'hora'],
    ['codigo' => 'MO-004', 'descripcion' => 'Lectura de códigos de falla y diagnóstico computarizado', 'categoria' => 'Mano de obra', 'precio' => 35.00, 'unidad' => 'unidad'],
    ['codigo' => 'MO-005', 'descripcion' => 'Preparación y pintura de un panel de carrocería', 'categoria' => 'Mano de obra', 'precio' => 120.00, 'unidad' => 'unidad'],
    ['codigo' => 'RP-001', 'descripcion' => 'Juego de pastillas de freno delanteras de cerámica', 'categoria' => 'Repuesto', 'precio' => 65.00, 'unidad' => 'unidad'],
    ['codigo' => 'RP-002', 'descripcion' => 'Filtro de aceite sintético de alta eficiencia', 'categoria' => 'Repuesto', 'precio' => 15.00, 'unidad' => 'unidad'],
    ['codigo' => 'RP-003', 'descripcion' => 'Filtro de aire de motor de papel plisado', 'categoria' => 'Repuesto', 'precio' => 18.00, 'unidad' => 'unidad'],
    ['codigo' => 'RP-004', 'descripcion' => 'Disco de freno ventilado delantero', 'categoria' => 'Repuesto', 'precio' => 55.00, 'unidad' => 'unidad'],
    ['codigo' => 'RP-005', 'descripcion' => 'Amortiguador de suspensión delantera a gas', 'categoria' => 'Repuesto', 'precio' => 90.00, 'unidad' => 'unidad'],
    ['codigo' => 'IN-001', 'descripcion' => 'Aceite de motor totalmente sintético 5W30', 'categoria' => 'Insumo', 'precio' => 12.50, 'unidad' => 'litro'],
    ['codigo' => 'IN-002', 'descripcion' => 'Fluido de frenos de alta temperatura DOT 4', 'categoria' => 'Insumo', 'precio' => 8.00, 'unidad' => 'litro'],
    ['codigo' => 'IN-003', 'descripcion' => 'Refrigerante de larga duración pre-diluido al 50%', 'categoria' => 'Insumo', 'precio' => 10.00, 'unidad' => 'litro'],
    ['codigo' => 'IN-004', 'descripcion' => 'Limpia partes de frenos en aerosol para grasa y polvo', 'categoria' => 'Insumo', 'precio' => 6.00, 'unidad' => 'unidad'],
    ['codigo' => 'IN-005', 'descripcion' => 'Materiales menores de taller (grasas, lijas, arandelas)', 'categoria' => 'Insumo', 'precio' => 15.00, 'unidad' => 'unidad'],
];
define('MOCK_TARIFARIO', $mock_tarifario);

// 3 Facturas de Ejemplo precargadas
$facturas_ejemplo = [
    'factura_1024' => [
        'id' => 'FAC-1024',
        'taller' => 'Taller El Amigo',
        'fecha' => '2026-05-20',
        'descripcion_error' => 'Código IN-001 (Aceite Sintético) cobrado a precio inflado de $25.00/L en vez de $12.50/L. Ahorro potencial: $50.00.',
        'items' => [
            ['codigo' => 'MO-001', 'descripcion' => 'Cambio de aceite de motor y filtro', 'cantidad' => 1.5, 'unidad' => 'hora', 'precio_unitario' => 30.00],
            ['codigo' => 'RP-002', 'descripcion' => 'Filtro de aceite sintético de alta eficiencia', 'cantidad' => 1.0, 'unidad' => 'unidad', 'precio_unitario' => 15.00],
            ['codigo' => 'IN-001', 'descripcion' => 'Aceite de motor totalmente sintético 5W30', 'cantidad' => 4.0, 'unidad' => 'litro', 'precio_unitario' => 25.00], // INFLADO ($12.50 acordado)
            ['codigo' => 'IN-005', 'descripcion' => 'Materiales menores de taller', 'cantidad' => 1.0, 'unidad' => 'unidad', 'precio_unitario' => 15.00],
        ]
    ],
    'factura_2088' => [
        'id' => 'FAC-2088',
        'taller' => 'Taller Norte Auto',
        'fecha' => '2026-05-21',
        'descripcion_error' => 'Código RP-001 (Pastillas de Freno Delanteras) duplicado en dos líneas independientes de cobro. Ahorro potencial: $65.00.',
        'items' => [
            ['codigo' => 'MO-003', 'descripcion' => 'Mano de obra para reemplazo de pastillas de freno delanteras', 'cantidad' => 2.0, 'unidad' => 'hora', 'precio_unitario' => 40.00],
            ['codigo' => 'RP-001', 'descripcion' => 'Juego de pastillas de freno delanteras de cerámica', 'cantidad' => 1.0, 'unidad' => 'unidad', 'precio_unitario' => 65.00],
            ['codigo' => 'RP-001', 'descripcion' => 'Juego de pastillas de freno delanteras de cerámica', 'cantidad' => 1.0, 'unidad' => 'unidad', 'precio_unitario' => 65.00], // DUPLICADO
            ['codigo' => 'IN-004', 'descripcion' => 'Limpia partes de frenos en aerosol', 'cantidad' => 1.0, 'unidad' => 'unidad', 'precio_unitario' => 6.00],
        ]
    ],
    'factura_3150' => [
        'id' => 'FAC-3150',
        'taller' => 'Taller Car Express',
        'fecha' => '2026-05-22',
        'descripcion_error' => 'Código inexistente RP-099 (Amortiguador Premium) cobrado por fuera de convenio ($140.00 c/u). Ahorro potencial: $280.00.',
        'items' => [
            ['codigo' => 'MO-002', 'descripcion' => 'Alineación de dirección y balanceo de 4 ruedas', 'cantidad' => 1.0, 'unidad' => 'unidad', 'precio_unitario' => 45.00],
            ['codigo' => 'RP-099', 'descripcion' => 'Amortiguador Premium Reforzado', 'cantidad' => 2.0, 'unidad' => 'unidad', 'precio_unitario' => 140.00], // CÓDIGO INEXISTENTE
            ['codigo' => 'MO-005', 'descripcion' => 'Preparación y pintura de un panel de carrocería', 'cantidad' => 1.0, 'unidad' => 'unidad', 'precio_unitario' => 120.00],
        ]
    ],
];
define('FACTURAS_EJEMPLO', $facturas_ejemplo);

// Historial Inicial de Auditorías Mockeado
$mock_historial = [
    [
        'id_factura' => 'FAC-9011',
        'taller' => 'Taller Sur Mecánica',
        'fecha' => '2026-05-18T14:30:00Z',
        'total_facturado' => 350.00,
        'total_correcto' => 350.00,
        'ahorro_detectado' => 0.00,
        'score_confianza' => 100,
        'resultado' => 'Aprobado',
        'discrepancias' => '[]'
    ],
    [
        'id_factura' => 'FAC-8043',
        'taller' => 'Taller Oeste Motors',
        'fecha' => '2026-05-19T10:15:00Z',
        'total_facturado' => 520.00,
        'total_correcto' => 400.00,
        'ahorro_detectado' => 120.00,
        'score_confianza' => 95,
        'resultado' => 'Revisar',
        'discrepancias' => '[{"codigo":"MO-005","descripcion":"Preparación y pintura de un panel de carrocería","cantidad":1,"unidad":"unidad","precio_facturado":240,"precio_acordado":120,"subtotal_facturado":240,"subtotal_correcto":120,"estado":"rojo","tipo_error":"Precio Excedido","detalles":"El precio de pintura por panel cobrado ($240) duplica el precio acordado de $120."}]'
    ],
];
define('MOCK_HISTORIAL', $mock_historial);

// Auditoría Simulada de la IA para el Modo Demo
$mock_gemini_audits = [
    'FAC-1024' => [
        'total_facturado' => 175.00,
        'total_correcto' => 125.00,
        'ahorro_detectado' => 50.00,
        'score_confianza' => 98,
        'resultado' => 'Revisar',
        'recomendacion' => 'Se detectó una discrepancia de precio en el insumo IN-001 (Aceite de motor totalmente sintético 5W30). Se cobró a $25.00 por litro, cuando el precio acordado es de $12.50. Se recomienda retener el pago de los $50.00 cobrados en exceso o pedir refacturación.',
        'items' => [
            [
                'codigo' => 'MO-001',
                'descripcion' => 'Cambio de aceite de motor y filtro',
                'cantidad' => 1.5,
                'unidad' => 'hora',
                'precio_facturado' => 30.00,
                'precio_acordado' => 30.00,
                'subtotal_facturado' => 45.00,
                'subtotal_correcto' => 45.00,
                'estado' => 'verde',
                'tipo_error' => null,
                'detalles' => 'Cobro correcto de mano de obra según tarifario acordado.'
            ],
            [
                'codigo' => 'RP-002',
                'descripcion' => 'Filtro de aceite sintético de alta eficiencia',
                'cantidad' => 1.0,
                'unidad' => 'unidad',
                'precio_facturado' => 15.00,
                'precio_acordado' => 15.00,
                'subtotal_facturado' => 15.00,
                'subtotal_correcto' => 15.00,
                'estado' => 'verde',
                'tipo_error' => null,
                'detalles' => 'Cobro correcto de repuesto según tarifario acordado.'
            ],
            [
                'codigo' => 'IN-001',
                'descripcion' => 'Aceite de motor totalmente sintético 5W30',
                'cantidad' => 4.0,
                'unidad' => 'litro',
                'precio_facturado' => 25.00,
                'precio_acordado' => 12.50,
                'subtotal_facturado' => 100.00,
                'subtotal_correcto' => 50.00,
                'estado' => 'rojo',
                'tipo_error' => 'Precio Excedido',
                'detalles' => 'El precio unitario de $25.00 excede el precio acordado de $12.50 en el tarifario.'
            ],
            [
                'codigo' => 'IN-005',
                'descripcion' => 'Materiales menores de taller (grasas, lijas, arandelas)',
                'cantidad' => 1.0,
                'unidad' => 'unidad',
                'precio_facturado' => 15.00,
                'precio_acordado' => 15.00,
                'subtotal_facturado' => 15.00,
                'subtotal_correcto' => 15.00,
                'estado' => 'verde',
                'tipo_error' => null,
                'detalles' => 'Cobro correcto de insumos menores de taller.'
            ]
        ]
    ],
    'FAC-2088' => [
        'total_facturado' => 216.00,
        'total_correcto' => 151.00,
        'ahorro_detectado' => 65.00,
        'score_confianza' => 95,
        'resultado' => 'Revisar',
        'recomendacion' => 'Se identificó un cobro duplicado del repuesto RP-001 (Juego de pastillas de freno delanteras de cerámica). El taller incluyó dos líneas independientes cobrando la misma unidad para la misma reparación. Se recomienda descontar $65.00 correspondiente a la segunda línea del repuesto duplicado.',
        'items' => [
            [
                'codigo' => 'MO-003',
                'descripcion' => 'Mano de obra para reemplazo de pastillas de freno delanteras',
                'cantidad' => 2.0,
                'unidad' => 'hora',
                'precio_facturado' => 40.00,
                'precio_acordado' => 40.00,
                'subtotal_facturado' => 80.00,
                'subtotal_correcto' => 80.00,
                'estado' => 'verde',
                'tipo_error' => null,
                'detalles' => 'Mano de obra justificada e imputada correctamente.'
            ],
            [
                'codigo' => 'RP-001',
                'descripcion' => 'Juego de pastillas de freno delanteras de cerámica',
                'cantidad' => 1.0,
                'unidad' => 'unidad',
                'precio_facturado' => 65.00,
                'precio_acordado' => 65.00,
                'subtotal_facturado' => 65.00,
                'subtotal_correcto' => 65.00,
                'estado' => 'verde',
                'tipo_error' => null,
                'detalles' => 'Primera unidad del repuesto cargada de acuerdo al precio concertado.'
            ],
            [
                'codigo' => 'RP-001',
                'descripcion' => 'Juego de pastillas de freno delanteras de cerámica',
                'cantidad' => 1.0,
                'unidad' => 'unidad',
                'precio_facturado' => 65.00,
                'precio_acordado' => 65.00,
                'subtotal_facturado' => 65.00,
                'subtotal_correcto' => 0.00,
                'estado' => 'rojo',
                'tipo_error' => 'Cobro Duplicado',
                'detalles' => 'Cobro duplicado. El taller imputó dos veces el mismo juego de pastillas delanteras para un mismo servicio.'
            ],
            [
                'codigo' => 'IN-004',
                'descripcion' => 'Limpia partes de frenos en aerosol para grasa y polvo',
                'cantidad' => 1.0,
                'unidad' => 'unidad',
                'precio_facturado' => 6.00,
                'precio_acordado' => 6.00,
                'subtotal_facturado' => 6.00,
                'subtotal_correcto' => 6.00,
                'estado' => 'verde',
                'tipo_error' => null,
                'detalles' => 'Consumible cobrado según tarifario.'
            ]
        ]
    ],
    'FAC-3150' => [
        'total_facturado' => 445.00,
        'total_correcto' => 165.00,
        'ahorro_detectado' => 280.00,
        'score_confianza' => 90,
        'resultado' => 'Rechazado',
        'recomendacion' => 'Se detectó un repuesto no convenido (Código RP-099 - Amortiguador Premium Reforzado, 2 unidades por $280.00) que no existe en el tarifario autorizado por la aseguradora. La factura ha sido rechazada para revisión técnica o desestimación del repuesto no homologado.',
        'items' => [
            [
                'codigo' => 'MO-002',
                'descripcion' => 'Alineación de dirección y balanceo de 4 ruedas',
                'cantidad' => 1.0,
                'unidad' => 'unidad',
                'precio_facturado' => 45.00,
                'precio_acordado' => 45.00,
                'subtotal_facturado' => 45.00,
                'subtotal_correcto' => 45.00,
                'estado' => 'verde',
                'tipo_error' => null,
                'detalles' => 'Servicio de alineación e imputación correcta.'
            ],
            [
                'codigo' => 'RP-099',
                'descripcion' => 'Amortiguador Premium Reforzado',
                'cantidad' => 2.0,
                'unidad' => 'unidad',
                'precio_facturado' => 140.00,
                'precio_acordado' => 0.00,
                'subtotal_facturado' => 280.00,
                'subtotal_correcto' => 0.00,
                'estado' => 'rojo',
                'tipo_error' => 'Código Inexistente',
                'detalles' => 'El código de repuesto RP-099 no está registrado en el tarifario de referencia acordado.'
            ],
            [
                'codigo' => 'MO-005',
                'descripcion' => 'Preparación y pintura de un panel de carrocería',
                'cantidad' => 1.0,
                'unidad' => 'unidad',
                'precio_facturado' => 120.00,
                'precio_acordado' => 120.00,
                'subtotal_facturado' => 120.00,
                'subtotal_correcto' => 120.00,
                'estado' => 'verde',
                'tipo_error' => null,
                'detalles' => 'Mano de obra de chapa y pintura autorizada.'
            ]
        ]
    ]
];
define('MOCK_GEMINI_AUDITS', $mock_gemini_audits);
