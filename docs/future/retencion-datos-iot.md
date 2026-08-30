# Crecimiento y retención de datos IoT

> **Estado:** anotado, sin trabajo asignado. **Hoy no es un problema.**
> **Decidido el:** 2026-08-19

## Situación actual

La tabla más grande ronda los **300.000 registros**. PostgreSQL lo lleva sin esfuerzo: no se ha
observado ninguna lentitud ni limitación atribuible al volumen.

No hay política de retención y las tablas de sensores crecen indefinidamente. **Es aceptable al
ritmo actual** y no justifica invertir tiempo ahora.

## Idea para el futuro

Cuando el volumen lo pida, particionar por año pasando a tablas con el año en el nombre
(`temperatures_2027`, `temperatures_2028`…), en vez de purgar datos.

Ventaja de este enfoque frente a borrar: **no se pierde histórico**. Para datos meteorológicos
y de energía, el histórico largo tiene valor por sí mismo.

## Cuándo revisarlo

Disparadores razonables para retomarlo:

- Alguna tabla de sensores supera los **5-10 millones** de registros.
- Una consulta del panel o de la API empieza a tardar más de ~1 s por volumen.
- El tamaño de la base de datos se acerca al límite del disco del VPS.
- Los backups tardan tanto que dejan de ser prácticos.

## Medición previa (cuando toque)

```sql
SELECT relname AS tabla,
       n_live_tup AS filas,
       pg_size_pretty(pg_total_relation_size(relid)) AS tamano
FROM pg_stat_user_tables
ORDER BY pg_total_relation_size(relid) DESC
LIMIT 25;
```

Y detectar índices que no se usan (ralentizan las escrituras sin aportar nada):

```sql
SELECT relname, indexrelname, idx_scan
FROM pg_stat_user_indexes
WHERE idx_scan = 0
ORDER BY relname;
```

## Opciones cuando llegue el momento

| Opción | Ventaja | Coste |
|--------|---------|-------|
| **Tablas por año con el año en el nombre** (idea preferida) | Sin pérdida de histórico. Simple de entender y de respaldar por separado | Hay que enrutar las consultas a la tabla correcta según el rango de fechas |
| **Particionado nativo de PostgreSQL 17 por rango de fechas** | Transparente para el código: una sola tabla lógica. Postgres descarta particiones solas | Requiere migración cuidadosa de las tablas existentes |
| **Agregados + purga del detalle** | La base se mantiene pequeña | Se pierde la resolución fina. Los modelos `MeteorologyResumeToday` / `Historical` ya existen para esto |

## Lo único que sí conviene hacer pronto

Independientemente de la retención, y con coste bajo:

- Verificar que existe índice en `(hardware_device_id, created_at)` en cada tabla de sensor.
  Es el patrón de consulta habitual del panel y de la API.
- Vigilar el tamaño total de la base desde el chequeo de salud del sistema, para enterarse
  del crecimiento antes de que sea un problema.
