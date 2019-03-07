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
     * Factory generator instance.
     *
     * @var FactoryGenerator
     */
    protected $factoryGenerator;

    /**
     * Create a new command instance.
     *
     * @param FactoryGenerator $factoryGenerator
     */
    public function __construct(FactoryGenerator $factoryGenerator)
    {
        parent::__construct();

        $this->factoryGenerator = $factoryGenerator;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $modelClass = $this->argument('model');

        $modelClass = Str::startsWith($modelClass, '\\') ? $modelClass : 'App\\'.$modelClass;

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

        $modelName = last(explode('\\', $modelClass));

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

        $factoryContent = $this->factoryGenerator->generate($model);

        File::put($factoryPath, $factoryContent);

        $this->info(
            sprintf('Populated "%s" factory for "%s" model has been created successfully!', $factoryName, $modelName)
        );
    }
}
