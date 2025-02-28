<?php

namespace App\Lib\Datatables;

use App\Lib\ListController;

/**
 * Class Controller
 * @package App\Lib\Datatables
 */
class Controller extends ListController
{

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @var int
     */
    protected $draw = 1;

    /**
     * @var array
     */
    protected $search = [];

    /**
     * @var array
     */
    protected $order = [];

    /**
     * @var int
     */
    protected $start = 0;

    /**
     * @var int
     */
    protected $length = 10;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var int
     */
    protected $recordsTotal = 0;

    /**
     * @var int
     */
    protected $recordsFiltered = 0;

    /**
     * @var string
     */
    protected $ajaxMessage = '';

    /**
     * @var array
     */
    private $leftJoinedTables = [];

    /**
     * @return $this|ListController
     */
    public function sort()
    {
        foreach ($this->order as $sort) {
            $this->builder->orderBy(
                $this->parseColumn($this->columns[$sort['column']]->getColumnName()),
                $this->parseDir($sort['dir'])
            );
        }

        return $this;
    }

    /**
     * @return $this|ListController
     */
    public function search()
    {
        if ($this->search && ! empty($this->search['value'])) {
            // Generic search-all
            $this->builder->search($this->search['value']);
        }

        foreach ($this->columns as $column) {
            // Individual column filter
            if ($search = $column->getSearch()) {
                $name = $column->getColumnName();

                if ($name === 'id') {
                    $this->builder->where(
                        $this->parseColumn($name),
                        '=',
                        $search
                    );
                } else {
                    $this->builder->where(
                        $this->parseColumn($column->getColumnName()),
                        'LIKE',
                        "%{$search}%"
                    );
                }
            }
        }

        return $this;
    }

    /**
     * @return $this|ListController
     */
    public function paginate()
    {
        // Artificially inject 'page' into the request for the paginator to consume
        $this->request->merge(['page' => $this->calculatePage()]);
        $this->builder = $this->builder->paginate($this->length, ["{$this->model->getTable()}.*"]);

        return $this;
    }

    /**
     * @param array $make_visible
     * @param array $make_hidden
     * @return mixed
     */
    public function getBuilder(array $make_visible = [], array $make_hidden = [])
    {
        if ($make_visible) {
            $this->builder->makeVisible($make_visible);
        }

        if ($make_hidden) {
            $this->builder->makeHidden($make_hidden);
        }

        return $this->builder;
    }

    /**
     * @return float|int
     */
    public function calculatePage()
    {
        return ($this->start + $this->length) / $this->length;
    }

    /**
     * @param array $make_visible
     * @param array $make_hidden
     * @return array
     */
    public function listToArray(array $make_visible = [], array $make_hidden = [])
    {
        $this->parseResults($make_visible, $make_hidden);

        return [
            'draw'            => $this->draw,
            'start'           => $this->start,
            'length'          => $this->length,
            'data'            => $this->data,
            'recordsTotal'    => $this->recordsTotal,
            'recordsFiltered' => $this->recordsFiltered,
            'message'         => $this->ajaxMessage,
        ];
    }

    /**
     * @return array
     */
    public function getColumnNames()
    {
        return array_unique(
            array_map(function ($v) {
                return (string) $v;
            }, $this->columns)
        );
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getColumn(string $name)
    {
        return collect(array_filter(
            $this->columns,
            function ($column) use ($name) {
                return strtolower($column->getRawColumnName()) == strtolower($name);
            }
        ))->first();
    }

    /**
     * @return $this|ListController
     */
    protected function parseList()
    {
        foreach ($this->request->toArray() as $prop => $val) {
            if (property_exists($this, $prop)) {
                $this->$prop = $val;
            }
        }

        foreach ($this->columns as &$column) {
            $column = new Column($column);
        }

        return $this;
    }

    /**
     * @param $make_visible
     * @param $make_hidden
     */
    public function parseResults($make_visible, $make_hidden)
    {
        $results = $this->getBuilder($make_visible, $make_hidden);

        foreach ($results as $model) {
            $row_data = [];

            foreach ($this->columns as $column) {
                $row_data[$column->data] = $column->loadFromModel($model)->render();
            }

            $this->data[] = $row_data;
        }

        $this->recordsTotal    = $results->total();
        $this->recordsFiltered = $this->recordsTotal;
    }

    /**
     * @return mixed
     */
    public function isDatatableRequest()
    {
        return $this->request->exists('_');
    }

    /**
     * @param string $columnName
     * @return string
     * @throws \Exception
     */
    private function parseColumn(string $columnName)
    {
        $table           = $this->model->getTable();
        $qualifiedColumn = "{$table}.{$columnName}";

        if (count($relationship = explode(':', $columnName)) > 1) {
            $relationshipName = $relationship[0];
            $relation         = $this->model->$relationshipName();
            $table            = $relation->getModel()->getTable();
            $relationship[0]  = $table;
            $columnName       = $relationship[1];
            $qualifiedColumn  = implode('.', $relationship);

            $this->leftJoinRelationship([
                'table'                 => $table,
                'qualified_foreign_key' => $relation->getQualifiedForeignKeyName(),
                'qualified_parent_key'  => $relation->getQualifiedParentKeyName(),
            ]);
        }

        $this->checkColumn($columnName, $table);

        return $qualifiedColumn;
    }

    /**
     * @param array $relationship
     */
    private function leftJoinRelationship(array $relationship)
    {
        if (! in_array($relationship['table'], $this->leftJoinedTables)) {
            $this->builder
                ->leftJoin(
                    $relationship['table'],
                    $relationship['qualified_foreign_key'],
                    '=',
                    $relationship['qualified_parent_key']
                );
            $this->leftJoinedTables[] = $relationship['table'];
        }
    }
}
