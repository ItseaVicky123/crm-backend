<?php

namespace App\Traits;

use GraphQL\Type\Definition\InputObjectType;
use Illuminate\Database\Eloquent\Builder;
use Rebing\GraphQL\Support\Facades\GraphQL;

trait SortableGraphQLQuery
{
    /**
     * @var array
     */
    protected array $sortableTypes = [
        \GraphQL\Type\Definition\NonNull::class,
        \GraphQL\Type\Definition\StringType::class,
        \GraphQL\Type\Definition\IntType::class,
        \GraphQL\Type\Definition\FloatType::class,
    ];

    /**
     * @param Builder $query
     * @param array $orderByArray
     */
    protected function sort(Builder $query, array $orderByArray)
    {
        $query->getQuery()->orders = null;

        foreach ($orderByArray as $field => $dir) {
            $query->orderBy($field, $dir);
        }
    }

    /**
     * @param $type
     * @return InputObjectType
     */
    protected function sortInputType($type)
    {
        $name       = $type->name;
        $typeName   = "\\App\\GraphQL\\Types\\{$name}Type";
        $typeFields = (new $typeName())->fields() ?? [];
        $fields     = [];

        if ($typeFields) {
            foreach ($typeFields as $fieldName => $fieldValues) {
                $selectable = $fieldValues['selectable'] ?? true;
                $typeClass  = get_class($fieldValues['type']);

                if ($selectable && in_array($typeClass, $this->sortableTypes)) {
                    $fields[$fieldName] = [
                        'name' => $fieldName,
                        'type' => GraphQL::type('SortDirection'),
                    ];
                }
            }
        }

        return new InputObjectType([
            'name'   => "{$name}Sort",
            'fields' => $fields,
        ]);
    }
}
