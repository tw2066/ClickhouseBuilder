<?php

namespace TangExample\ClickhouseBuilder;

use Tang\HyperfClickhouse\Model;

class MyTable extends Model
{
    // Not necessary. Can be obtained from class name MyTable => my_table
    protected string $table = 'my_table';

}