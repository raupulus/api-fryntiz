-- Monta la capa `energy_systems` / `hardware_energy` de V2 a partir de lo que
-- ya hay en los datos de V1.
--
-- V1 no tenía instalaciones: las lecturas colgaban del `hardware_device_id` y
-- punto. V2 mete una capa por medio —instalación → elemento → lectura— para
-- poder sumar por elemento y no por aparato. Esa capa no se puede copiar
-- porque en origen no existe, así que se deriva de lo que sí consta:
--
--  · Los dos controladores solares (hardware_type_id = 2) son GENERADORES, y
--    cada uno es su propia instalación: son dos montajes distintos, con su
--    panel y su batería.
--  · Todo dispositivo con histórico de consumo es una CARGA.
--  · Las dos filas de `hardware_energy` que ya venían de V1 son cargas
--    medidas por el Pico W; se completan, no se duplican.
BEGIN;

-- ── Una instalación por controlador solar ───────────────────────────────────
INSERT INTO energy_systems (user_id, name, slug, is_standalone, nominal_voltage, created_at, updated_at)
SELECT d.user_id,
       d.name,
       'controlador-'||d.id,
       false,
       12,
       now(), now()
FROM hardware_devices d
WHERE d.hardware_type_id = 2
ON CONFLICT (slug) DO NOTHING;

-- ── Los controladores solares, como elementos generadores ───────────────────
INSERT INTO hardware_energy (
    hardware_device_id, hardware_device_monitorized_id, energy_system_id,
    energy_source_type_id, name, role, is_generator, is_active, created_at, updated_at)
SELECT d.id, d.id, s.id,
       (SELECT id FROM energy_source_types WHERE slug = 'solar'),
       d.name, 'generator', true, true, now(), now()
FROM hardware_devices d
JOIN energy_systems s ON s.slug = 'controlador-'||d.id
WHERE d.hardware_type_id = 2
  AND NOT EXISTS (
    SELECT 1 FROM hardware_energy he
    WHERE he.hardware_device_monitorized_id = d.id AND he.role = 'generator');

-- ── Cada dispositivo con consumo, como elemento de carga ────────────────────
-- Se cuelgan de la instalación del Renogy, que es la que los alimenta. Las dos
-- filas que ya existían de V1 se actualizan en el paso siguiente en vez de
-- duplicarse.
INSERT INTO hardware_energy (
    hardware_device_id, hardware_device_monitorized_id, energy_system_id,
    energy_source_type_id, name, role, is_generator, is_active, created_at, updated_at)
SELECT DISTINCT h.hardware_device_id, h.hardware_device_id,
       (SELECT id FROM energy_systems WHERE slug = 'controlador-6'),
       (SELECT id FROM energy_source_types WHERE slug = 'solar'),
       d.name, 'load', false, true, now(), now()
FROM hardware_power_loads_historical h
JOIN hardware_devices d ON d.id = h.hardware_device_id
WHERE NOT EXISTS (
    SELECT 1 FROM hardware_energy he
    WHERE he.hardware_device_monitorized_id = h.hardware_device_id AND he.role = 'load');

-- ── Las dos filas heredadas de V1 se completan ──────────────────────────────
UPDATE hardware_energy he
SET energy_system_id = (SELECT id FROM energy_systems WHERE slug = 'controlador-6'),
    energy_source_type_id = (SELECT id FROM energy_source_types WHERE slug = 'solar'),
    role = 'load',
    is_active = true,
    name = COALESCE(he.name, d.name)
FROM hardware_devices d
WHERE d.id = he.hardware_device_monitorized_id
  AND he.energy_system_id IS NULL;

COMMIT;

-- ── Correcciones que hacen falta tras el bloque de arriba ───────────────────
BEGIN;

-- El consumo de cada controlador va a SU instalación, no a la del otro.
UPDATE hardware_energy he
SET energy_system_id = s.id
FROM energy_systems s
WHERE he.role = 'load'
  AND s.slug = 'controlador-'||he.hardware_device_monitorized_id;

-- Dispositivos que generan sin ser controlador solar (un portátil con su placa,
-- una Pi con panel propio): también son generadores.
INSERT INTO hardware_energy (
    hardware_device_id, hardware_device_monitorized_id, energy_system_id,
    energy_source_type_id, name, role, is_generator, is_active, created_at, updated_at)
SELECT DISTINCT g.hardware_device_id, g.hardware_device_id,
       (SELECT id FROM energy_systems WHERE slug='controlador-6'),
       (SELECT id FROM energy_source_types WHERE slug='solar'),
       d.name, 'generator', true, true, now(), now()
FROM hardware_power_generators g
JOIN hardware_devices d ON d.id = g.hardware_device_id
WHERE g.hardware_energy_id IS NULL
  AND NOT EXISTS (SELECT 1 FROM hardware_energy he
                  WHERE he.hardware_device_monitorized_id = g.hardware_device_id
                    AND he.role = 'generator');

COMMIT;
