<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Service\Generate;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Yii\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\SqLiteHelper;

final class CreateServiceTest extends TestCase
{
    public function testSetTemplate(): void
    {
        $service = SqLiteHelper::createContainer()->get(CreateService::class);

        $service->setTemplate('hello', '/templates/hello.php');

        $this->assertSame('/templates/hello.php', $service->getTemplate('hello'));
    }

    public function testSetTemplates(): void
    {
        $service = SqLiteHelper::createContainer()->get(CreateService::class);

        $service->setTemplates(['hello' => '/templates/hello.php']);

        $this->assertSame('/templates/hello.php', $service->getTemplate('hello'));
    }

    public function testNotExistsCommand(): void
    {
        $service = SqLiteHelper::createContainer()->get(CreateService::class);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('You must define a template to generate the migration.');
        $service->getTemplate('not-exists');
    }
}
