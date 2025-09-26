<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-12-03 23:57
 * update               :
 * project              : luphp
 */
/*
 * SELECT：用于检索数据库中的数据。
 * */
namespace MAOSIJI\LU\WP\SQL;
use MAOSIJI\LU\LUSend;

if ( ! defined( 'ABSPATH' ) ) { die; }
if ( !class_exists('LUWPDQL') ) {
    class LUWPDQL
    {
        function __construct()
        {
        }
        private function __clone()
        {
        }

        /**
         * 获取一列数据
         * @param $tableNameNoPrefix    : 没有前缀的表名
         * @param $col                  : 选择特定列，只能写一个。
         * @param $sql                  : SQL语句
         * @param $sqlFormat            : 格式数组
         * @return array
         */
        public function get_col( string $tableNameNoPrefix, string $col, string $whereSQL, array $whereValue ): array
        {
            global $wpdb;
            $table_name = $wpdb->prefix . $tableNameNoPrefix;
            if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name ) {

                if ( empty($whereValue) && empty($whereSQL) ) {
                    $return = $wpdb->get_col("SELECT " . $col . " FROM " . $table_name );
                }
                if (!empty($whereValue) && !empty($whereSQL)) {
                    $return = $wpdb->get_col($wpdb->prepare("SELECT " . $col . " FROM " . $table_name . $whereSQL, ...$whereValue));
                }

                // 失败
                if ( $return===null ) {
                    return (new LUSend())->send_array(0, 'col 查询失败', $return);
                }

                if ( count($return)===0 ) {
                    return (new LUSend())->send_array(-1, 'col 未查询到', $return);
                }

                return (new LUSend())->send_array(1, 'col 已查询到', $return);
            }

            return (new LUSend())->send_array(0, 'col 未找到表 '.$table_name, '');
        }

        /**
         * 获取聚合数据
         * @param $tableNameNoPrefix    : 没有前缀的表名
         * @param $colSql               : 聚合sql
         * @param $sql                  : SQL语句
         * @param $sqlFormat            : 格式数组
         * @return array
         */
        public function get_var( string $tableNameNoPrefix, string $col, string $whereSQL, array $whereValue ): array
        {
            global $wpdb;
            $table_name = $wpdb->prefix . $tableNameNoPrefix;
            if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name ) {

                if ( empty($whereValue) && empty($whereSQL) ) {
                    $return = $wpdb->get_var("SELECT " . $col . " FROM " . $table_name );
                }
                if ( !empty($whereValue) && !empty($whereSQL) ) {
                    $return = $wpdb->get_var($wpdb->prepare("SELECT " . $col . " FROM " . $table_name . $whereSQL, ...$whereValue));
                }

                // 失败
                if ( $return===null ) {
                    return (new LUSend())->send_array(0, 'var 查询失败', $return);
                }

                // 未查询到
                if ( $return==='0' ) {
                    return (new LUSend())->send_array(-1, 'var 未查询到', $return);
                }

                return (new LUSend())->send_array(1, 'var 已查询到', $return);
            }

            return (new LUSend())->send_array(0, 'var 未找到表 '.$table_name, '');
        }

        /**
         * 获取一行数据
         * @param $tableNameNoPrefix    : 没有前缀的表名
         * @param $cols                 : 特定列或全部
         * @param $sql                  : SQL语句
         * @param $sqlFormat            : 格式数组
         * @param $output               : 返回格式
         * @return array
         */
        public function get_row( string $tableNameNoPrefix, string $cols, string $whereSQL, array $whereValue, string $output ): array
        {
            global $wpdb;
            $table_name = $wpdb->prefix . $tableNameNoPrefix;
            if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name ) {

                if ( empty($whereValue) && empty($whereSQL) ) {
                    $return = $wpdb->get_row( "SELECT ".$cols." FROM " . $table_name, $output );
                }
                if ( !empty($whereValue) && !empty($whereSQL) ){
                    $return = $wpdb->get_row( $wpdb->prepare("SELECT ".$cols." FROM " . $table_name . $whereSQL, ...$whereValue), $output );
                }

                // 查询失败
                if ( !empty($wpdb->last_error) ) {
                    return (new LUSend())->send_array(0, 'row 查询失败', esc_html($wpdb->last_error) );
                }

                if ( $return===null ) {
                    return (new LUSend())->send_array(-1, 'row 未查询到', $return);
                }

                return (new LUSend())->send_array(1, 'row 已查询到', $return);
            }

            return (new LUSend())->send_array(0, 'row 未找到表 '.$table_name, '');
        }

        /**
         * 获取多行数据
         * @param $tableNameNoPrefix        : 没有前缀的表名
         * @param $cols                     : 特定列或全部
         * @param $whereSQL                      : SQL语句
         * @param array $whereValue         : 格式数组
         * @param $output                   : 返回格式
         * @return array
         */
        public function get_results( string $tableNameNoPrefix, string $cols, string $whereSQL, array $whereValue, string $output ): array
        {
            global $wpdb;
            $table_name = $wpdb->prefix . $tableNameNoPrefix;
            if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name ) {

                if ( empty($whereValue) ) {
                    $return = $wpdb->get_results( "SELECT " . $cols . " FROM " . $table_name, $output );
                }
                if ( !empty($whereValue) ) {
                    $return = $wpdb->get_results($wpdb->prepare("SELECT " . $cols . " FROM " . $table_name . $whereSQL, ...$whereValue), $output);
                }

                // 查询失败
                if ( !empty($wpdb->last_error) ) {
                    return (new LUSend())->send_array(0, 'results 查询失败', esc_html($wpdb->last_error) );
                }

                if ( count($return)===0 ) {
                    return (new LUSend())->send_array(-1, 'results 未查询到', $return);
                }

                return (new LUSend())->send_array(1, 'results 已查询到', $return);
            }

            return (new LUSend())->send_array(0, 'results 未找到表 '.$table_name, '');
        }

        /**
         * $wpdb->query 未完成
         * @return array
         */
        public function query()
        {

        }



    }
}