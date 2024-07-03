<?php

namespace Tinderbox\ClickhouseBuilder\Query;

use ClickHouseDB\Query\WhereInFile;
use ClickHouseDB\Statement;
use ClickHouseDB\Client;
use Tinderbox\ClickhouseBuilder\Exceptions\GrammarException;

class Builder extends BaseBuilder
{

    /**
     * @return Client
     */
    protected function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * Perform compiled from builder sql query and getting result.
     *
     * @return Statement[]|Statement
     */
    public function get()
    {
        if (!empty($this->async)) {
            $result = [];
            /** @var Builder $asyncQuery */
            foreach ($this->getAsyncQueries() as $asyncQuery) {
                $result[] = $this->getClient()->selectAsync($asyncQuery->toSql(), $asyncQuery->bindings, $asyncQuery->getWhereInFile(), $asyncQuery->getToFile());
            }
            $this->getClient()->executeAsync();
            return $result;
        } else {
            return $this->getClient()->select($this->toSql(),$this->bindings, $this->getWhereInFile(),$this->getToFile());
        }
    }

    protected function getWhereInFile()
    {
        $whereInFile = null;
        if($files = $this->getFiles()){
            $whereInFile = new WhereInFile();
            foreach ($files as $file_name=>$value) {
                $whereInFile->attachFile($file_name, $value['table_name'], $value['structure'], $value['format']);
            }
        }
        return $whereInFile;
    }

    /**
     * Performs compiled sql for count rows only. May be used for pagination
     * Works only without async queries.
     *
     * @return int
     */
    public function count()
    {
        if (!empty($this->groups)) {
            $subThis = clone $this;
            $subThis->orders = [];
            return $this->newQuery()->from($subThis)->count();
        }
        $builder = $this->getCountQuery();
        $result  = $builder->get();
        return intval($result->rows()[0]['count'] ?? 0);
    }


    /**
     * Performs insert query.
     *
     * @param array $values
     *
     * @throws GrammarException
     *
     * @return Statement|false
     */
    public function insert(array $values)
    {
        if (empty($values)) {
            return false;
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        } /*
         * Here, we will sort the insert keys for every record so that each insert is
         * in the same order for the record. We need to make sure this is the case
         * so there are not any errors or problems when inserting these records.
         */
        else {
            foreach ($values as $key => $value) {
                ksort($value);
                $values[$key] = $value;
            }
        }

        return $this->getClient()->write($this->grammar->compileInsert($this, $values));
    }

    /**
     * use with caution
     */
    public function update(array $values)
    {
        return $this->getClient()->write(
            $this->grammar->compileUpdate($this, $values)
        );
    }

    /**
     * Performs ALTER TABLE `table` DELETE query.
     *
     * @throws GrammarException
     *
     * @return Statement
     */
    public function delete($lightweight = true)
    {
        return $this->getClient()->write(
            $this->grammar->compileDelete($this,$lightweight)
        );
    }

    /**
     * Executes query to create table.
     *
     * @param        $tableName
     * @param string $engine
     * @param array  $structure
     *
     * @return Statement
     */
    public function createTable($tableName, string $engine, array $structure, ?string $extraOptions = null)
    {
        return $this->getClient()->write($this->grammar->compileCreateTable($tableName, $engine, $structure, false, $this->getOnCluster(), $extraOptions));
    }

    /**
     * Executes query to create table if table does not exists.
     *
     * @param        $tableName
     * @param string $engine
     * @param array  $structure
     *
     * @return Statement
     */
    public function createTableIfNotExists($tableName, string $engine, array $structure, ?string $extraOptions = null)
    {
        return $this->getClient()->write($this->grammar->compileCreateTable($tableName, $engine, $structure, true, $this->getOnCluster(), $extraOptions));
    }

    public function dropTable($tableName)
    {
        return $this->getClient()->write($this->grammar->compileDropTable($tableName));
    }

    public function dropTableIfExists($tableName)
    {
        return $this->getClient()->write($this->grammar->compileDropTable($tableName, true));
    }

    public function insertBatchFiles($fileNames, array $columns = [], string $format = 'CSV')
    {
        return $this->getClient()->insertBatchFiles($this->getFrom()->getTable()->__toString(),$fileNames,$columns,$format);
    }
}
