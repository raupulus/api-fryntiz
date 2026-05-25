# API V2 — Documentación de Endpoints

## Información General

- **Base URL:** `/api/v2`
- **Autenticación:** Laravel Sanctum (Bearer Token)
- **Formato:** JSON
- **Rate Limiting:** Configurado por tipo de endpoint

## Estructura de Respuesta Estándar

### Respuesta exitosa
```json
{
    "success": true,
    "message": "Descripción de la operación",
    "data": { ... }
}
```

### Respuesta de error
```json
{
    "success": false,
    "message": "Descripción del error",
    "errors": { "campo": ["Error específico"] }
}
```

---

## Auth

| Método | Endpoint | Auth | Rate Limit | Descripción |
|--------|----------|------|------------|-------------|
| POST | `/auth/login` | No | `api-auth` (10/min) | Iniciar sesión |
| POST | `/auth/signup` | No | `api-auth` (10/min) | Registrar usuario |
| POST | `/auth/logout` | Sí | `api-auth` (10/min) | Cerrar sesión |
| POST | `/auth/delete-account` | Sí | `api-auth` (10/min) | Eliminar cuenta |

### POST /auth/login
**Body:** `email` (required, email), `password` (required, min:6)
**Response 200:** `{ data: { token, user } }`

### POST /auth/signup
**Body:** `name` (required), `email` (required, unique), `password` (required, min:8, confirmed), `nickname` (optional)
**Response 201:** `{ data: { token, user } }`

---

## Contact

| Método | Endpoint | Auth | Rate Limit | Descripción |
|--------|----------|------|------------|-------------|
| POST | `/contact/send` | No | `contact` (5/hora) | Enviar formulario de contacto |

### POST /contact/send
**Body:** `name`, `email`, `subject`, `message` (min:10, max:5000), `g-recaptcha-response`

---

## Newsletter

| Método | Endpoint | Auth | Rate Limit | Descripción |
|--------|----------|------|------------|-------------|
| POST | `/newsletter/subscribe` | No | `api-auth` (10/min) | Suscribirse |
| GET | `/newsletter/verify/{token}` | No | `api-auth` (10/min) | Verificar email |
| GET | `/newsletter/unsubscribe/{token}` | No | `api-auth` (10/min) | Cancelar suscripción |

---

## Users

| Método | Endpoint | Auth | Rate Limit | Descripción |
|--------|----------|------|------------|-------------|
| GET | `/user` | Sí (Admin) | API | Listar usuarios |
| GET | `/user/{id}` | Sí | API | Ver usuario |
| PUT | `/user/{id}` | Sí (Owner) | API | Actualizar usuario |
| DELETE | `/user/{id}` | Sí (Owner/Admin) | API | Eliminar usuario |

---

## Content

| Método | Endpoint | Auth | Rate Limit | Descripción |
|--------|----------|------|------------|-------------|
| GET | `/content/{platform}/{slug}` | No | API | Ver contenido por plataforma y slug |
| GET | `/content/{slug}/pages` | No | API | Páginas de un contenido |
| GET | `/content/{slug}/related` | No | API | Contenido relacionado |

---

## Platform

| Método | Endpoint | Auth | Rate Limit | Descripción |
|--------|----------|------|------------|-------------|
| GET | `/platform` | No | API | Listar plataformas |
| GET | `/platform/{slug}` | No | API | Ver plataforma |
| GET | `/platform/{slug}/featured` | No | API | Contenido destacado |

---

## AirFlight

| Método | Endpoint | Auth | Rate Limit | Descripción |
|--------|----------|------|------------|-------------|
| GET | `/airflight/aircrafts` | No | API | Aviones detectados |
| GET | `/airflight/history` | No | API | Historial extendido |
| POST | `/airflight/register` | Sí | `api-store` (60/min) | Registrar avión |
| POST | `/airflight/register/batch` | Sí | `api-store-batch` (10/min) | Registrar lote |

---

## Hardware

| Método | Endpoint | Auth | Rate Limit | Descripción |
|--------|----------|------|------------|-------------|
| GET | `/hardware/device/{id}` | Sí | `api-store` | Ver dispositivo |
| GET | `/hardware/computers` | Sí | `api-store` | Listar computadores |
| POST | `/hardware/energy` | Sí | `api-store` (60/min) | Almacenar datos energía |
| POST | `/hardware/solar-charge` | Sí | `api-store` (60/min) | Almacenar carga solar |

---

## KeyCounter

| Método | Endpoint | Auth | Rate Limit | Descripción |
|--------|----------|------|------------|-------------|
| POST | `/keycounter/keyboard` | Sí | `api-store` (60/min) | Registro de teclado |
| POST | `/keycounter/mouse` | Sí | `api-store` (60/min) | Registro de ratón |

---

## SmartPlant

| Método | Endpoint | Auth | Rate Limit | Descripción |
|--------|----------|------|------------|-------------|
| POST | `/smartplant/register` | Sí | `api-store` (60/min) | Registro de planta |

---

## WeatherStation

| Método | Endpoint | Auth | Rate Limit | Descripción |
|--------|----------|------|------------|-------------|
| GET | `/weatherstation/resume` | No | API | Resumen meteorológico |
| GET | `/weatherstation/temperature` | No | API | Datos de temperatura |
| GET | `/weatherstation/humidity` | No | API | Datos de humedad |
| GET | `/weatherstation/pressure` | No | API | Datos de presión |
| POST | `/weatherstation/generic/store` | Sí | `api-store` (60/min) | Almacenar datos genéricos |
| POST | `/weatherstation/temperature/store` | Sí | `api-store` (60/min) | Almacenar temperatura |
| POST | `/weatherstation/humidity/store` | Sí | `api-store` (60/min) | Almacenar humedad |
| POST | `/weatherstation/pressure/store` | Sí | `api-store` (60/min) | Almacenar presión |

**Query params (GET):** `from` (date), `to` (date) — filtro por rango de fechas

---

## CV

| Método | Endpoint | Auth | Rate Limit | Descripción |
|--------|----------|------|------------|-------------|
| GET | `/cv` | No | API | CV completo |
| GET | `/cv/experience` | No | API | Experiencia laboral |
| GET | `/cv/education` | No | API | Formación académica |
| GET | `/cv/skills` | No | API | Habilidades |
| GET | `/cv/projects` | No | API | Proyectos |
| GET | `/cv/repositories` | No | API | Repositorios |
| GET | `/cv/services` | No | API | Servicios |
| GET | `/cv/collaborations` | No | API | Colaboraciones |
| GET | `/cv/hobbies` | No | API | Hobbies |
| GET | `/cv/jobs` | No | API | Trabajos |

---

## Códigos HTTP

| Código | Significado |
|--------|-------------|
| 200 | Operación exitosa |
| 201 | Recurso creado |
| 401 | No autenticado |
| 403 | No autorizado |
| 404 | No encontrado |
| 405 | Método no permitido |
| 422 | Error de validación |
| 429 | Demasiadas peticiones (rate limit) |
