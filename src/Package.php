<?php

declare(strict_types=1);

namespace PHP94;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Exception;
use PDO;
use Throwable;

class Package
{
    public static function onInstall(PackageEvent $event)
    {
        self::exec(function () use ($event) {
            /**
             * @var InstallOperation $operation
             */
            $operation = $event->getOperation();
            $package_name = $operation->getPackage()->getName();
            if ($package_name == 'php94/db') {
                self::initDb();
            }
            $file = dirname(__DIR__, 3) . '/' . $package_name . '/src/package/install.php';
            if (file_exists($file)) {
                (function () use ($file) {
                    require $file;
                })();
            }
        });
    }

    public static function onUnInstall(PackageEvent $event)
    {
        self::exec(function () use ($event) {
            /**
             * @var UninstallOperation $operation
             */
            $operation = $event->getOperation();
            $package_name = $operation->getPackage()->getName();
            $file = dirname(__DIR__, 3) . '/' . $package_name . '/src/package/uninstall.php';
            if (file_exists($file)) {
                (function () use ($file) {
                    require $file;
                })();
            }
        });
    }

    public static function onUpdate(PackageEvent $event)
    {
        self::exec(function () use ($event) {
            /**
             * @var UpdateOperation $operation
             */
            $operation = $event->getOperation();
            $package_name = $operation->getTargetPackage()->getName();
            $file = dirname(__DIR__, 3) . '/' . $package_name . '/src/package/update.php';
            if (file_exists($file)) {
                $oldversion = $operation->getInitialPackage()->getVersion();
                (function () use ($file, $oldversion) {
                    require $file;
                })();
            }
        });
    }

    private static function exec(callable $callable, ...$params)
    {
        start:
        try {
            $callable(...$params);
        } catch (Throwable $th) {
            fwrite(STDOUT, "发生错误：" . $th->getMessage() . " on " . $th->getFile() . "(" . $th->getLine() . ")" . "\n");
            fwrite(STDOUT, "重试请输[r] 忽略请输[y] 终止请输[q]：");
            $input = trim((string) fgets(STDIN));
            switch ($input) {
                case '':
                case 'r':
                    goto start;
                    break;

                case 'y':
                    fwrite(STDOUT, "已忽略该错误~\n");
                    break;

                default:
                    throw new Exception("发生错误，终止！");
                    break;
            }
        }
    }

    public static function querySql(string $sql)
    {
        $prefix = 'prefix_';
        $root = dirname(__DIR__, 4);
        $cfg_file = $root . '/config/database.php';
        if (!file_exists($cfg_file)) {
            throw new Exception('无数据库配置文件：' . $cfg_file);
        }
        $cfg = self::requireFile($cfg_file);
        if (isset($cfg['master']['prefix'])) {
            $prefix = $cfg['master']['prefix'];
        }

        $dsn = $cfg['master']['database_type'] . ':'
            . 'host=' . $cfg['master']['server'] . ';'
            . 'dbname=' . $cfg['master']['database_name'] . ';';
        $pdo = new PDO($dsn, $cfg['master']['username'], $cfg['master']['password'], $cfg['master']['option']);

        $pdo->exec('SET SQL_MODE=ANSI_QUOTES');
        $pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_general_ci');

        return $pdo->query(str_replace('prefix_', $prefix, $sql . ';'));
    }

    public static function execSql(string $sql)
    {
        $prefix = 'prefix_';
        $root = dirname(__DIR__, 4);
        $cfg_file = $root . '/config/database.php';
        if (!file_exists($cfg_file)) {
            throw new Exception('无数据库配置文件：' . $cfg_file);
        }
        $cfg = self::requireFile($cfg_file);
        if (isset($cfg['master']['prefix'])) {
            $prefix = $cfg['master']['prefix'];
        }

        $dsn = $cfg['master']['database_type'] . ':'
            . 'host=' . $cfg['master']['server'] . ';'
            . 'dbname=' . $cfg['master']['database_name'] . ';';
        $pdo = new PDO($dsn, $cfg['master']['username'], $cfg['master']['password'], $cfg['master']['option']);

        $pdo->exec('SET SQL_MODE=ANSI_QUOTES');
        $pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_general_ci');

        $sqls = array_filter(explode(";" . PHP_EOL, $sql));
        foreach ($sqls as $sql) {
            $pdo->exec(str_replace('prefix_', $prefix, $sql . ';'));
        }
    }

    private static function requireFile(string $file)
    {
        static $loader;
        if (!$loader) {
            $loader = new class()
            {
                public function load(string $file)
                {
                    return require $file;
                }
            };
        }
        return $loader->load($file);
    }

    private static function initDb()
    {
        database:
        fwrite(STDOUT, "请输入数据库地址(默认127.0.0.1)[server]：");
        $server = trim((string) fgets(STDIN)) ?: '127.0.0.1';

        fwrite(STDOUT, "请输入数据库端口(默认3306)[port]：");
        $port = trim((string) fgets(STDIN)) ?: 3306;

        fwrite(STDOUT, "请输入数据库名称[database]：");
        $database = trim((string) fgets(STDIN));

        fwrite(STDOUT, "请输入数据库账户(默认root)[username]：");
        $username = trim((string) fgets(STDIN)) ?: 'root';

        fwrite(STDOUT, "请输入数据库密码(默认空)[password]：");
        $password = trim((string) fgets(STDIN)) ?: '';
        fwrite(STDOUT, "地址：" . $server . " 端口：" . $port . " 数据库：" . $database . " 账户：" . $username . " 密码：" . $password . "\n");
        fwrite(STDOUT, "重试请输[no] 确认[yes]：");
        $input = trim((string) fgets(STDIN)) ?: 'yes';
        if ($input != 'yes') {
            goto database;
        }

        $databasetpl = <<<'str'
<?php
return [
    'master'=>[
        'database_type' => 'mysql',
        'database_name' => '{database}',
        'server' => '{server}',
        'username' => '{username}',
        'password' => '{password}',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_general_ci',
        'port' => '{port}',
        'logging' => false,
        'option' => [
            \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_STRINGIFY_FETCHES => false,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ],
        'command' => ['SET SQL_MODE=ANSI_QUOTES'],
    ],
];
str;
        $database_file = dirname(__DIR__, 4) . '/config/database.php';
        if (!file_exists($database_file)) {
            if (!is_dir(dirname($database_file))) {
                mkdir(dirname($database_file), 0755, true);
            }
        }
        file_put_contents($database_file, str_replace([
            '{server}',
            '{port}',
            '{database}',
            '{username}',
            '{password}',
        ], [
            $server,
            $port,
            $database,
            $username,
            $password
        ], $databasetpl));
    }
}
