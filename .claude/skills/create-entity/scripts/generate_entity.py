#!/usr/bin/env python3
"""
Entity Generator for Budget Manager
Generates complete CRUD entity (backend + frontend) following project conventions.
"""

import argparse
import os
import sys
from datetime import datetime
from pathlib import Path


def to_snake_case(text):
    """Convert camelCase or PascalCase to snake_case"""
    result = []
    for i, char in enumerate(text):
        if char.isupper() and i > 0:
            result.append('_')
        result.append(char.lower())
    return ''.join(result)


def to_pascal_case(text):
    """Convert snake_case to PascalCase"""
    return ''.join(word.capitalize() for word in text.split('_'))


def to_camel_case(text):
    """Convert snake_case to camelCase"""
    words = text.split('_')
    return words[0] + ''.join(word.capitalize() for word in words[1:])


def to_plural(text):
    """Simple pluralization"""
    if text.endswith('y'):
        return text[:-1] + 'ies'
    elif text.endswith('s'):
        return text + 'es'
    else:
        return text + 's'


class FieldType:
    """Mapping between Laravel migration types and TypeScript types"""

    MAPPINGS = {
        'string': {'laravel': 'string', 'ts': 'string'},
        'text': {'laravel': 'text', 'ts': 'string'},
        'integer': {'laravel': 'integer', 'ts': 'number'},
        'bigInteger': {'laravel': 'bigInteger', 'ts': 'number'},
        'boolean': {'laravel': 'boolean', 'ts': 'boolean'},
        'date': {'laravel': 'date', 'ts': 'string'},
        'datetime': {'laravel': 'dateTime', 'ts': 'string'},
        'timestamp': {'laravel': 'timestamp', 'ts': 'string'},
        'decimal': {'laravel': 'decimal', 'ts': 'number'},
        'float': {'laravel': 'float', 'ts': 'number'},
        'json': {'laravel': 'json', 'ts': 'any'},
        'foreignId': {'laravel': 'foreignId', 'ts': 'number'},
    }

    @classmethod
    def get_laravel_type(cls, field_type):
        return cls.MAPPINGS.get(field_type, {}).get('laravel', 'string')

    @classmethod
    def get_ts_type(cls, field_type):
        return cls.MAPPINGS.get(field_type, {}).get('ts', 'string')


def parse_fields(fields_str):
    """Parse fields string like 'name:string,amount:integer,isActive:boolean'"""
    fields = []
    for field in fields_str.split(','):
        if ':' not in field:
            continue
        name, field_type = field.strip().split(':')
        fields.append({
            'name_camel': name.strip(),
            'name_snake': to_snake_case(name.strip()),
            'type': field_type.strip(),
        })
    return fields


def generate_migration(entity_snake, fields, has_soft_deletes, output_dir):
    """Generate Laravel migration file"""
    timestamp = datetime.now().strftime('%Y_%m_%d_%H%M%S')
    table_name = to_plural(entity_snake)
    filename = f"{timestamp}_create_{table_name}_table.php"

    # Build fields
    field_lines = []
    for field in fields:
        field_name = field['name_snake']
        field_type = field['type']
        laravel_type = FieldType.get_laravel_type(field_type)

        if field_type == 'foreignId':
            # Extract the referenced table from field name
            # e.g., user_id -> users, category_id -> categories
            ref_table = to_plural(field_name.replace('_id', ''))
            field_lines.append(f"            $table->{laravel_type}('{field_name}')->constrained('{ref_table}')->onDelete('cascade');")
        else:
            field_lines.append(f"            $table->{laravel_type}('{field_name}');")

    fields_str = '\n'.join(field_lines)
    soft_deletes = "            $table->softDeletes();" if has_soft_deletes else ""

    content = f"""<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{{
    /**
     * Run the migrations.
     */
    public function up(): void
    {{
        Schema::create('{table_name}', function (Blueprint $table) {{
            $table->id();
{fields_str}
            $table->timestamps();
{soft_deletes}
        }});
    }}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {{
        Schema::dropIfExists('{table_name}');
    }}
}};
"""

    path = os.path.join(output_dir, 'backend', 'database', 'migrations', filename)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as f:
        f.write(content)

    return path


def generate_model(entity_pascal, entity_snake, fields, has_soft_deletes, output_dir):
    """Generate Eloquent model"""
    table_name = to_plural(entity_snake)

    # Build fillable array
    fillable_fields = [f"'{field['name_snake']}'" for field in fields]
    fillable_str = ',\n        '.join(fillable_fields)

    soft_deletes_import = "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n" if has_soft_deletes else ""
    soft_deletes_trait = "    use SoftDeletes;\n\n" if has_soft_deletes else ""

    content = f"""<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
{soft_deletes_import}
class {entity_pascal} extends Model
{{
    use HasFactory;
{soft_deletes_trait}
    protected $table = '{table_name}';

    protected $fillable = [
        {fillable_str}
    ];
}}
"""

    path = os.path.join(output_dir, 'backend', 'app', 'Models', f'{entity_pascal}.php')
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as f:
        f.write(content)

    return path


