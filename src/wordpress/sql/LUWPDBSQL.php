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
use MAOSIJI\LU\EXCEPTION\LUDatabaseException;
use MAOSIJI\LU\LUArray;

if ( ! defined( 'ABSPATH' ) ) { die; }
abstract class LUWPDBSQL
{

    protected $tableNameNoPrefix;
    private static $instances = [];
    // 当前页面查询缓存，在一个页面里相同的查询条目只用查询一次即可，第2次直接返回缓存里的。
    private static $query_cache = [];
    private function __construct( string $tableNameNoPrefix )
    {
        $this->tableNameNoPrefix = $tableNameNoPrefix;
    }

    /**
     * 每个表名单例
     * @param string $tableNameNoPrefix
     * @return mixed|static|null
     */
    public static function getObj( string $tableNameNoPrefix )
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
     * 数据表操作
     **************************************************/

    /**
     * 创建数据表
     *
     * @param string $columnsSql 列定义
     * @return LUWPDDLResult
     */
    protected function createTable( string $columnsSql ): LUWPDDLResult
    {
        $ddl = new LUWPDDL();
        return $ddl->createTable($this->tableNameNoPrefix, $columnsSql);
    }

    /**
     * 更新数据表结构
     * @return array
     */
    private function updateTable()
    {

    }

    /**
     * 删除数据表
     * @return array
     */
    private function deleteTable()
    {

    }

    /**************************************************
     * 数据插入
     **************************************************/

    /**
     * 插入单行数据
     *
     * @param array  $param             关联数组，键为字段名，值为要插入的数据
     * @param array  $format            与 $data 对应的占位符数组，如 ['%s','%d']
     *
     * @return LUWPDMLResult
     * @throws LUDatabaseException
     */
    protected function insert( array $param, array $format ): LUWPDMLResult
    {
        $dml = new LUWPDML();
        return $dml->insert( $this->tableNameNoPrefix, $param, $format );
    }

    /**
     * 批量插入多行（支持事务）
     *
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
    protected function batchInsert( array $rowParam, array $format, bool $useTransaction=true ): int
    {
        $dml = new LUWPDML();
        return $dml->batchInsert( $this->tableNameNoPrefix, $rowParam, $format, $useTransaction );
    }

    /**************************************************
     * 数据表更新
     **************************************************/

    /**
     * 更新记录
     *
     * @param array  $param             要更新的数据关联数组
     * @param array  $paramFormat       数据对应的占位符
     * @param array  $where             WHERE 条件关联数组
     * @param array  $whereFormat       WHERE 条件占位符
     *
     * @return int 受影响行数（可能为 0）
     * @throws LUDatabaseException
     */
    protected function update( array $param, array $paramFormat, array $where, array $whereFormat ): int
    {
        $dml = new LUWPDML();
        return $dml->update( $this->tableNameNoPrefix, $param, $paramFormat, $where, $whereFormat );
    }

    /**************************************************
     * 数据删除
     **************************************************/

    /**
     * 删除记录
     *
     * @param array  $whereParam         WHERE 条件关联数组
     * @param array  $whereFormat       条件占位符
     *
     * @return int 受影响行数
     * @throws LUDatabaseException
     */
    protected function delete( array $whereParam, array $whereFormat ): int
    {
        $dml = new LUWPDML();
        return $dml->delete( $this->tableNameNoPrefix, $whereParam, $whereFormat );
    }

    /**
     * 【单条件】自定义删除查询（query、支持事务）
     *
     * 适用于没有共同 WHERE 条件但需批量删除的场景，如按 ID 列表删除。
     *
     * @param string $colName           :列名
     * @param string $operator          :运算符 =, >, <, >=, <=, <>, !=, LIKE, BETWEEN, IN, NOT IN 等
     * @param string|array $whereFormat              :字段值占位符
     * @param mixed $whereValue               :筛选值数组
     * @param bool $isTransaction       :是否启用事务
     *
     * @return int 删除行数
     * @throws LUDatabaseException
     */
    protected function queryDeleteSingleCondition( string $colName, string $operator, $whereFormat, $whereValue, bool $isTransaction=false ): int
    {
        $whereCondition = (new LUWPSQLWhereCondition())->and( $colName, $operator, $whereValue, $whereFormat );
        $result = $whereCondition->build();
        $sql = $result['sql'];
        $value = $result['value'];

        $dml = new LUWPDML();
        return $dml->queryDelete( $this->tableNameNoPrefix, $sql, $value, $isTransaction );
    }

