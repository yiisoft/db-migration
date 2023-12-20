#!/usr/bin/env php
<?php

declare(strict_types=1);

$_composer_autoload_path = dirname(__DIR__, 2) . '/vendor/autoload.php';

require dirname(__DIR__) . '/runtime/bin/vendor/yiisoft/db-migration/bin/yii-db-migration';
