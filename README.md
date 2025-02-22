# AutoLara - Laravel Auto CRUD Generator

AutoLara is a Laravel package that automatically generates CRUD operations, including models, migrations, controllers, repositories, and routes based on a simple Artisan command.

## Features
- 🔹 Generates Model, Migration, Controller, Repository, and Form Requests.
- 🔹 Updates Routes automatically.
- 🔹 Runs migration after generation.
- 🔹 Uses stub templates for customization.

## Installation

Run the following command to install AutoLara:
```sh
composer require mdarmancse/autolara --dev
```

## Usage

Run the following command to generate CRUD for a model with specified fields:
```sh
php artisan autolara:crud ModelName field1:type field2:type ...
```

### Example:
```sh
php artisan autolara:crud Product name:string price:integer is_active:boolean
```

### Expected Output:
```
🔄 Generating CRUD for: Product
✅ Model created: Product
✅ Migration created: products
✅ Repository for Product generated.
✅ Controller created: ProductController
✅ Form request for Product generated.
✅ Routes for Product updated.
⚡ Running Migration...
✅ Migration successful.
```

## Stubs Customization

You can customize the stub templates by copying them to `resources/stubs/`:
```sh
mkdir -p resources/stubs
cp -r vendor/mdarmancse/autolara/stubs resources/stubs
```
Then modify the stub files inside `resources/stubs/` to fit your project requirements.

## Available Field Types
The package supports the following field types:
- `string`
- `integer`
- `boolean`
- `text`
- `date`
- `datetime`
- `float`
- `double`

## Troubleshooting

### 1. Stub File Not Found Error
If you encounter this error:
```
❌ Error: Stub file not found: vendor/mdarmancse/autolara/stubs/model.stub
```
Try running:
```sh
php artisan config:clear && php artisan cache:clear
```
If the issue persists, ensure that the stub files exist in the `vendor/mdarmancse/autolara/stubs/` directory.

### 2. Migration File Not Found Error
If you see:
```
❌ Error: File does not exist at path database/migrations/xxxx_xx_xx_xxxxxx_create_products_table.php
```
Manually run:
```sh
php artisan migrate
```


---

🚀 **Happy Coding!**

