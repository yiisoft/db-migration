<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Service\Migrate;

use Yiisoft\Yii\Db\Migration\Tests\Service\NamespaceMigrationServiceTest;

final class UpdateServiceTest extends NamespaceMigrationServiceTest
{
    public function testNotExistMigration(): void
    {
        $this->assertFalse($this->getUpdateService()->run('NotExistsMigration'));
    }
}
