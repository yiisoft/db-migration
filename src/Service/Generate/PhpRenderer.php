<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Service\Generate;

use Throwable;

/**
 * @internal
 */
final class PhpRenderer
{
    public function render(string $file, array $params = []): string
    {
        /** @psalm-suppress MissingClosureReturnType */
        $renderer = function () {
            /** @psalm-suppress MixedArgument */
            extract(func_get_arg(1), EXTR_OVERWRITE);
            /** @psalm-suppress UnresolvableInclude */
            require func_get_arg(0);
        };

        $obInitialLevel = ob_get_level();
        ob_start();
        ob_implicit_flush(false);
        try {
            /** @psalm-suppress PossiblyInvalidFunctionCall,PossiblyNullFunctionCall */
            $renderer->bindTo($this)($file, $params);
            return ob_get_clean();
        } catch (Throwable $e) {
            while (ob_get_level() > $obInitialLevel) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        }
    }
}