def generate_controller(entity_pascal, entity_snake, fields, output_dir):
    """Generate API controller"""
    entity_camel = to_camel_case(entity_snake)
    table_name = to_plural(entity_snake)

    # Build validation rules
    validation_rules = []
    for field in fields:
        field_name = field['name_snake']
        field_type = field['type']

        if field_type == 'foreignId':
            ref_table = to_plural(field_name.replace('_id', ''))
            validation_rules.append(f"            '{field_name}' => 'required|exists:{ref_table},id',")
        elif field_type in ['string', 'text']:
            validation_rules.append(f"            '{field_name}' => 'required|string',")
        elif field_type in ['integer', 'bigInteger']:
            validation_rules.append(f"            '{field_name}' => 'required|integer',")
        elif field_type == 'boolean':
            validation_rules.append(f"            '{field_name}' => 'required|boolean',")
        elif field_type in ['date', 'datetime', 'timestamp']:
            validation_rules.append(f"            '{field_name}' => 'required|date',")
        elif field_type in ['decimal', 'float']:
            validation_rules.append(f"            '{field_name}' => 'required|numeric',")
        else:
            validation_rules.append(f"            '{field_name}' => 'required',")

    validation_str = '\n'.join(validation_rules)

    content = f"""<?php

namespace App\\Http\\Controllers;

use App\\Models\\{entity_pascal};
use Illuminate\\Http\\Request;

class {entity_pascal}Controller extends Controller
{{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {{
        ${entity_camel}s = {entity_pascal}::all();
        return response()->json(${entity_camel}s);
    }}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {{
        $validated = $request->validate([
{validation_str}
        ]);

        ${entity_camel} = {entity_pascal}::create($validated);

        return response()->json(${entity_camel}, 201);
    }}

    /**
     * Display the specified resource.
     */
    public function show({entity_pascal} ${entity_camel})
    {{
        return response()->json(${entity_camel});
    }}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, {entity_pascal} ${entity_camel})
    {{
        $validated = $request->validate([
{validation_str}
        ]);

        ${entity_camel}->update($validated);

        return response()->json(${entity_camel});
    }}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy({entity_pascal} ${entity_camel})
    {{
        ${entity_camel}->delete();

        return response()->json(null, 204);
    }}
}}
"""

    path = os.path.join(output_dir, 'backend', 'app', 'Http', 'Controllers', f'{entity_pascal}Controller.php')
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as f:
        f.write(content)

    return path


def generate_routes(entity_pascal, entity_snake, output_dir):
    """Generate route snippet"""
    entity_camel = to_camel_case(entity_snake)
    table_name = to_plural(entity_snake)
    controller = f"{entity_pascal}Controller"

    content = f"""
// Add this to backend/routes/api.php inside the auth:sanctum middleware group:
Route::apiResource('{table_name}', {controller}::class);
"""

    path = os.path.join(output_dir, 'backend', 'routes', f'{entity_snake}_routes.txt')
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as f:
        f.write(content)

    return path


def generate_typescript_interface(entity_pascal, fields, output_dir):
    """Generate TypeScript interface"""
    # Build interface fields
    interface_fields = []
    for field in fields:
        field_name = field['name_camel']
        ts_type = FieldType.get_ts_type(field['type'])
        interface_fields.append(f"  {field_name}: {ts_type};")

    fields_str = '\n'.join(interface_fields)

    content = f"""
// Add this to frontend/src/types/index.ts:

export interface {entity_pascal} {{
  id: number;
{fields_str}
  createdAt: string;
  updatedAt: string;
}}
"""

    path = os.path.join(output_dir, 'frontend', 'src', 'types', f'{entity_snake}_interface.ts')
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as f:
        f.write(content)

    return path


