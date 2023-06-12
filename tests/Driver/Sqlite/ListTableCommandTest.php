<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Sqlite;

use Yiisoft\Yii\Db\Migration\Tests\Common\Command\AbstractListTablesCommandTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\SqLiteFactory;

/**
 * @group sqlite
 */
final class ListTableCommandTest extends AbstractListTablesCommandTest
{
    public function setup(): void
    {
        parent::setUp();
        $this->container = SqLiteFactory::createContainer();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        SqLiteFactory::clearDatabase($this->container);
    }
}
