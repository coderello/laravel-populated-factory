<?php

namespace Coderello\PopulatedFactory;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class FactoryGenerator
{
    const TAB = '    ';

    const NL = PHP_EOL;

    protected $guesser;

    protected $columnShouldBeIgnored;

    protected $appendFactoryPhpDoc = true;

    public function __construct(FakeValueExpressionGuesser $guesser, ColumnShouldBeIgnored $columnShouldBeIgnored)
    {
        $this->guesser = $guesser;

        $this->columnShouldBeIgnored = $columnShouldBeIgnored;
    }

    public function generate(Model $model): string
    {
        $table = $this->table($model);

        $columns = $this->columns($table);

        $modelNamespace = get_class($model);

        $modelClassName = class_basename($model);

        $definition = collect($columns)
            ->map(function (Column $column) {
                if (($this->columnShouldBeIgnored)($column)) {
                    return null;
                }

                if (is_null($value = $this->guessValue($column))) {
                    return null;
                }

                return str_repeat(self::TAB, 3).'\''.$column->getName().'\' => '.$value.',';
            })
            ->filter()
            ->implode(self::NL);

        return <<<FACTORY
<?php

namespace Database\Factories;

use {$modelNamespace};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class {$modelClassName}Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected \$model = {$modelClassName}::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
{$definition}
        ];
    }
}
FACTORY;
    }

    protected function table(Model $model): Table
    {
        $schemaManager = $model->getConnection()
            ->getDoctrineSchemaManager();

        $schemaManager->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');

        return $schemaManager->listTableDetails($model->getTable());
    }

    /**
     * @param Table $table
     * @return Column[]|array
     */
    protected function columns(Table $table): array
    {
        return $table->getColumns();
    }

    protected function guessValue(Column $column)
    {
        return $this->guesser->guess($column);
    }
}
