<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Service;

use Yiisoft\Files\FileHelper;
use Yiisoft\Yii\Db\Migration\Tests\BaseTest;

abstract class NamespaceMigrationServiceTest extends BaseTest
{
    private string $namespace = 'Yiisoft\\Yii\Db\\Migration\\TestsRuntime\\MigrationServiceNamespace';
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->getMigrationService()->createNamespace($this->namespace);
        $this->getMigrationService()->updateNamespace([$this->namespace]);
        $this->path = $this->getConsoleHelper()->getPathFromNamespace('@' . str_replace('\\', '/', $this->namespace));

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

    protected function getNamespace(): string
    {
        return $this->namespace;
    }

    protected function getPath(): string
    {
        return $this->path;
    }
}
