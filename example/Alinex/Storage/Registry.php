<?php

$registry = new ArrayRegistry();
$registry->value = 18
...
if ($registry->value < 10) {
    ...
}
unset($registry->value);

?>
