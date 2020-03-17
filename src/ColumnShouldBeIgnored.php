<?php

namespace Coderello\PopulatedFactory;

use Doctrine\DBAL\Schema\Column;

class ColumnShouldBeIgnored
{
    public function __invoke(Column $column): bool
    {
        if ($column->getAutoincrement()) {
            return true;
        }

        // enum
        if ($column->getType()->getName() === 'string' && $column->getLength() === 0) {
            return true;
        }

        if ($column->getName() === 'deleted_at') {
            return true;
        }

        return false;
    }
}
