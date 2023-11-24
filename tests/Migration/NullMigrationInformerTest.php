<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Informer\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;

final class NullMigrationInformerTest extends TestCase
{
    public function testSetIO(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $nullInformer = new NullMigrationInformer();

        $nullInformer->setIo($io);

        $this->expectNotToPerformAssertions();
    }
}
