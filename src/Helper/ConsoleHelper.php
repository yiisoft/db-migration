<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Helper;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Strings\Inflector;

final class ConsoleHelper
{
    private ?InputInterface $input = null;
    private ?OutputInterface $output = null;
    private ?SymfonyStyle $io = null;
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
}
