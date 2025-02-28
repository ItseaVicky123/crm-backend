<?php

namespace App\Lib\Datatables;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Column
 * @package App\Lib\Datatables
 */
class Column
{

    /**
     * @var string
     */
    public $data = '';

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var bool
     */
    public $searchable = false;

    /**
     * @var bool
     */
    public $orderable = false;

    /**
     * @var array
     */
    public $search = [
        'value' => '',
        'regex' => false,
    ];

    /**
     * @var null
     */
    private $alias = null;

    /**
     * @var null
     */
    private $value = null;

    /**
     * @var null
     */
    private $renderer = null;

    /**
     * @var string
     */
    private $dateFormat = 'm/d/Y';

    /**
     * Column constructor.
     * @param array $definition
     */
    public function __construct(array $definition)
    {
        foreach ($definition as $prop => $value) {
            if (property_exists($this, $prop)) {
                $method = 'set' . ucfirst($prop);

                if (method_exists($this, $method)) {
                    $this->$method($value);
                } else {
                    $this->$prop = $value;
                }
            }
        }
    }

    /**
     * @param $data
     * @return $this
     */
    protected function setData($data)
    {
        $this->data = $data;

        if (substr($data, 0, 1) == '_') {
            $this->setAlias(substr($data, 1));
        }

        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @param string $alias
     * @return $this
     */
    public function setAlias(string $alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @param string $format
     * @return $this
     */
    public function setDateFormat(string $format)
    {
        $this->dateFormat = $format;

        return $this;
    }

    /**
     * @return null
     */
    public function render()
    {
        return $this->renderer ? ($this->renderer)() : $this->value;
    }

    /**
     * @return string
     */
    public function getRawColumnName()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getColumnName()
    {
        return strtolower($this->alias ?? $this->getRawColumnName());
    }

    /**
     * @return mixed|null
     */
    public function getSearch()
    {
        return $this->search['value'] ?? null;
    }

    /**
     * @param Model $model
     * @return $this
     */
    public function loadFromModel(Model $model)
    {
        // Reset the value
        $this->setValue(null);

        $prop = $this->getColumnName();

        // Explicitly defined property, or one that is set via magic method
        if (property_exists($model, $prop) || isset($model->$prop)) {
            $value = $model->$prop;

            // If column is a date field, format it
            if ($value instanceof Carbon) {
                $value = $value->format($this->dateFormat);
            };

            $this->setValue($value);
        } else if ($relation = explode(':', $prop)) {
            if ($relationModel = $model->getRelation($relation[0])) {
                $prop = $relation[1];

                if (property_exists($relationModel, $prop) || isset($relationModel->$prop)) {
                    $value = $relationModel->$prop;

                    // If column is a date field, format it
                    if ($value instanceof Carbon) {
                        $value = $value->format($this->dateFormat);
                    };

                    $this->setValue($value);
                }
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getColumnName();
    }
}
