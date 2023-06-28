<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Pgsql;

use Yiisoft\Yii\Db\Migration\Tests\Common\Command\AbstractRedoCommandTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\PostgreSqlFactory;

/**
 * @group pgsql
 */
final class RedoCommandTest extends AbstractRedoCommandTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = PostgreSqlFactory::createContainer();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        PostgreSqlFactory::clearDatabase($this->container);
    }
}
