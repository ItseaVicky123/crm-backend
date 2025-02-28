<?php

namespace App\Traits\GraphQL;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait Searchable
 * @package App\Traits\GraphQL
 */
trait Searchable
{
    /**
     * This function return rules of array which will validate the GraphQL request.
     *
     * @param array $allowedSearchFields
     * @return array
     */
    public function getSearchArgs(array $allowedSearchFields = []): array
    {
        return [
            'search'       => [
                'name' => 'search',
                'type' => Type::string(),
            ],
            'searchFields' => [
                'name' => 'searchFields',
                'type' => Type::listOf(new EnumType([
                    'name'   => 'searchFieldsEnum',
                    'values' => $allowedSearchFields
                ])),
            ],
        ];
    }

    /**
     * @param builder $query
     * @param array $args
     */
    public function search(Builder $query, array $args)
    {
        $search       = $args['search'] ?? "";
        $searchFields = $args['searchFields'] ?? [];

        foreach ($searchFields as $field) {
            $query->orWhere($field, 'like', '%'.$search.'%');
        }
    }

    /**
     * @param array $args
     * @return boolean
     */
    function isSearchable(array $args): bool
    {
        return (! empty($args['search']) && ! empty($args['searchFields']));
    }
}