    /**
     * 自定义删除查询（query、支持事务）需要自己用 LUWPSQLWhereCondition 拼接 sql
     *
     * 适用于没有共同 WHERE 条件但需批量删除的场景，如按 ID 列表删除。
     *
     * @param string $whereSql          WHERE 子句，如 "WHERE id IN (%d,%d)"
     * @param array  $whereValues       绑定值数组
     * @param bool   $isTransaction     是否启用事务
     *
     * @return int 删除行数
     * @throws LUDatabaseException
     */
    protected function queryDelete(string $whereSql, array $whereValues, bool $isTransaction = false ): int
    {
        $dml = new LUWPDML();
        return $dml->queryDelete( $this->tableNameNoPrefix, $whereSql, $whereValues, $isTransaction );
    }

    /**************************************************
     * 数据查询
     **************************************************/

    /**
     * 【单条件】查询一列数据
     *
     * @param string $colName :列名
     * @param string $operator :运算符 =, >, <, >=, <=, <>, !=, LIKE, BETWEEN, IN, NOT IN 等
     * @param string|array $whereFormat :字段值占位符
     * @param mixed $whereValue :筛选值数组
     * @param bool $isCache:是否缓存
     *
     * @return array
     * @throws LUDatabaseException
     * */
    protected function getColSingleCondition( string $colName, string $operator, $whereFormat, $whereValue, bool $isCache=true ): array
    {
        // 如果有缓存，则先查看缓存里的
        if ( $isCache ) {
            $cache_key = $this->generate_cache_key( __FUNCTION__, func_get_args() );
            if ( isset( self::$query_cache[ $cache_key ] ) ) {
                return self::$query_cache[ $cache_key ];
            }
        }

        $whereCondition = (new LUWPSQLWhereCondition())->and( $colName, $operator, $whereValue, $whereFormat );
        $whereBuild = $whereCondition->build();
        $sql = $whereBuild['sql'];
        $value = $whereBuild['value'];

        $dql = new LUWPDQL();
        $result = $dql->getCol( $this->tableNameNoPrefix, $sql, $value );

        if ( $isCache ) {
            self::$query_cache[ $cache_key ] = $result;
        }

        return $result;
    }

    /**
     * 查询单列数据
     *
     * @param string $colName           要查询的列名
     * @param string $whereSql          WHERE 子句（含占位符），如 " WHERE status = %s"
     * @param array  $whereValues       绑定值数组
     *
     * @return array 列值数组，无结果时返回空数组
     * @throws LUDatabaseException
     */
    protected function getCol( string $colName, string $whereSql = '', array $whereValues = [], bool $isCache=true ): array
    {
        // 如果有缓存，则先查看缓存里的
        if ( $isCache ) {
            $cache_key = $this->generate_cache_key( __FUNCTION__, func_get_args() );
            if ( isset( self::$query_cache[ $cache_key ] ) ) {
                return self::$query_cache[ $cache_key ];
            }
        }

        $dql = new LUWPDQL();
        $result = $dql->getCol( $this->tableNameNoPrefix, $colName, $whereSql, $whereValues );

        if ( $isCache ) {
            self::$query_cache[ $cache_key ] = $result;
        }

        return $result;
    }


    /**
     * 查询单个聚合值（COUNT、SUM、AVG 等）
     *
     * @param string $aggregateColName 聚合函数的列名，如 "COUNT(*)" 中的 *，或 "SUM(amount)"中的 amount
     * @param string $aggregateFunctionName   聚合函数，如 "COUNT(*)" 中的 COUNT 或 "SUM(amount)"中的 SUM
     * @param string $colName :列名
     * @param string $operator :运算符 =, >, <, >=, <=, <>, !=, LIKE, BETWEEN, IN, NOT IN 等
     * @param string|array $whereFormat :字段值占位符
     * @param mixed $whereValue :筛选值数组
     * @param bool $isCache :是否缓存
     *
     * @return string|null 查询结果（可能为 null）
     * @throws LUDatabaseException
     */
    protected function getVar( string $aggregateColName, string $aggregateFunctionName, string $colName, string $operator, $whereFormat, $whereValue, bool $isCache=true )
    {
        $aggregateFunction = $aggregateFunctionName.'('.$aggregateColName.')';
    }

