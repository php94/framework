<?php

declare(strict_types=1);

namespace PHP94\Facade;

use Medoo\Medoo;
use PHP94\Db\Db as DbDb;

/**
 * @method static false|\PDOStatement query($query, $map = [])
 * @method static false|\PDOStatement exec($query, $map = [])
 * @method static string|false quote($string)
 * @method static false|\PDOStatement create($table, $columns, $options = null)
 * @method static false|\PDOStatement drop($table)
 * @method static array|false select($table, $join, $columns = null, $where = null)
 * @method static false|\PDOStatement insert($table, $datas)
 * @method static false|\PDOStatement update($table, $data, $where = null)
 * @method static false|\PDOStatement delete($table, $where)
 * @method static false|\PDOStatement replace($table, $columns, $where = null)
 * @method static false|string|int|array get($table, $join = null, $columns = null, $where = null)
 * @method static bool has($table, $join, $where = null)
 * @method static array|false rand($table, $join = null, $columns = null, $where = null)
 * @method static false|int count($table, $join = null, $column = null, $where = null)
 * @method static false|int avg($table, $join, $column = null, $where = null)
 * @method static false|int max($table, $join, $column = null, $where = null)
 * @method static false|int min($table, $join, $column = null, $where = null)
 * @method static false|int sum($table, $join, $column = null, $where = null)
 * @method static mixed action($actions)
 * @method static null|string|\PDOStatement|false id()
 * @method static \Medoo\Medoo debug()
 * @method static mixed error()
 * @method static string|string[]|null last()
 * @method static mixed log()
 * @method static array info()
 */
class Db
{
    public static function getInstance(): DbDb
    {
        return Container::get(DbDb::class);
    }

    public static function master(): Medoo
    {
        return self::getInstance()->master();
    }

    public static function slave(): Medoo
    {
        return self::getInstance()->slave();
    }

    public static function getMedoo(array $config = []): Medoo
    {
        return self::getInstance()->getMedoo($config);
    }

    public static function __callStatic($name, $arguments)
    {
        if (in_array($name, ['exec', 'create', 'drop', 'insert', 'update', 'delete', 'replace', 'action', 'id'])) {
            return self::master()->$name(...$arguments);
        } else {
            return self::slave()->$name(...$arguments);
        }
    }
}
