<?php

namespace Mdarmancse\AutoLara\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Exception;

class GenerateCrudCommand extends Command
{
    protected $signature = 'autolara:crud {model} {fields*}';
    protected $description = 'Generate CRUD operations including model, migration, repository, controller, and routes';

    public function handle()
    {
        $model = ucfirst($this->argument('model'));
        $fields = $this->argument('fields');
        $table = Str::plural(Str::snake($model));

        $this->info("🔄 Generating CRUD for: $model");

        try {
            $this->generateModel($model, $fields);
            $this->generateMigration($model, $fields);
            $this->generateRepository($model);
            $this->generateController($model);
            $this->generateRequest($model);
            $this->addRoutes($model);

            // Run Migration
            $this->runMigration();

            $this->info("✅ CRUD for $model generated successfully!");
        } catch (Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->call('migrate:rollback');
        }
    }

    private function generateModel($model, $fields)
    {
        $fillable = "'" . implode("', '", array_map(fn($f) => explode(':', $f)[0], $fields)) . "'";

        $this->generateFromStub('model.stub', app_path("Models/{$model}.php"), [
            '{{model}}' => $model,
            '{{fillable}}' => $fillable
        ]);
        $this->info("✅ Model created: $model");
    }

    private function generateMigration($model, $fields)
    {
        $table = Str::plural(Str::snake($model));

        $formattedFields = '';
        foreach ($fields as $field) {
            [$name, $type] = explode(':', $field);
            $formattedFields .= "\t\t\t\$table->$type('$name');\n";
        }

        $this->generateFromStub('migration.stub', database_path("migrations/" . date('Y_m_d_His') . "_create_{$table}_table.php"), [
            '{{table}}' => $table,
            '{{fields}}' => trim($formattedFields)
        ]);
        $this->info("✅ Migration created with fields: $table");
    }

    private function generateRepository($model)
    {
        $this->generateFromStub('repository.stub', app_path("Repositories/{$model}Repository.php"), [
            '{{model}}' => $model
        ]);
        $this->info("✅ Repository for $model generated.");
    }

    private function generateController($model)
    {
        $this->generateFromStub('controller.stub', app_path("Http/Controllers/{$model}Controller.php"), [
            '{{model}}' => $model
        ]);
        $this->info("✅ Controller created: {$model}Controller");
    }

    private function generateRequest($model)
    {
        $this->generateFromStub('request.stub', app_path("Http/Requests/{$model}Request.php"), [
            '{{model}}' => $model,
            '{{rules}}' => "'name' => 'required|string'"
        ]);
        $this->info("✅ Form request for $model generated.");
    }
    private function addRoutes($model)
    {
        $routeFile = base_path('routes/api.php');

        if (!file_exists($routeFile)) {
            file_put_contents($routeFile, "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n");
        }

        $routeContent = file_get_contents($routeFile);

        $routeDefinition = "\nRoute::apiResource('" . Str::plural(Str::snake($model)) . "', App\Http\Controllers\\{$model}Controller::class);\n";

        if (!str_contains($routeContent, $routeDefinition)) {
            file_put_contents($routeFile, $routeContent . $routeDefinition);
            $this->info("✅ Route added: /api/" . Str::plural(Str::snake($model)));
        } else {
            $this->warn("⚠️ Route already exists.");
        }
    }


    private function runMigration()
    {
        $migrationFile = collect(File::files(database_path('migrations')))
            ->sortByDesc(fn($file) => $file->getCTime())
            ->first();

        if ($migrationFile) {
            $relativePath = 'database/migrations/' . $migrationFile->getFilename();
            $this->info("⚡ Running Migration for: " . $migrationFile->getFilename());
            $this->call('migrate', ['--path' => $relativePath]);
        } else {
            $this->error("❌ No migration file found!");
        }
    }

    private function generateFromStub($stubFile, $destination, $replacements)
    {
        // Get absolute stub file path
        $stubPath = base_path("vendor/Mdarmancse/AutoLara/stubs/{$stubFile}");

        // Normalize the path to prevent double slashes
        $stubPath = str_replace(['\\', '//'], DIRECTORY_SEPARATOR, $stubPath);

        if (!File::exists($stubPath)) {
            throw new Exception("Stub file not found: " . realpath($stubPath));
        }

        $content = File::get($stubPath);
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        File::ensureDirectoryExists(dirname($destination));
        File::put($destination, $content);
    }

}
