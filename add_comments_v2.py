import os
import re

mig_dir = 'database/migrations'
files = [f for f in os.listdir(mig_dir) if f.endswith('.php')]

def get_table_comment(table_name):
    name = table_name.replace('_', ' ')
    name = name.replace('this->tableName', 'la tabla')
    name = name.replace('"', '').replace("'", "").strip()
    return f"Tabla para almacenar información de {name}"

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

    # Find Schema::create block

    # We use regex to find Schema::create(..., function (Blueprint $table) {
    # and then find the corresponding closing });

    # To handle table comment
    # Does it have 'DB::statement("COMMENT ON TABLE' ?
    has_db_comment = 'COMMENT ON TABLE' in content

    pattern_create = re.compile(r"Schema::create\(\s*([^,]+)\s*,\s*function.*?\{", re.DOTALL)

    offset = 0
    new_content = ""
    for match in pattern_create.finditer(content):
        # Everything before the match
        new_content += content[offset:match.end()]

        table_name_expr = match.group(1).strip()
        tbl_comm = get_table_comment(table_name_expr)

        # Insert table comment if not present right after the match
        # Let's peek into the next few characters
        rest = content[match.end():]
        if '->comment(' not in rest[:150] and '$table->comment' not in rest[:150]:
            if "Table" not in tbl_comm or "this->tableName" not in tbl_comm: # basic check to avoid messy strings
                new_content += f"\n            $table->comment('{tbl_comm}');"

        offset = match.end()

    new_content += content[offset:]

    content = new_content

    # Now lets find all $table->something(...);
    # that DO NOT contain ->comment(...)

    pattern_col = re.compile(r"(\$table->([A-Za-z]+)\(([^)]*)\)[^;]*);")

    # We will do sub with a function
    def repl_col(m):
        full_match = m.group(1)
        semi = ';'
        col_type = m.group(2)
        col_args = m.group(3)

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
            return m.group(0) # return unchanged

        if '->comment(' in full_match:
            return m.group(0)

        col_name = col_type
        m_arg = re.search(r"['\"]([^'\"]*)['\"]", col_args)
        if m_arg:
            col_name = m_arg.group(1)

        comm = get_column_comment(col_type, col_name)

        return f"{full_match}->comment('{comm}'){semi}"

    # We should only replace inside Schema::create blocks to be safe, but since only migrations have $table-> it's okay to run globally
    content = pattern_col.sub(repl_col, content)

    with open(path, 'w') as f_out:
        f_out.write(content)

print("Done v2.")
