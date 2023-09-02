<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Common\Service\Generate;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Migration\Service\Generate\CreateService;

abstract class AbstractCreateServiceTest extends TestCase
{
    protected ContainerInterface $container;

    public function testSetTemplate(): void
    {
        $service = $this->container->get(CreateService::class);

        $service->setTemplate('hello', '/templates/hello.php');

        $this->assertSame('/templates/hello.php', $service->getTemplate('hello'));
    }

    public function testSetTemplates(): void
    {
        $service = $this->container->get(CreateService::class);

        $service->setTemplates(['hello' => '/templates/hello.php']);

        $this->assertSame('/templates/hello.php', $service->getTemplate('hello'));
    }

    public function testNotExistsCommand(): void
    {
        $service = $this->container->get(CreateService::class);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('You must define a template to generate the migration.');

        $service->getTemplate('not-exists');
    }
}
