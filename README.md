# Auditor Agéntico de Facturación de Siniestros 🛡️🤖

Este proyecto es un prototipo funcional para hackathons que audita de forma automática y agéntica las facturas enviadas por talleres mecánicos a una aseguradora de vehículos. 

El sistema detecta discrepancias en los precios cobrados frente a un tarifario acordado, cobros duplicados en una misma factura, e ítems cargados con códigos inexistentes en el convenio. Utiliza **Google Gemini API** (Gemini 1.5 Pro) para razonar y generar los reportes de auditoría en JSON estructurado, y **Notion API** como base de datos descentralizada para el tarifario oficial y el historial de auditorías.

---

## 🛠️ Stack Tecnológico
- **Backend:** PHP 8.2 Puro (sin frameworks ni dependencias pesadas).
- **Frontend:** HTML5, CSS3 Premium (variables personalizadas, animaciones fluidas) y JavaScript Vanilla.
- **Base de Datos:** Notion API (vía cURL en el servidor).
- **Inteligencia Artificial:** Google Gemini API (`gemini-1.5-pro` vía cURL, con salida JSON nativa).
- **Contenedorización:** Docker y Docker Compose.
- **Plataforma de Despliegue:** Dokploy (compatible con VPS propio).

---

## 📂 Estructura de Archivos
```
/
├── Dockerfile              # Configuración del contenedor PHP 8.2 con Apache
├── docker-compose.yml      # Orquestación local para pruebas rápidas
├── .env.example            # Plantilla para variables de entorno
├── config.php              # Cargador híbrido de variables y base de datos simulada (Modo Demo)
├── index.php               # Panel principal: selección de facturas de ejemplo y visor de tarifario
├── auditar.php             # Procesador de auditoría (IA + Notion) y dashboard de resultados
├── historial.php           # Vista interactiva de auditorías anteriores leídas desde Notion
├── cargar_tarifario.php    # Herramienta para rellenar el tarifario de Notion en un clic
├── lib/
│   ├── notion.php          # Métodos cURL para interactuar con la API de Notion
│   └── gemini.php          # Conector a la API de Gemini (System Instructions + JSON response)
└── assets/
    ├── style.css           # Estilos corporativos premium, responsivos y de cargador holográfico
    └── app.js              # Lógica de actualización de facturas y animación del loader secuencial
```

---

## ⚙️ Configuración de Notion (Paso a Paso)

Para conectar tu sistema con Notion, necesitas crear dos bases de datos y enlazarlas a través de un token de integración.

### Paso 1: Crear la Base de Datos 1 (Tarifario)
1. En tu espacio de trabajo de Notion, crea una nueva página y selecciona **Tabla** (Table database) como vista.
2. Nómbrala como **Tarifario**.
3. Configura las siguientes columnas con sus tipos exactos:
   - **Código**: Renombra la columna principal (`Name`) a **Código** (Tipo: `Title`).
   - **Descripción**: Crea una propiedad llamada **Descripción** (Tipo: `Text` / Rich text).
   - **Categoría**: Crea una propiedad llamada **Categoría** (Tipo: `Select`). Agrega las siguientes opciones:
     - `Mano de obra`
     - `Repuesto`
     - `Insumo`
   - **Precio acordado**: Crea una propiedad llamada **Precio acordado** (Tipo: `Number`). Configura el formato de número como *Dólar* (`$`) o *Número estándar*.
   - **Unidad**: Crea una propiedad llamada **Unidad** (Tipo: `Text` / Rich text). Admite valores como `hora`, `unidad`, `litro`.

### Paso 2: Crear la Base de Datos 2 (Historial de Auditorías)
1. Crea otra página de tipo **Tabla** en tu espacio de trabajo.
2. Nómbrala como **Historial**.
3. Configura las siguientes columnas con sus tipos exactos:
   - **ID Factura**: Renombra la columna principal (`Name`) a **ID Factura** (Tipo: `Title`).
   - **Taller**: Crea una propiedad llamada **Taller** (Tipo: `Text` / Rich text).
   - **Fecha auditoría**: Crea una propiedad llamada **Fecha auditoría** (Tipo: `Date`).
   - **Total facturado**: Crea una propiedad llamada **Total facturado** (Tipo: `Number`).
   - **Total correcto según tarifario**: Crea una propiedad llamada **Total correcto según tarifario** (Tipo: `Number`).
   - **Ahorro detectado**: Crea una propiedad llamada **Ahorro detectado** (Tipo: `Number`).
   - **Score de confianza**: Crea una propiedad llamada **Score de confianza** (Tipo: `Number`).
   - **Resultado**: Crea una propiedad llamada **Resultado** (Tipo: `Select`). Agrega las siguientes opciones:
     - `Aprobado`
     - `Revisar`
     - `Rechazado`
   - **Discrepancias**: Crea una propiedad llamada **Discrepancias** (Tipo: `Text` / Rich text) para guardar el resumen en formato JSON.

