<?php

declare(strict_types=1);

namespace Tinderbox\ClickhouseBuilder;

use Tinderbox\ClickhouseBuilder\Query\Expression;

/**
 * Call the given Closure with the given value then return the value.
 *
 * @param mixed $value
 * @param callable $callback
 *
 * @return mixed
 */
function tp($value, $callback)
{
    $callback($value);

    return $value;
}
/**
 * Flatten a multi-dimensional array into a single level.
 *
 * @param array $array
 * @param int $depth
 */
function array_flatten($array, $depth = INF): array
{
    return array_reduce($array, function ($result, $item) use ($depth) {
        if (! is_array($item)) {
            return array_merge($result, [$item]);
        }
        if ($depth === 1) {
            return array_merge($result, array_values($item));
        }
        return array_merge($result, array_flatten($item, $depth - 1));
    }, []);
}

/**
 * Wrap string into Expression object for inserting in sql query as is.
 */
function raw(string $expr): Expression
{
    return new Expression($expr);
}
