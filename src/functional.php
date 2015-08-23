<?php

function partial(callable $func, $arg1)
{
    $func_args = func_get_args();
    $args = array_slice($func_args, 1);

    return function() use($func, $args) {
        $full_args = array_merge($args, func_get_args());
        return call_user_func_array($func, $full_args);
    };
}

function apply(\Traversable $traversable, callable $callback)
{
    foreach ($traversable as $item) {
        $callback($item);
    }
}

function memoize($function)
{
    return function() use ($function) {
        static $results = array();
        $args = func_get_args();
        $key = serialize($args);
        if (empty($results[$key])) {
            $results[$key] = call_user_func_array($function, $args);
        }
        return $results[$key];
    };
}