    /**
     * 查询1列的值并进行聚合运算
     * @param $where                : (array) where的数组，必须包含元素 array('meta'=>array(),'compare=>array(),'format'=>array())
     *                                          meta : array(字段名称=>字段值)
     *                                          compare : array('=') 运算符数组（=、<、LIKE等）
     *                                          format : array('%d') 格式数组
     * @param $col                 : (string) 选择特定列或所有列， *，全部显示。只能写一个。
     * @param $juhe                : 默认 COUNT，可选
     *                                  COUNT（总行数）、
     *                                  SUM（该列字段值的总和）、
     *                                  AVG（该列字段值的平均值）、
     *                                  MIN（该列字段值的最小值）、
     *                                  MAX（该列字段值的最大值）
     * @param array $whereParam
     * @param array $whereFormat
     * @param array $whereCompare
     * @return array
     */
    protected function get_var( string $col='id', string $juhe='COUNT', array $whereParam=[], array $whereCompare=[], array $whereFormat=[], bool $isCache=false ): array
    {
        // 如果用缓存，则先查看缓存里的
        if ( $isCache ) {
            $cache_key = $this->generate_cache_key( __FUNCTION__, func_get_args() );
            if ( isset( self::$query_cache[ $cache_key ] ) ) {
                return self::$query_cache[ $cache_key ];
            }
        }

        // 检测 col 输入是否正确
        $colVerify = $this->verify('cols', $col);
        if ( $colVerify['code'] == 0 ) { return $colVerify; }
        // 检测 juhe 输入是否正确
        $juheVerify = $this->verify('juhe', $juhe);
        if ( $juheVerify['code'] == 0 ) { return $juheVerify; }

        $whereSQL = '';
        $whereValue = '';
        if ( !empty($whereParam) || !empty($whereFormat) || !empty($whereCompare) ) {
            // 检测 where 输入是否正确
            $whereVerify = $this->verify('where', array(
                'meta' => $whereParam,
                'format' => $whereFormat,
                'compare' => $whereCompare,
            ));
            if ($whereVerify['code'] == 0) {
                return $whereVerify;
            }

            $where = $this->generate_where_sql( $whereParam, $whereCompare, $whereFormat );
            $whereSQL = $where['sql'];
            $whereValue = $where['value'];
        }

        // 合并语句
        $jhCol = ' '.$juhe.'('.$col.') ';

        $dql = new LUWPDQL();
        $result = $dql->get_var( $this->tableName, $jhCol, $whereSQL, $whereValue );

        if ( $isCache ) {
            self::$query_cache[ $cache_key ] = $result;
        }

        return $result;
    }

    /**
     * 查询1行
     * @param $where                : (array) where的数组，必须包含元素 array('meta'=>array(),'compare=>array(),'format'=>array())
     *                                          meta : array(字段名称=>字段值)
     *                                          compare : array('=') 运算符数组（=、<、LIKE等）
     *                                          format : array('%d') 格式数组
     * @param $orderSort            : (string) 排序（如 ORDER BY id DESC ），必须包含元素 array('orderby'=>'id', 'sort'=>'DESC')
     *                                          orderby : string 字段名
     *                                          sort : 排序方式 ASC 正序 DESC 倒序
     * @param $cols                 : (string) 选择特定列或所有列，默认 *，全部显示。若写多个，如：id,name,age
     * @param array $whereFormat
     * @param array $whereCompare
     * @param array $whereParam
     * @param $output               : (string) OBJECT（对象）、ARRAY_A（关联数组）、ARRAY_N（数值数组）。默认 ARRAY_A。
     * @return array
     *
     * 查询 sex是女，age大于20 的行
     * array(
     *      'meta'=>array(
     *          'sex' => '女',
     *          'age'  => 20,
     *      ),
     *      'compare=>array(
     *          '=',
     *          '>',
     *      ),
     *      'format'=>array(
     *          ‘%s’,
     *          '%d',
     *      )
     * )
     */
    protected function get_row( string $cols='*', array $whereParam=[], array $whereCompare=[], array $whereFormat=[], bool $isCache=false, string $output='ARRAY_A', array $orderSort=array('orderby'=>'id', 'sort'=>'DESC') ): array
    {
        // 如果用缓存，则先查看缓存里的
        if ( $isCache ) {
            $cache_key = $this->generate_cache_key( __FUNCTION__, func_get_args() );
            if ( isset( self::$query_cache[ $cache_key ] ) ) {
                return self::$query_cache[ $cache_key ];
            }
        }

        // 检测 ordersort 输入是否正确
        $orderSortVerify = $this->verify('ordersort', $orderSort);
        if ( $orderSortVerify['code'] == 0 ) { return $orderSortVerify; }
        $orderSort = $orderSortVerify['data'];
        // 检测 cols 输入是否正确
        $colsVerify = $this->verify('cols', $cols);
        if ( $colsVerify['code'] == 0 ) { return $colsVerify; }
        // 检测 cols 输入是否正确
        $outputVerify = $this->verify('output', $output);
        if ( $outputVerify['code'] == 0 ) { return $outputVerify; }

        $whereSQL = '';
        $whereValue = '';
        if ( !empty($whereParam) || !empty($whereFormat) || !empty($whereCompare) ) {
            // 检测 where 输入是否正确
            $whereVerify = $this->verify('where', array(
                'meta' => $whereParam,
                'format' => $whereFormat,
                'compare' => $whereCompare,
            ));
            if ($whereVerify['code'] == 0) {
                return $whereVerify;
            }

            $where = $this->generate_where_sql( $whereParam, $whereCompare, $whereFormat );
            $whereSQL = $where['sql'];
            $whereValue = $where['value'];
        }

        // 编写 orderby 语句
        $orderSortSQL = ' ORDER BY '.$orderSort['orderby'].' '.$orderSort['sort'].' ';
        // 合并语句
        $sql = $whereSQL.$orderSortSQL;

        $dql = new LUWPDQL();
        $result = $dql->get_row( $this->tableName, $cols, $sql, $whereValue, $output );

        if ( $isCache ) {
            self::$query_cache[ $cache_key ] = $result;
        }

        return $result;
    }

