<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Support;

use PHPUnit\Framework\Constraint\StringContains;
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

trait AssertTrait
{
    protected function assertExistsTables(ContainerInterface $container, string ...$tables): void
    {
        $existsTables = $container->get(ConnectionInterface::class)->getSchema()->getTableNames();
        foreach ($tables as $table) {
            $this->assertContains($table, $existsTables);
        }
    }

    protected function assertNotExistsTables(ContainerInterface $container, string ...$tables): void
    {
        $existsTables = $container->get(ConnectionInterface::class)->getSchema()->getTableNames();
        foreach ($tables as $table) {
            $this->assertNotContains($table, $existsTables);
        }
    }

    /**
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function assertStringContainsStringIgnoringLineEndings(
        string $needle,
        string $haystack,
        string $message = ''
    ): void {
        $needle = $this->normalizeLineEndings($needle);
        $haystack = $this->normalizeLineEndings($haystack);

        static::assertThat($haystack, new StringContains($needle, false), $message);
    }

    /**
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
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
