import os
import re

mig_dir = 'database/migrations'
files = [f for f in os.listdir(mig_dir) if f.endswith('.php')]

def get_table_comment(table_name):
    name = table_name.replace('_', ' ')
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

    new_lines = []
    lines = content.split('\n')

    in_create = False
    table_name = ""
    bracket_level = 0

    i = 0
    while i < len(lines):
        line = lines[i]

        m_create = re.search(r"Schema::create\(\s*['\"]([^'\"]+)['\"]\s*,\s*function", line)
        if m_create:
            in_create = True
            table_name = m_create.group(1)
            bracket_level = 1

        if in_create:
            if '{' in line and not m_create:
                bracket_level += line.count('{')
            if '}' in line:
                bracket_level -= line.count('}')
                if bracket_level <= 0:
                    in_create = False

            if m_create:
                new_lines.append(line)
                tbl_comment = get_table_comment(table_name)
                # Check if next line contains a comment on the table builder level
                # Usually we want it exactly after the Schema::create { closure start
                if i+1 < len(lines) and '->comment(' not in lines[i+1]:
                    new_lines.append(f"            $table->comment('{tbl_comment}');")
                i += 1
                continue

            # Simple multiline hack fix for things like foreignId(...)->nullable() without trailing semicolon on first line
            # we just look for $table->foo(bar)
            m_col = re.search(r"^\s*\$table->([A-Za-z]+)\(([^)]*)\)(.*)$", line)

            if m_col and 'comment(' not in line:
                col_type = m_col.group(1)
                col_args = m_col.group(2)
                rest = m_col.group(3)

                has_comment_next = False
                temp_j = i + 1
                while temp_j < len(lines):
                    next_l = lines[temp_j].strip()
                    if next_l.startswith('->'):
                        if '->comment(' in next_l:
                            has_comment_next = True
                            break
                        if ';' in next_l:
                            break
                        temp_j += 1
                    else:
                        break

                # Check if this statement ends locally with a semicolon at the end of the line, or ends on a different line?
                ends_with_semi = line.strip().endswith(';')
                if not ends_with_semi and rest.strip():
                    ends_with_semi = rest.strip().endswith(';')

                # if not ending with semicolon, maybe it's chaining to next line
                chaining_to_next_line = False
                if not ends_with_semi and temp_j < len(lines):
                   next_tmp = lines[i+1].strip()
                   if next_tmp.startswith('->'):
                       chaining_to_next_line = True

                valid_types_for_comment = [
                    'id', 'bigIncrements', 'increments', 'uuid', 'string', 'text', 'integer',
                    'bigInteger', 'tinyInteger', 'smallInteger', 'mediumInteger', 'float',
                    'double', 'decimal', 'boolean', 'enum', 'json', 'jsonb', 'date', 'dateTime',
                    'dateTimeTz', 'time', 'timeTz', 'timestamp', 'timestampTz', 'year', 'binary',
                    'macAddress', 'ipAddress', 'geometry', 'point', 'lineString', 'polygon',
                    'geometryCollection', 'multiPoint', 'multiLineString', 'multiPolygon',
                    'foreignId', 'foreignIdFor', 'foreignUuid', 'timestamps', 'softDeletes',
                    'rememberToken', 'morphs', 'uuidMorphs', 'nullableMorphs', 'nullableUuidMorphs'
                ]

                if (ends_with_semi or not chaining_to_next_line) and not has_comment_next and col_type in valid_types_for_comment:
                    col_name = None
                    m_arg = re.search(r"['\"]([^'\"]*)['\"]", col_args)
                    if m_arg:
                        col_name = m_arg.group(1)
                    else:
                        col_name = col_type

                    comm = get_column_comment(col_type, col_name)

                    if ends_with_semi:
                        line_no_semi = list(line)
                        semi_idx = len(line) - 1 - line[::-1].index(';')
                        line_no_semi[semi_idx] = f"->comment('{comm}');"
                        line = "".join(line_no_semi)
                    else:
                        pass

        new_lines.append(line)
        i += 1

    with open(path, 'w') as f_out:
        f_out.write('\n'.join(new_lines))

print("Modified files.")
