<?php

namespace Mdarmancse\AutoLara\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Exception;

class GenerateCrudCommand extends Command
{
    protected $signature = 'autolara:crud {name}';
    protected $description = 'Generate CRUD files using Repository Pattern';

    public function handle()
    {
        $name = ucfirst($this->argument('name'));
        $this->info("🔄 Generating CRUD for: $name");

        try {
            $this->generateModel($name);
            $this->generateMigration($name);
            $this->generateRepository($name);
            $this->generateController($name);
            $this->generateRequest($name);
            $this->updateRoutes($name);

            $this->info("✅ CRUD for $name generated successfully!");
        } catch (Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }

    private function generateModel($name)
    {
        $path = app_path("Models/{$name}.php");

        if ($this->fileExists($path, "Model")) {
            return;
        }

        $this->createFileFromStub('model', $path, ['{{name}}' => $name]);
        $this->info("✅ Model created: app/Models/{$name}.php");
    }

    private function generateMigration($name)
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
        $stubPath = __DIR__ . '/../../stubs/repository.stub';

        if (!File::exists($stubPath)) {
            $this->error("❌ Stub file missing: {$stubPath}");
            return;
        }

        $stub = file_get_contents($stubPath);
        $stub = str_replace(['{{name}}', '{{ Name }}'], $name, $stub);

        File::put($filePath, $stub);
        $this->info("✅ Repository created: app/Repositories/{$name}Repository.php");
    }

    private function generateController($name)
    {
        $controllerPath = app_path('Http/Controllers');
        $controllerFile = "{$controllerPath}/{$name}Controller.php";

        if (!File::exists($controllerPath)) {
            File::makeDirectory($controllerPath, 0755, true);
        }

        $stubPath = __DIR__ . '/../../stubs/controller.stub';

        if (!File::exists($stubPath)) {
            $this->error("❌ Stub file missing: {$stubPath}");
            return;
        }

        $stub = file_get_contents($stubPath);
        $stub = str_replace(
            ['{{name}}', '{{Name}}'],
            [$name, ucfirst($name)],
            $stub
        );

        File::put($controllerFile, $stub);
        $this->info("✅ Controller created: app/Http/Controllers/{$name}Controller.php");
    }

    private function generateRequest($name)
    {
        $this->call('make:request', ['name' => "{$name}Request"]);
        $this->info("✅ Request created: app/Http/Requests/{$name}Request.php");
    }

    private function updateRoutes($name)
    {
        $routePath = base_path('routes/api.php');

        // Ensure routes directory exists
        if (!File::exists(dirname($routePath))) {
            File::makeDirectory(dirname($routePath), 0755, true);
        }

        // Ensure api.php file exists
        if (!File::exists($routePath)) {
            File::put($routePath, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n");
            $this->info("✅ Created routes/api.php file.");
        }

        $routeDefinition = "\n// AutoLara Routes for {$name}
Route::apiResource('" . strtolower(Str::plural($name)) . "', \\App\\Http\\Controllers\\{$name}Controller::class);";

        if (strpos(file_get_contents($routePath), "{$name}Controller::class") === false) {
            file_put_contents($routePath, $routeDefinition, FILE_APPEND);
            $this->info("✅ Routes added for {$name} in routes/api.php");
        } else {
            $this->warn("⚠️ Routes for {$name} already exist in routes/api.php");
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
}