    /**
     * 查询多行
     * @param string $cols          : 选择特定列或所有列，默认 *，全部显示。若写多个，如：id,name,age
     * @param array $whereParam     : where数组，如 array(字段名称=>字段值)
     * @param array $whereCompare   : where符号数组，如 array('=') 运算符数组（=、<、LIKE等）
     * @param array $whereFormat    : where格式数组，如 array('%d')
     * @param bool $isCache         : 是否缓存，只在当前页面缓存，默认 false。
     * @param $limit                : (int) 查询数量，不可使用 OFFSET 属性，如要分页可根据上一次查询的id来操作。默认 0，即无限制
     * @param $output               : (string) OBJECT（对象）、ARRAY_A（关联数组）、ARRAY_N（数值数组）。默认 ARRAY_A。
     * @param $orderSort            : (array) 排序（如 ORDER BY id DESC ），必须包含元素 array('orderby'=>'id', 'sort'=>'DESC')
     *                                          orderby : string 字段名
     *                                          sort : 排序方式 ASC 正序 DESC 倒序
     * @return array
     */
    protected function get_results( string $cols='*', array $whereParam=[], array $whereCompare=[], array $whereFormat=[], bool $isCache=false, int $limit=0, string $output='ARRAY_A', array $orderSort=array('orderby'=>'id', 'sort'=>'DESC') ): array
    {
        // 如果用缓存，则先查看缓存里的
        if ( $isCache ) {
            $cache_key = $this->generate_cache_key( __FUNCTION__, func_get_args() );
            if ( isset( self::$query_cache[ $cache_key ] ) ) {
                return self::$query_cache[ $cache_key ];
            }
        }

        // 检测 ordersort 输入是否正确
        $orderSortVerify = $this->verify('ordersort', $orderSort);
        if ( $orderSortVerify['code'] == 0 ) { return $orderSortVerify; }
        // 检测 limit 输入是否正确
        $limitVerify = $this->verify('limit', $limit);
        if ( $limitVerify['code'] == 0 ) { return $limitVerify; }
        // 检测 cols 输入是否正确
        $colsVerify = $this->verify('cols', $cols);
        if ( $colsVerify['code'] == 0 ) { return $colsVerify; }
        // 检测 cols 输入是否正确
        $outputVerify = $this->verify('output', $output);
        if ( $outputVerify['code'] == 0 ) { return $outputVerify; }

        $orderSort = $orderSortVerify['data'];

        $whereSQL = '';
        $whereValue = [];
        if ( !empty($whereParam) || !empty($whereFormat) || !empty($whereCompare) ) {
            // 检测 where 输入是否正确
            $whereVerify = $this->verify('where', array(
                'meta' => $whereParam,
                'format' => $whereFormat,
                'compare' => $whereCompare,
            ));
            if ($whereVerify['code'] == 0) {
                return $whereVerify;
            }

            $where = $this->generate_where_sql( $whereParam, $whereCompare, $whereFormat );
            $whereSQL = $where['sql'];
            $whereValue[] = $where['value'];
        }

        // 编写 orderby 语句
        $orderSortSQL = ' ORDER BY '.$orderSort['orderby'].' '.$orderSort['sort'].' ';

        // 编写 limit 语句
        $limitSQL = '';
        $limitValue = [];
        if ( $limit>0 ) {
            $limitSQL = ' LIMIT %d ';
            $limitValue[] = $limit;
        }

        // 合并语句
        $sql = $whereSQL.$orderSortSQL.$limitSQL;
        $value = array_merge( $whereValue, $limitValue );

//            return array('code'=>0, 'msg'=>'测试', 'data2'=>$sql, 'data3'=>$orderSort);

        $dql = new LUWPDQL();
        $result = $dql->get_results( $this->tableName, $cols, $sql, $value, $output );

        if ( $isCache ) {
            self::$query_cache[ $cache_key ] = $result;
        }

        return $result;
    }

