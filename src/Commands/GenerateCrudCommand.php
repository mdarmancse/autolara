<?php

namespace Mdarmancse\AutoLara\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateCrudCommand extends Command
{
    protected $signature = 'autolara:crud {name}';
    protected $description = 'Generate CRUD files for a given model name';

    public function handle()
    {
        $name = ucfirst($this->argument('name'));
        $this->info("Generating CRUD for: $name");

        $this->generateModel($name);
        $this->generateMigration($name);
        $this->generateRepository($name);
        $this->generateController($name);
        $this->generateRequest($name);
        $this->updateRoutes($name);

        $this->info("✅ CRUD for $name generated successfully!");
    }

    private function generateModel($name)
    {
        $path = app_path("Models/{$name}.php");
        if (!File::exists($path)) {
            $template = str_replace('{{name}}', $name, $this->getStub('model'));
            File::put($path, $template);
            $this->info("✅ Model created: Models/{$name}.php");
        } else {
            $this->warn("⚠️ Model already exists: Models/{$name}.php");
        }
    }

    private function generateMigration($name)
    {
        $table = strtolower(Str::plural($name));
        $this->call('make:migration', [
            'name' => "create_{$table}_table"
        ]);
        $this->info("✅ Migration created: database/migrations/*_create_{$table}_table.php");
    }

    private function generateRepository($name)
    {
        $repositoryPath = app_path('Repositories');
        if (!File::exists($repositoryPath)) {
            File::makeDirectory($repositoryPath, 0755, true);
        }

        $filePath = "{$repositoryPath}/{$name}Repository.php";

        $stub = file_get_contents(__DIR__ . '/../stubs/repository.stub');
        $stub = str_replace('{{model}}', $name, $stub);

        File::put($filePath, $stub);

        $this->info("✅ Repository created: app/Repositories/{$name}Repository.php");
    }

    private function generateController($name)
    {
        $this->call('make:controller', [
            'name' => "{$name}Controller",
            '--resource' => true
        ]);
        $this->info("✅ Controller created: app/Http/Controllers/{$name}Controller.php");
    }

    private function generateRequest($name)
    {
        $this->call('make:request', [
            'name' => "{$name}Request"
        ]);
        $this->info("✅ Request created: app/Http/Requests/{$name}Request.php");
    }

    private function updateRoutes($name)
    {
        $routePath = base_path('routes/api.php');
        $routeDefinition = "\n// AutoLara Routes for {$name}
Route::apiResource('" . strtolower(Str::plural($name)) . "', \\App\\Http\\Controllers\\{$name}Controller::class);";

        if (file_exists($routePath)) {
            if (strpos(file_get_contents($routePath), "{$name}Controller::class") === false) {
                file_put_contents($routePath, $routeDefinition, FILE_APPEND);
                $this->info("✅ Routes added for {$name} in routes/api.php");
            } else {
                $this->warn("⚠️ Routes for {$name} already exist in routes/api.php");
            }
        } else {
            $this->warn("⚠️ routes/api.php not found, could not add routes.");
        }
    }

    private function getStub($type)
    {
        return File::get(__DIR__ . "/../stubs/{$type}.stub");
    }
}
