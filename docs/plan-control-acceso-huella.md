# Plan: Control de acceso por huella dactilar (Opción A)

> Documento de referencia para decidir e implementar más adelante.
> Fecha: 2026-06-26

## Decisiones tomadas

- **Uso principal:** Control de acceso / entrada (el miembro pone el dedo al entrar y el sistema valida si está solvente).
- **Hosting:** En la nube (Render).
- **Hardware:** Se va a comprar (recomendado: **HID DigitalPersona U.are.U 4500**, USB).
- **Puerta:** La maneja una persona en recepción (no hay torniquete automático).

## Stack del proyecto (contexto)

- Backend: **Symfony 7.3 + MongoDB ODM** (PHP 8.3).
- Frontend: **AngularJS** (controladores en `public/js/angular/`).
- Despliegue: **Docker + Render** (`render.yaml`).

---

## Concepto clave

El backend está en **Render (la nube)** y **PHP no puede comparar huellas**. Por eso el reparto de responsabilidades es:

```
┌─ PC de recepción ─────────────────┐         ┌─ Render (Symfony) ──────┐
│ Lector USB (U.are.U 4500)         │         │                          │
│   └─ Servicio local DigitalPersona│         │  Mongo: Cliente          │
│        (WebSocket en localhost)   │         │   + huellaTemplate       │
│   └─ Navegador (pantalla acceso)  │◄──HTTPS─►│  Endpoints nuevos        │
│        · descarga plantillas      │         │   /acceso/plantillas     │
│        · hace el match 1:N local  │         │   /cliente/enrolar       │
│        · pide solvencia por id    │         │   /acceso/verificar      │
└────────────────────────────────────┘         └──────────────────────────┘
```

- **El match de huella se hace en la PC local** (el SDK identifica al cliente).
- **Symfony solo guarda la plantilla y responde la solvencia** (lógica ya existente en `fecha_vencimiento`).

---

## Fase 0 — Hardware y servicio local (sin código del proyecto)

1. Comprar **HID DigitalPersona U.are.U 4500** (USB).
2. Instalar en la PC de recepción el **DigitalPersona Lite Client / Web SDK** → expone un WebSocket en `localhost` que el navegador usa para capturar/comparar.
3. Verificar que captura una plantilla de prueba (base64) desde el navegador.

> **Riesgo #1 del proyecto.** Validar el hardware ANTES de tocar código. Si el lector entrega plantillas base64 en el navegador, el resto es directo porque la lógica de solvencia ya existe.

---

## Fase 1 — Backend: guardar la huella

**`src/Document/Cliente.php`**
- Añadir campos:
  - `protected ?string $huellaTemplate = null;` (`#[MongoDB\Field(type: 'string')]`)
  - `protected bool $huellaEnrolada = false;` (`#[MongoDB\Field(type: 'bool')]`)
- Añadir getters/setters (mismo estilo que `fecha_vencimiento`).

**`src/Controller/ClienteController.php`** — nuevo endpoint:
- `#[Route('/cliente/enrolar/', name: 'cliente_enrolar', methods: ['POST'], options: ['expose' => true])]`
- Recibe `cliente_id` + `template` (base64) por **POST** (no por query como `guardar`, porque la plantilla es grande).
- Guarda `huellaTemplate`, pone `huellaEnrolada = true`, `flush()`.

> Nota de seguridad: guardar la **plantilla** (no la imagen), idealmente cifrada con una clave de app (ver Fase 4).

---

## Fase 2 — Enrolar al miembro (UI registro)

**`public/js/angular/cliente-controller.js`** + **`templates/cliente/list.html.twig`** (modal de miembro):
- Botón **"Capturar huella"** en el modal de registro/edición.
- Al hacer clic: llama al WebSocket local → obtiene la plantilla → `POST` a `cliente_enrolar`.
- Indicador visual "✔ Huella registrada" (reusa el patrón `$ctrl.guardando` + spinner ya existente).

---

## Fase 3 — Pantalla de acceso (el corazón)

**`src/Controller/ClienteController.php`** — dos endpoints:
1. `/acceso/plantillas/` → devuelve `[{id, nombre, template}]` de los clientes con `huellaEnrolada = true` (lo consume el match local).
2. `/acceso/verificar/?id=...` → devuelve `{nombre, apellidos, solvente, fechaVencimiento, diasVencido}` reutilizando el cálculo que **ya existe** en `listarJsonAction` (líneas 68-90 de `ClienteController.php`).

**Nueva vista `templates/cliente/acceso.html.twig`** + ruta `/cliente/acceso/` + **`public/js/angular/acceso-controller.js`**:
- Pantalla completa, simple, para el monitor de recepción.
- Flujo: carga plantillas → escucha el lector → al poner el dedo, el SDK identifica al cliente → consulta `verificar` → muestra:
  - 🟢 **VERDE grande**: "PASE — Juan Pérez · vence 15/07/2026"
  - 🔴 **ROJO grande**: "VENCIDO — Juan Pérez · venció hace 5 días"
- La **persona en recepción** ve el semáforo y abre la puerta (no hay torniquete).

**`templates/Menu/menuprincipal.html.twig`**: añadir enlace "Control de Acceso" (icono `fa-id-card`).

---

## Fase 4 — Seguridad / legal

- Cifrar `huellaTemplate` antes de guardar (clave en variable de entorno de Render).
- Servir `/acceso/plantillas/` solo a usuarios autenticados.
- Añadir casilla de **consentimiento** al enrolar (dato biométrico = dato sensible; aplica en Venezuela y en general).

---

## Resumen de archivos a tocar

| Archivo | Acción |
|--------|--------|
| `src/Document/Cliente.php` | + `huellaTemplate`, `huellaEnrolada` + getters/setters |
| `src/Controller/ClienteController.php` | + `enrolar`, `acceso (vista)`, `plantillas`, `verificar` |
| `templates/cliente/list.html.twig` | botón "Capturar huella" en modal |
| `public/js/angular/cliente-controller.js` | lógica de enrolamiento |
| `templates/cliente/acceso.html.twig` | **nueva** pantalla de acceso |
| `public/js/angular/acceso-controller.js` | **nuevo** controlador de acceso |
| `templates/Menu/menuprincipal.html.twig` | enlace en el menú |

---

## Alternativa futura (no elegida ahora)

**Opción B — Terminal biométrico autónomo (ZKTeco):** terminal de pared con relé que abre torniquete/cerradura automáticamente y sincroniza con el backend por API. Más caro (~$120-250), ideal si en el futuro se quiere acceso sin personal en la puerta. Se puede migrar sin perder lo construido en la Opción A.

---

## Próximo paso sugerido

Implementar la **Fase 1** (campos en `Cliente` + endpoint `enrolar`), que es la base y **no depende del hardware**. Así se avanza mientras llega el lector.
