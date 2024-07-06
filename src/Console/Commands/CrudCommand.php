<?php

namespace Mahin\Crud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CrudCommand extends Command
{
    protected $signature = 'make:crudnew {name}';
    protected $description = 'Create CRUD operations for a model';

    public function handle()
    {
        $name = $this->argument('name');
        $columns = $this->getColumns();

        $this->createModel($name, $columns);
        $this->createController($name, $columns);
        $this->createViews($name, $columns);
        $this->createMigration($name, $columns);
        $this->createRoute($name);
    }

    protected function getColumns()
    {
        $jsonPath = base_path('columns.json');
        if (!file_exists($jsonPath)) {
            throw new \Exception("JSON file does not exist: {$jsonPath}");
        }

        $json = file_get_contents($jsonPath);
        return json_decode($json, true)['columns'];
    }

    // protected function createModel($name, $columns)
    // {
    //     $modelTemplate = str_replace(
    //         ['{{modelName}}'],
    //         [$name],
    //         $this->getStub('Model')
    //     );

    //     file_put_contents(app_path("/Models/{$name}.php"), $modelTemplate);
    //     $this->info("Model {$name} created successfully.");
    // }
    protected function createRoute($name)
    {
        $routeDefinition = "Route::resource('" . strtolower($name) . "', {$name}Controller::class);\n";
        $routePath = base_path('routes/web.php');

        if (file_exists($routePath)) {
            file_put_contents($routePath, $routeDefinition, FILE_APPEND);
            $this->info("Route for {$name} created successfully.");
        } else {
            $this->error("web.php file not found.");
        }
    }
    protected function createModel($name, $columns)
    {
       
        $modelTemplate = str_replace(
            ['{{modelName}}', '{{columnString}}'],
            [$name, $this->getColumnString($columns)],
            $this->getStub('model')
        );

        file_put_contents(app_path("/Models/{$name}.php"), $modelTemplate);
        $this->info("Model created successfully.");
    }

    protected function createController($name, $columns)
    {
        $controllerTemplate = str_replace(
            ['{{modelName}}', '{{modelNamePluralLowerCase}}', '{{modelNameLowerCase}}', '{{columnString}}'],
            [$name, strtolower($name), strtolower($name), $this->getColumnString($columns)],
            $this->getStub('controller')
        );

        file_put_contents(app_path("/Http/Controllers/{$name}Controller.php"), $controllerTemplate);
        $this->info("Controller created successfully.");
    }

    protected function getColumnString($columns)
    {
        return "'" . implode("', '", array_column($columns, 'name')) . "'";
    }


    protected function getColumnStringForController($columns)
    {
        return implode(', ', array_map(function ($column) {
            return "'{$column['name']}'";
        }, $columns));
    }

    protected function createViews($name, $columns)
    {
        $views = ['index', 'create', 'edit', 'show'];
        $nameLowerCase = strtolower($name);
        foreach ($views as $view) {
          
            $viewTemplate = str_replace(
                ['{{modelName}}', '{{modelNamePluralLowerCase}}', '{{modelNameLowerCase}}', '{{columnsHeaders}}', '{{columnsData}}', '{{columnsFormFields}}'],
                [$name, strtolower($name), strtolower($name), $this->getColumnsHeaders($columns), $this->getColumnsData($columns, $name), $this->getColumnsFormFields($columns, $view, strtolower($name))],
                $this->getStub("views/{$view}")
            );
            $viewPath = resource_path("views/{$nameLowerCase}");
            if (!File::exists($viewPath)) {
                File::makeDirectory($viewPath, 0755, true);
            }

            file_put_contents(resource_path("views/{$nameLowerCase}/{$view}.blade.php"), $viewTemplate);
            $this->info("View {$view} created successfully.");
        }
    }






    protected function getColumnsHeaders($columns)
    {
        $headers = '';
        foreach ($columns as $column) {
            $headers .= "<th>{$column['name']}</th>\n";
        }
        return $headers;
    }

    // protected function getColumnsData($columns)
    // {
    //     $data = '';
    //     foreach ($columns as $column) {
    //         $data .= "<td>{{ \${{modelNameLowerCase}}->{$column['name']} }}</td>\n";
    //     }
    //     return $data;
    // }
    protected function getColumnsData($columns, $name)
    {
        $variableName = strtolower($name);
        $data = '';
        foreach ($columns as $column) {
            $data .= "<td>{{ \${$variableName}->{$column['name']} }}</td>\n";
        }
        return $data;
    }



    // protected function getColumnsFormFields($columns)
    // {
    //     $fields = '';
    //     foreach ($columns as $column) {
    //         $fields .= "<div class=\"form-group\">
    //                     <label for=\"{$column['name']}\">{$column['name']}</label>
    //                     <input type=\"text\" name=\"{$column['name']}\" id=\"{$column['name']}\" value=\"{{ old('{$column['name']}') }}\" class=\"form-control\">
    //                 </div>\n";
    //     }
    //     return $fields;
    // }
    protected function getColumnsFormFields($columns, $view = 'edit', $name)
    {
        $fields = '<div class="row">';
        foreach ($columns as $column) {
            $value = $view === 'edit' ? "{{ \$$name->{$column['name']} }}" : "{{ old('{$column['name']}') }}";
            $fields .= "<div class=\"form-group col-md-4\">
                        <label for=\"{$column['name']}\">{$column['name']}</label>
                        <input type=\"text\" name=\"{$column['name']}\" id=\"{$column['name']}\" value=\"$value\" class=\"form-control\">
                    </div>\n";
        }
        $fields .= '</div>';
        return $fields;
    }





    protected function createMigration($name, $columns)
    {
        $tableName = Str::plural(strtolower($name));
        $migrationTemplate = str_replace(
            ['{{tableName}}', '{{migrationColumns}}'],
            [$tableName, $this->getMigrationColumns($columns)],
            $this->getStub('Migration')
        );
        $fileName = date('Y_m_d_His') . "_create_{$tableName}_table.php";
        file_put_contents(database_path("/migrations/{$fileName}"), $migrationTemplate);
        $this->info("Migration for {$tableName} created successfully.");

        Artisan::call('migrate');
    }

    protected function getMigrationColumns($columns)
    {
        $columnString = "";
        foreach ($columns as $column) {
            $columnString .= "\$table->{$column['type']}('{$column['name']}');\n";
        }
        return $columnString;
    }

    protected function getStub($type)
    {
        return file_get_contents(resource_path("stubs/$type.stub"));
    }
}
