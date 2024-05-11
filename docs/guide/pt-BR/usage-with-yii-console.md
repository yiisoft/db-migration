# Uso com Yii Console

Neste exemplo, usamos [yiisoft/app](https://github.com/yiisoft/app).

Primeiro, configure o contêiner DI. Crie `config/common/db.php` com o seguinte conteúdo:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection as SqliteConnection;

return [
    ConnectionInterface::class => [
        'class' => SqliteConnection::class,
        '__construct()' => [
            'dsn' => 'sqlite:' . __DIR__ . '/Data/yiitest.sq3'
        ]
    ]
];
```

Adicione em `config/params.php`:

```php
...
'yiisoft/db-migration' => [
    'newMigrationNamespace' => 'App\\Migration',
    'sourceNamespaces' => ['App\\Migration'],
],
...
```

Agora o `MigrationService::class` usa o `View` da aplicação que já está cadastrada em `yiisoft/view`.

Execute `composer du` no console para reconstruir a configuração.

Agora temos o pacote `yiisoft/db-migration` configurado e ele pode ser chamado no console.

Veja a lista de comandos disponíveis com `./yii list`:

```shell
./yii list
```
