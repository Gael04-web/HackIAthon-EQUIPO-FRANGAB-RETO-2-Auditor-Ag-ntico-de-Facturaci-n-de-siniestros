<?php
/**
 * LIBRERÍA DE INTEGRACIÓN CON GOOGLE GEMINI API
 * Auditor Agéntico de Facturación de Siniestros
 */

require_once __DIR__ . '/../config.php';

/**
 * Llama a la API de Google Gemini para auditar una factura contra el tarifario
 */
function gemini_audit_invoice($invoice, $tarifario) {
    if (DEMO_MODE) {
        $invoiceId = $invoice['id'];
        if (isset(MOCK_GEMINI_AUDITS[$invoiceId])) {
            return MOCK_GEMINI_AUDITS[$invoiceId];
        }
        // Fallback genérico por si no coincide la factura
        return [
            'total_facturado' => 100.00,
            'total_correcto' => 100.00,
            'ahorro_detectado' => 0.00,
            'score_confianza' => 100,
            'resultado' => 'Aprobado',
            'recomendacion' => 'Simulación: Factura auditada con éxito en modo demostración. No se detectaron errores.',
            'items' => []
        ];
    }

    $systemInstruction = "Eres un sistema de Inteligencia Artificial experto en auditoría y control de facturación para una aseguradora de vehículos de siniestros de talleres mecánicos. Tu tarea es analizar detalladamente una factura emitida por un taller mecánico en busca de discrepancias con respecto a un tarifario de referencia acordado previamente.

REGLAS DE AUDITORÍA:
1. Comparar cada ítem de la factura con el tarifario de referencia buscando coincidencia exacta por el campo 'codigo'.
2. Si un código NO existe en el tarifario, márcalo con estado 'rojo', tipo_error = 'Código Inexistente', subtotal_correcto = 0.00, y detalla que no existe convenio para ese código.
3. Si el precio unitario facturado supera el precio acordado en el tarifario, márcalo con estado 'rojo', tipo_error = 'Precio Excedido', calcula la diferencia, y establece el subtotal_correcto basado en el precio acordado (cantidad * precio_acordado).
4. Si un código aparece duplicado o repetido en diferentes líneas de la factura sin una clara justificación (ej. cobrar dos veces las pastillas de freno en líneas separadas), marca la segunda línea repetida con estado 'rojo', tipo_error = 'Cobro Duplicado' y subtotal_correcto = 0.00.
5. Si el cobro está alineado con el tarifario o el precio facturado es menor o igual al precio acordado, márcalo con estado 'verde', tipo_error = null y subtotal_correcto = subtotal_facturado.
6. Calcula:
   - total_facturado: Suma de todos los subtotales cobrados en la factura.
   - total_correcto: Suma de todos los subtotales correctos de los ítems auditados.
   - ahorro_detectado: total_facturado - total_correcto.
   - score_confianza: Un número entero de 0 a 100. Inicia en 100. Si no hay errores, es 100. Por cada ítem con sobreprecio resta 10 puntos. Por cada cobro duplicado resta 15 puntos. Por cada código inexistente en el tarifario resta 20 puntos.
   - resultado: Determina la decisión recomendada:
     * 'Aprobado': si no hay discrepancias (ahorro_detectado = 0).
     * 'Revisar': si hay discrepancias leves o diferencias de precio (ahorro_detectado > 0 y ahorro_detectado < 100.00).
     * 'Rechazado': si hay discrepancias graves como códigos no concertados o cobros duplicados de alto costo (ahorro_detectado >= 100.00).
   - recomendacion: Un texto en español claro, profesional, ejecutivo y conciso explicando la justificación del dictamen y las discrepancias halladas.

Debes retornar obligatoriamente un objeto JSON que coincida exactamente con esta estructura:
{
  \"total_facturado\": 0.00,
  \"total_correcto\": 0.00,
  \"ahorro_detectado\": 0.00,
  \"score_confianza\": 100,
  \"resultado\": \"Aprobado o Revisar o Rechazado\",
  \"recomendacion\": \"Resumen ejecutivo del resultado de la auditoría en español\",
  \"items\": [
    {
      \"codigo\": \"Código del ítem\",
      \"descripcion\": \"Descripción según factura\",
      \"cantidad\": 1.0,
      \"unidad\": \"hora/unidad/litro\",
      \"precio_facturado\": 0.00,
      \"precio_acordado\": 0.00,
      \"subtotal_facturado\": 0.00,
      \"subtotal_correcto\": 0.00,
      \"estado\": \"verde o amarillo o rojo\",
      \"tipo_error\": \"Precio Excedido o Cobro Duplicado o Código Inexistente o null\",
      \"detalles\": \"Comentario analítico sobre este ítem en específico\"
    }
  ]
}";

    $userPrompt = "Factura del taller a auditar:\n" . json_encode($invoice, JSON_UNESCAPED_UNICODE) . "\n\n" .
                  "Tarifario de referencia autorizado:\n" . json_encode($tarifario, JSON_UNESCAPED_UNICODE);

    // Endpoint de Google Gemini API
    $url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;

    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $userPrompt]
                ]
            ]
        ],
        'systemInstruction' => [
            'parts' => [
                ['text' => $systemInstruction]
            ]
        ],
        'generationConfig' => [
            'responseMimeType' => 'application/json',
            'temperature' => 0.0
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("Error cURL de Gemini API: " . $error);
    }

    if ($httpCode >= 400) {
        $errorResponse = json_decode($response, true);
        $errMsg = isset($errorResponse['error']['message']) ? $errorResponse['error']['message'] : 'Error desconocido de la API';
        throw new Exception("Gemini API falló [Código {$httpCode}]: " . $errMsg);
    }

    $result = json_decode($response, true);
    if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception("Gemini retornó un formato inesperado: " . $response);
    }

    $jsonText = $result['candidates'][0]['content']['parts'][0]['text'];
    
    // Decodificar el JSON devuelto por el modelo
    $auditResult = json_decode(trim($jsonText), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error al decodificar el JSON de auditoría de Gemini: " . json_last_error_msg() . "\nTexto recibido: " . $jsonText);
    }

    return $auditResult;
}
