# 🛡️ Auditor Agéntico de Facturación de Siniestros

> Proyecto desarrollado para el HackIAthon — Reto 2: Auditoría Automática de Facturas con IA Generativa.

---

## ¿Qué problema resuelve?

En una aseguradora de vehículos, cuando ocurre un siniestro, el taller mecánico que repara el auto envía una factura con los costos del trabajo realizado. Ese proceso hoy en día es **100% manual**: un auditor humano revisa línea por línea si los precios cobrados corresponden a lo que se acordó previamente en el tarifario con la red de talleres.

Este proceso es lento, tedioso y muy susceptible a errores humanos. Los talleres saben esto y a veces inflan precios, duplican cobros o incluyen repuestos que no están dentro del convenio.

**Nuestra solución:** Un agente de IA que hace eso mismo en menos de 10 segundos, con la misma factura que cualquier taller enviaría por WhatsApp o correo (una foto, un PDF o texto plano).

---

## ¿Cómo funciona?

El flujo es sencillo de explicar:

1. **El auditor sube la factura** — Ya sea tomándole una foto con el celular a una factura en papel, subiendo un PDF o simplemente escribiendo los datos.

2. **El sistema consulta el Tarifario en tiempo real** — Antes de llamar a la IA, el sistema se conecta a una base de datos en **Notion** donde está guardado el tarifario oficial acordado con los talleres (precio máximo por cada tipo de repuesto, insumo u hora de mano de obra). Esto garantiza que si un precio cambia en Notion, la próxima auditoría ya usará el precio nuevo, sin tocar código.

3. **Gemini lee la factura y la compara contra el tarifario** — Le enviamos a Google Gemini 1.5 Flash (o Pro) la imagen/PDF de la factura junto con el tarifario. La IA "lee" el documento, extrae todos los ítems y los compara contra los precios acordados.

4. **El agente emite un dictamen** — La IA devuelve un análisis estructurado que detalla:
   - Qué ítems están bien cobrados ✅
   - Qué ítems superan el precio acordado 🔴 (Precio Excedido)
   - Qué ítems aparecen cobrados dos veces 🔴 (Cobro Duplicado)
   - Qué códigos de repuesto no existen en el convenio 🔴 (Código Inexistente)
   - El **ahorro total detectado** para la aseguradora
   - Un **veredicto final**: Aprobado, Revisar o Rechazado

5. **El resultado queda registrado en Notion** — Cada auditoría se guarda automáticamente en el Historial de Notion, para que el equipo pueda hacer seguimiento de qué talleres cometen errores recurrentes.

---

## Lo que la IA NO puede hacer

Para ser claros: la IA no modifica ni aumenta los precios del tarifario. Solo audita. Si los precios acordados necesitan actualizarse, eso lo hace un humano directamente en Notion, y la IA respetará automáticamente esos nuevos valores en la siguiente auditoría.

---

## 📁 Archivos de prueba incluidos

Dentro de la carpeta **`imagenes y pdf para pruebas/`** encontrarás cuatro archivos listos para que puedas hacer tus primeras auditorías sin necesidad de conseguir facturas reales.

| Archivo | Tipo | Qué demuestra |
|---|---|---|
| `Tarifario de Notion.pdf` | PDF | Es el tarifario de referencia tal como debe cargarse en Notion. Puedes usarlo para ver qué precios son los acordados antes de auditar. |
| `Factura precios normales.png` | Imagen | Una factura de taller sin irregularidades. El agente debería responder con **Aprobado** y $0 de ahorro detectado. |
| `Factura un precio distinto.pdf` | PDF | Una factura con un ítem cobrado por encima del tarifario. El agente debería detectar ese ítem en **Rojo** y calcular el ahorro correspondiente. |
| `Factura Sobreprecios.png` | Imagen | Una factura con múltiples irregularidades (precios inflados, cobros duplicados o códigos inexistentes). El agente debería emitir un dictamen de **Rechazado** con varios ítems en Rojo. |

### ¿Cómo usarlos?

1. Entra al sistema desde tu navegador.
2. En la pantalla principal, haz clic en la zona de carga y selecciona uno de esos archivos (o tómale una foto con el celular si estás en móvil).
3. Presiona **Iniciar Auditoría Agéntica** y espera unos segundos.
4. El sistema mostrará el resultado completo con el análisis ítem por ítem.

> Recomendamos empezar con `Factura precios normales.png` para confirmar que todo funciona, y luego continuar con `Factura Sobreprecios.png` para ver el agente detectando los errores en acción.

---

## Stack tecnológico

- **Backend:** PHP 8.2 puro, sin frameworks. Rápido de desplegar y fácil de entender.
- **Frontend:** HTML5, CSS3 con animaciones y JavaScript Vanilla.
- **Base de datos:** Notion API — accesible para cualquier equipo sin conocimientos técnicos.
- **IA:** Google Gemini 1.5 Flash/Pro — soporte nativo para imágenes, PDFs y texto.
- **Despliegue:** Docker + Dokploy (VPS propio con SSL automático).

