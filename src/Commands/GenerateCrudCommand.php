<?php

namespace Mdarmancse\AutoLara\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class GenerateCrudCommand extends Command
{
    protected $signature = 'auto-crud:generate {table} {columns}';
    protected $description = 'Generate CRUD (Migration, Model, Controller, Repository, Request, Resource, Routes)';

    public function handle()
    {
        $table = $this->argument('table');
        $columns = $this->argument('columns');

        // Generate Migration
        Artisan::call("make:migration create_{$table}_table --create={$table}");
        $this->info("Migration created for table: {$table}");

        // Generate Model
        Artisan::call("make:model {$this->studly($table)}");
        $this->info("Model created: {$this->studly($table)}");

        // Generate Controller
        Artisan::call("make:controller {$this->studly($table)}Controller --api");
        $this->info("Controller created: {$this->studly($table)}Controller");

        // Generate Repository
        $this->createRepository($table);

        // Generate Request
        Artisan::call("make:request {$this->studly($table)}Request");
        $this->info("Form Request created: {$this->studly($table)}Request");

        // Generate Resource
        Artisan::call("make:resource {$this->studly($table)}Resource");
        $this->info("Resource created: {$this->studly($table)}Resource");

        // Generate Routes
        $this->appendRoutes($table);

        $this->info("CRUD for {$table} has been generated successfully.");
    }

    private function studly($value)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $value)));
    }

    private function createRepository($table)
    {
        $repositoryPath = app_path("Repositories/{$this->studly($table)}Repository.php");

        $repositoryContent = <<<PHP
<?php

namespace App\Repositories;

use App\Models\\{$this->studly($table)};

class {$this->studly($table)}Repository
{
    public function all()
    {
        return {$this->studly($table)}::all();
    }

    public function find(\$id)
    {
        return {$this->studly($table)}::find(\$id);
    }

    public function create(array \$data)
    {
        return {$this->studly($table)}::create(\$data);
    }

    public function update(\$id, array \$data)
    {
        \$record = {$this->studly($table)}::find(\$id);
        \$record->update(\$data);
        return \$record;
    }

    public function delete(\$id)
    {
        return {$this->studly($table)}::destroy(\$id);
    }
}
PHP;

        File::put($repositoryPath, $repositoryContent);
        $this->info("Repository created: {$this->studly($table)}Repository");
    }

    private function appendRoutes($table)
    {
        $routesFile = base_path('routes/web.php');
        $routeEntry = <<<PHP

// Routes for {$table}
Route::apiResource('{$table}', App\Http\Controllers\\{$this->studly($table)}Controller::class);
PHP;

        File::append($routesFile, $routeEntry);
        $this->info("Routes added for: {$table}");
    }
}
