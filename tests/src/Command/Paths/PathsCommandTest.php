<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command\Paths;

use Yiisoft\Files\FileHelper;
use Yiisoft\Yii\Db\Migration\Tests\Command\CommandTest;

abstract class PathsCommandTest extends CommandTest
{
    private string $pathAlias = '@runtime/migration-path';
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->getMigrationService()->createPath($this->pathAlias);
        $this->getMigrationService()->updatePath([$this->pathAlias]);

        $this->path = $this->getAliases()->get($this->pathAlias);

        if (file_exists($this->path)) {
            FileHelper::clearDirectory($this->path);
        } else {
            mkdir($this->path);
        }
    }

    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->path);

        parent::tearDown();
    }

    public function getPathAlias(): string
    {
        return $this->pathAlias;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
