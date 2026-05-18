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
use MAOSIJI\LU\LUResult;

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
     * @return LUResult
     */
    public function insert( string $tableNameNoPrefix, array $param, array $format ): LUResult
    {
        $validParamFormat = LUWPSQLParamValidator::paramFormat($param, $format);
        if ( $validParamFormat->isError() ) {
            return $validParamFormat;
        }

        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        $result = $wpdb->insert($tableName, $param, $format);

        if ($result === false) {
            return LUResult::error( 1000, '插入失败：' . $wpdb->last_error, [
                'table_name_no_prefix'  => $tableNameNoPrefix,
                'param'                 => $param,
                'format'                => $format
            ]);
        }

        if ( $result===0 ) {
            return LUResult::success([
                'data'                  => $result,
                'table_name_no_prefix'  => $tableNameNoPrefix,
                'param'                 => $param,
                'format'                => $format
            ], '插入成功，但没有行受到影响');
        }

        return LUResult::success([
            'insert_id'     => $wpdb->insert_id,
            'data'          => $result
        ], '插入成功');
    }

    /**
     * 批量插入多行（支持事务）
     *
     * @param string $tableNameNoPrefix 表名（无前缀）
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
     *                      ['name' => 'Alice', 'age' => 25],
     *                      ['name' => 'Bob',   'age' => 30],
     *                  ];
     *          $formats = ['%s', '%d'];
     *          $inserted = $dml->batchInsert('users', $rowParam, $format);
     */
    public function batchInsert( string $tableNameNoPrefix, array $rowParam, array $format, bool $useTransaction = true): LUResult
    {
        $validRowParamFormat = LUWPSQLParamValidator::rowParamFormat($rowParam, $format);
        if ( $validRowParamFormat->isError() ) {
            return $validRowParamFormat;
        }

        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

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
                return LUResult::error( 1000, '批量插入失败：' . $wpdb->last_error, [
                    'table_name_no_prefix'  => $tableNameNoPrefix,
                    'row_param'             => $rowParam,
                    'format'                => $format,
                    'useTransaction'        => $useTransaction
                ]);
            }

            if ($useTransaction) {
                $wpdb->query('COMMIT');
            }

            if ( $result===0 ) {
                return LUResult::success([
                    'data'                  => $result,
                    'table_name_no_prefix'  => $tableNameNoPrefix,
                    'row_param'             => $rowParam,
                    'format'                => $format,
                    'useTransaction'        => $useTransaction
                ], '批量插入成功，但没有行受到影响');
            }

            return LUResult::success(['data'=>$result], '批量插入成功');
        } catch (\Exception $e) {
            if ($useTransaction) {
                $wpdb->query('ROLLBACK');
            }
            return LUResult::exception($e);
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
     * @return LUResult 受影响行数（可能为 0）
     */
    public function update( string $tableNameNoPrefix, array $param, array $paramFormat, array $whereParam, array $whereFormat ): LUResult
    {
        $validParam = LUWPSQLParamValidator::paramFormat($param, $paramFormat);
        if ( $validParam->isError() ) {
            return $validParam;
        }
        $validWhere = LUWPSQLParamValidator::paramFormat($whereParam, $whereFormat);
        if ( $validWhere->isError() ) {
            return $validWhere;
        }

        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        $rows = $wpdb->update($tableName, $param, $whereParam, $paramFormat, $whereFormat);

        if ($rows === false) {
            return LUResult::error( 1000, '更新失败：' . $wpdb->last_error, [
                'table_name_no_prefix'  => $tableNameNoPrefix,
                'param'                 => $param,
                'param_format'          => $paramFormat,
                'where_param'           => $whereParam,
                'where_format'          => $whereFormat
            ]);
        }

        if ( $rows===0 ) {
            return LUResult::success([
                'data'                  => $rows,
                'table_name_no_prefix'  => $tableNameNoPrefix,
                'param'                 => $param,
                'param_format'          => $paramFormat,
                'where_param'           => $whereParam,
                'where_format'          => $whereFormat
            ], '更新成功，但没有行受到影响');
        }

        return LUResult::success(['data'=>$rows], '更新成功');
    }

    /**
     * 批量更新多行（支持事务，逐行执行）
     *
     * @param string $tableNameNoPrefix 表名（无前缀）
     * @param array  $rowParam          二维数组，每个元素是要更新的数据关联数组（所有行结构必须相同）
     * @param array  $format        一维占位符数组，与更新列的字段顺序对应，如 [‘%s’,‘%d’]
     * @param array  $rowWhereParam         二维数组，每个元素是 WHERE 条件关联数组（与 dataRows 一一对应）
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
    public function batchUpdate( string $tableNameNoPrefix, array $rowParam, array $format, array $rowWhereParam, array $whereFormat, bool $useTransaction = true ): LUResult
    {
        $validRowParam = LUWPSQLParamValidator::rowParamFormat($rowParam, $format);
        if ( $validRowParam->isError() ) {
            return $validRowParam;
        }
        $validRowWhereParam = LUWPSQLParamValidator::rowParamFormat($rowWhereParam, $whereFormat);
        if ( $validRowWhereParam->isError() ) {
            return $validRowWhereParam;
        }

        // 2. 校验表是否存在
        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $checkTable = $this->checkTableExist($tableName);
        if ($checkTable->isError()) {
            return $checkTable;
        }

        // 3. 开启事务（如果需要）
        if ($useTransaction) {
            $wpdb->query('START TRANSACTION');
        }

        $totalAffected = 0;
        try {
            foreach ($rowParam as $index => $data) {

                $where = $rowWhereParam[$index];
                // 执行更新（利用 WordPress 内置方法，自动处理占位符转义）
                $rows = $wpdb->update($tableName, $data, $where, $format, $whereFormat);
                if ($rows === false) {
                    return LUResult::error( 1000, sprintf('第 %d 行更新失败：%s', $index + 1, $wpdb->last_error), [
                        'current'               => $data,
                        'table_name_no_prefix'  => $tableNameNoPrefix,
                        'row_param'             => $rowParam,
                        'format'                => $format,
                        'row_where_param'       => $rowWhereParam,
                        'where_format'          => $whereFormat,
                        'use_transaction'       => $useTransaction
                    ]);
                }
                $totalAffected += $rows;
            }

            // 提交事务
            if ($useTransaction) {
                $wpdb->query('COMMIT');
            }

            return LUResult::success(['data' => $totalAffected], '批量更新成功');
        } catch (\Exception $e) {
            // 回滚事务
            if ($useTransaction) {
                $wpdb->query('ROLLBACK');
            }
            return LUResult::exception($e);
        }
    }

    /**
     * 删除一条数据
     *
     * @param string $tableNameNoPrefix
     * @param array  $whereParam         WHERE 条件关联数组
     * @param array  $whereFormat       条件占位符
     *
     * @return LUResult 受影响行数
     */
    public function delete( string $tableNameNoPrefix, array $whereParam, array $whereFormat ): LUResult
    {
        $validWhere = LUWPSQLParamValidator::paramFormat($whereParam, $whereFormat);
        if ( $validWhere->isError() ) {
            return $validWhere;
        }

        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        $rows = $wpdb->delete($tableName, $whereParam, $whereFormat);

        if ($rows === false) {
            return LUResult::error( 1000, '删除失败：' . $wpdb->last_error, [
                'table_name_no_prefix'  => $tableNameNoPrefix,
                'where_param'           => $whereParam,
                'where_format'          => $whereFormat
            ]);
        }

        if ( $rows===0 ) {
            return LUResult::success([
                'data'                  => $rows,
                'table_name_no_prefix'  => $tableNameNoPrefix,
                'where_param'           => $whereParam,
                'where_format'          => $whereFormat
            ], '删除成功，但没有行受到影响');
        }

        return LUResult::success(['data'=>$rows], '删除成功');
    }

    /**
     * 自定义删除查询（支持事务）
     *
     * 适用于没有共同 WHERE 条件但需批量删除的场景，如按 ID 列表删除。
     *
     * @param string $tableNameNoPrefix 表名
     * @param string $whereSql          WHERE 子句，如 "WHERE id IN (%d,%d)"
     * @param array  $whereValue       绑定值数组
     * @param bool   $useTransaction     是否启用事务
     *
     * @return LUResult 删除行数
     */
    public function queryDelete( string $tableNameNoPrefix, string $whereSql, array $whereValue, bool $useTransaction = false): LUResult
    {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        if ($useTransaction) {
            $wpdb->query('START TRANSACTION');
        }

        try {
            $sql = "DELETE FROM $tableName $whereSql";
            $prepared = $wpdb->prepare($sql, $whereValue);
            $result = $wpdb->query($prepared);

            if ($result === false) {
                return LUResult::error( 1000, '删除查询失败：' . $wpdb->last_error, [
                    'table_name_no_prefix'  => $tableNameNoPrefix,
                    'where_sql'             => $whereSql,
                    'where_value'           => $whereValue,
                    'use_transaction'       => $useTransaction
                ]);
            }

            if ($useTransaction) {
                $wpdb->query('COMMIT');
            }

            if ( $result===0 ) {
                return LUResult::success([
                    'data'                  => $result,
                    'table_name_no_prefix'  => $tableNameNoPrefix,
                    'where_sql'             => $whereSql,
                    'where_value'           => $whereValue,
                    'use_transaction'       => $useTransaction
                ], '批量删除成功，但没有行受到影响');
            }

            return LUResult::success(['data'=>$result], '批量删除成功');
        } catch (\Exception $e) {
            if ($useTransaction) {
                $wpdb->query('ROLLBACK');
            }
            return LUResult::exception($e);
        }
    }



}