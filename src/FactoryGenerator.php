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

    protected $connection;

    protected $guesser;

    protected $columnShouldBeIgnored;

    protected $appendFactoryPhpDoc = true;

    public function __construct(Connection $connection, FakeValueExpressionGuesser $guesser, ColumnShouldBeIgnored $columnShouldBeIgnored)
    {
        $this->connection = $connection;

        $this->guesser = $guesser;

        $this->columnShouldBeIgnored = $columnShouldBeIgnored;
    }

    public function generate(Model $model): string
    {
        $table = $this->table($model);

        $columns = $this->columns($table);

        return collect([
            '<?php', self::NL, self::NL, 'use Faker\Generator as Faker;', self::NL,
        ])->when($this->appendFactoryPhpDoc, function (Collection $collection) {
            return $collection->merge([
                self::NL, '/** @var $factory \Illuminate\Database\Eloquent\Factory */', self::NL,
            ]);
        })->merge([
            self::NL, '$factory->define(\\', get_class($model), '::class, function (Faker $faker) {',
            self::NL, self::TAB, 'return [', self::NL
        ])->pipe(function (Collection $collection) use ($columns) {
            foreach ($columns as $column) {
                if (($this->columnShouldBeIgnored)($column)) {
                    continue;
                }

                if (is_null($value = $this->guessValue($column))) {
                    continue;
                }

                $collection = $collection->merge([
                    self::TAB, self::TAB, '\'', $column->getName(), '\' => ', $value, ',', self::NL,
                ]);
            }

            return $collection;
        })->merge([
            self::TAB, '];', self::NL, '});', self::NL,
        ])->implode('');
    }

    protected function table(Model $model): Table
    {
        return $this->connection
            ->getDoctrineSchemaManager()
            ->listTableDetails($model->getTable());
    }

    protected function columns(Table $table): array
    {
        return $table->getColumns();
    }

    protected function guessValue(Column $column)
    {
        return $this->guesser->guess($column);
    }
}
