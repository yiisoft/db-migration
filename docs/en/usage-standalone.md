# Standalone usage

1. Copy configuration file `./vendor/yiisoft/db-migration/bin/yii-db-migration.php` to root folder of your project:

    ```shell
    cp ./vendor/yiisoft/db-migration/bin/yii-db-migration.php ./yii-db-migration.php
    ```

2. Define DB connection in configuration file (see 
   [Yii DB documentation](https://github.com/yiisoft/db/blob/master/docs/en/README.md#create-connection)).
   For example, MySQL connection: 
    ```php
    'db' => new \Yiisoft\Db\Mysql\Connection(
        new \Yiisoft\Db\Mysql\Driver('mysql:host=mysql;dbname=mydb', 'user', 'q1w2e3r4'),
        new \Yiisoft\Db\Cache\SchemaCache(new \Yiisoft\Cache\ArrayCache()),
    ),
    ```
   
3. Optionally, modify other options in the configuration file. Each option has a comment with description.
4. Run the console command without arguments to see the list of available migration commands:

    ```shell
    ./vendor/bin/yii-db-migration
    ```
