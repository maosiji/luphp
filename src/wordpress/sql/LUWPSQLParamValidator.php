<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-30 15:15
 * update               : 
 * project              : luphp
 * description          : 数据库参数验证器
 */

namespace MAOSIJI\LU\WP\SQL;
use MAOSIJI\LU\EXCEPTION\LUDatabaseException;
use MAOSIJI\LU\LUArray;

class LUWPSQLParamValidator
{
    private static $allowFormat = ['%s', '%d', '%f'];

    /**
     * 检测去掉空格的没有前缀的表名是否为空，若不，则返回去掉前后空格的表名
     *
     * 不能为空，只能包含数字和字母。
     *
     * @param $tableNameNoPrefix
     */
    public static function tableNameNoPrefix( $tableNameNoPrefix )
    {
        if ( !is_string($tableNameNoPrefix) ) {
            throw new LUDatabaseException(
            //'table name not empty',
                '表名（无前缀）必须是字符串',
                LUDatabaseException::CODE_INVALID_TABLE_NAME
            );
        }

        if ($tableNameNoPrefix==='') {
            throw new LUDatabaseException(
                //'table name not empty',
                '表名（无前缀）不能为空',
                LUDatabaseException::CODE_INVALID_TABLE_NAME
            );
        }

        if ( !ctype_alnum($tableNameNoPrefix) ) {
            throw new LUDatabaseException(
            //'table name not empty',
                '表名（无前缀）只能由字母和数字组成',
                LUDatabaseException::CODE_INVALID_TABLE_NAME
            );
        }
    }

    public static function tableName( $tableName )
    {
        if ( !is_string($tableName) ) {
            throw new LUDatabaseException(
            //'table name not empty',
                '表名 必须是字符串',
                LUDatabaseException::CODE_INVALID_TABLE_NAME
            );
        }

        if ($tableName==='') {
            throw new LUDatabaseException(
            //'table name not empty',
                '表名 不能为空',
                LUDatabaseException::CODE_INVALID_TABLE_NAME
            );
        }
    }

    public static function sql( $sql )
    {
        if ( !is_string($sql) ) {
            throw new LUDatabaseException(
                'SQL 语句必须是字符串',
                LUDatabaseException::CODE_INVALID_SQL
            );
        }

        if ( $sql==='' ) {
            throw new LUDatabaseException(
                'SQL 语句不能为空',
                LUDatabaseException::CODE_INVALID_SQL
            );
        }
    }

    public static function paramFormat( $param, $format )
    {
        LUWPSQLParamValidator::format($format);

        if ( !is_array($param) || !is_array($format) ) {
            throw new LUDatabaseException(
                '参数 param 和 format 必须是数组',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "param"  => $param,
                    "format" => $format
                ]
            );
        }

