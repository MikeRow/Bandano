<?php

require_once __DIR__ . '/../../src/Tools.php';

use php4nano\Tools as NanoTools;

$hexs =
[
	'9C5F967FB821CD48BA545547B32150008D356DC9C876894D14AA4959DE4818B6',
	'9C5F967FB821CD48BA545547B32150008D356DC9C876894D14AA4959DE4818B6',
	'9C5F967FB821CD48BA545547B32150008D356DC9C876894D14AA4959DE4818B6',
	'9C5F967FB821CD48BA545547B32150008D356DC9C876894D14AA4959DE4818B6',
	'9C5F967FB821CD48BA545547B32150008D356DC9C876894D14AA4959DE4818B6',
	'9C5F967FB821CD48BA545547B32150008D356DC9C876894D14AA4959DE4818B6'
];

echo NanoTools::getBlockId($hexs);
