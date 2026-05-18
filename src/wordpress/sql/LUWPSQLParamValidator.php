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
use MAOSIJI\LU\LUResult;

class LUWPSQLParamValidator
{
    /**
     * 检测表名是否有效
     *
     * @param string $tableName
     * @return LUResult
     */
    public static function tableName( string $tableName ): LUResult
    {
        if ( !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $tableName) ) {
            return LUResult::error( 1000, '表名只能由字母、数字和下划线组成，且不能以数字开头', [
                'table_name'  => $tableName
            ]);
        }

        return LUResult::success([
            'table_name'    => $tableName
        ], '表名 检测通过');
    }

    public static function columnName( string $columnName ): LUResult
    {
        if ( ! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $columnName) ) {
            return LUResult::error(1001, '无效的列名', ['column_name' => $columnName]);
        }

        return LUResult::success([
            'column_name'    => $columnName
        ], '列名 检测通过');
    }

    private static $allowFormat = ['%s', '%d', '%f'];

    public static function paramFormat( $param, $format ): LUResult
    {
        $validFormat = LUWPSQLParamValidator::format($format);
        if ( $validFormat->isError() ) {
            return $validFormat;
        }

        if ( !is_array($param) || !is_array($format) ) {
            return LUResult::error( 1000, '参数 param 和 format 必须是数组', [
                "param"  => $param,
                "format" => $format
            ]);
        }

        if ( count($param)===0 || count($format)===0 ) {
            return LUResult::error( 1000, '值数组或占位符数组不能为空数组', [
                "param"  => $param,
                "format" => $format
            ]);
        }

        if (count($param) !== count($format)) {
            return LUResult::error( 1000, '数据字段数量与占位符数量不一致', [
                "param" => $param,
                "format" => $format
            ]);
        }

        return LUResult::success([
            "param" => $param,
            "format" => $format
        ], '检测通过');
    }

    public static function rowParamFormat( $rowParam, $format ): LUResult
    {
        $validFormat = LUWPSQLParamValidator::format($format);
        if ( $validFormat->isError() ) {
            return $validFormat;
        }

        if ( !(new LUArray())->isTwoDimensionalArray($rowParam) ) {
            return LUResult::error( 1000, 'rowParam 必须是二维数组', [
                "row_param" => $rowParam,
                "format" => $format
            ]);
        }

        if ( !is_array($format) ) {
            return LUResult::error( 1000, '参数 format 必须是数组', [
                "row_param" => $rowParam,
                "format" => $format
            ]);
        }

        if ( count($rowParam)===0 || count($format)===0 ) {
            return LUResult::error( 1000, '值数组或占位符数组不能为空数组', [
                "row_param" => $rowParam,
                "format" => $format
            ]);
        }

        foreach ( $rowParam as $rp ) {
            if ( count($rp) !== count($format) ) {
                return LUResult::error( 1000, '参数 rowParam 和 参数 format 不匹配', [
                    "row_param" => $rowParam,
                    "format" => $format
                ]);
            }
        }

        return LUResult::success([
            "row_param" => $rowParam,
            "format" => $format
        ], '检测通过');
    }

    /**
     * 检查 BETWEEN 语句的值和占位符
     *
     * @param $param
     * @param $format
     * @return LUResult
     */
    public static function between( $param, $format ): LUResult
    {
        $validFormat = LUWPSQLParamValidator::format($format);
        if ( $validFormat->isError() ) {
            return $validFormat;
        }

        if ( !is_array($param) || !is_array($format) ) {
            return LUResult::error( 1000, '参数 param 和 format 必须是数组', [
                "param"  => $param,
                "format" => $format
            ]);
        }

        if ( count($param) !== 2 || count($format) !== 2 ) {
            return LUResult::error( 1000, '参数 param 和 format 必须是两个元素的数组', [
                "param"  => $param,
                "format" => $format
            ]);
        }

        return LUResult::success([
            "param"  => $param,
            "format" => $format
        ], '检测通过');
    }

    /**
     * 检查 IN 和 NOT IN 语句的值和占位符
     *
     * @param $param :可以是数组，也可以是单个的值
     * @param $format :可以是数组，也可以是单个的占位符
     * @return LUResult
     */
    public static function inAndNotin( $param, $format ): LUResult
    {
        $validFormat = LUWPSQLParamValidator::format($format);
        if ( $validFormat->isError() ) {
            return $validFormat;
        }

        if ( !is_array($param) || count($param)===0 ) {
            return LUResult::error( 1000, 'IN 和 NOT IN 的参数必须是非空数组', [
                "param"  => $param,
                "format" => $format
            ]);
        }

        if ( is_array($format) && count($format)!==count($param) ) {
            return LUResult::error( 1000, '如果 format 是占位符数组，那么其长度必须和参数 param 的长度一致', [
                "param"  => $param,
                "format" => $format
            ]);
        }

        return LUResult::success([
            "param"  => $param,
            "format" => $format
        ], '检测通过');
    }

    /**
     * 检查 format 是否是 %s %d %f 中的一个
     *
     * @param array|string $format :可能是单个占位符，也可能是占位符数组
     * @return LUResult
     * */
    public static function format( $format ): LUResult
    {
        if ( !is_array($format) ) {

            if ($format==='') {
                return LUResult::error( 1000, 'format 参数不能为空', [
                    'format'    => $format
                ]);
            }

            if ( !in_array($format, self::$allowFormat) ) {
                return LUResult::error( 1000, 'format 参数必须是 %s %d %f 中的一个', [
                    'format'    => $format
                ]);
            }

        }

        if ( is_array($format) ) {
            foreach ( $format as $f ) {
                self::format( $f );
            }
        }

        return LUResult::success([
            'format'    => $format
        ], '检测通过');
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