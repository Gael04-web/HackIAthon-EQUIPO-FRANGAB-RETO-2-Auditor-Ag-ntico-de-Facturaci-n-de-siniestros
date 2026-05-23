/**
 * COMPORTAMIENTO INTERACTIVO Y ANIMACIONES DE CARGA
 * Auditor Agéntico de Facturación de Siniestros
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Selección Interactiva de Facturas
    const invoiceCards = document.querySelectorAll('.invoice-option');
    const viewerContainer = document.getElementById('active-invoice-items');
    const viewerTotal = document.getElementById('active-invoice-total');
    const formInvoiceInput = document.getElementById('selected-invoice-id');
    const auditBtn = document.getElementById('btn-auditar');

    if (invoiceCards.length > 0 && viewerContainer) {
        invoiceCards.forEach(card => {
            card.addEventListener('click', () => {
                // Quitar selección previa
                invoiceCards.forEach(c => c.classList.remove('selected'));
                
                // Agregar clase al seleccionado
                card.classList.add('selected');
                
                // Activar radio button interno
                const radio = card.querySelector('.hidden-radio');
                if (radio) {
                    radio.checked = true;
                    formInvoiceInput.value = radio.value;
                }

                // Cargar datos del atributo data-invoice
                const invoiceData = JSON.parse(card.getAttribute('data-invoice'));
                updateInvoiceViewer(invoiceData);
                
                // Habilitar botón de auditoría
                if (auditBtn) {
                    auditBtn.removeAttribute('disabled');
                    auditBtn.innerText = `🔍 Auditar Factura ${invoiceData.id}`;
                }
            });
        });
    }

    // Actualiza la vista previa de la factura seleccionada
    function updateInvoiceViewer(invoice) {
        viewerContainer.innerHTML = '';
        let total = 0;

        invoice.items.forEach(item => {
            const subtotal = item.cantidad * item.precio_unitario;
            total += subtotal;

            const row = document.createElement('div');
            row.className = 'viewer-item-row animate-fade-in';
            row.innerHTML = `
                <div>
                    <span class="badge badge-code">${item.codigo}</span>
                    <span class="viewer-item-desc">${item.descripcion}</span>
                </div>
                <div class="viewer-item-details">
                    ${item.cantidad} ${item.unidad} x $${item.precio_unitario.toFixed(2)}
                </div>
                <div class="viewer-item-price">
                    $${subtotal.toFixed(2)}
                </div>
            `;
            viewerContainer.appendChild(row);
        });

        viewerTotal.innerText = `$${total.toFixed(2)}`;
    }

    // 2. Animación del Cargador de Auditoría Agéntica
    const auditForm = document.getElementById('audit-form');
    const loaderOverlay = document.getElementById('audit-loader');
    const loaderSubtext = document.getElementById('loader-subtext-msgs');

    if (auditForm && loaderOverlay && loaderSubtext) {
        auditForm.addEventListener('submit', (e) => {
            e.preventDefault(); // Detener el envío temporalmente para la animación

            // Mostrar el loader
            loaderOverlay.classList.add('active');

            // Secuencia de mensajes simulando la tarea del agente IA
            const steps = [
                { time: 0, text: 'Leyendo el archivo de factura y extrayendo ítems...' },
                { time: 1000, text: 'Conectando con Notion para descargar tarifario de referencia...' },
                { time: 2000, text: 'Enviando tarifario y factura a la IA de Google Gemini...' },
                { time: 3200, text: 'Gemini está comparando precios y analizando duplicados...' },
                { time: 4500, text: 'Calculando ahorros potenciales e importes correctos...' },
                { time: 5500, text: 'Guardando reporte de auditoría en el historial de Notion...' },
                { time: 6500, text: 'Redireccionando al dashboard de resultados...' }
            ];

            steps.forEach(step => {
                setTimeout(() => {
                    loaderSubtext.innerHTML = `<strong>Paso actual:</strong> ${step.text}`;
                }, step.time);
            });

            // Enviar el formulario tras completar la animación descriptiva
            setTimeout(() => {
                auditForm.submit();
            }, 7000);
        });
    }

    // 3. Buscador en la tabla de Tarifario (Opcional, mejora visual y práctica)
    const searchInput = document.getElementById('tarifario-search');
    const tableRows = document.querySelectorAll('#tarifario-table-body tr');

    if (searchInput && tableRows.length > 0) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase().trim();
            
            tableRows.forEach(row => {
                const code = row.querySelector('.badge-code').textContent.toLowerCase();
                const desc = row.cells[1].textContent.toLowerCase();
                const cat = row.querySelector('.badge-category').textContent.toLowerCase();
                
                if (code.includes(query) || desc.includes(query) || cat.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
