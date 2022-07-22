<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service\Generate;

use Throwable;

/**
 * @internal
 */
final class PhpRenderer
{
    public function render(string $file, array $params = []): string
    {
        $renderer = function () {
            extract(func_get_arg(1), EXTR_OVERWRITE);
            require func_get_arg(0);
        };

        $obInitialLevel = ob_get_level();
        ob_start();
        ob_implicit_flush(false);
        try {
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