### Paso 3: Crear el Notion Integration Token
1. Accede a [Notion Integrations](https://www.notion.so/my-integrations).
2. Haz clic en **New integration** (+).
3. Selecciona tu espacio de trabajo, nómbrala (ej. *Auditor Agéntico*) y haz clic en **Submit**.
4. Copia el **Internal Integration Token** (empieza por `ntn_`). Este será tu `NOTION_TOKEN`.

### Paso 4: Conectar la Integración a las Bases de Datos
1. Ve a tu base de datos **Tarifario**.
2. Haz clic en los tres puntos (`...`) en la esquina superior derecha.
3. Desplázate hacia abajo hasta **Connections** -> **Connect to**.
4. Busca y selecciona el nombre de tu integración (*Auditor Agéntico*) y confirma.
5. Repite exactamente los mismos pasos con tu base de datos **Historial**.

### Paso 5: Obtener los IDs de las Bases de Datos
Los IDs se encuentran en la URL de cada base de datos.
La URL tiene el siguiente formato:
`https://www.notion.so/mi-espacio/`**`[ID_DE_LA_BASE_DE_DATOS]`**`?v=...`
El ID es la cadena alfanumérica de 32 caracteres que va después del nombre del espacio y antes del signo de interrogación `?`.
- Guarda el ID de la tabla Tarifario (`NOTION_TARIFARIO_DB_ID`).
- Guarda el ID de la tabla Historial (`NOTION_HISTORIAL_DB_ID`).

---

## ⚡ Carga Automática de Datos de Ejemplo
Para no tener que digitar los 15 ítems del tarifario a mano en Notion, sigue estos pasos:
1. Una vez desplegada la aplicación y configuradas las variables de entorno, ve a la esquina inferior derecha de la pantalla principal y presiona el enlace **⚙️ Administrar base de datos** o ve a `http://tu-dominio/cargar_tarifario.php`.
2. Presiona el botón **🚀 Poblar Base de Datos en Notion Now**.
3. El script insertará uno a uno los 15 ítems en tu base de datos de Notion en tiempo real.

---

## 💻 Desarrollo Local (Docker / Docker-Compose)

Puedes probar todo localmente de manera inmediata sin necesidad de configurar APIs reales gracias al **Modo Demo**. Si las variables en tu `.env` están vacías, el sistema simulará la base de datos y la IA automáticamente.

1. Clona este repositorio:
   ```bash
   git clone https://github.com/tu-usuario/auditor-siniestros.git
   cd auditor-siniestros
   ```
2. Crea el archivo `.env` copiándolo del ejemplo:
   ```bash
   cp .env.example .env
   ```
3. *(Opcional)* Edita el archivo `.env` con tus claves reales. Si lo dejas vacío, correrá en **Modo Demo**.
4. Levanta el contenedor con Docker Compose:
   ```bash
   docker-compose up -d --build
   ```
5. Accede a `http://localhost:8080` desde tu navegador web.

---

## 🚀 Despliegue en Dokploy (VPS Propio)

Dokploy es una alternativa open-source a Heroku/Vercel autohospedada que maneja contenedores Docker con facilidad. Sigue estos pasos para desplegar la app:

### Paso 1: Configurar Proyecto en Dokploy
1. Inicia sesión en tu panel de Dokploy.
2. Crea un nuevo **Project** y asígnale un nombre (ej: `Auditor Siniestros`).
3. Dentro del proyecto, añade un nuevo servicio de tipo **Application**.

### Paso 2: Conectar Repositorio GitHub
1. En la configuración de la Aplicación, selecciona como Provider **GitHub**.
2. Conecta tu cuenta y selecciona el repositorio de este proyecto.
3. Elige la rama de despliegue (generalmente `main` o `master`).
4. En **Build Type**, selecciona **Docker**. Dokploy detectará automáticamente el `Dockerfile` que viene incluido en la raíz de este proyecto.

### Paso 3: Configurar Variables de Entorno (Environment)
1. Ve a la pestaña **Environment** (o Variables de Entorno) del servicio en Dokploy.
2. Añade las siguientes claves tal como están en tu archivo `.env`:
   - `GEMINI_API_KEY`: Tu clave de Google AI Studio (consíguela gratis en [Google AI Studio](https://aistudio.google.com/)).
   - `GEMINI_MODEL`: `gemini-1.5-pro` (o puedes usar `gemini-1.5-flash` si prefieres máxima velocidad).
   - `NOTION_TOKEN`: Tu token de integración de Notion (`ntn_...`).
   - `NOTION_TARIFARIO_DB_ID`: ID de la BD de Tarifario de 32 caracteres.
   - `NOTION_HISTORIAL_DB_ID`: ID de la BD de Historial de 32 caracteres.
3. Guarda los cambios.

### Paso 4: Configurar Dominio e Iniciar Despliegue
1. Ve a la pestaña **Domains** en Dokploy.
2. Configura el subdominio o dominio que deseas asignar a tu aplicación. Dokploy se encargará de configurar los certificados SSL de Let's Encrypt de forma automática.
3. En la sección superior derecha de Dokploy, haz clic en **Deploy**.
4. Sigue el log de construcción de Docker. En pocos minutos la aplicación estará en línea y lista para la presentación.
