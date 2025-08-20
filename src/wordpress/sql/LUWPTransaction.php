<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-08-16 03:59
 * update               : 
 * project              : luphp
 * description          : 事务操作
 */

namespace MAOSIJI\LU\WP\SQL;
use MAOSIJI\LU\LUSend;

if ( ! defined( 'ABSPATH' ) ) { die; }
if (!class_exists('LUWPTransaction')) {
    class LUWPTransaction
    {
        function __construct()
        {
        }
        private function __clone() {}

        private function a( string $tableNameNoPrefix )
        {
            global $wpdb;
            $table_name = $wpdb->prefix . $tableNameNoPrefix;

//            $next_id = false;
//
//            if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name ) {
//
//                $wpdb->query('START TRANSACTION');
//                $current_id = $wpdb->get_row(
//                    $wpdb->prepare(
//                        "SELECT id FROM {$table_name} FOR UPDATE"
//                    )
//                )
//            }

        }


    }
}