<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Common\Service\Generate;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Migration\Service\Generate\CreateService;

use function dirname;
use function token_get_all;

use const TOKEN_PARSE;

abstract class AbstractCreateServiceTest extends TestCase
{
    protected ContainerInterface $container;

    public static function transactionModes(): iterable
    {
        $modes = [
            'template default' => [null, null, null],
            'configured on' => [true, null, true],
            'configured off' => [false, null, false],
            'override on' => [null, true, true],
            'override off' => [null, false, false],
            'override configured on' => [true, false, false],
            'override configured off' => [false, true, true],
            'explicit on' => [true, true, true],
            'explicit off' => [false, false, false],
        ];

        foreach (['create', 'table', 'dropTable', 'addColumn', 'dropColumn', 'junction'] as $command) {
            foreach ($modes as $name => [$default, $override, $expected]) {
                yield "$command: $name" => [
                    $command,
                    $default,
                    $override,
                    $expected ?? ($command === 'table' || $command === 'junction'),
                ];
            }
        }
    }

    #[DataProvider('transactionModes')]
    public function testTransactionMode(string $command, ?bool $default, ?bool $override, bool $expected): void
    {
        $service = new CreateService($this->container->get(ConnectionInterface::class), transactional: $default);

        $code = $service->run(
            $command,
            'post',
            'Example',
            fields: 'title:string',
            and: 'tag',
            transactional: $override,
        );

        token_get_all($code, TOKEN_PARSE);
        if ($expected) {
            $this->assertStringContainsString('use Yiisoft\\Db\\Migration\\TransactionalMigrationInterface;', $code);
            $this->assertStringContainsString(
                'implements RevertibleMigrationInterface, TransactionalMigrationInterface',
                $code,
            );
        } else {
            $this->assertStringNotContainsString('TransactionalMigrationInterface', $code);
            $this->assertStringContainsString('implements RevertibleMigrationInterface', $code);
        }
    }

    public static function customTemplateTransactionModes(): array
    {
        return [
            [null, null, 'template default'],
            [true, null, 'transactional'],
            [false, null, 'nontransactional'],
            [true, false, 'nontransactional'],
            [false, true, 'transactional'],
        ];
    }

    #[DataProvider('customTemplateTransactionModes')]
    public function testCustomTemplateReceivesTransactionMode(?bool $default, ?bool $override, string $expected): void
    {
        $service = new CreateService($this->container->get(ConnectionInterface::class), transactional: $default);
        $service->setTemplate('table', dirname(__DIR__, 3) . '/Support/Templates/transactionMode.php');

        $code = $service->run('table', 'post', 'Example', transactional: $override);

        $this->assertSame($expected, $code);
    }

    public function testJunctionWithoutRelatedTable(): void
    {
        $service = $this->container->get(CreateService::class);

        $this->assertSame(
            $service->run('junction', 'post', 'CreateJunction', and: ''),
            $service->run('junction', 'post', 'CreateJunction'),
        );
    }

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