def generate_api_client(entity_pascal, entity_snake, output_dir):
    """Generate API client"""
    entity_camel = to_camel_case(entity_snake)
    entity_camel_plural = to_plural(entity_camel)
    table_name = to_plural(entity_snake)

    content = f"""import axios from './axios';
import type {{ {entity_pascal} }} from '@/types';

export const {entity_camel_plural}Api = {{
  async list(): Promise<{entity_pascal}[]> {{
    const response = await axios.get('/{table_name}');
    return response.data;
  }},

  async get(id: number): Promise<{entity_pascal}> {{
    const response = await axios.get(`/{table_name}/${{id}}`);
    return response.data;
  }},

  async create(data: Omit<{entity_pascal}, 'id' | 'createdAt' | 'updatedAt'>): Promise<{entity_pascal}> {{
    const response = await axios.post('/{table_name}', data);
    return response.data;
  }},

  async update(id: number, data: Partial<Omit<{entity_pascal}, 'id' | 'createdAt' | 'updatedAt'>>): Promise<{entity_pascal}> {{
    const response = await axios.put(`/{table_name}/${{id}}`, data);
    return response.data;
  }},

  async delete(id: number): Promise<void> {{
    await axios.delete(`/{table_name}/${{id}}`);
  }},
}};
"""

    path = os.path.join(output_dir, 'frontend', 'src', 'api', f'{entity_camel_plural}.ts')
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as f:
        f.write(content)

    return path


def generate_pinia_store(entity_pascal, entity_snake, output_dir):
    """Generate Pinia store"""
    entity_camel = to_camel_case(entity_snake)
    entity_camel_plural = to_plural(entity_camel)

    content = f"""import {{ defineStore }} from 'pinia';
import {{ ref }} from 'vue';
import {{ {entity_camel_plural}Api }} from '@/api/{entity_camel_plural}';
import type {{ {entity_pascal} }} from '@/types';

export const use{entity_pascal}Store = defineStore('{entity_camel}', () => {{
  const {entity_camel_plural} = ref<{entity_pascal}[]>([]);
  const current{entity_pascal} = ref<{entity_pascal} | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetch{entity_pascal}s() {{
    loading.value = true;
    error.value = null;
    try {{
      {entity_camel_plural}.value = await {entity_camel_plural}Api.list();
    }} catch (e: any) {{
      error.value = e.response?.data?.message || 'Failed to fetch {entity_camel_plural}';
      throw e;
    }} finally {{
      loading.value = false;
    }}
  }}

  async function fetch{entity_pascal}(id: number) {{
    loading.value = true;
    error.value = null;
    try {{
      current{entity_pascal}.value = await {entity_camel_plural}Api.get(id);
    }} catch (e: any) {{
      error.value = e.response?.data?.message || 'Failed to fetch {entity_camel}';
      throw e;
    }} finally {{
      loading.value = false;
    }}
  }}

  async function create{entity_pascal}(data: Omit<{entity_pascal}, 'id' | 'createdAt' | 'updatedAt'>) {{
    loading.value = true;
    error.value = null;
    try {{
      const new{entity_pascal} = await {entity_camel_plural}Api.create(data);
      {entity_camel_plural}.value.push(new{entity_pascal});
      return new{entity_pascal};
    }} catch (e: any) {{
      error.value = e.response?.data?.message || 'Failed to create {entity_camel}';
      throw e;
    }} finally {{
      loading.value = false;
    }}
  }}

  async function update{entity_pascal}(id: number, data: Partial<Omit<{entity_pascal}, 'id' | 'createdAt' | 'updatedAt'>>) {{
    loading.value = true;
    error.value = null;
    try {{
      const updated{entity_pascal} = await {entity_camel_plural}Api.update(id, data);
      const index = {entity_camel_plural}.value.findIndex((item) => item.id === id);
      if (index !== -1) {{
        {entity_camel_plural}.value[index] = updated{entity_pascal};
      }}
      if (current{entity_pascal}.value?.id === id) {{
        current{entity_pascal}.value = updated{entity_pascal};
      }}
      return updated{entity_pascal};
    }} catch (e: any) {{
      error.value = e.response?.data?.message || 'Failed to update {entity_camel}';
      throw e;
    }} finally {{
      loading.value = false;
    }}
  }}

  async function delete{entity_pascal}(id: number) {{
    loading.value = true;
    error.value = null;
    try {{
      await {entity_camel_plural}Api.delete(id);
      {entity_camel_plural}.value = {entity_camel_plural}.value.filter((item) => item.id !== id);
      if (current{entity_pascal}.value?.id === id) {{
        current{entity_pascal}.value = null;
      }}
    }} catch (e: any) {{
      error.value = e.response?.data?.message || 'Failed to delete {entity_camel}';
      throw e;
    }} finally {{
      loading.value = false;
    }}
  }}

  return {{
    {entity_camel_plural},
    current{entity_pascal},
    loading,
    error,
    fetch{entity_pascal}s,
    fetch{entity_pascal},
    create{entity_pascal},
    update{entity_pascal},
    delete{entity_pascal},
  }};
}});
"""

    path = os.path.join(output_dir, 'frontend', 'src', 'stores', f'{entity_camel}.ts')
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as f:
        f.write(content)

    return path


