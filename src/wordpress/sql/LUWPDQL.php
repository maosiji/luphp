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
 * 数据查询语言（DQL）操作类
 *
 * 封装 SELECT 相关查询，统一使用 $wpdb->prepare 防止 SQL 注入。
 */

namespace MAOSIJI\LU\WP\SQL;

use MAOSIJI\LU\EXCEPTION\LUDatabaseException;

if ( ! defined( 'ABSPATH' ) ) { die; }
class LUWPDQL
{
    use LUWPSQLPublic;
    function __construct()
    {
    }
    private function __clone()
    {
    }

    /**
     * 查询单列数据
     *
     * @param string $tableNameNoPrefix 表名（无前缀）
     * @param string $columnName        要查询的列名
     * @param string $whereSql          WHERE 子句（含占位符），如 " WHERE status = %s"
     * @param array  $whereValues       绑定值数组
     *
     * @return array 列值数组，无结果时返回空数组
     * @throws LUDatabaseException
     */
    public function getCol( string $tableNameNoPrefix, string $columnName, string $whereSql = '', array $whereValues = [] ): array
    {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $this->checkTableExists($tableName);

        if (empty($whereSql)) {
            $results = $wpdb->get_col("SELECT $columnName FROM $tableName");
        } else {
            $prepared = $wpdb->prepare("SELECT $columnName FROM $tableName $whereSql", $whereValues);
            $results = $wpdb->get_col($prepared);
        }

        if ($wpdb->last_error) {
            throw new LUDatabaseException(
                'getCol failed: ' . $wpdb->last_error,
                LUDatabaseException::CODE_QUERY_FAILED
            );
        }

        return is_array($results) ? $results : [];
    }

    /**
     * 查询单个聚合值（COUNT、SUM、AVG 等）
     *
     * @param string $tableNameNoPrefix
     * @param string $columnExpression   聚合表达式，如 "COUNT(*)" 或 "SUM(amount)"
     * @param string $whereSql
     * @param array  $whereValues
     *
     * @return string|null 查询结果（可能为 null）
     * @throws LUDatabaseException
     */
    public function getVar( string $tableNameNoPrefix, string $columnExpression, string $whereSql = '', array $whereValues = [] )
    {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $this->checkTableExists($tableName);

        if (empty($whereSql)) {
            $result = $wpdb->get_var("SELECT $columnExpression FROM $tableName");
        } else {
            $prepared = $wpdb->prepare("SELECT $columnExpression FROM $tableName $whereSql", $whereValues);
            $result = $wpdb->get_var($prepared);
        }

        if ($wpdb->last_error) {
            throw new LUDatabaseException(
                'getVar failed: ' . $wpdb->last_error,
                LUDatabaseException::CODE_QUERY_FAILED
            );
        }

        return $result;
    }

    /**
     * 查询单行数据
     *
     * @param string $tableNameNoPrefix
     * @param string $columnsName          列名，'*' 或逗号分隔
     * @param string $whereSql         WHERE 子句（含占位符）
     * @param array  $whereValues
     * @param string $output           返回格式：OBJECT|ARRAY_A|ARRAY_N，默认 ARRAY_A
     *
     * @return object|array|null 无记录时返回 null
     * @throws LUDatabaseException
     */
    public function getRow( string $tableNameNoPrefix, string $columnsName, string $whereSql = '', array $whereValues = [], string $output = 'ARRAY_A' )
    {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $this->checkTableExists($tableName);

        if (empty($whereSql)) {
            $row = $wpdb->get_row("SELECT $columnsName FROM $tableName", $output);
        } else {
            $prepared = $wpdb->prepare("SELECT $columnsName FROM $tableName $whereSql", $whereValues);
            $row = $wpdb->get_row($prepared, $output);
        }

        if ($wpdb->last_error) {
            throw new LUDatabaseException(
                'getRow failed: ' . $wpdb->last_error,
                LUDatabaseException::CODE_QUERY_FAILED
            );
        }

        return $row;
    }

    /**
     * 查询多行数据
     *
     * @param string $tableNameNoPrefix
     * @param string $columnsName
     * @param string $whereSql
     * @param array  $whereValues
     * @param string $output        OBJECT|ARRAY_A|ARRAY_N
     *
     * @return array  结果集数组，无记录时返回空数组
     * @throws LUDatabaseException
     */
    public function getResults( string $tableNameNoPrefix, string $columnsName, string $whereSql = '', array $whereValues = [], string $output = 'ARRAY_A'): array
    {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $this->checkTableExists($tableName);

        if (empty($whereSql)) {
            $results = $wpdb->get_results("SELECT $columnsName FROM $tableName", $output);
        } else {
            $prepared = $wpdb->prepare("SELECT $columnsName FROM $tableName $whereSql", $whereValues);
            $results = $wpdb->get_results($prepared, $output);
        }

        if ($wpdb->last_error) {
            throw new LUDatabaseException(
                'getResults failed: ' . $wpdb->last_error,
                LUDatabaseException::CODE_QUERY_FAILED
            );
        }

        return is_array($results) ? $results : [];
    }



}