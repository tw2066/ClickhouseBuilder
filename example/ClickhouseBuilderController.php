<?php
namespace TangExample\ClickhouseBuilder;

use Hyperf\Collection\Collection;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Controller;
use Tang\HyperfClickhouse\DB;
use Tinderbox\ClickhouseBuilder\Query\Builder;

#[AutoController(prefix: '/ck')]
class ClickhouseBuilderController
{
    public function init()
    {
        $this->createAndInsert();
        $count = MyTable::query()->count();
        assert($count == 3);
        $this->delete();
        $count = MyTable::query()->count();
        assert($count == 2);
        $first = $this->first();
        //dump($first);
        assert($first->get('id') == 2);

        $this->update();
        $firstUpdate = $this->first();
        assert($firstUpdate->get('name') == 'name2_new');

        return 2;
    }

    private function createAndInsert(): void
    {
        $client = DB::query()->getClient();

        $client->write('drop table if exists my_table');
        $client->write('create table if not exists my_table (id Int64,name String, string String) engine = Memory');

        MyTable::query()->insert([[
            'id' => 1,
            'name' => 'name1',
            'string' => 'value1',
        ], [
            'id' => 2,
            'name' => 'name2',
            'string' => 'value2',
        ], [
            'id' => 3,
            'name' => 'name3',
            'string' => 'value3',
        ]]);

    }

    private function delete(): void
    {
       MyTable::query()->where('id', '=', 1)->delete();
    }

    private function first(): ?Collection
    {
       return MyTable::query()->where('id', '=', 2)->first();
    }

    private function update(): void
    {
        MyTable::query()->where('id', '=', 2)->update(['name' => 'name2_new']);
    }

}