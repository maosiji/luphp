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
 *  主要用于定义或修改数据库结构，包括创建、删除和修改数据库中的对象如表、索引、视图等
 *
 *  CREATE：用于创建数据库或数据库中的对象（例如表、视图）。
    ALTER：用于修改现有的数据库结构。
    DROP：用于删除数据库对象。
    TRUNCATE：用于删除表中的所有数据但保留表结构。
    COMMENT：用于向数据字典添加注释。
    RENAME：用于重命名一个对象。
 * */
namespace MAOSIJI\LU\WP;
if (!class_exists('LUWPDdl')) {
    class LUWPDdl
    {

        function __construct()
        {
        }
        private function __clone() {}

        /**
         * @param $tableNameNoPrefix
         * @param $sql
         * @return bool 创建表
         */
        public function createTable( string $tableNameNoPrefix, string $sql )
        {
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();
            $table_name = $wpdb->prefix . $tableNameNoPrefix;

            if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}' ") != $table_name) {
                $sqls = "
                    CREATE TABLE " . $table_name . " (
                        ".$sql."
                    ) ENGINE=InnoDB DEFAULT CHARSET=" . $charset_collate . " COLLATE=utf8mb4_unicode_520_ci
                ";

                require_once ABSPATH . "wp-admin/includes/upgrade.php";
                dbDelta($sqls);
            }

        }

    }

}