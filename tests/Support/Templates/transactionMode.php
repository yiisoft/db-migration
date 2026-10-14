<?php

declare(strict_types=1);

/** @var bool|null $transactional */

echo match ($transactional) {
    true => 'transactional',
    false => 'nontransactional',
    null => 'template default',
};
