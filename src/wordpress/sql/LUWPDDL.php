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
 * 主要用于定义或修改数据库结构，包括创建、删除和修改数据库中的对象如表、索引、视图等
 *
 *  CREATE：用于创建数据库或数据库中的对象（例如表、视图）。
    ALTER：用于修改现有的数据库结构。
    DROP：用于删除数据库对象。
    TRUNCATE：用于删除表中的所有数据但保留表结构。
    COMMENT：用于向数据字典添加注释。
    RENAME：用于重命名一个对象。
 */

namespace MAOSIJI\LU\WP\SQL;

use MAOSIJI\LU\EXCEPTION\LUDatabaseException;

if ( ! defined( 'ABSPATH' ) ) { die; }
class LUWPDDL
{
    use LUWPSQLPublic;
    function __construct()
    {
    }
    private function __clone() {}

    /**
     * 创建表
     *
     * @param string $tableNameNoPrefix 不带 WordPress 表前缀的表名
     * @param string $columnsSql        列定义 SQL 片段，如 "id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100)"
     *
     * @return LUWPDDLResult
     * @throws LUDatabaseException 创建失败时抛出
     */
    public function createTable( string $tableNameNoPrefix, string $columnsSql ): LUWPDDLResult
    {
        LUWPSQLParamValidator::tableNameNoPrefix( $tableNameNoPrefix );
        LUWPSQLParamValidator::sql( $columnsSql );

        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;

        // 1. 表已存在则直接返回幂等结果
        if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") === $tableName) {
            return LUWPDDLResult::alreadyExists($tableName);
        }

        $charsetCollate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $tableName ($columnsSql) ENGINE=InnoDB $charsetCollate";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $output = dbDelta($sql);

        // 2. 再次检查表是否存在，避免依赖 dbDelta 的文字输出
        $tableNowExists = $wpdb->get_var("SHOW TABLES LIKE '$tableName'") === $tableName;

        if ($tableNowExists) {
            return LUWPDDLResult::created($tableName);
        }

        // 3. 如果仍未创建，视为失败
        throw new LUDatabaseException(
            //"Failed to create table $tableName",
            "表 $tableName 创建失败",
            LUDatabaseException::CODE_CREATE_FAILED,
            null,
            ['raw_output' => $output]
        );
    }

}
