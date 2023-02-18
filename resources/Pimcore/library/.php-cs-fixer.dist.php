<?php

declare(strict_types=1);

$configurator = require __DIR__ .'/vendor/sigwin/infra/resources/PHP/php-cs-fixer.php';

$header = <<<'EOF'
All rights reserved.

(c) sigwin.hr
EOF;

return $configurator(__DIR__, $header);
