<?php

namespace Coderello\PopulatedFactory\Commands;

use Coderello\PopulatedFactory\FactoryGenerator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PopulatedFactoryMake extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:populated-factory {model} {name?} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make populated factory';

    /**
     * Execute the console command.
     *
     * @param FactoryGenerator $factoryGenerator
     *
     * @return mixed
     */
    public function handle(FactoryGenerator $factoryGenerator)
    {
        $modelClass = $this->argument('model');

        $appNamespace = trim($this->getLaravel()->getNamespace(), '\\');

        if (! Str::startsWith($modelClass, '\\')) {
            $modelClass = class_exists($appNamespace.'\\Models\\'.$modelClass)
                ? $appNamespace.'\\Models\\'.$modelClass
                : $appNamespace.'\\'.$modelClass;
        }

        if (! class_exists($modelClass)) {
            $this->error(
                sprintf('"%s" class does not exist.', $modelClass)
            );

            return;
        }

        $model = new $modelClass;

        if (! $model instanceof Model) {
            $this->error(
                sprintf('"%s" is not a model.', $modelClass)
            );

            return;
        }

        $modelName = class_basename($model);

        $factoryName = $this->argument('name') ?? $modelName.'Factory';

        $factoryPath = database_path(
            sprintf('factories/%s.php', $factoryName)
        );

        if (is_file($factoryPath) && ! $this->option('force')) {
            $this->error(
                sprintf('Factory for "%s" model already exists!', $modelName)
            );

            return;
        }

        $factoryContent = $factoryGenerator->generate($model);

        File::put($factoryPath, $factoryContent);

        $this->info(
            sprintf('Populated "%s" factory for "%s" model has been created successfully!', $factoryName, $modelName)
        );
    }
}