    /**
     * 组装 where 语句
     * @param array $whereParam : where数组，如 array(字段名称=>字段值)
     * @param array $whereCompare : where符号数组，如 array('=') 运算符数组（=、<、LIKE等）
     * @param array $whereFormat : where格式数组，如 array('%d')
     * @return array
     */
    private function generate_where_sql( array $whereParam=[], array $whereCompare=[], array $whereFormat=[] ): array
    {
        /*
        array(
            // AND
            'post_status'   => 'publish',
            // OR
            'or'            => array(
                'post_title'    => '标题',
                'post_contnet'  => '内容',
                'post_id'       => array(
                    1,2,3,4,5,6
                ),
            ),
            // AND
            'post_parent'   => 1,
            // AND
            'create_time'   => array(
                2025-01-01 00:00:00,
                2025-01-01 23:59:59
            )
        );

        array(
            '=',
            array( 'LIKE', 'LIKE', array( '=','=','=','=','=','=' ) ),
            array( '=' ),
            array( '>=', '<=' )
        )

        array(
            '%s',
            array( '%s','%s', array( '%d','%d','%d','%d','%d','%d' ) ),
            '%d',
            array( '%s', '%s' )
        )
         * */
        $whereValue = array();
        $whereSQL   = '';

        $whereSQL .= ' WHERE ';
        $o = 0;
        foreach ( $whereParam as $key => $value ) {

            // key为or时，才组装成or语句
            if ( strtolower($key)==='or' && is_array($value) ) {
                $p = 0;
                foreach ( $value as $pk=>$pv ) {
                    if ( $p===0 ) {
                        $whereSQL .= ($o!==0?' AND ':'').' ( ';
                    }

                    // 如果不是数组
                    if ( !is_array($pv) ) {
                        $whereSQL .= ($p!==0 ? ' OR ':' ').$pk.$whereCompare[$o][$p].$whereFormat[$o][$p];
                        $whereValue[] = strtolower($whereCompare[$o][$p])==='like' ? $this->_like_sql($pv) : $pv;
                    }

                    // 如果是三级数组
                    if ( is_array($pv) ) {
                        $q = 0;
                        foreach ( $pv as $qk=>$qv ) {
                            $whereSQL .= ($p!==0 ? ' OR ':' ').$pk.$whereCompare[$o][$p][$q].$whereFormat[$o][$p][$q];
                            $whereValue[] = strtolower($whereCompare[$o][$p][$q])==='like' ? $this->_like_sql($qv) : $qv;

                            $q++;
                        }
                    }

                    if ( $p===count($value)-1 ) {
                        $whereSQL .= ' )  ';
                    }

                    $p++;
                }
            }
            // 都按 and 语句
            else {

                // 如果不是数组
                if ( !is_array($value) ) {
                    $whereSQL .= ($o!==0?' AND ':'') . ' '.$key.$whereCompare[$o].$whereFormat[$o].' ';
                    $whereValue[] = strtolower($whereCompare[$o])==='like' ? $this->_like_sql($value) : $value;
                }

                // 如果是数组
                if ( is_array($value) ) {
                    $p = 0;
                    foreach ( $value as $pv ) {
                        $whereSQL .= ( $o!==0||$p!==0 ? ' AND ':'' ).$key.$whereCompare[$o][$p].$whereFormat[$o][$p].' ';
                        $whereValue[] = strtolower($whereCompare[$o][$p])==='like' ? $this->_like_sql($pv) : $pv;

                        $p++;
                    }
                }

            }

            $o++;
        }

        return array('sql'=>$whereSQL, 'value'=>$whereValue);
    }
    private function _like_sql( $value )
    {
        global $wpdb;
        return '%'.$wpdb->esc_like($value).'%';
    }



