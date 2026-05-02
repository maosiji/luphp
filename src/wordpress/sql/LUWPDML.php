<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-12-03 23:57
 * update               :
 * project              : luphp
 *
 * 主要用于检索、插入、更新和删除数据库中的数据。它直接处理数据库中的数据内容。
    INSERT：用于向数据库表中插入新记录。
    UPDATE：用于更新数据库表中的现有记录。
    DELETE：用于从数据库表中删除记录。
 */

namespace MAOSIJI\LU\WP\SQL;
use MAOSIJI\LU\EXCEPTION\LUDatabaseException;

if ( ! defined( 'ABSPATH' ) ) { die; }
class LUWPDML
{
    use LUWPSQLPublic;
    function __construct() {}
    private function __clone() {}

    /**
     * 插入单行数据
     *
     * @param string $tableNameNoPrefix 不带前缀的表名
     * @param array  $param             关联数组，键为字段名，值为要插入的数据
     * @param array  $format            与 $data 对应的占位符数组，如 ['%s','%d']
     *
     * @return LUWPDMLResult
     * @throws LUDatabaseException
     */
    public function insert( string $tableNameNoPrefix, array $param, array $format ): LUWPDMLResult
    {
        LUWPSQLParamValidator::tableNameNoPrefix($tableNameNoPrefix);
        LUWPSQLParamValidator::paramFormat($param, $format);

        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $this->checkTableExists($tableName);

        $result = $wpdb->insert($tableName, $param, $format);

        if ($result === false) {
            throw new LUDatabaseException(
                '插入失败：' . $wpdb->last_error,
                LUDatabaseException::CODE_QUERY_FAILED
            );
        }

        return new LUWPDMLResult($wpdb->insert_id, $result);
    }

    /**
     * 批量插入多行（支持事务）
     *
     * @param string $tableNameNoPrefix 表名（无前缀）
     * @param array  $rowParam          二维数组，每个元素是一行的数据关联数组
     * @param array  $format            一维占位符数组，如 ['%s','%d','%f']，与每行的列顺序对应
     * @param bool   $useTransaction    是否启用事务（默认开启，保证原子性）
     *
     * @return int 插入的总行数
     * @throws LUDatabaseException
     *
     * 调用示例
     *
     *          $dml = new LUWPDML();
     *          $rowParam = [
     *                      ['name' => 'Alice', 'age' => 25],
     *                      ['name' => 'Bob',   'age' => 30],
     *                  ];
     *          $formats = ['%s', '%d'];
     *          $inserted = $dml->batchInsert('users', $rowParam, $format);
     */
    public function batchInsert( string $tableNameNoPrefix, array $rowParam, array $format, bool $useTransaction = true): int
    {
        LUWPSQLParamValidator::tableNameNoPrefix($tableNameNoPrefix);
        LUWPSQLParamValidator::rowParamFormat($rowParam, $format);

        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $this->checkTableExists($tableName);

        // 构建占位符行
        $rowPlaceholder = '(' . implode(',', $format) . ')';
        $placeholders = implode(', ', array_fill(0, count($rowParam), $rowPlaceholder));

        // 列名（从第一行取键名）
        $columns = '`' . implode('`, `', array_keys(reset($rowParam))) . '`';

        // 展平值
        $values = [];
        foreach ($rowParam as $row) {
            foreach ($row as $val) {
                $values[] = $val;
            }
        }

        if ($useTransaction) {
            $wpdb->query('START TRANSACTION');
        }

        try {
            $sql = "INSERT INTO $tableName ($columns) VALUES $placeholders";
            $prepared = $wpdb->prepare($sql, $values);
            $result = $wpdb->query($prepared);

            if ($result === false) {
                throw new LUDatabaseException(
                    '批量插入失败：' . $wpdb->last_error,
                    LUDatabaseException::CODE_QUERY_FAILED
                );
            }

            if ($useTransaction) {
                $wpdb->query('COMMIT');
            }

            return $result;
        } catch (\Exception $e) {
            if ($useTransaction) {
                $wpdb->query('ROLLBACK');
            }
            throw new LUDatabaseException(
                $e->getMessage(),
                LUDatabaseException::CODE_QUERY_FAILED,
                $e
            );
        }
    }

