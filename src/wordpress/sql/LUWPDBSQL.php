<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-03-12 18:17
 * update               :
 * project              : luphp
 */
namespace MAOSIJI\LU\WP\SQL;
use MAOSIJI\LU\LUSend;

if ( ! defined( 'ABSPATH' ) ) { die; }
if ( !class_exists('LUWPDBSQL') ) {

    abstract class LUWPDBSQL {

        protected $tableName;
        private static $instances = [];

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
        private function deleteTable()
        {

        }

        /**
         * 插入1行
         * @param $params   : 插入的数据数组
         * @param $formats  : 插入的数据数组对应的格式数组
         * @return array
         */
        protected function insert( array $params, array $formats )
        {
            // 检测  输入是否正确
            $param = $this->verify('param', array('param'=>$params, 'format'=>$formats));
            if ( $param['code'] == 0 ) { return $param; }

            $dml = new LUWPDML();
            return $dml->insert( $this->tableName, $params, $formats );
        }

        /**
         * 插入多行【未测试】
         * @param array $data           :要插入的数据（二维数组）
         * @param array $columns        :列名数组
         * @param array $formats        :数据格式（如 '%s', '%d'）数组
         * @return array
         */
        private function batchInsert( array $data, array $columns, array $formats )
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

        /**
         * 删除符合条件的行
         * @param array $wheres
         * @param array $wheresFormat
         * @return array
         */
        protected function delete( array $wheres, array $wheresFormat )
        {
            // 检测  输入是否正确
            $param2 = $this->verify('param', array('param'=>$wheres, 'format'=>$wheresFormat));
            if ( $param2['code'] == 0 ) { return $param2; }

            $dml = new LUWPDML();
            return $dml->delete( $this->tableName, $wheres, $wheresFormat );
        }

        protected function deleteIn( string $whereMeta, array $wherePF, bool $isTransaction=false )
        {
            // 检测  输入是否正确
            $param2 = $this->verify( 'param', $wherePF );
            if ( $param2['code'] == 0 ) { return $param2; }

            $whereFormat = explode( ',', $wherePF['format'] );
            $params = $whereFormat['param'];

            $dml = new LUWPDML();
            return $dml->deleteIn( $this->tableName, $whereMeta, $whereFormat, $params, $isTransaction );
        }

        /**
         * 更新1行
         * @param $params           : 更新的数据数组
         * @param $formats          : 更新的数据数组对应的格式数组
         * @param $wheres           : Where条件数据数组
         * @param $wheresFormat     : Where条件数据数组对应的格式数组
         *
         * $params              array('no' => $no,'status' => $status,)
         * $formats             array('%s', '%d')
         * $wheres              array( 'no' => $no, 'status' => $status, )
         * $wheresFormat        array('%s', '%d')
         *
         * @return array
         */
        protected function update( array $params, array $formats, array $wheres, array $wheresFormat )
        {
            // 检测  输入是否正确
            $param1 = $this->verify('param', array('param'=>$params, 'format'=>$formats));
            if ( $param1['code'] == 0 ) { return $param1; }

            // 检测  输入是否正确
            $param2 = $this->verify('param', array('param'=>$wheres, 'format'=>$wheresFormat));
            if ( $param2['code'] == 0 ) { return $param2; }

            $dml = new LUWPDML();
            return $dml->update( $this->tableName, $params, $formats, $wheres, $wheresFormat );
        }

        /**
         * 查询1列
         * @param $where                : (array) where的数组，必须包含元素 array('meta'=>array(),'compare=>array(),'format'=>array())
         *                                          meta : array(字段名称=>字段值)
         *                                          compare : array('=') 运算符数组（=、<、LIKE等）
         *                                          format : array('%d') 格式数组
         * @param $col                 : (string) 选择特定列，只能写一个。
         * @return array
         */
        protected function get_col( array $where, string $col )
        {
            // 检测 where 输入是否正确
            $whereVerify = $this->verify('where', $where);
            if ( $whereVerify['code'] == 0 ) { return $whereVerify; }
            // 检测 col 输入是否正确
            $colVerify = $this->verify('cols', $col);
            if ( $colVerify['code'] == 0 ) { return $colVerify; }

            $formats = array();
            // 编写 where 语句
            $whereSQL = '';
            if ( !empty($where['meta']) ) {
                $whereSQL .= ' WHERE ';
                $wn = 0;
                foreach ( $where['meta'] as $key => $value ) {
                    if ( is_array($value) ) {
                        $n = 0;
                        foreach ( $value as $v ) {
                            $whereSQL .= ($wn!==0||$n!==0 ? ' AND ':'') . ' '.$key.$where['compare'][$wn][$n].$where['format'][$wn][$n] . ' ';
                            $formats[] = $v;
                            $n++;
                        }
                    } else {
                        $whereSQL .= ($wn!==0?' AND ':'') . ' '.$key.$where['compare'][$wn].$where['format'][$wn].' ';
                        $formats[] = $value;
                    }

                    $wn++;
                }
            }

            // 合并语句
            $sql = $whereSQL;
            $sqlFormat = $formats;

            $dql = new LUWPDQL();
            return $dql->get_col( $this->tableName, $col, $sql, $sqlFormat );
        }

        /**
         * 查询1列的值并进行聚合运算
         * @param $where                : (array) where的数组，必须包含元素 array('meta'=>array(),'compare=>array(),'format'=>array())
         *                                          meta : array(字段名称=>字段值)
         *                                          compare : array('=') 运算符数组（=、<、LIKE等）
         *                                          format : array('%d') 格式数组
         * @param $col                 : (string) 选择特定列或所有列，默认 *，全部显示。只能写一个。
         * @param $juhe                : 默认 COUNT，可选
         *                                  COUNT（总行数）、
         *                                  SUM（该列字段值的总和）、
         *                                  AVG（该列字段值的平均值）、
         *                                  MIN（该列字段值的最小值）、
         *                                  MAX（该列字段值的最大值）
         * @return array
         */
        protected function get_var( array $where, string $col='id', string $juhe='COUNT' )
        {
            // 检测 where 输入是否正确
            $whereVerify = $this->verify('where', $where);
            if ( $whereVerify['code'] == 0 ) { return $whereVerify; }
            // 检测 col 输入是否正确
            $colVerify = $this->verify('cols', $col);
            if ( $colVerify['code'] == 0 ) { return $colVerify; }
            // 检测 juhe 输入是否正确
            $juheVerify = $this->verify('juhe', $juhe);
            if ( $juheVerify['code'] == 0 ) { return $juheVerify; }

            $formats = array();
            // 编写 where 语句
            $whereSQL = '';
            if ( !empty($where['meta']) ) {
                $whereSQL .= ' WHERE ';
                $wn = 0;
                foreach ( $where['meta'] as $key => $value ) {
                    if ( is_array($value) ) {
                        $n = 0;
                        foreach ( $value as $v ) {
                            $whereSQL .= ($wn!==0||$n!==0 ? ' AND ':'') . ' '.$key.$where['compare'][$wn][$n].$where['format'][$wn][$n] . ' ';
                            $formats[] = $v;
                            $n++;
                        }
                    } else {
                        $whereSQL .= ($wn!==0?' AND ':'') . ' '.$key.$where['compare'][$wn].$where['format'][$wn].' ';
                        $formats[] = $value;
                    }

                    $wn++;
                }
            }

            // 合并语句
            $sql = $whereSQL;
            $sqlFormat = $formats;
            $colSql = ' '.$juhe.'('.$col.') ';

            $dql = new LUWPDQL();
            return $dql->get_var( $this->tableName, $colSql, $sql, $sqlFormat );
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
        protected function get_row( array $where, string $cols='*', string $output='ARRAY_A', array $orderSort=array('orderby'=>'id', 'sort'=>'DESC') )
        {
            // 检测 where 输入是否正确
            $whereVerify = $this->verify('where', $where);
            if ( $whereVerify['code'] == 0 ) { return $whereVerify; }
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

            $formats = array();
            // 编写 where 语句
            $whereSQL = '';
            if ( !empty($where['meta']) ) {
                $whereSQL .= ' WHERE ';
                $wn = 0;
                foreach ( $where['meta'] as $key => $value ) {
                    if ( is_array($value) ) {
                        $n = 0;
                        foreach ( $value as $v ) {
                            $whereSQL .= ($wn!==0||$n!==0 ? ' AND ':'') . ' '.$key.$where['compare'][$wn][$n].$where['format'][$wn][$n] . ' ';
                            $formats[] = $v;
                            $n++;
                        }
                    } else {
                        $whereSQL .= ($wn!==0?' AND ':'') . ' '.$key.$where['compare'][$wn].$where['format'][$wn].' ';
                        $formats[] = $value;
                    }

                    $wn++;
                }
            }

            // 编写 orderby 语句
            $orderSortSQL = ' ORDER BY '.$orderSort['orderby'].' '.$orderSort['sort'].' ';

            // 合并语句
            $sql = $whereSQL.$orderSortSQL;
            $sqlFormat = $formats;

//            return array('code'=>-2, 'msg'=>'测试', 'data'=>$sqlFormat);

            $dql = new LUWPDQL();
            return $dql->get_row( $this->tableName, $cols, $sql, $sqlFormat, $output );
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
         * @param $cols                 : (string) 选择特定列或所有列，默认 *，全部显示。若写多个，如：id,name,age
         * @param $output               : (string) OBJECT（对象）、ARRAY_A（关联数组）、ARRAY_N（数值数组）。默认 ARRAY_A。
         * @return array
         */
        protected function get_results( array $where, string $cols='*', array $orderSort=array('orderby'=>'id', 'sort'=>'DESC'), int $limit=0, string $output='ARRAY_A' )
        {
            // 检测 where 输入是否正确
            $whereVerify = $this->verify('where', $where);
            if ( $whereVerify['code'] == 0 ) { return $whereVerify; }
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

            $params = array();
            // 编写 where 语句
            $whereSQL = '';
            if (
                isset($where['meta']) && !empty($where['meta']) &&
                isset($where['compare']) && !empty($where['compare']) &&
                isset($where['format']) && !empty($where['format'])
            ) {
                $whereSQL .= ' WHERE ';
                $wn = 0;
                foreach ( $where['meta'] as $key => $value ) {
                    if ( is_array($value) ) {
                        $n = 0;
                        foreach ( $value as $v ) {
                            $whereSQL .= ($wn!==0||$n!==0 ? ' AND ':'') . ' '.$key.$where['compare'][$wn][$n].$where['format'][$wn][$n] . ' ';
                            $params[] = $v;
                            $n++;
                        }
                    } else {
                        $whereSQL .= ($wn!==0?' AND ':'') . ' '.$key.$where['compare'][$wn].$where['format'][$wn].' ';
                        $params[] = $value;
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
            $sqlParams = $params;

//            return array('code'=>0, 'msg'=>'测试', 'data2'=>$sql, 'data3'=>$orderSort);

            $dql = new LUWPDQL();
            return $dql->get_results( $this->tableName, $cols, $sql, $sqlParams, $output );
        }

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

            return $this->get_row($where);
        }

        /**
         * 获取总条目数
         * @return array
         */
        protected function get_total_num()
        {
            return $this->get_var( array() );
        }



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
                case 'col':

                    if ( !is_string($args) ) {
                        return array('code'=>0, 'msg'=>'参数 '.$args_name.' 不是字符串', 'data'=>$args);
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
    }

}