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
 * whereMeta            : string 指 表中的字段名
 * whereValue           : array  指 字段名对应的值，可能是1个，也可能是多个
 * whereParam           : array  指 array('字段名‘=>'字段值'...)
 * whereCompare         : string|array 指 符号。> < = IN LIKE ...
 * whereFormat          : string|array 指 格式化
 */

namespace MAOSIJI\LU\WP\SQL;
use MAOSIJI\LU\LUSend;

if ( ! defined( 'ABSPATH' ) ) { die; }
if ( !class_exists('LUWPDBSQL') ) {
    abstract class LUWPDBSQL {
        protected $tableName;
        private static $instances = [];
        // 当前页面查询缓存，在一个页面里相同的查询条目只用查询一次即可，第2次直接返回缓存里的。
        private static $query_cache = [];
        private function __construct( string $tableName )
        {
            $this->tableName = $tableName;
        }
        public static function getObj( string $tableName )
        {
            // 使用 get_called_class() 确保每个子类都有自己的实例
            $calledClass = get_called_class();

            if (!isset(self::$instances[$calledClass])) {
                self::$instances[$calledClass] = null;
            }

            if (is_null(self::$instances[$calledClass])) {
                self::$instances[$calledClass] = new static($tableName);
            }

            return self::$instances[$calledClass];
        }
        private function __clone()
        {
        }

        /**************************************************
         * 数据表操作
         **************************************************/

        /**
         * 创建数据表结构
         * @param $sql :创建表的语句
         * @return array|void
         */
        protected function create_table( string $sql )
        {
            if ( empty($sql) ) {
                return (new LUSend())->send_array(0, 'SQL语句是空的', $sql );
            }

            $ddl = new LUWPDDL();
            return $ddl->createTable( $this->tableName, $sql );
        }

        /**
         * 更新数据表结构
         * @return array
         */
        private function update_table()
        {

        }

        /**
         * 删除数据表
         * @return array
         */
        private function delete_table()
        {

        }

        /**************************************************
         * 数据表查询
         **************************************************/

        /**
         * 查询1列
         *
         * @param string $col : 选择特定列，只能写一个。
         * @param array $whereParam
         * @param array $whereCompare
         * @param array $whereFormat
         * @return array
         */
        protected function get_col( string $col='id', array $whereParam=[], array $whereCompare=[], array $whereFormat=[], bool $isCache=false ): array
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

            $whereValue = array();
            // 编写 where 语句
            $whereSQL = '';

            if ( !empty($whereParam) || !empty($whereFormat) || !empty($whereCompare) ) {

                // 检测 where 输入是否正确
                $whereVerify = $this->verify('where', array(
                    'meta' => $whereParam,
                    'format' => $whereFormat,
                    'compare' => $whereCompare,
                ));
                if ( $whereVerify['code'] == 0 ) { return $whereVerify; }

                $whereSQL .= ' WHERE ';
                $wn = 0;
                foreach ( $whereParam as $key => $value ) {
                    if ( is_array($value) ) {
                        $n = 0;
                        foreach ( $value as $v ) {
                            $whereSQL .= ($wn!==0||$n!==0 ? ' AND ':'') . ' '.$key.$whereCompare[$wn][$n].$whereFormat[$wn][$n] . ' ';
                            $whereValue[] = $v;
                            $n++;
                        }
                    } else {
                        $whereSQL .= ($wn!==0?' AND ':'') . ' '.$key.$whereCompare[$wn].$whereFormat[$wn].' ';
                        $whereValue[] = $value;
                    }

                    $wn++;
                }
            }

            $dql = new LUWPDQL();
            $result = $dql->get_col( $this->tableName, $col, $whereSQL, $whereValue );

            if ( $isCache ) {
                self::$query_cache[ $cache_key ] = $result;
            }

            return $result;
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
        protected function get_var( string $col='id', string $juhe='COUNT', array $whereParam=[], array $whereCompare=[], array $whereFormat=[], $isCache=false ): array
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

            $whereValue = array();
            // 编写 where 语句
            $whereSQL = '';
            if ( !empty($whereParam) || !empty($whereFormat) || !empty($whereCompare) ) {

                // 检测 where 输入是否正确
                $whereVerify = $this->verify('where', array(
                    'meta' => $whereParam,
                    'format' => $whereFormat,
                    'compare' => $whereCompare,
                ));
                if ( $whereVerify['code'] == 0 ) { return $whereVerify; }

                $whereSQL .= ' WHERE ';
                $wn = 0;
                foreach ( $whereParam as $key => $value ) {
                    if ( is_array($value) ) {
                        $n = 0;
                        foreach ( $value as $v ) {
                            $whereSQL .= ($wn!==0||$n!==0 ? ' AND ':'') . ' '.$key.$whereCompare[$wn][$n].$whereFormat[$wn][$n] . ' ';
                            $whereValue[] = $v;
                            $n++;
                        }
                    } else {
                        $whereSQL .= ($wn!==0?' AND ':'') . ' '.$key.$whereCompare[$wn].$whereFormat[$wn].' ';
                        $whereValue[] = $value;
                    }

                    $wn++;
                }
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
        protected function get_row( string $cols='*', array $whereParam=[], array $whereCompare=[], array $whereFormat=[], string $output='ARRAY_A', array $orderSort=array('orderby'=>'id', 'sort'=>'DESC'), $isCache=false ): array
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

            $whereValue = array();
            // 编写 where 语句
            $whereSQL = '';
            if ( !empty($whereParam) || !empty($whereCompare) || !empty($whereFormat) ) {

                // 检测 where 输入是否正确
                $whereVerify = $this->verify('where', array(
                    'meta' => $whereParam,
                    'format' => $whereFormat,
                    'compare' => $whereCompare,
                ));
                if ( $whereVerify['code'] == 0 ) { return $whereVerify; }

                $whereSQL .= ' WHERE ';
                $wn = 0;
                foreach ( $whereParam as $key => $value ) {
                    if ( is_array($value) ) {
                        $n = 0;
                        foreach ( $value as $v ) {
                            $whereSQL .= ($wn!==0||$n!==0 ? ' AND ':'') . ' '.$key.$whereCompare[$wn][$n].$whereFormat[$wn][$n] . ' ';
                            $whereValue[] = $v;
                            $n++;
                        }
                    } else {
                        $whereSQL .= ($wn!==0?' AND ':'') . ' '.$key.$whereCompare[$wn].$whereFormat[$wn].' ';
                        $whereValue[] = $value;
                    }

                    $wn++;
                }
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
         * @param $where                : (array) where的数组，必须包含元素 array('meta'=>'','compare=>'','format'=>'')
         *                                          meta : array(字段名称=>字段值)
         *                                          compare : array('=') 运算符数组（=、<、LIKE等）
         *                                          format : array('%d') 格式数组
         * @param $orderSort            : (array) 排序（如 ORDER BY id DESC ），必须包含元素 array('orderby'=>'id', 'sort'=>'DESC')
         *                                          orderby : string 字段名
         *                                          sort : 排序方式 ASC 正序 DESC 倒序
         * @param $limit                : (int) 查询数量，不可使用 OFFSET 属性，如要分页可根据上一次查询的id来操作。默认 0，即无限制
         * @param $col                  : (string) 选择特定列或所有列，默认 *，全部显示。若写多个，如：id,name,age
         * @param $output               : (string) OBJECT（对象）、ARRAY_A（关联数组）、ARRAY_N（数值数组）。默认 ARRAY_A。
         * @return array
         */
        protected function get_results( string $cols='*', array $whereParam=[], array $whereCompare=[], array $whereFormat=[], int $limit=0, string $output='ARRAY_A', array $orderSort=array('orderby'=>'id', 'sort'=>'DESC'), $isCache=false ): array
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

            $whereValue = array();
            // 编写 where 语句
            $whereSQL = '';
            if ( !empty($whereParam) || !empty($whereFormat) || !empty($whereCompare) ) {
                // 检测 where 输入是否正确
                $whereVerify = $this->verify('where', array(
                    'meta' => $whereParam,
                    'format' => $whereFormat,
                    'compare' => $whereCompare,
                ));
                if ( $whereVerify['code'] == 0 ) { return $whereVerify; }

                $whereSQL .= ' WHERE ';
                $wn = 0;
                foreach ( $whereParam as $key => $value ) {
                    if ( is_array($value) ) {
                        $n = 0;
                        foreach ( $value as $v ) {
                            $whereSQL .= ($wn!==0||$n!==0 ? ' AND ':'') . ' '.$key.$whereCompare[$wn][$n].$whereFormat[$wn][$n] . ' ';
                            $whereValue[] = $v;
                            $n++;
                        }
                    } else {
                        $whereSQL .= ($wn!==0?' AND ':'') . ' '.$key.$whereCompare[$wn].$whereFormat[$wn].' ';
                        $whereValue[] = $value;
                    }

                    $wn++;
                }
            }

            // 编写 orderby 语句
            $orderSortSQL = ' ORDER BY '.$orderSort['orderby'].' '.$orderSort['sort'].' ';

            // 编写 limit 语句
            $limitSQL = '';
            if ( $limit>0 ) {
                $limitSQL = ' LIMIT %d ';
                $params[] = $limit;
            }

            // 合并语句
            $sql = $whereSQL.$orderSortSQL.$limitSQL;

//            return array('code'=>0, 'msg'=>'测试', 'data2'=>$sql, 'data3'=>$orderSort);

            $dql = new LUWPDQL();
            $result = $dql->get_results( $this->tableName, $cols, $sql, $whereValue, $output );

            if ( $isCache ) {
                self::$query_cache[ $cache_key ] = $result;
            }

            return $result;
        }

        /**************************************************
         * 数据表插入
         **************************************************/

        /**
         * 插入1行
         * @param $param   : 插入的数据数组
         * @param $format  : 插入的数据数组对应的格式数组
         * @return array
         */
        protected function insert( array $param, array $format ): array
        {
            // 检测  输入是否正确
            $paramVerify = $this->verify('param', array('param'=>$param, 'format'=>$format));
            if ( $paramVerify['code'] == 0 ) { return $paramVerify; }

            $dml = new LUWPDML();
            return $dml->insert( $this->tableName, $param, $format );
        }

        /**
         * 插入多行【未测试】
         * @param array $data           :要插入的数据（二维数组）
         * @param array $columns        :列名数组
         * @param array $formats        :数据格式（如 '%s', '%d'）数组
         * @return array
         */
        private function batchInsert( array $data, array $columns, array $formats ): array
        {
            $format = [];
            $insert_data = [];

            foreach ($data as $row) {
                // 生成占位符并展平数据
                $format[] = '(' . implode(', ', $formats) . ')';
                $insert_data = array_merge($insert_data, array_values($row));
            }

            // 构建 SQL 查询
            $columnSQL    = '`' . implode('`, `', $columns) . '`'; // 列名
            $formatSQL  = implode(', ', $format);

//            return array('code'=>0, 'msg'=>$formatSQL, 'data'=>$columnSQL, 'd'=>$insert_data);

            $dml = new LUWPDML();
            return $dml->batchInsert( $this->tableName, $insert_data, $columnSQL, $formatSQL );
        }

        /**************************************************
         * 数据表更新
         **************************************************/

        /**
         * 更新1行
         * @param array $param           : 更新的数据数组
         * @param array $format          : 更新的数据数组对应的格式数组
         * @param array $whereParam      : Where条件数据数组
         * @param array $wheresFormat    : Where条件数据数组对应的格式数组
         *
         * $params              array('no' => $no,'status' => $status,)
         * $formats             array('%s', '%d')
         * $wheres              array( 'no' => $no, 'status' => $status, )
         * $wheresFormat        array('%s', '%d')
         *
         * @return array
         */
        protected function update( array $param, array $format, array $whereParam, array $wheresFormat ): array
        {
            // 检测  输入是否正确
            $param1 = $this->verify('param', array('param'=>$param, 'format'=>$format));
            if ( $param1['code'] == 0 ) { return $param1; }

            // 检测  输入是否正确
            $param2 = $this->verify('param', array('param'=>$whereParam, 'format'=>$wheresFormat));
            if ( $param2['code'] == 0 ) { return $param2; }

            $dml = new LUWPDML();
            return $dml->update( $this->tableName, $param, $format, $whereParam, $wheresFormat );
        }

        /**************************************************
         * 数据表删除
         **************************************************/

        /**
         * 删除符合条件的行
         * @param array $whereParam
         * @param array $wheresFormat
         * @return array
         */
        protected function delete( array $whereParam, array $wheresFormat ): array
        {
            // 检测  输入是否正确
            $paramVerify = $this->verify('param', array('param'=>$whereParam, 'format'=>$wheresFormat));
            if ( $paramVerify['code'] == 0 ) { return $paramVerify; }

            $dml = new LUWPDML();
            return $dml->delete( $this->tableName, $whereParam, $wheresFormat );
        }

        /**
         * （支持事务）删除1个或多个条目，删除多个条目时，相互之间没有共同点，比如按id删除。
         *
         * DELETE FROM my_custom_table WHERE id IN (%d,%d,%d)
         *
         * @param string $col :where条件，列的名称，只能有一个。如 id
         * @param string $whereCompare :where条件的条件，> < = IN LIKE
         * @param string $whereFormat :where条件的格式
         * @param array $whereValue :条件的值的数组
         * @param bool $isDeleteAll :当 $whereCompare、$whereFormat、$whereValue 都为 0 时，则会删除全部行。为了防止误删，则此参数必须为 true 时才可以删除全部。
         * @param bool $isTransaction :是否开启事务
         * @return array
         */
        protected function query_delete( string $col, string $whereCompare, string $whereFormat, array $whereValue, bool $isDeleteAll=false, bool $isTransaction=false ): array
        {
            // 检测  输入是否正确
            $colVerify = $this->verify( 'col', $col );
            if ( $colVerify['code'] == 0 ) { return $colVerify; }

            $whereSQL = " WHERE {$col} {$whereCompare} ({$whereFormat})";

            if ( empty($whereCompare) && empty($whereFormat) && empty($whereValue) && !is_array($whereValue) ) {

                if ( !$isDeleteAll ) {
                    return (new LUSend())->send_array( 0, '若想删除该表里的所有行，则参数 isDeleteAll 必须为 true' );
                }

                $whereSQL = "";
            }

            $dml = new LUWPDML();
            return $dml->query_delete( $this->tableName, $whereSQL, $whereValue, $isTransaction );
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

                    if ( !empty($args) ) {

                        if ( !isset($args['meta']) || !isset($args['compare']) || !isset($args['format']) ) {
                            return array('code'=>0, 'msg'=>'参数 '.$args_name.' 数组缺少元素', 'data'=>$args);
                        }

                        if ( !is_array($args['meta']) || !is_array($args['compare']) || !is_array($args['format']) ) {
                            return array('code'=>0, 'msg'=>'参数 '.$args_name.' 数组元素不是数组', 'data'=>$args);
                        }

                        // 可以都为空，但不能有的为空，有的不为空
//                        if ( empty($args['meta']) || empty($args['compare']) || empty($args['format']) ) {
//                            return array('code'=>0, 'msg'=>'参数 '.$args_name.' 数组元素不可为空', 'data'=>$args);
//                        }

                        if ( count($args['meta']) !== count($args['compare']) &&
                            count($args['compare']) !== count($args['format']) &&
                            count($args['format']) !== count($args['meta'])
                        ) {
                            return array('code'=>0, 'msg'=>'参数 '.$args_name.' 数组元素的长度不一致', 'data'=>$args);
                        }

                    }

                    break;

                case 'ordersort':

                    if ( !isset($args['orderby']) || empty($args['orderby']) ) { $args['orderby'] = 'id'; }
                    if ( !isset($args['sort']) || empty($args['sort']) || !in_array($args['sort'], array('ASC', 'DESC', 'asc', 'desc')) ) { $args['sort'] = 'DESC'; }

                    break;

                case 'param':

                    if ( !isset($args['param']) || !isset($args['format']) ) {
                        return array('code'=>0, 'msg'=>'参数 '.$args_name.' 数组缺少元素', 'data'=>$args);
                    }

                    if ( !is_array($args['param']) || !is_array($args['format']) ) {
                        return array('code'=>0, 'msg'=>'参数 '.$args_name.' 数组元素不是数组', 'data'=>$args);
                    }

                    if ( empty($args['param']) || empty($args['format']) ) {
                        return array('code'=>0, 'msg'=>'参数 '.$args_name.' 数组元素不可为空', 'data'=>$args);
                    }

                    if ( count($args['param']) !== count($args['format']) ) {
                        return array('code'=>0, 'msg'=>'参数 '.$args_name.' 数组元素的长度不一致', 'data'=>$args);
                    }

                    break;

                case 'limit':

                    if ( !is_int($args) ) {
                        return array('code'=>0, 'msg'=>'参数 '.$args_name.' 不是正整数', 'data'=>$args);
                    }

                    break;

                case 'cols':

                    if ( !is_string($args) ) {
                        return array('code'=>0, 'msg'=>'参数 '.$args_name.' 不是字符串', 'data'=>$args);
                    }

                    break;

                case 'col':

                    if ( !is_string($args) ) {
                        return array('code'=>0, 'msg'=>'参数 '.$args_name.' 不是字符串', 'data'=>$args);
                    }

                    if ( strpos($args, ' ') !== false ) {
                        return array('code'=>0, 'msg'=>'参数 '.$args_name.' 含有空格，应该传入一列的名称，不能传入多列名称', 'data'=>$args);
                    }

                    break;

                case 'output':

                    if ( !in_array($args, array('ARRAY_A', 'ARRAY_N', 'OBJECT')) ) {
                        return array('code'=>0, 'msg'=>'参数 '.$args_name.' 只能是 ARRAY_A、ARRAY_N、OBJECT', 'data'=>$args);
                    }

                    break;

                case 'juhe':

                    if ( !in_array($args, array('COUNT', 'SUM', 'AVG', 'MIN', 'MAX', 'count', 'sum', 'avg', 'min', 'max')) ) {
                        return array('code'=>0, 'msg'=>'参数 '.$args_name.' 只能是 COUNT、SUM、AVG、MIN、MAX', 'data'=>$args);
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
                $this->tableName .         // 表名
                $method .                  // 方法名（get_row / get_col / get_var）
                serialize($args_for_key)   // 参数（不含 $nocache）
            );
        }




    }

}