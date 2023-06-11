<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

use RectorLaravel\Rector\FuncCall\StrtolowerFuncToStrLowerStaticMethodRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->import(__DIR__ . '/../../../../../config/config.php');

    $rectorConfig->rule(StrtolowerFuncToStrLowerStaticMethodRector::class);
};
