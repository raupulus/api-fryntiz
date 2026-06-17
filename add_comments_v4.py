import os
import re

mig_dir = 'database/migrations'
files = [f for f in os.listdir(mig_dir) if f.endswith('.php')]

def get_column_comment(col_str, match_inner=None):
    if 'timestamps' in col_str:
        return "Marcas de tiempo de creación y actualización"
    if 'softDeletes' in col_str:
        return "Marca de tiempo para borrado lógico"
    if 'rememberToken' in col_str:
        return "Token de sesión para recordar usuario"
    if 'id' in col_str and ('bigIncrements' in col_str or 'increments' in col_str or 'uuid' in col_str):
        if match_inner == 'id':
            return "Identificador único de la tabla"

    col_name = match_inner if match_inner else ""
    col_name = col_name.strip("'\"")

    dict_map = {
        'id': 'Identificador único',
        'user_id': 'Identificador del usuario asociado',
        'uuid': 'Identificador universalmente único',
        'name': 'Nombre',
        'slug': 'Identificador amigable para URLs',
        'description': 'Descripción detallada',
        'content': 'Contenido',
        'status': 'Estado actual',
        'type': 'Tipo',
        'email': 'Correo electrónico',
        'password': 'Contraseña cifrada',
        'created_at': 'Fecha de creación',
        'updated_at': 'Fecha de actualización',
        'deleted_at': 'Fecha de eliminación lógica',
        'title': 'Título principal',
        'subtitle': 'Subtítulo',
        'image': 'Ruta o nombre de la imagen principal',
        'url': 'Enlace o URL',
        'order': 'Orden de visualización',
        'icon': 'Icono representativo',
        'color': 'Color hexadecimal o nombre del color',
        'active': 'Indica si está activo o inactivo',
        'ip': 'Dirección IP',
        'user_agent': 'Navegador o agente de usuario',
        'payload': 'Datos o carga útil',
        'date': 'Fecha',
        'time': 'Hora',
        'value': 'Valor registrado',
        'version': 'Versión',
        'start_date': 'Fecha de inicio',
        'end_date': 'Fecha de finalización',
        'latitude': 'Latitud geográfica',
        'longitude': 'Longitud geográfica',
        'altitude': 'Altitud o elevación',
        'exception': 'Detalle de la excepción',
        'file_id': 'Referencia a un archivo',
        'parent_id': 'Referencia al elemento padre',
        'image_id': 'Referencia a la imagen',
        'priority': 'Prioridad',
        'locale': 'Código de idioma local',
        'iso_locale': 'Código ISO local',
        'iso2': 'Código ISO de 2 letras',
        'iso3': 'Código ISO de 3 letras',
        'icon16': 'Icono de 16px',
        'icon32': 'Icono de 32px'
    }

    if col_name in dict_map:
        return dict_map[col_name]

    if col_name.endswith('_id'):
        related = col_name[:-3].replace('_', ' ')
        return f"Identificador asociado a {related}"

    if col_name.startswith('is_') or col_name.startswith('has_'):
        return f"Indicador de tipo booleano para {col_name.replace('_', ' ')}"

    return f"Columna {col_name.replace('_', ' ')}"

for f in sorted(files):
    path = os.path.join(mig_dir, f)
    with open(path, 'r') as file:
        content = file.read()

    pattern_statement = re.compile(r"(\$table\->([A-Za-z]+)\(([^)]*)\))([^;]*;)", re.MULTILINE | re.DOTALL)

    def repl_stmt(m):
        base_call = m.group(1)
        col_type = m.group(2)
        col_args = m.group(3)
        rest = m.group(4)

        valid_types_for_comment = [
            'id', 'bigIncrements', 'increments', 'uuid', 'string', 'text', 'longText', 'integer',
            'bigInteger', 'tinyInteger', 'smallInteger', 'mediumInteger', 'float',
            'double', 'decimal', 'boolean', 'enum', 'json', 'jsonb', 'date', 'dateTime',
            'dateTimeTz', 'time', 'timeTz', 'timestamp', 'timestampTz', 'year', 'binary',
            'macAddress', 'ipAddress', 'geometry', 'point', 'lineString', 'polygon',
            'geometryCollection', 'multiPoint', 'multiLineString', 'multiPolygon',
            'foreignId', 'foreignIdFor', 'foreignUuid', 'timestamps', 'softDeletes',
            'rememberToken', 'morphs', 'uuidMorphs', 'nullableMorphs', 'nullableUuidMorphs',
            'nullableTimestamps', 'char'
        ]

        if col_type not in valid_types_for_comment:
            return m.group(0)

        if 'comment(' in rest or 'comment(' in base_call:
            return m.group(0)

        col_name = col_type
        m_arg = re.search(r"['\"]([^'\"]*)['\"]", col_args)
        if m_arg:
            col_name = m_arg.group(1)

        comm = get_column_comment(col_type, col_name)

        return f"{base_call}->comment('{comm}'){rest}"

    new_content = pattern_statement.sub(repl_stmt, content)

    with open(path, 'w') as f_out:
        f_out.write(new_content)

print("Done v4.")
