<?php

namespace App\Lib;

use App\Models\BaseModel;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Lib\Datatables\Controller as DatatableController;
use App\Lib\ListController as ListController;
use Illuminate\Support\Facades\DB;

/**
 * Trait ListResponder
 * @package App\Lib
 */
trait ListResponder
{
    /**
     * @var DatatableController | ListController | null
     */
    protected $controller = null;

    /**
     * @var null | Request
     */
    protected $request = null;

    /**
     * @param Model   $model
     * @param Builder $builder
     * @param Request $request
     * @return $this
     */
    protected function initList(Model $model, Builder $builder, Request &$request)
    {
        $this->setController($request);

        //set the builder's connection to readonly
        $builderQuery             = $builder->getQuery();
        $builderQuery->connection = DB::connection(BaseModel::SLAVE_CONNECTION);
        $builder->setQuery($builderQuery);

        $this->controller->initList($model, $builder, $request);

        return $this;
    }

    /**
     * @param string  $query
     * @param Request $request
     * @param bool    $paginate
     * @return $this
     * @throws \Exception
     */
    protected function initListRaw(string $query, Request &$request, $paginate = true)
    {
        $this->setController($request);

        $this->controller->initListRaw($query, $request, $paginate);

        return $this;
    }

    /**
     * @param array $make_visible
     * @param array $make_hidden
     * @return mixed
     */
    protected function listRespond(array $make_visible = [], array $make_hidden = [])
    {
        return $this->getListResponse($make_visible, $make_hidden);
    }

    protected function setRequest(Request $request)
    {
        $this->request = &$request;

        if (isset($this->controller)) {
            $this->controller->setRequest($request);
        }

        return $this;
    }

    /**
     * @param array $make_visible
     * @param array $make_hidden
     * @return array
     * @throws \Exception
     */
    protected function getListResponse(array $make_visible = [], array $make_hidden = [])
    {
        return $this
            ->controller
            ->sort()
            ->search()
            ->paginate()
            ->listToArray($make_visible, $make_hidden);
    }

    protected function listToArray(array $make_visible = [], array $make_hidden = [])
    {
        return $this->controller->listToArray($make_visible, $make_hidden);
    }

    /**
     * @param string $name
     * @return mixed
     */
    protected function getColumn(string $name)
    {
        return $this->controller->getColumn($name);
    }

    private function setController(Request $request)
    {
        if ($request->exists('_')) {
            $this->controller = new DatatableController;
        } else {
            $this->controller = new ListController;
        }

        return $this;
    }
}
