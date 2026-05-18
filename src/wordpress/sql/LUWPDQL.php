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
use MAOSIJI\LU\LUResult;

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
     * @param array  $whereValue       绑定值数组
     * @param bool $isDistinct         是否去重
     *
     * @return LUResult 列值数组，无结果时返回空数组
     */
    public function getCol( string $tableNameNoPrefix, string $columnName, string $whereSql = '', array $whereValue = [], bool $isDistinct=false ): LUResult
    {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        if ($whereSql==='') {
            if ( $isDistinct ) {
                $result = $wpdb->get_col("SELECT DISTINCT $columnName FROM $tableName");
            }
            else {
                $result = $wpdb->get_col("SELECT $columnName FROM $tableName");
            }

        } else {
            if ( $isDistinct ) {
                $prepared = $wpdb->prepare("SELECT DISTINCT $columnName FROM $tableName $whereSql", $whereValue);
            }
            else {
                $prepared = $wpdb->prepare("SELECT $columnName FROM $tableName $whereSql", $whereValue);
            }
            $result = $wpdb->get_col($prepared);
        }

        if ($wpdb->last_error) {
            return LUResult::error( 1000, 'getCol 失败: ' . $wpdb->last_error, [
                'table_name_no_prefix'  => $tableNameNoPrefix,
                'column_name'           => $columnName,
                'where_sql'             => $whereSql,
                'where_value'           => $whereValue
            ]);
        }

        if ( $result===null ) {
            return LUResult::success([
                'data'                  => $result,
                'table_name_no_prefix'  => $tableNameNoPrefix,
                'column_name'           => $columnName,
                'where_sql'             => $whereSql,
                'where_value'           => $whereValue
            ], '未查询到数据');
        }

        return LUResult::success(['data'=>$result], '查询成功');
    }

    /**
     * 查询单个值
     * 查询单行单列某个具体值 或 查询聚合值（COUNT、MAX、MIN、AVG、SUM）CONCAT（字符串拼接）
     *
     * @param string $tableNameNoPrefix
     * @param string $columnExpression   聚合表达式，如 "COUNT(*)" 或 "SUM(amount)"
     * @param string $whereSql
     * @param array  $whereValue
     *
     * @return LUResult 查询结果（可能为 null）
     */
    public function getVar( string $tableNameNoPrefix, string $columnExpression, string $whereSql = '', array $whereValue = [] ): LUResult
    {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        if (empty($whereSql)) {
            $result = $wpdb->get_var("SELECT $columnExpression FROM $tableName");
        } else {
            $prepared = $wpdb->prepare("SELECT $columnExpression FROM $tableName $whereSql", $whereValue);
            $result = $wpdb->get_var($prepared);
        }

        if ($wpdb->last_error) {
            return LUResult::error( 1000, 'getVar 失败: ' . $wpdb->last_error, [
                'table_name_no_prefix'      => $tableNameNoPrefix,
                'column_expression'         => $columnExpression,
                'where_sql'                 => $whereSql,
                'where_value'               => $whereValue
            ]);
        }

        if ( $result===null ) {
            return LUResult::success([
                'data'                      => $result,
                'table_name_no_prefix'      => $tableNameNoPrefix,
                'column_expression'         => $columnExpression,
                'where_sql'                 => $whereSql,
                'where_value'               => $whereValue
            ], '未找到数据');
        }

        return LUResult::success(['data'=>$result], '查询成功');
    }

    /**
     * 查询单行数据
     *
     * @param string $tableNameNoPrefix
     * @param string $columnsName           列名，'*' 或逗号分隔
     * @param string $whereSql              WHERE 子句（含占位符）
     * @param array  $whereValue
     * @param string $output                返回格式：OBJECT（对象）/ARRAY_A（默认，关联数组）/ARRAY_N（索引数组）
     * @param int $piece                    提取查询结果中的第几条数据，从 0 开始计数，默认 0
     *
     * @return LUResult 无记录时返回 null
     */
    public function getRow( string $tableNameNoPrefix, string $columnsName='*', string $whereSql = '', array $whereValue = [], string $output = 'ARRAY_A', int $piece=0 ): LUResult
    {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        if (empty($whereSql)) {
            $row = $wpdb->get_row("SELECT $columnsName FROM $tableName", $output, $piece);
        } else {
            $prepared = $wpdb->prepare("SELECT $columnsName FROM $tableName $whereSql", $whereValue);
            $row = $wpdb->get_row($prepared, $output, $piece);
        }

        if ($wpdb->last_error) {
            return LUResult::error( 1000, 'getRow 失败: ' . $wpdb->last_error, [
                'table_name_no_prefix'  => $tableNameNoPrefix,
                'columns_name'          => $columnsName,
                'where_sql'             => $whereSql,
                'where_value'           => $whereValue,
                'output'                => $output
            ]);
        }

        // 未查询到
        if ( $row===null ) {
            return LUResult::success([
                'data'                  => $row,
                'table_name_no_prefix'  => $tableNameNoPrefix,
                'columns_name'          => $columnsName,
                'where_sql'             => $whereSql,
                'where_value'           => $whereValue,
                'output'                => $output
            ], '未找到数据');
        }

        return LUResult::success(['data'=>$row], '查询成功');
    }

    /**
     * 查询多行数据
     *
     * @param string $tableNameNoPrefix
     * @param string $columnsName
     * @param string $whereSql
     * @param array  $whereValue
     * @param string $output        返回格式：OBJECT（对象）/ARRAY_A（默认，关联数组）/ARRAY_N（索引数组）
     * @param bool $isDistinct         是否去重
     *
     * @return LUResult  结果集数组，无记录时返回空数组
     */
    public function getResults( string $tableNameNoPrefix, string $columnsName, string $whereSql = '', array $whereValue = [], string $output = 'ARRAY_A',  bool $isDistinct=false ): LUResult
    {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        $distinctSQL = '';
        if ( $isDistinct ) {
            $distinctSQL = ' DISTINCT ';
        }

        if (empty($whereSql)) {
            $results = $wpdb->get_results("SELECT $distinctSQL $columnsName FROM $tableName", $output);
        } else {
            $prepared = $wpdb->prepare("SELECT $distinctSQL $columnsName FROM $tableName $whereSql", $whereValue);
            $results = $wpdb->get_results($prepared, $output);
        }

        if ($wpdb->last_error) {
            return LUResult::error( 1000, 'getResults 失败: ' . $wpdb->last_error, [
                'table_name_no_prefix'  => $tableNameNoPrefix,
                'columns_name'          => $columnsName,
                'where_sql'             => $whereSql,
                'where_value'           => $whereValue,
                'output'                => $output
            ]);
        }

        if ( $results===[] ) {
            return LUResult::success([
                'data'                  => $results,
                'table_name_no_prefix'  => $tableNameNoPrefix,
                'columns_name'          => $columnsName,
                'where_sql'             => $whereSql,
                'where_value'           => $whereValue,
                'output'                => $output
            ], '未查询到数据');
        }

        return LUResult::success([
            'data'=>$results,
            'where_sql'=> $whereSql
        ], '查询成功');
    }



}