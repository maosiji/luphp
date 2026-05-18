<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025年8月11日 17:10:30
 * update               :
 * project              : luphp
 * description          : 统一相同的参数的类型和写法，如 whereMeta/whereValue/whereParam/whereCompare/whereFormat
 *
 * 数据库操作抽象基类
 *
 * 提供表对象单例、缓存、以及便捷的增删改查封装。
 * 子类继承后可直接使用 $this->getRow() 等受保护方法操作各自表。
 *
 * 查询条件现在推荐使用 WhereCondition 对象，以保证类型安全和易读性。
 *
 */

namespace MAOSIJI\LU\WP\SQL;
use MAOSIJI\LU\LUResult;

if ( ! defined( 'ABSPATH' ) ) { die; }
abstract class LUWPDBSQL
{
    protected $tableNameNoPrefix;
    private static $instances = [];
    // 当前页面查询缓存，在一个页面里相同的查询条目只用查询一次即可，第2次直接返回缓存里的。
    private $query_cache = [];
    protected function __construct( string $tableNameNoPrefix )
    {
        $this->tableNameNoPrefix = $tableNameNoPrefix;
    }

    /**
     * 每个表名单例
     * @param string $tableNameNoPrefix
     * @return mixed|static|null
     */
    protected static function getObj( string $tableNameNoPrefix )
    {
        // 使用 get_called_class() 确保每个子类都有自己的实例
        $calledClass = get_called_class();
        $key = $calledClass.'::'.$tableNameNoPrefix;

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new static($tableNameNoPrefix);
        }

