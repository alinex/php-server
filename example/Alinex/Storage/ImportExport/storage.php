<?php 
// ---------------------------------------------------------------------------
// Test of storage export/import
// ---------------------------------------------------------------------------

$values['boolean'] = true;

$values['integer'] = 5;

$values['float'] = 5.3;

$values['text.simple'] = 'short text';

$values['text.multiline'] = 'line1
line2
line3';

$values['text.empty'] = '';

$values['array.simple'] = array (
  0 => 1,
  1 => 2,
  2 => 3,
);

$values['array.hash'] = array (
  'eins' => 1,
  'zwei' => 2,
  'drei' => 3,
);

$values['array.array'] = array (
  0 => 
  array (
    0 => 1,
    1 => 2,
    2 => 3,
  ),
  1 => 
  array (
    0 => 4,
    1 => 5,
    2 => 6,
  ),
);

