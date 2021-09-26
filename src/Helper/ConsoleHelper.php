<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Helper;

use Composer\Autoload\ClassLoader;
use ReflectionClass;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Strings\Inflector;

final class ConsoleHelper
{
    private ?InputInterface $input = null;
    private ?OutputInterface $output = null;
    private ?SymfonyStyle $io = null;
    private ?Table $table = null;
    private ?Inflector $inflector = null;

    public function input(): InputInterface
    {
        if ($this->input === null) {
            $this->input = new ArgvInput();
        }

        return $this->input;
    }

    public function output(): OutputInterface
    {
        if ($this->output === null) {
            $this->output = new ConsoleOutput();
        }

        return $this->output;
    }

    public function io(): SymfonyStyle
    {
        if ($this->io === null) {
            $this->io = new SymfonyStyle($this->input(), $this->output());
        }

        return $this->io;
    }

    public function table(): Table
    {
        if ($this->table === null) {
            $this->table = new Table($this->output());
        }

        return $this->table;
    }

    public function getPathFromNamespace(string $path): string
    {
        $namespacesPath = [];

        /** @psalm-suppress UnresolvableInclude */
        $map = require $this->getVendorDir() . '/composer/autoload_psr4.php';

        foreach ($map as $namespace => $directorys) {
            foreach ($directorys as $directory) {
                $namespacesPath[str_replace('\\', '/', trim($namespace, '\\'))] = $directory;
            }
        }

        $aliases = new Aliases($namespacesPath);

        return $aliases->get($path);
    }

    public function inflector(): Inflector
    {
        if ($this->inflector === null) {
            $this->inflector = new Inflector();
        }

        return $this->inflector;
    }

    public function getVendorDir(): string
    {
        $class = new ReflectionClass(ClassLoader::class);

        return dirname($class->getFileName(), 2);
    }
}
