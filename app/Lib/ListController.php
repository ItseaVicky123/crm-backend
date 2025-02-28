<?php

namespace App\Lib;

use App\Lib\Datatables\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ListController
{
    protected $model;
    protected $builder;
    protected $request;
    protected $list_query;
    protected $is_paginated;
    protected $page_size      = 10;
    protected $page_offset    = 0;
    protected $is_raw         = false;
    protected $offsetMetaData = [];
    protected $maxPerPage     = 15;
    protected const PAGINATION_TYPE_PAGE   = 'page';
    protected const PAGINATION_TYPE_OFFSET = 'offset';

    /**
     * @var string
     * @description  page, offset
     */
    protected $pagination_type = self::PAGINATION_TYPE_PAGE;

    /**
     * @param Model $model
     * @param Builder $builder
     * @param Request $request
     * @return $this
     */
    public function initList(Model $model, Builder $builder, Request &$request)
    {
        $this->setModel($model)
            ->setBuilder($builder)
            ->setRequest($request)
            ->setPaginationType($request)
            ->setOffsetMetaData()
            ->parseList();
        $this->validatePaginationRequest();

        return $this;
    }

    /**
     * @param string $query
     * @param Request $request
     * @param bool $paginate
     * @return $this
     */
    public function initListRaw(string $query, Request &$request, $paginate = true)
    {
        $this->is_raw       = true;
        $this->is_paginated = $paginate;

        $this->setQuery($query, $request)
            ->setPaginationType($request)
            ->setOffsetMetaData()
            ->setRequest($request);
        $this->validatePaginationRequest();

        return $this;
    }

    /**
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @param Builder $builder
     * @return $this
     */
    public function setBuilder(Builder $builder)
    {
        $this->builder = &$builder;

        return $this;
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = &$request;

        return $this;
    }

    /**
     * @param string $query
     * @return $this
     */
    public function setQuery(string $query)
    {
        $this->list_query = $query;

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function sort()
    {
        if ($sort = $this->request->get('sort')) {
            foreach ($sort as $col => $dir) {
                $this->checkColumn($col);
                $this->builder->orderBy($col, $this->parseDir($dir));
            }
        }

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function search()
    {
        if ($search = $this->request->get('search')) {
            // Generic search-all
            if (isset($search['_ALL_'])) {
                $this->builder->search($search['_ALL_']);

                unset($search['_ALL_']);
            }

            foreach ($search as $column => $criteria) {
                if (strlen($criteria)) {
                    $this->checkColumn($column);

                    foreach ((array) $criteria as $criterion) {
                        [$operator, $value] = $this->parseSearchCriterion($criterion);
                        if (strtolower($operator) === 'between' && is_array($value)) {
                            $this->builder->whereBetween($column, $value);
                        } else {
                            $this->builder->where($column, $operator, $value);
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param $col
     * @param null $table
     * @throws \Exception
     */
    public function checkColumn($col, $table = null)
    {
        if (!Schema::hasColumn($table ?? $this->model->getTable(), $col)) {
            throw new \Exception("Invalid column '{$col}'");
        }
    }

    /**
     * @param $dir
     * @return string
     */
    public function parseDir($dir)
    {
        return strtoupper($dir) == 'DESC' ? 'DESC' : 'ASC';
    }

    /**
     * @param $criterion
     * @return array
     */
    public function parseSearchCriterion($criterion)
    {
        $operator = '=';
        $value    = $criterion;

        if (strpos($criterion, '::')) {
            [$operator, $value] = explode('::', $criterion);
        }

        if (strpos($criterion, '*') !== false) {
            $operator = 'like';
            $value    = str_replace('*', '%', $value);
        }

        if (strpos($criterion, 'start') !== false && strpos($criterion, 'end') !== false) {
            $handle   = json_decode($value, true);
            $operator = 'between';
            $value    = [$handle['start'], $handle['end']];
        }
        return [$operator, $value];
    }

    /**
     * @return $this
     */
    public function paginate()
    {
        if ($this->isPaginated()) {
            if ($this->is_raw) {
                $this->paginateRaw();
            } else {
                $perPage = $this->getPageSize();

                if ($this->pagination_type == self::PAGINATION_TYPE_OFFSET) {
                    $offset        = $this->getPageOffset();
                    $totalCount    = $this->builder->count();
                    $this->builder = $this->builder->limit((int) $perPage)->offset($offset)->get();
                    $this->setOffsetMetaData($this->builder->count(), $offset, $perPage, $totalCount);
                } else {
                    $this->builder = $this->builder->paginate((int) $perPage);
                }
            }
        }

        return $this;
    }

    /**
     * @param array $make_visible
     * @param array $make_hidden
     * @return mixed
     */
    public function getBuilder(array $make_visible = [], array $make_hidden = [])
    {
        $builder = $this->is_paginated
            ? $this->builder
            : $this->builder->get();

        if ($make_visible) {
            $builder->makeVisible($make_visible);
        }

        if ($make_hidden) {
            $builder->makeHidden($make_hidden);
        }

        return $builder;
    }

    /**
     * @return int
     */
    public function getPageSize()
    {
        try {
            $perPage = (int) $this->request->get('per_page', $this->request->get('limit', $this->model->getPerPage()));
        } catch (\Throwable $e) {
            $perPage = $this->page_size;
        }

        return $perPage;
    }

    /**
     * @return int
     */
    public function getPageOffset()
    {
        return (int) $this->request->get('offset', $this->page_offset);
    }

    /**
     * @param array $make_visible
     * @param array $make_hidden
     * @return array
     */
    public function listToArray(array $make_visible = [], array $make_hidden = [])
    {
        $list = $this->is_raw
            ? $this->paginate()->list_results
            : $this->getBuilder($make_visible, $make_hidden)->toArray();

        if (is_array($list)) {
            if ($this->pagination_type == self::PAGINATION_TYPE_OFFSET) {
                $list = [
                    'status'   => 'SUCCESS',
                    'metadata' => $this->offsetMetaData,
                    'data'     => $list,
                ];
            } else {
                $list = array_merge(['status' => 'SUCCESS'], $list);
            }
        }

        return $list;
    }

    /**
     * @return bool
     */
    protected function isPaginated()
    {
        if (! isset($this->is_paginated)) {
            $this->is_paginated = (bool) $this->request->get('paginate', 1);
        }

        return $this->is_paginated;
    }

    /**
     * @param $page
     * @return float|int
     */
    protected function calculatePageOffset($page)
    {
        return ($this->page_offset = ($page * $this->getPageSize()) - $this->getPageSize());
    }

    /**
     * @return mixed
     */
    protected function getQueryCount()
    {
        return DB::select(DB::raw('SELECT FOUND_ROWS() AS `row_count`'))[0]->row_count;
    }

    /**
     * @return $this
     */
    protected function paginateRaw()
    {
        $page        = $this->request->get('page', 1);
        $page_size   = $this->getPageSize();
        $page_offset = ($this->pagination_type == self::PAGINATION_TYPE_PAGE) ? $this->calculatePageOffset($page) : $this->getPageOffset();
        $data        = DB::select("
        SELECT 
              SQL_CALC_FOUND_ROWS 
              * 
          FROM 
              (
                 {$this->list_query}
              ) q 
         LIMIT 
              {$page_offset}, {$page_size}
        ");

        if ($this->pagination_type == self::PAGINATION_TYPE_OFFSET) {
            $this->setOffsetMetaData(count($data), $page_offset, $page_size, $this->getQueryCount());
            $this->list_results = $data;
        } else {
            $this->list_results = new LengthAwarePaginator($data, $this->getQueryCount(), $page_size, $page, [
                'path'  => $this->request->url(),
                'query' => $this->request->query(),
            ]);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function parseList()
    {
        return $this;
    }

    /**
     * @param Request $request
     * @return $this
     */
    protected function setPaginationType(Request $request)
    {
        if ($request->has('page') && is_numeric($request->get('page')) && $request->get('page') > 0) {
            $this->pagination_type = self::PAGINATION_TYPE_PAGE;
        } elseif ($request->has('offset') && is_numeric($request->get('offset')) && $request->get('offset') >= 0) {
            $this->pagination_type = self::PAGINATION_TYPE_OFFSET;
        }

        return $this;
    }

    /**
     * @param int $count
     * @param int $offset
     * @param int $limit
     * @param int $total
     * @return $this
     */
    protected function setOffsetMetaData($count = 0, $offset = 0, $limit = 0, $total = 0)
    {
        $this->offsetMetaData = [
            "count"  => $count,
            "offset" => $offset,
            "limit"  => $limit,
            "total"  => $total,
        ];

        return $this;
    }

    /**
     * @return $this
     */
    protected function validatePaginationRequest()
    {
        $maxPerPage = ($this->model && (int) $this->model->maxPerPage > 0) ? $this->model->maxPerPage : $this->maxPerPage;
        $maxPerPageValidation = "";

        if (! Auth::user() instanceof User) {
            $maxPerPageValidation = "|between:1,$maxPerPage";
        }

        $validator = Validator::make($this->request->all(), [
            'per_page' => "sometimes|integer" . $maxPerPageValidation,
            'limit'    => "sometimes|integer" . $maxPerPageValidation,
            'offset'   => "sometimes|integer|min:0",
            'page'     => "sometimes|integer|min:1",
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return $this;
    }

    /*
    public function listExport($name, Collection $model, array $headers, string $format = 'xlsx')
    {
        set_time_limit(300);

        return Excel::create(
            $name,
            function ($excel) use ($model, $headers) {
                $excel->setTitle('Contacts')
                    ->sheet('Page 1', function ($sheet) use ($model, $headers) {
                        if ($headers) {
                            // Headers
                            $model->prepend($headers);
                        }

                        $sheet->fromModel($model, null, 'A1', false, false);
                    });
            }
        )->download($format);
    }
    */
}
