<?php

namespace Mdarmancse\AutoLara\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Exception;

class GenerateCrudCommand extends Command
{
    protected $signature = 'autolara:crud {name} {--columns=}';
    protected $description = 'Generate CRUD files using Repository Pattern with dynamic columns';

    public function handle()
    {
        $name = ucfirst($this->argument('name'));
        $columns = $this->option('columns');
        $columnsArray = $this->parseColumns($columns);

        $this->info("🔄 Generating CRUD for: $name");

        try {
            $this->generateModel($name, $columnsArray);
            $this->generateMigration($name, $columnsArray);
            $this->generateRepository($name);
            $this->generateController($name);
            $this->generateRequest($name);
            $this->generateRoutes($name);

            $this->info("✅ CRUD for $name generated successfully!");
        } catch (Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }

    private function generateModel($name, $columnsArray)
    {
        $path = app_path("Models/{$name}.php");

        if ($this->fileExists($path, "Model")) {
            return;
        }

        $fillable = implode(", ", array_map(fn($col) => "'{$col['name']}'", $columnsArray));
        $this->createFileFromStub('model', $path, [
            '{{name}}' => $name,
            '{{fillable}}' => "[{$fillable}]"
        ]);

        $this->info("✅ Model created: app/Models/{$name}.php");
    }

    private function generateMigration($name, $columnsArray)
    {
        $table = Str::plural(Str::snake($name));
        $this->call('make:migration', ['name' => "create_{$table}_table"]);
        $this->info("✅ Migration created: database/migrations/*_create_{$table}_table.php");
    }

    private function generateRepository($name)
    {
        $repositoryPath = app_path('Repositories');

        if (!File::exists($repositoryPath)) {
            File::makeDirectory($repositoryPath, 0755, true);
        }

        $filePath = "{$repositoryPath}/{$name}Repository.php";
        $this->createFileFromStub('repository', $filePath, ['{{name}}' => $name]);

        $this->info("✅ Repository created: app/Repositories/{$name}Repository.php");
    }

    private function generateController($name)
    {
        $this->call('make:controller', ['name' => "{$name}Controller", '--resource' => true]);
        $this->info("✅ Controller created: app/Http/Controllers/{$name}Controller.php");
    }

    private function generateRequest($name)
    {
        $this->call('make:request', ['name' => "{$name}Request"]);
        $this->info("✅ Request created: app/Http/Requests/{$name}Request.php");
    }

    private function generateRoutes($name)
    {
        $apiRoutesPath = base_path('routes/api.php');
        if (!File::exists($apiRoutesPath)) {
            File::put($apiRoutesPath, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n");
            $this->info("✅ Created routes/api.php file");
        }

        $routeDefinition = "Route::apiResource('" . Str::plural(Str::snake($name)) . "', " . $name . "Controller::class);\n";
        File::append($apiRoutesPath, $routeDefinition);
        $this->info("✅ Routes added to routes/api.php");
    }

    private function parseColumns($columns)
    {
        $columnsArray = [];
        if ($columns) {
            $pairs = explode(' ', $columns);
            foreach ($pairs as $pair) {
                [$name, $type] = explode(':', $pair);
                $columnsArray[] = ['name' => $name, 'type' => $type];
            }
        }
        return $columnsArray;
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
}
