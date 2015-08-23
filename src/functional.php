<?php

function partial(callable $func, $arg1) {
    $func_args = func_get_args();
    $args = array_slice($func_args, 1);

    return function() use($func, $args) {
        $full_args = array_merge($args, func_get_args());
        return call_user_func_array($func, $full_args);
    };
}

function apply(\Traversable $traversable, callable $callback) {
    foreach ($traversable as $item) {
        $callback($item);
    }
}