def generate_vue_page(entity_pascal, entity_snake, output_dir):
    """Generate Vue page component"""
    entity_camel = to_camel_case(entity_snake)
    entity_camel_plural = to_plural(entity_camel)

    content = f"""<script setup lang="ts">
import {{ onMounted }} from 'vue';
import {{ use{entity_pascal}Store }} from '@/stores/{entity_camel}';

const {entity_camel}Store = use{entity_pascal}Store();

onMounted(() => {{
  {entity_camel}Store.fetch{entity_pascal}s();
}});
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">{entity_pascal}s</h1>

    <div v-if="{entity_camel}Store.loading" class="text-center py-8">
      <p>Loading...</p>
    </div>

    <div v-else-if="{entity_camel}Store.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
      {{ {entity_camel}Store.error }}
    </div>

    <div v-else>
      <table class="min-w-full bg-white border border-gray-300">
        <thead>
          <tr class="bg-gray-100">
            <th class="px-6 py-3 border-b text-left">ID</th>
            <!-- Add more columns based on your fields -->
            <th class="px-6 py-3 border-b text-left">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="{entity_camel} in {entity_camel}Store.{entity_camel_plural}" :key="{entity_camel}.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 border-b">{{ {entity_camel}.id }}</td>
            <!-- Add more cells based on your fields -->
            <td class="px-6 py-4 border-b">
              <button @click="{entity_camel}Store.delete{entity_pascal}({entity_camel}.id)" class="text-red-600 hover:text-red-800">
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
"""

    path = os.path.join(output_dir, 'frontend', 'src', 'pages', f'{entity_pascal}sPage.vue')
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as f:
        f.write(content)

    return path


def generate_vue_route(entity_pascal, entity_snake, output_dir):
    """Generate Vue Router route snippet"""
    entity_camel_plural = to_plural(to_camel_case(entity_snake))
    table_name = to_plural(entity_snake)

    content = f"""
// Add this to frontend/src/router/index.ts inside the routes array:
{{
  path: '/{table_name}',
  name: '{entity_camel_plural}',
  component: () => import('@/pages/{entity_pascal}sPage.vue'),
  meta: {{ requiresAuth: true }},
}},
"""

    path = os.path.join(output_dir, 'frontend', 'src', 'router', f'{entity_snake}_route.txt')
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as f:
        f.write(content)

    return path


def main():
    parser = argparse.ArgumentParser(description='Generate CRUD entity for Budget Manager')
    parser.add_argument('entity', help='Entity name (e.g., category, transaction)')
    parser.add_argument('--fields', required=True, help='Fields definition (e.g., "name:string,amount:integer")')
    parser.add_argument('--soft-deletes', action='store_true', help='Add soft deletes support')
    parser.add_argument('--output', default='.', help='Output directory (default: current directory)')

    args = parser.parse_args()

    # Normalize entity name
    entity_snake = to_snake_case(args.entity)
    entity_pascal = to_pascal_case(entity_snake)

    # Parse fields
    fields = parse_fields(args.fields)

    if not fields:
        print("Error: No valid fields provided")
        sys.exit(1)

    print(f"Generating entity: {entity_pascal} ({entity_snake})")
    print(f"Fields: {len(fields)}")
    print(f"Soft deletes: {args.soft_deletes}")
    print()

    # Generate all files
    generated_files = []

    print("Generating backend files...")
    generated_files.append(generate_migration(entity_snake, fields, args.soft_deletes, args.output))
    generated_files.append(generate_model(entity_pascal, entity_snake, fields, args.soft_deletes, args.output))
    generated_files.append(generate_controller(entity_pascal, entity_snake, fields, args.output))
    generated_files.append(generate_routes(entity_pascal, entity_snake, args.output))

    print("Generating frontend files...")
    generated_files.append(generate_typescript_interface(entity_pascal, fields, args.output))
    generated_files.append(generate_api_client(entity_pascal, entity_snake, args.output))
    generated_files.append(generate_pinia_store(entity_pascal, entity_snake, args.output))
    generated_files.append(generate_vue_page(entity_pascal, entity_snake, args.output))
    generated_files.append(generate_vue_route(entity_pascal, entity_snake, args.output))

    print("\n✓ Entity generation complete!")
    print("\nGenerated files:")
    for file_path in generated_files:
        print(f"  - {file_path}")

    print("\n⚠ Manual steps required:")
    print(f"  1. Add route to backend/routes/api.php (see {args.output}/backend/routes/{entity_snake}_routes.txt)")
    print(f"  2. Add interface to frontend/src/types/index.ts (see {args.output}/frontend/src/types/{entity_snake}_interface.ts)")
    print(f"  3. Add route to frontend/src/router/index.ts (see {args.output}/frontend/src/router/{entity_snake}_route.txt)")
    print(f"  4. Run migration: make artisan CMD=\"migrate\"")
    print(f"  5. Customize the Vue page component as needed")


if __name__ == '__main__':
    main()