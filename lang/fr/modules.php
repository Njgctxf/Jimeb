<?php

$parts = [];
foreach (glob(__DIR__.'/_parts/modules-*.php') as $file) {
    $parts[] = require $file;
}

return array_replace_recursive([], ...$parts);
