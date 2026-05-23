/**
 * COMPORTAMIENTO INTERACTIVO Y ANIMACIONES DE CARGA
 * Auditor Agéntico de Facturación de Siniestros
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Validación de Formulario Multimodal
    const auditBtn = document.getElementById('btn-auditar');
    const fileInput = document.getElementById('invoice_file');
    const textInput = document.getElementById('invoice_text');
    
    // Activar estilo al arrastrar archivo sobre la zona y mostrar vista previa
    const uploadZone = document.querySelector('.upload-zone');
    const previewContainer = document.getElementById('file-preview-container');
    const previewContent = document.getElementById('file-preview-content');

    if (uploadZone && fileInput) {
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                uploadZone.style.backgroundColor = 'var(--color-accent-blue-soft)';
                uploadZone.style.borderColor = 'var(--color-accent-blue)';
                // Limpiar el text input si se sube archivo
                if (textInput) textInput.value = '';

                // Mostrar vista previa
                const file = fileInput.files[0];
                if (previewContainer && previewContent) {
                    previewContainer.style.display = 'block';
                    previewContent.innerHTML = ''; // Limpiar previo

                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = URL.createObjectURL(file);
                        img.style.maxWidth = '100%';
                        img.style.maxHeight = '250px';
                        img.style.objectFit = 'contain';
                        img.style.display = 'block';
                        previewContent.appendChild(img);
                    } else if (file.type === 'application/pdf') {
                        const embed = document.createElement('embed');
                        embed.src = URL.createObjectURL(file);
                        embed.type = 'application/pdf';
                        embed.style.width = '100%';
                        embed.style.height = '250px';
                        previewContent.appendChild(embed);
                    } else {
                        previewContent.innerHTML = `<div style="padding: 20px; font-weight: 500; color: var(--color-navy-dark);">📎 ${file.name}</div>`;
                    }
                }
            } else {
                uploadZone.style.backgroundColor = '#f8fafc';
                if (previewContainer) previewContainer.style.display = 'none';
                if (previewContent) previewContent.innerHTML = '';
            }
        });
    }

    if (textInput && fileInput) {
        textInput.addEventListener('input', () => {
            if (textInput.value.trim().length > 0) {
                // Limpiar file input y vista previa si se escribe texto
                fileInput.value = '';
                if(uploadZone) {
                    uploadZone.style.backgroundColor = '#f8fafc';
                }
                if (previewContainer) previewContainer.style.display = 'none';
                if (previewContent) previewContent.innerHTML = '';
            }
        });
    }

    // 2. Animación del Cargador de Auditoría Agéntica
    const auditForm = document.getElementById('audit-form');
    const loaderOverlay = document.getElementById('audit-loader');
    const loaderSubtext = document.getElementById('loader-subtext-msgs');

    if (auditForm && loaderOverlay && loaderSubtext) {
        auditForm.addEventListener('submit', (e) => {
            if (!fileInput.value && (!textInput.value || textInput.value.trim() === '')) {
                e.preventDefault();
                alert('Por favor, sube un archivo o digita los datos de la factura antes de auditar.');
                return;
            }

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