    /**
     * 更新记录
     *
     * @param string $tableNameNoPrefix 表名（无前缀）
     * @param array  $param             要更新的数据关联数组
     * @param array  $paramFormat       数据对应的占位符
     * @param array  $whereParam        WHERE 条件关联数组
     * @param array  $whereFormat       WHERE 条件占位符
     *
     * @return int 受影响行数（可能为 0）
     * @throws LUDatabaseException
     */
    public function update( string $tableNameNoPrefix, array $param, array $paramFormat, array $whereParam, array $whereFormat ): int
    {
        LUWPSQLParamValidator::tableNameNoPrefix($tableNameNoPrefix);
        LUWPSQLParamValidator::paramFormat($param, $paramFormat);
        LUWPSQLParamValidator::paramFormat($whereParam, $whereFormat);

        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $this->checkTableExists($tableName);

        $rows = $wpdb->update($tableName, $param, $whereParam, $paramFormat, $whereFormat);

        if ($rows === false) {
            throw new LUDatabaseException(
                //'Update failed: ' . $wpdb->last_error,
                '更新失败：' . $wpdb->last_error,
                LUDatabaseException::CODE_QUERY_FAILED,
                null,
                [
                    'table_name_no_prefix'  => $tableNameNoPrefix,
                    'param'                 => $param,
                    'param_format'          => $paramFormat,
                    'where_param'           => $whereParam,
                    'where_format'          => $whereFormat
                ]
            );
        }

        return $rows;
    }

    /**
     * 删除记录
     *
     * @param string $tableNameNoPrefix
     * @param array  $whereParam         WHERE 条件关联数组
     * @param array  $whereFormat       条件占位符
     *
     * @return int 受影响行数
     * @throws LUDatabaseException
     */
    public function delete( string $tableNameNoPrefix, array $whereParam, array $whereFormat ): int
    {
        LUWPSQLParamValidator::tableNameNoPrefix($tableNameNoPrefix);
        LUWPSQLParamValidator::paramFormat($whereParam, $whereFormat);

        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $this->checkTableExists($tableName);

        $rows = $wpdb->delete($tableName, $whereParam, $whereFormat);

        if ($rows === false) {
            throw new LUDatabaseException(
                //'Delete failed: ' . $wpdb->last_error,
                '删除失败：' . $wpdb->last_error,
                LUDatabaseException::CODE_QUERY_FAILED,
                null,
                [
                    'table_name_no_prefix'  => $tableNameNoPrefix,
                    'where_param'           => $whereParam,
                    'where_format'          => $whereFormat
                ]
            );
        }

        return $rows;
    }

    /**
     * 自定义删除查询（支持事务）
     *
     * 适用于没有共同 WHERE 条件但需批量删除的场景，如按 ID 列表删除。
     *
     * @param string $tableNameNoPrefix 表名
     * @param string $whereSql          WHERE 子句，如 "WHERE id IN (%d,%d)"
     * @param array  $whereValues       绑定值数组
     * @param bool   $isTransaction     是否启用事务
     *
     * @return int 删除行数
     * @throws LUDatabaseException
     */
    public function queryDelete( string $tableNameNoPrefix, string $whereSql, array $whereValues, bool $isTransaction = false): int
    {
        LUWPSQLParamValidator::tableNameNoPrefix($tableNameNoPrefix);

        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $this->checkTableExists($tableName);

        if ($isTransaction) {
            $wpdb->query('START TRANSACTION');
        }

        try {
            $sql = "DELETE FROM $tableName $whereSql";
            $prepared = $wpdb->prepare($sql, $whereValues);
            $result = $wpdb->query($prepared);

            if ($result === false) {
                throw new LUDatabaseException(
                    //'Delete query failed: ' . $wpdb->last_error,
                    '删除查询失败：' . $wpdb->last_error,
                    LUDatabaseException::CODE_QUERY_FAILED
                );
            }

            if ($isTransaction) {
                $wpdb->query('COMMIT');
            }

            return $result;
        } catch (\Exception $e) {
            if ($isTransaction) {
                $wpdb->query('ROLLBACK');
            }
            throw new LUDatabaseException(
                $e->getMessage(),
                LUDatabaseException::CODE_QUERY_FAILED,
                $e
            );
        }
    }



}