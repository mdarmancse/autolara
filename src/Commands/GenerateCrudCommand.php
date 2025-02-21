<?php

namespace Mdarmancse\AutoLara\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Exception;

class GenerateCrudCommand extends Command
{
    protected $signature = 'autolara:crud {model} {fields*}';
    protected $description = 'Generate CRUD files using Repository Pattern with Migration, Seeder, and Routes';

    public function handle()
    {
        $model = ucfirst($this->argument('model'));
        $fields = $this->argument('fields');

        $table = Str::plural(Str::snake($model));

        $this->info("🔄 Generating CRUD for: $model");

        try {
            $this->generateModel($model);
            $this->generateMigration($model, $fields);
            $this->generateRepository($model);
            $this->generateController($model);
            $this->generateRequest($model);
            $this->generateSeeder($model, $fields);
            $this->updateRoutes($model);

            $this->info("⚡ Running Migration & Seeding...");
            $this->call('migrate');
            $this->call('db:seed', ['--class' => "{$model}Seeder"]);

            $this->info("✅ CRUD for $model generated successfully!");
        } catch (Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }

    private function generateModel($model)
    {
        $path = app_path("Models/{$model}.php");
        if (File::exists($path)) {
            $this->warn("⚠️ Model already exists: app/Models/{$model}.php");
            return;
        }
        $this->createFileFromStub('model', $path, ['{{model}}' => $model]);
    }

    private function generateMigration($model, $fields)
    {
        $table = Str::plural(Str::snake($model));
        $fieldDefinitions = "";
        foreach ($fields as $field) {
            [$name, $type] = explode(':', $field);
            $fieldDefinitions .= "\$table->${type}('${name}');\n";
        }
        $this->call('make:migration', ['name' => "create_{$table}_table"]);
    }

    private function generateRepository($model)
    {
        $path = app_path("Repositories/{$model}Repository.php");
        $this->createFileFromStub('repository', $path, ['{{model}}' => $model]);
    }

    private function generateController($model)
    {
        $path = app_path("Http/Controllers/{$model}Controller.php");
        $this->createFileFromStub('controller', $path, ['{{model}}' => $model]);
    }

    private function generateRequest($model)
    {
        $this->call('make:request', ['name' => "{$model}Request"]);
    }

    private function generateSeeder($model, $columns)
    {
        $table = Str::plural(Str::snake($model));
        $seederPath = database_path("seeders/{$model}Seeder.php");
        $stubPath = __DIR__ . '/../../stubs/seeder.stub';

        if (!File::exists($stubPath)) {
            $this->error("❌ Stub file missing: {$stubPath}");
            return;
        }

        // Ensure correct formatting of column data
        $columnData = [];
        foreach ($columns as $column => $type) {
            $fakerValue = $this->getFakerValue($type);
            $columnData[] = "                '{$column}' => {$fakerValue}";
        }

        // Convert array to properly formatted string
        $columnString = implode(",\n", $columnData);

        $stub = file_get_contents($stubPath);
        $stub = str_replace(
            ['{{model}}', '{{table}}', '{{dynamic_columns}}'],
            [$model, $table, $columnString],
            $stub
        );

        File::put($seederPath, $stub);
        $this->info("✅ Seeder created: database/seeders/{$model}Seeder.php");
    }

    /**
     * Get corresponding Faker function for different column types.
     */
    private function getFakerValue($type)
    {
        return match ($type) {
            'string' => '$faker->word',
            'integer' => '$faker->randomNumber()',
            'boolean' => '$faker->boolean',
            'text' => '$faker->sentence',
            'date' => '$faker->date',
            'datetime' => '$faker->dateTime',
            'email' => '$faker->email',
            'float' => '$faker->randomFloat(2, 1, 1000)',
            default => "''"
        };
    }

    private function updateRoutes($model)
    {
        $routesPath = base_path('routes/api.php');
        if (!File::exists($routesPath)) {
            File::put($routesPath, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");
        }
        $routeEntry = "Route::apiResource('" . Str::plural(Str::snake($model)) . "', \App\Http\Controllers\\{$model}Controller::class);\n";
        File::append($routesPath, "\n" . $routeEntry);
    }

    private function createFileFromStub($stubName, $destinationPath, array $replacements = [])
    {
        $stubPath = realpath(__DIR__ . "/../../stubs/{$stubName}.stub");
        if (!$stubPath || !File::exists($stubPath)) {
            throw new Exception("Stub file '{$stubName}.stub' not found.");
        }
        $stubContent = File::get($stubPath);
        $content = str_replace(array_keys($replacements), array_values($replacements), $stubContent);
        File::put($destinationPath, $content);
    }
}
