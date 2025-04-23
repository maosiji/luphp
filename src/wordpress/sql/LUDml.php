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
 *  LUDml 主要用于检索、插入、更新和删除数据库中的数据。它直接处理数据库中的数据内容。常用的 LUDml 命令包括：
    INSERT：用于向数据库表中插入新记录。
    UPDATE：用于更新数据库表中的现有记录。
    DELETE：用于从数据库表中删除记录。
 * */
namespace MAOSIJI\LU\WP;
if ( !class_exists('LUDml') ) {
    class LUDml
    {
        function __construct() {}
        private function __clone() {}

        /** 添加表条目
         * @param $tableNameNoPrefix        : 没有前缀的表名
         * @param $params                   : 插入的数据
         * @param $formats                  : 插入的数据占位符
         * @return array
         */
        public function insert( string $tableNameNoPrefix, array $params, array $formats )
        {
            global $wpdb;
            $table_name = $wpdb->prefix . $tableNameNoPrefix;
            if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name ) {
                // int|false 插入的行数（注意不是行号），如果出现错误则返回 false。
                $return = $wpdb->insert( $table_name, $params, $formats );

                if ( !empty($wpdb->last_error) ) {
                    return array('code'=>0, 'msg'=>'insert 插入失败', 'data'=>esc_html($wpdb->last_error) );
                }

                if ( $return===false ) {
                    return array('code'=>0, 'msg'=>'insert 插入失败2', 'data'=>$return );
                }

                $params['id'] = $wpdb->insert_id;
                return array('code'=>1, 'msg'=>'insert 插入成功', 'data'=>$params);
            }

            return array('code'=>0, 'msg'=>'insert 未找到表名', 'data'=>'');
        }

        /**
         * 批量插入数据到指定表（支持事务）【未测试】
         *
         * @param string $tableNameNoPrefix     :表名（不带前缀）
         * @param array $data                   :要插入的数据（二维数组）
         * @param array $columns                :列名数组
         * @param array $format          :数据格式（如 '%s', '%d'）数组
         * @return array
         */
        public function batchInsert( string $tableNameNoPrefix, array $data, string $columnSQL, string $formatSQL ): array
        {
            global $wpdb;
            $table_name = $wpdb->prefix . $tableNameNoPrefix;
            if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name ) {

                // 开启事务
                $wpdb->query('START TRANSACTION');

                try {
                    $sql = "INSERT INTO {$table_name} ({$columnSQL}) VALUES " . $formatSQL;

                    // 使用 prepare 构建安全的 SQL 查询
                    $sql = $wpdb->prepare($sql, $data);

                    // 执行查询
                    $result = $wpdb->query($sql);

                    // 检查结果
                    if ($result === false) {
                        return array('code'=>0, 'msg'=>'query 批量插入失败', 'data'=>$wpdb->last_error);
                    }

                    // 提交事务
                    $wpdb->query('COMMIT');
                    return array('code'=>1, 'msg'=>'query 批量插入成功', 'data'=>$result);
                } catch (\Exception $e) {
                    // 回滚事务
                    $wpdb->query('ROLLBACK');
                    return array('code'=>0, 'msg'=>'query 批量插入失败，回滚事务', 'data'=>'');
                }

            }

            return array('code'=>0, 'msg'=>'query 未找到表名', 'data'=>'');
        }

        /**
         * @param $tableNameNoPrefix        : 没有前缀的表名
         * @param $params                   : 更新的数据数组
         * @param $formats                  : 更新的数据数组对应的格式数组
         * @param $wheres                   : Where条件数据数组
         * @param $wheresFormat             : Where条件数据数组对应的格式数组
         * @return array
         *
         *  $params              array('no' => $no,'status' => $status,)
         *  $formats             array('%s', '%d')
         *  $wheres              array( 'no' => $no, 'status' => $status, )
         *  $wheresFormat        array('%s', '%d')
         */
        public function update( string $tableNameNoPrefix, array $params, array $formats, array $wheres, array $wheresFormat )
        {
            global $wpdb;
            $table_name = $wpdb->prefix . $tableNameNoPrefix;

            if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name ) {

                $update = $wpdb->update( $table_name, $params, $wheres, $formats, $wheresFormat );

                if ( !empty($wpdb->last_error) ) {
                    return array('code'=>0, 'msg'=>'update 更新失败', 'data'=>esc_html($wpdb->last_error) );
                }

                // 出错返回 false
                if ( $update===false ) {
                    return array('code'=>0, 'msg'=>'update 更新失败2', 'data'=>$update );
                }

                // 返回值 0，代表无需更新
                if ( $update===0 ) {
                    return array('code'=>-1, 'msg'=>'update 无需更新', 'data'=>$update );
                }
			    // 返回值 1，代表更新了一行
			    // 返回值 2，代表更新了两行，此时，是不应该发生的情况
                return array('code'=>1, 'msg'=>'update 更新成功', 'data'=>$update );
            }

            return array('code'=>0, 'msg'=>'update 未找到表名', 'data'=>'' );
        }

        /**
         * @param $tableNameNoPrefix        : 没有前缀的表名
         * @param $wheres                   : Where条件数据数组
         * @param $wheresFormat             : Where条件数据数组对应的格式数组
         * @return array
         */
        public function delete( string $tableNameNoPrefix, array $wheres, array $wheresFormat )
        {
            global $wpdb;
            $table_name = $wpdb->prefix . $tableNameNoPrefix;

            if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name ) {

                $update = $wpdb->delete( $table_name, $wheres, $wheresFormat );

                if ( !empty($wpdb->last_error) ) {
                    return array('code'=>0, 'msg'=>'delete 删除失败', 'data'=>esc_html($wpdb->last_error) );
                }

                // 出错返回 false
                if ( $update===false ) {
                    return array('code'=>0, 'msg'=>'delete 删除失败2', 'data'=>$update );
                }

                // 返回值 0，代表无需删除
                if ( $update===0 ) {
                    return array('code'=>-1, 'msg'=>'delete 无需删除，没有符合条件的', 'data'=>$update );
                }
                // 返回值 1，代表删除了一行
                // 返回值 2，代表删除了两行
                return array('code'=>1, 'msg'=>'delete 删除成功', 'data'=>$update );
            }

            return array('code'=>0, 'msg'=>'delete 未找到表名', 'data'=>'' );
        }

    }
}