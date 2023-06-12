<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Support;

use PHPUnit\Framework\Constraint\StringContains;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\NotSupportedException;

trait AssertTrait
{
    /**
     * Asserting two strings equality ignoring line endings.
     *
     * @param string $expected Expected string.
     * @param string $actual Actual string.
     */
    protected function assertEqualsWithoutLE(string $expected, string $actual, string $message = ''): void
    {
        $expected = $this->normalizeLineEndings($expected);
        $actual = $this->normalizeLineEndings($actual);

        self::assertEquals($expected, $actual, $message);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotSupportedException
     * @throws NotFoundExceptionInterface
     */
    protected function assertExistsTables(ContainerInterface $container, string ...$tables): void
    {
        $existsTables = $container->get(ConnectionInterface::class)->getSchema()->getTableNames();
        foreach ($tables as $table) {
            $this->assertContains($table, $existsTables);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws NotSupportedException
     */
    protected function assertNotExistsTables(ContainerInterface $container, string ...$tables): void
    {
        $existsTables = $container->get(ConnectionInterface::class)->getSchema()->getTableNames();
        foreach ($tables as $table) {
            $this->assertNotContains($table, $existsTables);
        }
    }

    public function assertStringContainsStringCollapsingSpaces(
        string $needle,
        string $haystack,
        string $message = ''
    ): void {
        $needle = $this->collapseSpaces($needle);
        $haystack = $this->collapseSpaces($haystack);

        static::assertThat($haystack, new StringContains($needle, false), $message);
    }

    private function collapseSpaces(string $value): string
    {
        return preg_replace('/\s\s+/', ' ', $value);
    }

    private function normalizeLineEndings(string $value): string
    {
        return strtr($value, [
            "\r\n" => "\n",
            "\r" => "\n",
        ]);
    }
}