        if ( count($param)===0 || count($format)===0 ) {
            throw new LUDatabaseException(
                '值数组或占位符数组不能为空数组',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "param"  => $param,
                    "format" => $format
                ]
            );
        }

        if (count($param) !== count($format)) {
            throw new LUDatabaseException(
                '数据字段数量与占位符数量不一致',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "param" => $param,
                    "format" => $format
                ]
            );
        }
    }

    public static function rowParamFormat( $rowParam, $format )
    {
        LUWPSQLParamValidator::format($format);

        if ( !(new LUArray())->isTwoDimensionalArray($rowParam) ) {
            throw new LUDatabaseException(
                'rowParam 必须是二维数组',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "row_param" => $rowParam,
                    "format" => $format
                ]
            );
        }

        if ( !is_array($format) ) {
            throw new LUDatabaseException(
                '参数 format 必须是数组',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "row_param" => $rowParam,
                    "format" => $format
                ]
            );
        }

        if ( count($rowParam)===0 || count($format)===0 ) {
            throw new LUDatabaseException(
                '值数组或占位符数组不能为空数组',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "row_param" => $rowParam,
                    "format" => $format
                ]
            );
        }

        foreach ( $rowParam as $rp ) {
            if ( count($rp) !== count($format) ) {
                throw new LUDatabaseException(
                    '参数 rowParam 和 参数 format 不匹配',
                    LUDatabaseException::CODE_INVALID_PARAM,
                    null,
                    [
                        "row_param" => $rowParam,
                        "format" => $format
                    ]
                );
            }
        }
    }


    /**
     * 检查 BETWEEN 语句的值和占位符
     *
     * @param $param
     * @param $format
     */
    public static function between( $param, $format )
    {
        LUWPSQLParamValidator::format($format);

        if ( !is_array($param) || !is_array($format) ) {
            throw new LUDatabaseException(
                '参数 param 和 format 必须是数组',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "param"  => $param,
                    "format" => $format
                ]
            );
        }

        if ( count($param) !== 2 || count($format) !== 2 ) {
            throw new LUDatabaseException(
                '参数 param 和 format 必须是两个元素的数组',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "param"  => $param,
                    "format" => $format
                ]
            );
        }
    }

    /**
     * 检查 IN 和 NOT IN 语句的值和占位符
     *
     * @param $param :可以是数组，也可以是单个的值
     * @param $format :可以是数组，也可以是单个的占位符
     */
    public static function inAndNotin( $param, $format )
    {
        LUWPSQLParamValidator::format($format);

        if ( !is_array($param) || count($param)===0 ) {
            throw new LUDatabaseException(
                'IN 和 NOT IN 的参数必须是非空数组',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "param"  => $param,
                    "format" => $format
                ]
            );
        }

        if ( is_array($format) && count($format)!==count($param) ) {
            throw new LUDatabaseException(
                '如果 format 是占位符数组，那么其长度必须和参数 param 的长度一致',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "param"  => $param,
                    "format" => $format
                ]
            );
        }
    }

    /**
     * 检查 format 是否是 %s %d %f 中的一个
     *
     * @param array|string $format :可能是单个占位符，也可能是占位符数组
     * */
    public static function format( $format )
    {
        if ($format==='') {
            throw new LUDatabaseException(
                'format 参数不能为空',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "format" => $format
                ]
            );
        }

        if ( is_array($format) ) {
            foreach ( $format as $f ) {
                if ( !in_array($f, self::$allowFormat) ) {
                    throw new LUDatabaseException(
                        'format 参数必须是 %s %d %f 中的一个',
                        LUDatabaseException::CODE_INVALID_PARAM,
                        null,
                        [
                            "format" => $format,
                        ]
                    );
                }
            }
        }
        else {
            if ( !in_array($format, self::$allowFormat) ) {
                throw new LUDatabaseException(
                    'format 参数必须是 %s %d %f 中的一个',
                    LUDatabaseException::CODE_INVALID_PARAM,
                    null,
                    [
                        "format" => $format,
                    ]
                );
            }
        }
    }

    /**
     * 检查 format 是否是字符串，且是 %s %d %f 中的一个
     *
     * @param $format
     */
    public static function formatIsString( $format )
    {
        if( !is_string($format) ) {
            throw new LUDatabaseException(
                'format 必须是字符串',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "format" => $format
                ]
            );
        }

        if ( !in_array($format, self::$allowFormat) ) {
            throw new LUDatabaseException(
                'format 参数必须是 %s %d %f 中的一个',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "format" => $format,
                ]
            );
        }
    }



    /**
     * 验证 where 结构：meta / compare / format 必须同形
     *
     * @param array  $meta
     * @param array  $compare
     * @param array  $format
     * @throws LUDatabaseException
     */
    public static function where(array $meta, array $compare, array $format)
    {
        // 依赖原有 LUArray::arrays_shape_match 检查三维结构是否一致
        if (!(new LUArray())->arrays_shape_match($meta, $compare, $format)) {
            throw new LUDatabaseException(
                'where 结构不一致：meta/compare/format 三个数组形状必须相同',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "meta" => $meta,
                    "compare" => $compare,
                    "format" => $format
                ]
            );
        }
    }

    /**
     * 验证排序参数，并应用默认值
     *
     * @param array $orderSort  ['orderby'=>'...','sort'=>'ASC']
     * @return array 规范化后的排序数组
     * @throws LUDatabaseException
     */
    public static function orderSort( array $orderSort ): array
    {
        // 填充默认 orderby
        if (empty($orderSort['orderby'])) {
            $orderSort['orderby'] = 'id';
        }

        // 排序方向
        $allowed = ['ASC', 'DESC', 'asc', 'desc'];
        if (empty($orderSort['sort']) || !in_array($orderSort['sort'], $allowed, true)) {
            $orderSort['sort'] = 'DESC';
        }

        return $orderSort;
    }

    /**
     * 验证 limit 为正整数
     *
     * @param int $limit
     * @throws LUDatabaseException
     */
    public static function limit(int $limit)
    {
        if (!is_int($limit) || $limit < 0) {
            throw new LUDatabaseException(
                'limit 必须是非负整数',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "limit" => $limit
                ]
            );
        }
    }

    /**
     * 验证列名字符串（可多列逗号分隔，但不可为空）
     *
     * @param string $cols  如 'id,name' 或 '*'
     * @throws LUDatabaseException
     */
    public static function columns(string $cols)
    {
        if (!is_string($cols) || trim($cols) === '') {
            throw new LUDatabaseException(
                '列名字符串不能为空',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "cols" => $cols
                ]
            );
        }
    }

    /**
     * 验证单列名（不含空格，不能为空）
     *
     * @param string $col
     * @throws LUDatabaseException
     */
    public static function singleColumn(string $col)
    {
        if (!is_string($col) || trim($col) === '') {
            throw new LUDatabaseException(
                '列名不能为空字符串',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "col" => $col
                ]
            );
        }
        if (strpos($col, ' ') !== false) {
            throw new LUDatabaseException(
                '列名不得包含空格（应只传入单列名称）',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "col" => $col
                ]
            );
        }
    }

    /**
     * 验证输出格式
     *
     * @param string $output
     * @throws LUDatabaseException
     */
    public static function output(string $output)
    {
        $allowed = ['ARRAY_A', 'ARRAY_N', 'OBJECT'];
        if (!in_array($output, $allowed, true)) {
            throw new LUDatabaseException(
                'output 只能是 ARRAY_A, ARRAY_N 或 OBJECT',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "output" => $output
                ]
            );
        }
    }

    /**
     * 验证聚合函数名
     *
     * @param string $juhe
     * @throws LUDatabaseException
     */
    public static function aggregate( string $juhe )
    {
        $allowed = ['COUNT', 'SUM', 'AVG', 'MIN', 'MAX'];
        if (!in_array(strtoupper($juhe), $allowed, true)) {
            throw new LUDatabaseException(
                '聚合函数只能是 COUNT, SUM, AVG, MIN, MAX',
                LUDatabaseException::CODE_INVALID_PARAM,
                null,
                [
                    "juhe" => $juhe
                ]
            );
        }
    }


}