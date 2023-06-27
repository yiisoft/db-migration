<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Sqlite;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Yii\Db\Migration\Tests\Common\AbstractMigrationBuilderTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\SqLiteFactory;

/**
 * @group sqlite
 */
final class MigrationBuilderTest extends AbstractMigrationBuilderTest
{
    public function setup(): void
    {
        $this->container = SqLiteFactory::createContainer();
        $this->db = $this->container->get(ConnectionInterface::class);

        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        SqLiteFactory::clearDatabase($this->container);
    }

    /**
     * @dataProvider dataAddColumn
     */
    public function testAddColumn($type, string $expectedComment = null): void
    {
        if ($expectedComment === 'test comment') {
            $this->expectException(NotSupportedException::class);
            $this->expectExceptionMessage(
                'Yiisoft\Db\Sqlite\DDLQueryBuilder::addCommentOnColumn is not supported by SQLite.'
            );
        }

        parent::testAddColumn($type, $expectedComment);
    }

    public function testAddCommentOnColumn(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Sqlite\DDLQueryBuilder::addCommentOnColumn is not supported by SQLite.'
        );

        parent::testAddCommentOnColumn();
    }

    public function testAddCommentOnTable(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Sqlite\DDLQueryBuilder::addCommentOnTable is not supported by SQLite.'
        );

        parent::testAddCommentOnTable();
    }

    public function testAddPrimaryKey(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Sqlite\DDLQueryBuilder::addPrimaryKey is not supported by SQLite.'
        );

        parent::testAddPrimaryKey();
    }

    /**
     * @dataProvider dataAlterColumn
     */
    public function testAlterColumn($type, string $expectedComment = null): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Sqlite\DDLQueryBuilder::alterColumn is not supported by SQLite.'
        );

        parent::testAlterColumn($type, $expectedComment);
    }

    public function testAddForeignKey(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Sqlite\DDLQueryBuilder::addForeignKey is not supported by SQLite.'
        );

        parent::testAddForeignKey();
    }

    public function testDropColumn(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Sqlite\DDLQueryBuilder::dropColumn is not supported by SQLite.'
        );

        parent::testDropColumn();
    }

    public function testDropCommentFromColumn(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Sqlite\DDLQueryBuilder::addCommentOnColumn is not supported by SQLite.'
        );

        parent::testDropCommentFromColumn();
    }

    public function testDropCommentFromTable(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Sqlite\DDLQueryBuilder::addCommentOnTable is not supported by SQLite.'
        );

        parent::testDropCommentFromTable();
    }

    public function testDropForeignKey(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Sqlite\DDLQueryBuilder::addForeignKey is not supported by SQLite.'
        );

        parent::testDropForeignKey();
    }

    public function testDropPrimaryKey(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Sqlite\DDLQueryBuilder::dropPrimaryKey is not supported by SQLite.'
        );

        parent::testDropPrimaryKey();
    }

    public function testRenameColumn(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Sqlite\DDLQueryBuilder::renameColumn is not supported by SQLite.'
        );

        parent::testRenameColumn();
    }
}
