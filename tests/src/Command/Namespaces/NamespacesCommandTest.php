<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command\Namespaces;

use Yiisoft\Files\FileHelper;
use Yiisoft\Yii\Db\Migration\Tests\Command\CommandTest;

abstract class NamespacesCommandTest extends CommandTest
{
    private string $namespace = 'Yiisoft\\Yii\Db\\Migration\\TestsRuntime\\MigrationNamespace';
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->getMigrationService()->createNamespace($this->namespace);
        $this->getMigrationService()->updateNamespaces([$this->namespace]);
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
