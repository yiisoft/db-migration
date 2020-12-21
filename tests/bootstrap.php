<?php

declare(strict_types=1);

use Yiisoft\Files\FileHelper;
use Yiisoft\Yii\Db\Migration\Tests\BaseTest;

if (file_exists(BaseTest::DB_FILE)) {
    FileHelper::unlink(BaseTest::DB_FILE);
}