        return self::$instances[$key];
    }
    private function __clone()
    {
    }

    /**************************************************
     * 数据表操作 DDL
     **************************************************/

    /**
     * 创建数据表
     *
     * @param string $colSQL 列定义
     * @return LUResult
     */
    protected function createTable( string $colSQL ): LUResult
    {
        $ddl = new LUWPDDL();
        return $ddl->createTable($this->tableNameNoPrefix, $colSQL);
    }

    /**
     * 修改表名
     *
     * @param string $newTableNameNoPrefix
     * @return LUResult
     * */
    protected function renameTableName( string $newTableNameNoPrefix ): LUResult
    {
        $ddl = new LUWPDDL();
        return $ddl->renameTableName($this->tableNameNoPrefix, $newTableNameNoPrefix);
    }

    /**
     * 清空表数据，保留结构
     *
     * @return LUResult
     * */
    protected function truncateTable(): LUResult
    {
        $ddl = new LUWPDDL();
        $result = $ddl->truncateTable($this->tableNameNoPrefix);
        if ( $result->isSuccess() ) {
            $this->clearTableCache();
        }

        return $result;
    }

    /**
     * 修改表注释
     *
     * @param string $comment
     * @return LUResult
     */
    protected function commentTable( string $comment ): LUResult
    {
        $ddl = new LUWPDDL();
        return $ddl->commentTable($this->tableNameNoPrefix, $comment);
    }

    /**
     * 删除表
     *
     * @return LUResult
     */
    protected function deleteTable(): LUResult
    {
        $ddl = new LUWPDDL();
        $result = $ddl->deleteTable($this->tableNameNoPrefix);
        if ( $result->isSuccess() ) {
            $this->clearTableCache();
        }

        return $result;
    }

    /**
     * 修改表 添加列
     *
     * @param string $columnName 列名
     * @param string $columnType 列类型
     * @param string $columnFormat 占位符
     * @param string|null $columnDefault 默认值
     * @param string $columnComment 备注、说明
     *
     * @return LUResult
     * */
    protected function alterTableAddColumn( string $columnName, string $columnType='', string $columnFormat='%s', $columnDefault='', string $columnComment='' ): LUResult
    {
        $ddl = new LUWPDDL();
        $result = $ddl->alterTableAddColumn($this->tableNameNoPrefix, $columnName, $columnType, $columnFormat, $columnDefault, $columnComment);
        if ( $result->isSuccess() ) {
            $this->clearTableCache();
        }

        return $result;
    }

    /**
     * 修改表 修改列，不能修改列名【注意：更改之前已经存在的内容不会变】
     *
     * @param string $columnName 列名
     * @param string $columnType 列类型
     * @param string $columnFormat 占位符
     * @param string|null $columnDefault 默认值
     * @param string $columnComment 备注、说明
     *
     * @return LUResult
     */
    protected function alterTableModifyColumn( string $columnName, string $columnType, string $columnFormat='%s', $columnDefault='', string $columnComment='' ): LUResult
    {
        $ddl = new LUWPDDL();
        $result = $ddl->alterTableModifyColumn($this->tableNameNoPrefix, $columnName, $columnType, $columnFormat, $columnDefault, $columnComment);
        if ( $result->isSuccess() ) {
            $this->clearTableCache();
        }

        return $result;
    }

    /**
     * 修改表 修改列，可以修改列名【注意：更改之前已经存在的内容不会变】
     *
     * @param string $columnName 列名
     * @param string $newColumnName 新列名
     * @param string $columnType 列类型
     * @param string $columnFormat 占位符
     * @param string|null $columnDefault 默认值
     * @param string $columnComment 备注、说明
     *
     * @return LUResult
     */
    protected function alterTableChangeColumn( string $columnName, string $newColumnName, string $columnType, string $columnFormat='%s', $columnDefault='', string $columnComment='' ): LUResult
    {
        $ddl = new LUWPDDL();
        $result = $ddl->alterTableChangeColumn($this->tableNameNoPrefix, $columnName, $newColumnName, $columnType, $columnFormat, $columnDefault, $columnComment);
        if ( $result->isSuccess() ) {
            $this->clearTableCache();
        }

        return $result;
    }

    /**
     * 修改表 删除列
     *
     * @param string $columnName:要删除的列名
     * @return LUResult
     */
    protected function alterTableDeleteColumn( string $columnName ): LUResult
    {
        $ddl = new LUWPDDL();
        $result = $ddl->alterTableDeleteColumn($this->tableNameNoPrefix, $columnName);
        if ( $result->isSuccess() ) {
            $this->clearTableCache();
        }

        return $result;
    }

    /**
     * 修改表 添加索引
     *
     * @param string $indexName 索引名
     * @param string $columnName 列名
     * @return LUResult
     */
    protected function alterTableAddIndex( string $indexName, string $columnName ): LUResult
    {
        $ddl = new LUWPDDL();
        return $ddl->alterTableAddIndex($this->tableNameNoPrefix, $indexName, $columnName);
    }

    /**
     * 修改表 添加唯一约束（创建唯一索引）
     *
     * @param string $indexName 唯一索引名
     * @param string $columnName 列名
     * @return LUResult
     */
    protected function alterTableAddUnique( string $indexName, string $columnName ): LUResult
    {
        $ddl = new LUWPDDL();
        return $ddl->alterTableAddUnique($this->tableNameNoPrefix, $indexName, $columnName);
    }

    /**
     * 删除索引 或 唯一索引
     *
     * @param string $indexName
     * @return LUResult
     */
    protected function alterTableDeleteIndex( string $indexName ): LUResult
    {
        $ddl = new LUWPDDL();
        return $ddl->alterTableDeleteIndex($this->tableNameNoPrefix, $indexName);
    }


    /**************************************************
     * 数据操作 DML
     **************************************************/


    /**
     * 插入一条数据
     *
     * @param array  $param             关联数组，键为字段名，值为要插入的数据
     * @param array  $format            与 $param 对应的占位符数组，如 ['%s','%d']
     *
     * @return LUResult
     */
    protected function insert( array $param, array $format ): LUResult
    {
        $dml = new LUWPDML();
        $result = $dml->insert( $this->tableNameNoPrefix, $param, $format );
        if ( $result->isSuccess() ) {
            $this->clearTableCache();
        }

        return $result;
    }

    /**
     * 批量插入多行（支持事务）
     *
     * @param array  $rowParam          二维数组，每个元素是一行的数据关联数组
     * @param array  $format            一维占位符数组，如 ['%s','%d','%f']，与每行的列顺序对应
     * @param bool   $useTransaction    是否启用事务（默认开启，保证原子性）
     *
     * @return LUResult 插入的总行数
     *
     * 调用示例
     *
     *          $dml = new LUWPDML();
     *          $rowParam = [
     *                          ['name' => 'Alice', 'age' => 25],
     *                          ['name' => 'Bob',   'age' => 30],
     *                      ];
     *          $formats = ['%s', '%d'];
     *          $inserted = $dml->batchInsert('users', $rowParam, $format);
     */
    protected function batchInsert( array $rowParam, array $format, bool $useTransaction=true ): LUResult
    {
        $dml = new LUWPDML();
        $result = $dml->batchInsert( $this->tableNameNoPrefix, $rowParam, $format, $useTransaction );
        if ( $result->isSuccess() ) {
            $this->clearTableCache();
        }

        return $result;
    }

    /**
     * 更新一条数据
     *
     * @param array  $param             要更新的数据关联数组
     * @param array  $format            数据对应的占位符
     * @param array  $whereParam        WHERE 条件关联数组
     * @param array  $whereFormat       WHERE 条件占位符
     *
     * @return LUResult 受影响行数（可能为 0）
     */
    protected function update( array $param, array $format, array $whereParam, array $whereFormat ): LUResult
    {
        $dml = new LUWPDML();
        $result = $dml->update( $this->tableNameNoPrefix, $param, $format, $whereParam, $whereFormat );
        if ( $result->isSuccess() ) {
            $this->clearTableCache();
        }

        return $result;
    }

    /**
     * 批量更新多行（支持事务，逐行执行）
     *
     * @param array  $rowParam          二维数组，每个元素是要更新的数据关联数组（所有行结构必须相同）
     * @param array  $format            一维占位符数组，与更新列的字段顺序对应，如 [‘%s’,‘%d’]
     * @param array  $rowWhereParam     二维数组，每个元素是 WHERE 条件关联数组（与 dataRows 一一对应）
     * @param array  $whereFormat       一维占位符数组，与条件列的字段顺序对应
     * @param bool   $useTransaction    是否启用事务（默认开启，保证原子性）
     *
     * @return LUResult 总受影响的行数
     *
     * 调用示例：
     *      $dml = new LUWPDML();
     *      $data = [
     *          ['name' => 'Alice', 'age' => 26],
     *          ['name' => 'Bob',   'age' => 31],
     *      ];
     *      $conditions = [
     *          ['id' => 1],
     *          ['id' => 2],
     *      ];
     *      $result = $dml->batchUpdate('users', $data, ['%s','%d'], $conditions, ['%d']);
     */
    protected function batchUpdate( array $rowParam, array $format, array $rowWhereParam, array $whereFormat, bool $useTransaction = true ): LUResult
    {
        $dml = new LUWPDML();
        $result = $dml->batchUpdate($this->tableNameNoPrefix, $rowParam, $format, $rowWhereParam, $whereFormat, $useTransaction);
        if ( $result->isSuccess() ) {
            $this->clearTableCache();
        }

        return $result;
    }

    /**
     * 删除一条数据
     *
     * @param array  $whereParam         WHERE 条件关联数组
     * @param array  $whereFormat       条件占位符
     *
     * @return LUResult 受影响行数
     */
    protected function delete( array $whereParam, array $whereFormat ): LUResult
    {
        $dml = new LUWPDML();
        $result = $dml->delete( $this->tableNameNoPrefix, $whereParam, $whereFormat );
        if ( $result->isSuccess() ) {
            $this->clearTableCache();
        }

        return $result;
    }

    /**
     * 自定义删除查询（query、支持事务）需要自己用 LUWPSQLWhereCondition 拼接 sql
     *
     * 适用于没有共同 WHERE 条件但需批量删除的场景，如按 ID 列表删除。
     *
     * @param string $whereSql          WHERE 子句，如 "WHERE id IN (%d,%d)"
     * @param array  $whereValue        绑定值数组
     * @param bool   $useTransaction    是否启用事务
     *
     * @return LUResult 删除行数
     */
    protected function queryDelete(string $whereSql, array $whereValue, bool $useTransaction=true ): LUResult
    {
        $dml = new LUWPDML();
        $result = $dml->queryDelete( $this->tableNameNoPrefix, $whereSql, $whereValue, $useTransaction );
        if ( $result->isSuccess() ) {
            $this->clearTableCache();
        }

        return $result;
    }


    /**************************************************
     * 数据查询 DQL
     **************************************************/

    /**
     * 查询单列数据
     *
     * @param string $columnName             要查询的列名
     * @param string $whereSql               WHERE 子句（含占位符），如 " WHERE status = %s"
     * @param array  $whereValue             绑定值数组
     * @param bool $isDistinct               是否去重
     * @param bool $isCache:                 是否缓存
     *
     * @return LUResult 列值数组，无结果时返回空数组
     */
    protected function getCol( string $columnName, string $whereSql = '', array $whereValue = [], bool $isDistinct=false, bool $isCache=true ): LUResult
    {
        // 如果有缓存，则先查看缓存里的
        if ( $isCache ) {
            $cache_key = $this->generate_cache_key( __FUNCTION__, func_get_args() );
            if ( isset( $this->query_cache[ $cache_key ] ) ) {
                return $this->query_cache[ $cache_key ];
            }
        }

        $dql = new LUWPDQL();
        $result = $dql->getCol( $this->tableNameNoPrefix, $columnName, $whereSql, $whereValue, $isDistinct );

        if ( $isCache ) {
            $this->query_cache[ $cache_key ] = $result;
        }

        return $result;
    }

    /**
     * 查询单个值
     * 查询单行单列某个具体值 或 查询聚合值（COUNT、MAX、MIN、AVG、SUM）CONCAT（字符串拼接）
     *
     * @param string $columnName 列名  可以是单个列名；也可以是多个，即 'name, "-", age'
     * @param string $aggregateFunctionName 聚合函数，如 "COUNT(*)" 中的 COUNT 或 "SUM(amount)"中的 SUM
     * @param string $whereSql
     * @param array  $whereValue
     * @param bool $isCache:                 是否缓存
     *
     * @return LUResult 查询结果（可能为 null）
     */
    protected function getVar( string $columnName, string $aggregateFunctionName, string $whereSql='', array $whereValue=[], bool $isCache=true ): LUResult
    {
        // 如果有缓存，则先查看缓存里的
        if ( $isCache ) {
            $cache_key = $this->generate_cache_key( __FUNCTION__, func_get_args() );
            if ( isset( $this->query_cache[ $cache_key ] ) ) {
                return $this->query_cache[ $cache_key ];
            }
        }

        $columnExpression = $columnName;
        if ( $aggregateFunctionName!=='' ) {
            // 聚合表达式，如 "COUNT(*)" 或 "SUM(amount)"
            $columnExpression = $aggregateFunctionName.'('.$columnName.')';
        }

        $dql = new LUWPDQL();
        $result = $dql->getVar( $this->tableNameNoPrefix, $columnExpression, $whereSql, $whereValue );

        if ( $isCache ) {
            $this->query_cache[ $cache_key ] = $result;
        }

        return $result;
    }

    /**
     * 查询单行数据
     *
     * @param string $columnsName           列名，'*' 或逗号分隔
     * @param string $whereSql              WHERE 子句（含占位符）
     * @param array  $whereValue
     * @param string $output                返回格式：OBJECT|ARRAY_A|ARRAY_N，默认 ARRAY_A
     * @param  int $piece                    提取查询结果中的第几条数据，从 0 开始计数，默认 0
     * @param bool $isCache:                 是否缓存
     *
     * @return LUResult 无记录时返回 null
     */
    protected function getRow( string $columnsName='', string $whereSql = '', array $whereValue = [], int $piece=0, string $output = 'ARRAY_A', bool $isCache=true ): LUResult
    {
        // 如果有缓存，则先查看缓存里的
        if ( $isCache ) {
            $cache_key = $this->generate_cache_key( __FUNCTION__, func_get_args() );
            if ( isset( $this->query_cache[ $cache_key ] ) ) {
                return $this->query_cache[ $cache_key ];
            }
        }

        if (trim($columnsName)==='') { $columnsName='*'; }

        $dql = new LUWPDQL();
        $result = $dql->getRow($this->tableNameNoPrefix, $columnsName, $whereSql, $whereValue, $output, $piece);

        if ( $isCache ) {
            $this->query_cache[ $cache_key ] = $result;
        }

        return $result;
    }

    /**
     * 查询多行数据
     *
     * @param string $columnsName
     * @param string $whereSql
     * @param array  $whereValue
     * @param string $output        返回格式：OBJECT（对象）/ARRAY_A（默认，关联数组）/ARRAY_N（索引数组）
     * @param bool $isDistinct         是否去重
     * @param bool $isCache:                 是否缓存
     *
     * @return LUResult  结果集数组，无记录时返回空数组
     */
    protected function getResults( string $columnsName='', string $whereSql='', array $whereValue=[], bool $isDistinct=false, string $output = 'ARRAY_A', bool $isCache=true ): LUResult
    {
        // 如果有缓存，则先查看缓存里的
        if ( $isCache ) {
            $cache_key = $this->generate_cache_key( __FUNCTION__, func_get_args() );
            if ( isset( $this->query_cache[ $cache_key ] ) ) {
                return $this->query_cache[ $cache_key ];
            }
        }

        if (trim($columnsName)==='') { $columnsName='*'; }

        $dql = new LUWPDQL();
        $result = $dql->getResults($this->tableNameNoPrefix, $columnsName, $whereSql, $whereValue, $output, $isDistinct);

        if ( $isCache ) {
            $this->query_cache[ $cache_key ] = $result;
        }

        return $result;
    }


    /**************************************************
     * 万能方法
     **************************************************/


    /**
     * 自写SQL语句，操作前、操作后的验证也需要自行写
     *
     * @param string $sql
     * @param array $value
     * @param bool $isClearTableCache 是否清空缓存
     * @param bool $isCache:                 是否启用缓存
     * @return LUResult
     */
    protected function query( string $sql, array $value, bool $isClearTableCache=true, bool $isCache=false ): LUResult
    {
        // 如果有缓存，则先查看缓存里的
        if ( $isCache ) {
            $cache_key = $this->generate_cache_key( __FUNCTION__, func_get_args() );
            if ( isset( $this->query_cache[ $cache_key ] ) ) {
                return $this->query_cache[ $cache_key ];
            }
        }

        $ddl = new LUWPDDL();
        $result =  $ddl->query($sql, $value);
        if ( $result->isSuccess() && $isClearTableCache ) {
            $this->clearTableCache();
        }

        if ( $isCache ) {
            $this->query_cache[ $cache_key ] = $result;
        }

        return $result;
    }


    /**************************************************
     * 私有方法
     **************************************************/

    /**
     * 生成缓存 key
     */
    private function generate_cache_key( string $method, array $args ): string
    {
        // 移除最后一个参数（$nocache），避免影响 key
        $args_for_key = $args;
        array_pop($args_for_key); // 删除 $nocache

        return md5(
            get_called_class() . // 子类名
            $this->tableNameNoPrefix .         // 无前缀表名
            $method .                  // 方法名（get_row / get_col / get_var）
            serialize($args_for_key)   // 参数（不含 $nocache）
        );
    }

    private function clearTableCache( )
    {
        $this->query_cache = [];
    }



}
