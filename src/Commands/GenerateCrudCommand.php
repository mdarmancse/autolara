<?php

namespace Mdarmancse\AutoLara\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Exception;

class GenerateCrudCommand extends Command
{
    protected $signature = 'autolara:crud {model} {fields*}';
    protected $description = 'Generate CRUD files using Repository Pattern';

    public function handle()
    {
        $model = ucfirst($this->argument('model'));
        $fields = $this->argument('fields');

        $this->info("🔄 Generating CRUD for: $model with fields: " . implode(', ', $fields));

        try {
            $this->generateModel($model, $fields);
            $this->generateMigration($model, $fields);
            $this->generateRepository($model);
            $this->generateRequest($model, $fields);
            $this->generateController($model);
            $this->addRoutes($model);

            $this->info("✅ CRUD for $model generated successfully!");
        } catch (Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }

    private function generateModel($model, $fields)
    {
        $path = app_path("Models/{$model}.php");

        if ($this->fileExists($path, "Model")) {
            return;
        }

        // Generate fillable fields
        $fillableFields = array_map(fn($field) => "'" . explode(':', $field)[0] . "'", $fields);
        $fillable = implode(", ", $fillableFields);

        $this->createFileFromStub('model', $path, [
            '{__model__}' => $model,
            '{__fillable__}' => $fillable
        ]);

        $this->info("✅ Model created: app/Models/{$model}.php");
    }

    private function generateMigration($model, $fields)
    {
        $table = Str::plural(Str::snake($model));
        $migrationName = "create_{$table}_table";

        $this->call('make:migration', ['name' => $migrationName]);

        $this->info("✅ Migration created: database/migrations/*_{$migrationName}.php");
    }

    private function generateRepository($model)
    {
        $repositoryPath = app_path("Repositories/{$model}Repository.php");

        if ($this->fileExists($repositoryPath, "Repository")) {
            return;
        }

        $this->createFileFromStub('repository', $repositoryPath, [
            '{__model__}' => $model
        ]);

        $this->info("✅ Repository created: app/Repositories/{$model}Repository.php");
    }

    private function generateRequest($model, $fields)
    {
        $requestPath = app_path("Http/Requests/{$model}Request.php");

        if ($this->fileExists($requestPath, "Request")) {
            return;
        }

        // Generate validation rules dynamically
        $rulesArray = [];
        foreach ($fields as $field) {
            [$name, $type] = explode(':', $field);
            $rulesArray[] = "'$name' => '" . $this->getValidationRule($type) . "'";
        }
        $rules = implode(",\n            ", $rulesArray);

        $this->createFileFromStub('request', $requestPath, [
            '{__model__}' => $model,
            '{__rules__}' => $rules
        ]);

        $this->info("✅ Request created: app/Http/Requests/{$model}Request.php");
    }

    private function generateController($model)
    {
        $controllerPath = app_path("Http/Controllers/{$model}Controller.php");

        if ($this->fileExists($controllerPath, "Controller")) {
            return;
        }

        $this->createFileFromStub('controller', $controllerPath, [
            '{__model__}' => $model
        ]);

        $this->info("✅ Controller created: app/Http/Controllers/{$model}Controller.php");
    }

    private function addRoutes($model)
    {
        $apiRoutesPath = base_path('routes/api.php');

        if (!File::exists($apiRoutesPath)) {
            File::put($apiRoutesPath, "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n");
        }

        $routeEntry = "Route::apiResource('" . Str::plural(Str::snake($model)) . "', \App\Http\Controllers\\{$model}Controller::class);\n";

        if (!str_contains(File::get($apiRoutesPath), $routeEntry)) {
            File::append($apiRoutesPath, $routeEntry);
            $this->info("✅ Route added: " . trim($routeEntry));
        } else {
            $this->warn("⚠️ Route already exists in api.php");
        }
    }

    private function createFileFromStub($stubName, $destinationPath, array $replacements = [])
    {
        $stubPath = realpath(__DIR__ . "/../../stubs/{$stubName}.stub");

        if (!$stubPath || !File::exists($stubPath)) {
            throw new Exception("Stub file '{$stubName}.stub' not found in the stubs directory.");
        }

        $stubContent = File::get($stubPath);
        $content = str_replace(array_keys($replacements), array_values($replacements), $stubContent);

        File::put($destinationPath, $content);
    }

    private function fileExists($filePath, $type)
    {
        if (File::exists($filePath)) {
            $this->warn("⚠️ {$type} already exists: " . str_replace(base_path() . '/', '', $filePath));
            return true;
        }
        return false;
    }

    private function getValidationRule($type)
    {
        return match ($type) {
            'integer' => 'required|integer',
            'boolean' => 'required|boolean',
            'string' => 'required|string|max:255',
            'text' => 'required|string',
            'date' => 'required|date',
            'email' => 'required|email|unique:users,email',
            default => 'required'
        };
    }
}