    /**************************************************
     * 自定义
     **************************************************/

    /**
     * 根据编号查询是否有记录，在主表中为每条的编号，在meta表中为主表的编号
     * @param $no : 编号
     * @return array
     */
    protected function get_no( string $no ): array
    {
        if ( empty($no) ) {
            return array('code'=>0, 'msg'=>'$no 为空', 'data'=>$no);
        }

        $where = array(
            'meta'  => array(
                'no'    => $no,
            ),
            'compare'   => array(
                '='
            ),
            'format'    => array(
                '%s'
            )
        );

        return $this->get_row( 'no', $where );
    }

    /**
     * 获取总条目数
     * @return array
     */
    protected function get_total_num()
    {
        return $this->get_var();
    }




    /**************************************************
     * 公共方法
     **************************************************/

    /**
     * 数据验证
     * @param $args_name    : 验证的名称
     * @param $args         : 验证的数组
     * @return array
     */
    private function verify( string $args_name, $args )
    {
//            if ( !is_array($args) ) { return array('code'=>0, 'msg'=>'参数 '.$args_name.' 必须是数组', 'data'=>$args); }

        switch( $args_name ) {

            case 'where':

                if ( !(new LUArray())->arrays_shape_match( $args['meta'], $args['compare'], $args['format'] ) ) {
                    return (new LUSend())->send_array( 0, 'whereParam / whereCompar / whereFormat 三个参数的结构不一致', $args );
                }

                break;

            case 'ordersort':

                if ( !isset($args['orderby']) || empty($args['orderby']) ) { $args['orderby'] = 'id'; }
                if ( !isset($args['sort']) || empty($args['sort']) || !in_array($args['sort'], array('ASC', 'DESC', 'asc', 'desc')) ) { $args['sort'] = 'DESC'; }

                break;

            case 'param':

                if ( !isset($args['param']) || !isset($args['format']) ) {
                    return (new LUSend())->send_array( 0, '参数 '.$args_name.' 数组缺少元素', $args );
                }

                if ( !is_array($args['param']) || !is_array($args['format']) ) {
                    return (new LUSend())->send_array( 0, '参数 '.$args_name.' 数组元素不是数组', $args );
                }

                if ( empty($args['param']) || empty($args['format']) ) {
                    return (new LUSend())->send_array( 0, '参数 '.$args_name.' 数组元素不可为空', $args );
                }

                if ( count($args['param']) !== count($args['format']) ) {
                    return (new LUSend())->send_array( 0, '参数 '.$args_name.' 数组元素的长度不一致', $args );
                }

                break;

            case 'limit':

                if ( !is_int($args) ) {
                    return (new LUSend())->send_array( 0, '参数 '.$args_name.' 不是正整数', $args );
                }

                break;

            case 'cols':

                if ( !is_string($args) ) {
                    return (new LUSend())->send_array( 0, '参数 '.$args_name.' 不是字符串', $args );
                }

                break;

            case 'col':

                if ( !is_string($args) ) {
                    return (new LUSend())->send_array( 0, '参数 '.$args_name.' 不是字符串', $args );
                }

                if ( strpos($args, ' ') !== false ) {
                    return (new LUSend())->send_array( 0, '参数 '.$args_name.' 含有空格，应该传入一列的名称，不能传入多列名称', $args );
                }

                break;

            case 'output':

                if ( !in_array($args, array('ARRAY_A', 'ARRAY_N', 'OBJECT')) ) {
                    return (new LUSend())->send_array( 0, '参数 '.$args_name.' 只能是 ARRAY_A、ARRAY_N、OBJECT', $args );
                }

                break;

            case 'juhe':

                if ( !in_array($args, array('COUNT', 'SUM', 'AVG', 'MIN', 'MAX', 'count', 'sum', 'avg', 'min', 'max')) ) {
                    return (new LUSend())->send_array( 0, '参数 '.$args_name.' 只能是 COUNT、SUM、AVG、MIN、MAX', $args );
                }

                break;

        }

        return array('code'=>1, 'msg'=>'检测通过', 'data'=>$args);
    }

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




}