---

## Guía de instalación

### Lo que necesitas antes de empezar

- Una cuenta en [Google AI Studio](https://aistudio.google.com/) para obtener tu API Key de Gemini (es gratuita).
- Una cuenta de [Notion](https://notion.so/) con dos bases de datos creadas (ver abajo).
- Docker instalado (para correrlo localmente) o un VPS con Dokploy.

---

### Paso 1 — Crear las bases de datos en Notion

Necesitas crear dos tablas en tu Notion.

**Tabla 1: Tarifario**

| Columna | Tipo |
|---|---|
| Código | Título (Title) — es la columna principal |
| Descripción | Texto (Rich text) |
| Categoría | Selector (Select): opciones `Mano de obra`, `Repuesto`, `Insumo` |
| Precio acordado | Número (Number) |
| Unidad | Texto (Rich text): valores como `hora`, `unidad`, `litro` |

**Tabla 2: Historial**

| Columna | Tipo |
|---|---|
| ID Factura | Título (Title) — columna principal |
| Taller | Texto (Rich text) |
| Fecha auditoría | Fecha (Date) |
| Total facturado | Número (Number) |
| Total correcto según tarifario | Número (Number) |
| Ahorro detectado | Número (Number) |
| Score de confianza | Número (Number) |
| Resultado | Selector (Select): opciones `Aprobado`, `Revisar`, `Rechazado` |
| Discrepancias | Texto (Rich text) |

---

### Paso 2 — Crear un token de integración en Notion

1. Ve a [notion.so/my-integrations](https://www.notion.so/my-integrations).
2. Crea una nueva integración, dale un nombre (ej. *Auditor Agéntico*) y selecciona tu espacio de trabajo.
3. Copia el **Internal Integration Token** — empieza con `ntn_...`.
4. Ahora ve a cada una de tus dos bases de datos, haz clic en los `...` de la esquina superior derecha → **Connections** → **Connect to** → y selecciona tu integración.

---

### Paso 3 — Obtener los IDs de las bases de datos

La URL de cada base de datos de Notion tiene este formato:

```
https://www.notion.so/mi-espacio/[ESTE-ES-EL-ID-32-CARACTERES]?v=...
```

Copia el bloque alfanumérico de 32 caracteres que aparece justo antes del `?v=`. Ese es el ID que necesitas. Guarda uno para el Tarifario y otro para el Historial.

---

### Paso 4 — Configurar el archivo `.env`

Copia el archivo de ejemplo:

```bash
cp .env.example .env
```

Abre `.env` y llena los valores:

```env
GEMINI_API_KEY=AIza...tu_clave_aqui
GEMINI_MODEL=gemini-1.5-flash
NOTION_TOKEN=ntn_...tu_token_aqui
NOTION_TARIFARIO_DB_ID=...id_de_32_caracteres...
NOTION_HISTORIAL_DB_ID=...id_de_32_caracteres...
```

> ⚠️ El archivo `.env` está en el `.gitignore`. Nunca lo subas a GitHub.

---

### Paso 5 — Levantar con Docker (local)

```bash
docker-compose up -d --build
```

Luego abre `http://localhost:8080` en tu navegador.

Si dejas las variables del `.env` vacías, el sistema arranca en **Modo Demo** automáticamente, con datos simulados para que puedas ver cómo se ve todo sin necesitar credenciales reales.

---

### Paso 6 — Cargar el tarifario de ejemplo en Notion

Una vez que el sistema esté corriendo con tus credenciales reales, ve a:

```
http://tu-dominio/cargar_tarifario.php
```

Y presiona el botón **🚀 Poblar Base de Datos en Notion**. El sistema insertará automáticamente 15 ítems de ejemplo en tu tabla de Notion para que tengas datos con los cuales probar las auditorías de inmediato.

---

## Despliegue en producción (Dokploy)

1. Sube el código a un repositorio de GitHub (asegúrate de que el `.env` NO esté incluido).
2. En tu panel de Dokploy, crea un nuevo proyecto y añade un servicio de tipo **Application**.
3. Conecta tu repositorio de GitHub y selecciona la rama `main`.
4. En **Build Type**, elige **Docker** — el `Dockerfile` ya está listo en la raíz del proyecto.
5. Ve a la pestaña **Environment** y añade todas las variables de tu `.env` directamente en Dokploy.
6. En la pestaña **Domains**, añade tu dominio y activa **HTTPS** con Let's Encrypt. Pon el **Container Port** en `80`.
7. Haz clic en **Deploy** y espera unos minutos a que termine la construcción.

¡Listo! Tu aplicación estará en línea con SSL incluido.

---

## Equipo

Proyecto desarrollado por el equipo **FranGab** como parte del Reto 2 del HackIAthon.
