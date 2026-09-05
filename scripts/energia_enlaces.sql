-- Ata las lecturas ya cargadas a su elemento de `hardware_energy`.
--
-- Sin `hardware_energy_id` las lecturas siguen colgando sólo del aparato, que
-- es como estaban en V1: el panel de V2 agrupa por elemento, así que sin este
-- enlace las instalaciones salen sin lecturas.
BEGIN;

UPDATE hardware_power_generators r SET hardware_energy_id = he.id
FROM hardware_energy he
WHERE he.hardware_device_monitorized_id = r.hardware_device_id
  AND he.role = 'generator' AND r.hardware_energy_id IS NULL;

UPDATE hardware_power_generators_today r SET hardware_energy_id = he.id
FROM hardware_energy he
WHERE he.hardware_device_monitorized_id = r.hardware_device_id
  AND he.role = 'generator' AND r.hardware_energy_id IS NULL;

UPDATE hardware_power_generators_historical r SET hardware_energy_id = he.id
FROM hardware_energy he
WHERE he.hardware_device_monitorized_id = r.hardware_device_id
  AND he.role = 'generator' AND r.hardware_energy_id IS NULL;

UPDATE hardware_power_loads r SET hardware_energy_id = he.id
FROM hardware_energy he
WHERE he.hardware_device_monitorized_id = r.hardware_device_id
  AND he.role = 'load' AND r.hardware_energy_id IS NULL;

UPDATE hardware_power_loads_today r SET hardware_energy_id = he.id
FROM hardware_energy he
WHERE he.hardware_device_monitorized_id = r.hardware_device_id
  AND he.role = 'load' AND r.hardware_energy_id IS NULL;

UPDATE hardware_power_loads_historical r SET hardware_energy_id = he.id
FROM hardware_energy he
WHERE he.hardware_device_monitorized_id = r.hardware_device_id
  AND he.role = 'load' AND r.hardware_energy_id IS NULL;

COMMIT;
