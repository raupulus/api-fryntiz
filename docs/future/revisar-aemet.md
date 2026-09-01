# Revisar AEMET

> **Estado:** pendiente. No bloquea ningún despliegue.

## Endpoints que faltan por decidir

AEMET OpenData publica su especificación completa (OpenAPI). Hoy se consumen **9 productos** —los que
tienen comando `aemet:*`— de los **64 endpoints** ya verificados contra la API real en
`docs/apis/aemet/`. Falta **decidir cuáles de los 55 restantes interesan** a este proyecto.

> ⚠️ **No fiarse del todo de la especificación.** `docs/apis/aemet/` documenta lo que cada endpoint
> devuelve **de verdad**, no siempre lo que dice el spec. Validar contra una respuesta real antes de
> dar por bueno un contrato nuevo.

Modelos AEMET que ya existen, para no duplicar: `AEMET`, `AEMETPrediction`, `AEMETPredictionBeach`,
`AEMETCoast`, `AEMETHighSea`, `AEMETOzone`, `AEMETContamination`, `AEMETSunRadiation`,
`AEMETAdverseEvents`.

## Referencias

- AEMET OpenData: https://opendata.aemet.es/
- Alta de API key: https://opendata.aemet.es/centrodedescargas/altaUsuario
- Cómo se usa aquí: [`docs/info/apis/aemet.md`](../info/apis/aemet.md)
- Documentación oficial destilada: [`docs/apis/aemet/`](../apis/aemet/README.md)

> Creado: 2026-08-30 · Última revisión: 2026-09-01
