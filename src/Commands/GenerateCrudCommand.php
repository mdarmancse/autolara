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
        $path = app_path("Repositories/{$name}Repository.php");
        if (!File::exists($path)) {
            $template = str_replace('{{name}}', $name, $this->getStub('repository'));
            File::put($path, $template);
            $this->info("✅ Repository created: Repositories/{$name}Repository.php");
        } else {
            $this->warn("⚠️ Repository already exists: Repositories/{$name}Repository.php");
        }
    }

    private function generateController($name)
    {
        $this->call('make:controller', [
            'name' => "{$name}Controller",
            '--resource' => true
        ]);
        $this->info("✅ Controller created: Http/Controllers/{$name}Controller.php");
    }

    private function generateRequest($name)
    {
        $this->call('make:request', [
            'name' => "{$name}Request"
        ]);
        $this->info("✅ Request created: Http/Requests/{$name}Request.php");
    }

    private function getStub($type)
    {
        return File::get(__DIR__."/../../stubs/{$type}.stub");
    }
}
