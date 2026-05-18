<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-30 14:19
 * update               : 
 * project              : luphp
 */

namespace MAOSIJI\LU\WP\SQL;

use MAOSIJI\LU\LUResult;

trait LUWPSQLPublic
{
    /**
     * 确保表存在
     *
     * @param string $tableName 完整表名（含前缀）
     * @return LUResult
     */
    public function checkTableExist( string $tableName ): LUResult
    {
        global $wpdb;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$tableName'") === $tableName;
        if (!$exists) {
            return LUResult::error( 800800, "表 $tableName 不存在", [
                'table_name'    => $tableName
            ]);
        }

        return LUResult::success([
            'table_name'    => $tableName
        ], "表 $tableName 存在");
    }



}