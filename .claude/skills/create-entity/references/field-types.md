# Field Types Reference

This document lists all supported field types for entity generation with their Laravel migration and TypeScript mappings.

## Supported Field Types

| Field Type | Laravel Migration | TypeScript Type | Description | Example |
|------------|------------------|-----------------|-------------|---------|
| `string` | `string` | `string` | Variable-length string (VARCHAR 255) | `name:string` |
| `text` | `text` | `string` | Long text (TEXT) | `description:text` |
| `integer` | `integer` | `number` | Integer number | `count:integer` |
| `bigInteger` | `bigInteger` | `number` | Big integer (BIGINT) | `amount:bigInteger` |
| `boolean` | `boolean` | `boolean` | True/false value | `isActive:boolean` |
| `date` | `date` | `string` | Date only (YYYY-MM-DD) | `birthDate:date` |
| `datetime` | `dateTime` | `string` | Date and time | `scheduledAt:datetime` |
| `timestamp` | `timestamp` | `string` | Timestamp | `publishedAt:timestamp` |
| `decimal` | `decimal` | `number` | Decimal number with precision | `price:decimal` |
| `float` | `float` | `number` | Floating point number | `rating:float` |
| `json` | `json` | `any` | JSON data | `metadata:json` |
| `foreignId` | `foreignId` | `number` | Foreign key reference | `userId:foreignId` |

## Field Naming Conventions

### Backend (Laravel)
- **Database columns**: `snake_case`
- **Model attributes**: `snake_case`
- Examples: `user_id`, `created_at`, `is_active`

### Frontend (TypeScript/Vue)
- **Interface properties**: `camelCase`
- **Variable names**: `camelCase`
- Examples: `userId`, `createdAt`, `isActive`

### Automatic Conversion
The project uses middleware to automatically convert between cases:
- **Request**: `ConvertRequestToSnakeCase` - Converts frontend camelCase → backend snake_case
- **Response**: `ConvertResponseToCamelCase` - Converts backend snake_case → frontend camelCase

## Foreign Key Conventions

When using `foreignId`, follow these naming patterns:

### Field Naming
- Field name should end with `Id` in camelCase (frontend) or `_id` in snake_case (backend)
- Examples: `userId`, `categoryId`, `budgetTemplateId`

### Automatic Table Resolution
The generator automatically extracts the referenced table from the field name:
- `userId` → references `users` table
- `categoryId` → references `categories` table
- `roleId` → references `roles` table

### Generated Constraint
```php
$table->foreignId('user_id')->constrained('users')->onDelete('cascade');
```

## Money/Currency Fields

For monetary values, use `integer` type and store amounts in cents:

```
amount:integer        // Store 1599 for 15.99€
priceCents:integer    // Explicit naming
```

**Important conventions:**
- Database: Store in cents (integer)
- API: Transfer cents values
- Frontend display: Convert to euros (`cents / 100`)
- Frontend input: Convert euros to cents (`euros * 100`)

## Validation Rules

The generator creates validation rules based on field types:

| Field Type | Validation Rule |
|------------|----------------|
| `string`, `text` | `required\|string` |
| `integer`, `bigInteger` | `required\|integer` |
| `boolean` | `required\|boolean` |
| `date`, `datetime`, `timestamp` | `required\|date` |
| `decimal`, `float` | `required\|numeric` |
| `foreignId` | `required\|exists:table,id` |

## Timestamps

All entities automatically include:
- `created_at` (timestamp)
- `updated_at` (timestamp)

These are added by Laravel's `$table->timestamps()` and do not need to be specified in the fields list.

## Soft Deletes

Use the `--soft-deletes` flag to add soft delete support:
- Adds `deleted_at` column to migration
- Adds `SoftDeletes` trait to model
- Deleted records are not permanently removed from database

## Example Usage

### Simple Entity
```bash
/create-entity category --fields="name:string,description:text,isActive:boolean"
```

### Entity with Relations
```bash
/create-entity expense --fields="amount:integer,description:text,date:date,categoryId:foreignId,userId:foreignId"
```

### Entity with Soft Deletes
```bash
/create-entity product --fields="name:string,price:integer,stock:integer" --soft-deletes
```

### Complex Entity
```bash
/create-entity subscription --fields="userId:foreignId,planId:foreignId,startDate:date,endDate:date,status:string,amountCents:integer,metadata:json" --soft-deletes
```