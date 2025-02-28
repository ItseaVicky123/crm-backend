<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * This class is to provide model scopes to use with intended to locate the query easily by inserting comment in it
 *
 * Trait ModelReader
 * @package App\Traits
 */
trait ModelCommenter
{
    /**
     * This scope is to append the comment to default SELECT `*` query
     *
     * @param $query
     * @param string $commentAppend this is a required unique message that would help us quickly identify the query
     * @param mixed ...$additionalAppends this would be a list of magic constants that would help us identify
     * the location where this query has been run. These usually would be: __FILE__, __METHOD__ and more arguments
     * @return Builder
     */
    public function scopeSelectWithComment($query, string $commentAppend, ...$additionalAppends): Builder
    {
        return $query->customSelectWithComment('*', $commentAppend, ...$additionalAppends);
    }

    /**
     * This scope is to append the comment to custom SELECT query
     *
     * @param $query
     * @param string $select
     * @param string $commentAppend this is a required unique message that would help us quickly identify the query
     * @param mixed ...$additionalAppends this would be a list of magic constants that would help us identify
     * the location where this query has been run. These usually would be: __FILE__, __METHOD__ and more arguments
     * @return Builder
     */
    public function scopeCustomSelectWithComment($query, string $select, string $commentAppend, ...$additionalAppends): Builder
    {
        return $query->selectRaw($select . $this->commentBuilder($commentAppend, ...$additionalAppends));
    }

    /**
     * This scope is to append the comment to default WHERE `1=1` query
     *
     * @param $query
     * @param string $commentAppend this is a required unique message that would help us quickly identify the query
     * @param mixed ...$additionalAppends this would be a list of magic constants that would help us identify
     * the location where this query has been run. These usually would be: __FILE__, __METHOD__ and more arguments
     * @return Builder
     */
    public function scopeWhereWithComment($query, string $commentAppend, ...$additionalAppends): Builder
    {
        return $query->whereRaw('1=1' . $this->commentBuilder($commentAppend, ...$additionalAppends));
    }

    /**
     * This is the actual comment builder that you would use fo few types of queries
     *
     * @param mixed ...$appends this field would be a unique message + magic constants
     * @return string
     */
    private function commentBuilder(...$appends): string
    {
        $comment = implode('; ', [...$appends, static::class]);

        return " /* $comment */";
    }
}
