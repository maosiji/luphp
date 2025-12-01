<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-11-09 03:57
 * update               :
 * project              : luphp
 */

namespace WUTI\XYYSD;

if (!defined('ABSPATH')) {die;}
if (!class_exists('LUWPCache')) {
    /**
     * 高级WordPress缓存管理类，不能存 false
     *
     * 提供多级缓存管理（内存缓存、对象缓存、瞬态缓存）
     * 支持自动刷新和事件驱动清除机制
     *
     */
    class LUWPCache
    {
        /**
         * 内存缓存存储数组
         * @var array 存储当前请求内的缓存数据
         */
        private $cache = [];

        /**
         * 已注册的定时任务记录
         * @var array 防止重复注册定时任务
         */
        private $scheduled_hooks = [];

        /**
         * 单例实例
         * @var LUWPCache|null
         */
        private static $instance = null;

        /**
         * 获取单例实例
         *
         * @return LUWPCache 返回类的单例实例
         */
        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * 私有构造函数（单例模式）
         */
        private function __construct() {
            // 初始化代码可以放在这里
        }

        /* ==================== 缓存操作 ==================== */

        /**
         * 设置缓存数据（ajax/cron/rest/favicon不可缓存）
         *
         * @param string $key 缓存键名（建议使用前缀避免冲突）
         * @param mixed $data 要缓存的数据（支持所有可序列化类型）
         * @param string $group             : 组名称（必须）
         * @param int $expire_senconds      : 过期秒数，0 表示永不过期，默认 0.
         * @param bool $persistent 是否持久化到对象缓存和瞬态缓存
         * @param array $requests 需要强制缓存的请求类型，ajax/cron/rest/favicon
         * @return void
         */
        public function set( string $key, $data, int $expire_senconds=0, string $group='xyysd', bool $persistent=true, array $requests=[] )
        {
            // 防重复执行（同一请求内）
            static $executed = [];
            $groupKey = $group.'_'.$key;

            if (isset($executed[$groupKey])) {
                return;
            }

            // 跳过非主请求（favicon / ajax / cron / rest）
            if ($this->is_background_request( $requests )) {
                return;
            }

            // 1. 设置内存缓存（当前请求内有效）
            $this->cache[$groupKey] = $data;

            // 对象缓存（通常存储在内存缓存服务如Redis/Memcached中）
            wp_cache_set($groupKey, $data, $group, $expire_senconds);

            // 2. 如果需要持久化，设置对象缓存和瞬态缓存
            if ($persistent) {
                // 瞬态缓存（存储在数据库中，确保持久性）
                set_transient($groupKey, $data, $expire_senconds);
            }
        }

        /**
         * 获取缓存数据
         *
         * @param string $key 要获取的缓存键名
         * @param int $expiration 如果生成新数据，设置缓存过期时间
         * @param bool $persistent 是否持久化新生成的数据
         * @return mixed 返回缓存数据，如果不存在且无回调则返回false
         */
        public function get( string $key, string $group='xyysd' ) {

            $groupKey = $group.'_'.$key;

            // 1. 首先检查内存缓存（最快）
            if (isset($this->cache[$groupKey])) {
                return $this->cache[$groupKey];
            }

            // 2. 检查对象缓存（内存级缓存）
            if ( false !== ($cached = wp_cache_get($groupKey, $group)) ) {
                $this->cache[$groupKey] = $cached;
                return $cached;
            }

            // 3. 检查瞬态缓存（数据库）
            if (false !== ($cached = get_transient($groupKey))) {
                // 回填到内存缓存
                $this->cache[$groupKey] = $cached;
                // 回填到对象缓存
                $timeout_option = '_transient_timeout_' . $groupKey;
                $expiration_timestamp = get_option($timeout_option, 0);
                $remaining_seconds = $expiration_timestamp > time() ? ($expiration_timestamp - time()) : 0;
                wp_cache_set($groupKey, $cached, $group, $remaining_seconds);

                return $cached;
            }

            return false;
        }

        /**
         * 删除缓存数据
         *
         * @param string $key 要删除的缓存键名
         * @param $groups:单个组名、组名数组，或 空 表示全部
         *
         *  当 key 为空，groups 为空时，清除全部；
         *  当 key 不为空，groups 为空时，精准清除;
         *  当 key 为空，groups 不为空时，清除组的全部；
         *  当 key 不为空，groups 不为空时，精准清除。
         *
         */
        public function delete( string $key='', $groups='' ) {

            $this->delete_memory_cache( $key, $groups );
            $this->delete_object_cache( $key, $groups );
            $this->delete_transients( $key, $groups );
        }

        /* ==================== 各个缓存清理方法 ==================== */

        /**
         * 清除内存缓存
         *
         * @param string $key:指定 key，仅清除 group_key 这一项（需配合 $groups 使用）
         * @param $groups:单个组名、组名数组，或 空 表示全部
         *
         * // 1. 清空全部
         * $cache->delete_memory_cache();
         *
         * // 2. 清空指定组
         * $cache->delete_memory_cache('xyysd');
         *
         * // 3. 清空多个组
         * $cache->delete_memory_cache( '', ['xyysd', 'user'] );
         *
         * // 4. 精准清除单个缓存项
         * $cache->delete_memory_cache( 'xyysd', 'access_token' );
         *
         * // 5. 精准清除多个组下的同名 key
         * $cache->delete_memory_cache( 'config', ['group1', 'group2'] );
         */
        private function delete_memory_cache( string $key='', $groups='' )
        {
            if ( $key==='' && $groups==='' ) {
                $this->cache = [];
                return;
            }

            if ( $key!=='' && $groups==='' ) {
                unset( $this->cache[$key] );
                return;
            }

            if ( $groups!=='' ) {
                $groups = (array) $groups;
                foreach ( $groups as $gs ) {
                    if ( $key==='' ) {
                        unset( $this->cache[$gs] );
                    }
                    if ( $key!=='' ) {
                        unset( $this->cache[$gs.'_'.$key] );
                    }
                }
            }

        }

        /**
         * 清除对象缓存
         *
         * @param string $key :指定 key，仅清除 group_key 这一项（需配合 $groups 使用）
         * @param $groups :单个组名、组名数组，或 空 表示全部
         *
         * // 1. 清空全部对象缓存
         * $cache->delete_object_cache();
         *
         * // 2. 清空指定组
         * $cache->delete_object_cache('xyysd');
         *
         * // 3. 清空多个组
         * $cache->delete_object_cache( '', ['xyysd', 'user'] );
         *
         * // 4. 精准清除单个缓存项
         * $cache->delete_object_cache( 'xyysd', 'access_token' );
         *
         * // 5. 精准清除多个组下的同名 key
         * $cache->delete_object_cache( 'config', ['group1', 'group2'] );
         */
        private function delete_object_cache( string $key='', $groups='' )
        {
            if ( $key==='' && $groups==='' ) {
                wp_cache_flush();
                return;
            }

            if ( $key!=='' && $groups==='' ) {
                wp_cache_delete($key);
                return;
            }

            if ( $groups!=='' ) {
                $groups = (array) $groups;
                foreach ( $groups as $gs ) {
                    if ( $key==='' ) {
                        wp_cache_flush_group($gs);
                    }
                    if ( $key!=='' ) {
                        wp_cache_delete($key, $gs);
                    }
                }
            }

        }

        /**
         * 清除瞬态缓存
         *
         * @param string $key :指定 key，仅清除 group_key 这一项（需配合 $groups 使用）
         * @param $groups :单个组名、组名数组，或 空 表示全部
         *
         * // 1. 清空全部瞬态缓存
         * $cache->delete_transients();
         *
         * // 2. 清空指定组
         * $cache->delete_transients( 'xyysd' );
         *
         * // 3. 清空多个组
         * $cache->delete_transients( '', ['xyysd', 'user'] );
         *
         * // 4. 精准清除单个缓存项
         * $cache->delete_transients( 'xyysd', 'access_token' );
         *
         * // 5. 精准清除多个组下的同名 key
         * $cache->delete_transients( 'config', ['group1', 'group2'] );
         */
        private function delete_transients( string $key='', $groups='' )
        {
            global $wpdb;

            if ( $key==='' && $groups==='' ) {

                $deleted_options = $wpdb->query(
                    "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_%' 
            OR option_name LIKE '_transient_timeout_%'"
                );

                if (is_multisite()) {
                    $deleted_sitemeta = $wpdb->query(
                        "DELETE FROM {$wpdb->sitemeta} 
                WHERE meta_key LIKE '_site_transient_%' 
                OR meta_key LIKE '_site_transient_timeout_%'"
                    );
                }

                return;
            }

            if ( $key!=='' && $groups==='' ) {
                delete_transient($key);
                if ( is_multisite() ) {
                    delete_site_transient($key);
                }
                return;
            }

            if ( $groups!=='' ) {
                $groups = (array) $groups;
                foreach ( $groups as $gs ) {
                    if ( $key==='' ) {

                        $like_group = $wpdb->esc_like($gs) . '_%';
                        $wpdb->query(
                            $wpdb->prepare(
                                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                                '_transient_' . $like_group,
                                '_transient_timeout_' . $like_group
                            )
                        );

                        if (is_multisite()) {
                            $wpdb->query(
                                $wpdb->prepare(
                                    "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
                                    '_site_transient_' . $like_group,
                                    '_site_transient_timeout_' . $like_group
                                )
                            );
                        }

                    }
                    if ( $key!=='' ) {
                        delete_transient($gs.'_'.$key);
                        if ( is_multisite() ) {
                            delete_site_transient($gs.'_'.$key);
                        }
                    }
                }
            }

        }

        /* ==================== 高级缓存获取（带并发保护） ==================== */

        /**
         * 获取缓存，若不存在则通过回调函数重建数据，并防止缓存雪崩（并发保护）
         *
         * @param string   $key             缓存键名（不含组前缀）
         * @param string   $group           缓存组名
         * @param callable $callback        重建缓存的回调函数（必须返回可缓存的数据）
         * @param int      $expire_seconds  真实缓存的过期时间（秒），默认 3600
         * @param int      $lock_ttl        锁的过期时间（秒），防止死锁，默认 5
         * @param bool     $persistent      是否持久化到对象缓存和瞬态缓存
         * @return mixed                    返回缓存数据或重建后的数据
         */
        public function get_or_rebuild(
            string $key,
            $callback,
            int $expire_seconds = 3600,
            string $group='xyysd',
            int $lock_ttl = 5,
            bool $persistent = true
        )
        {
            $groupKey = $group . '_' . $key;
            $lockKey  = $groupKey . '_LOCK';

            // 1. 尝试从三层缓存获取真实数据
            if (($data = $this->get($key, $group)) !== false) {
                return $data;
            }

            // 2. 检查是否已有其他进程在重建（存在有效锁）
            if ($this->is_locked($lockKey, $group)) {
                // 策略：短暂等待后再次尝试读取（简单轮询，避免复杂队列）
                usleep(50000); // 等待 50ms
                if (($data = $this->get($key, $group)) !== false) {
                    return $data;
                }
                // 若仍无数据，则直接执行回调（降级处理，避免永久阻塞）
            }

            // 3. 尝试获取重建锁（原子操作）
            if (!$this->acquire_lock($lockKey, $group, $lock_ttl)) {
                // 获取锁失败（极小概率并发冲突），降级：直接重建
                return call_user_func($callback);
            }

            try {
                // 4. 双重检查：可能在获取锁期间已被其他进程写入
                if (($data = $this->get($key, $group)) !== false) {
                    return $data;
                }

                // 5. 执行重建逻辑
                $data = call_user_func($callback);

                // 6. 写入缓存
                $this->set($key, $data, $group, $expire_seconds, $persistent);

                return $data;
            } finally {
                // 7. 释放锁（确保 always 释放）
                $this->release_lock($lockKey, $group);
            }
        }

        /* ==================== 锁机制实现（支持对象缓存原子操作） ==================== */

        /**
         * 尝试获取重建锁（优先使用对象缓存的原子 add 操作）
         *
         * @param string $lockKey
         * @param string $group
         * @param int    $ttl
         * @return bool  是否成功获取锁
         */
        private function acquire_lock( string $lockKey, string $group, int $ttl )
        {
            return wp_cache_add($lockKey, 1, $group, $ttl);
        }

        /**
         * 检查锁是否存在
         *
         * @param string $lockKey
         * @param string $group
         * @return bool
         */
        private function is_locked( string $lockKey, string $group )
        {
            return wp_cache_get($lockKey, $group) !== false;
        }

        /**
         * 释放锁
         *
         * @param string $lockKey
         * @param string $group
         */
        private function release_lock( string $lockKey, string $group )
        {
            wp_cache_delete($lockKey, $group);
        }

        /* ==================== 私有方法 ==================== */

        /**
         * 不需要的请求（favicon / ajax / cron / rest）
         * @param array $arr : 可选值：空、favicon / ajax / cron / rest，填写哪些请求，则不禁止哪些请求。
         * @return bool
         */
        private function is_background_request( $arr=[] ): bool {

            if ( !empty($arr) ) {

                if ( !in_array('ajax', $arr) && wp_doing_ajax() ) {
                    return true;
                }

                if ( !in_array('cron', $arr) && wp_doing_cron() ) {
                    return true;
                }

                if ( !in_array('rest', $arr) && defined('REST_REQUEST') && REST_REQUEST ) {
                    return true;
                }

                if ( !in_array('favicon', $arr) && isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] === '/favicon.ico' ) {
                    return true;
                }

            }
            else {
                // WordPress 内置判断
                if (wp_doing_ajax() || wp_doing_cron()) {
                    return true;
                }

                // REST API 请求
                if (defined('REST_REQUEST') && REST_REQUEST) {
                    return true;
                }

                // favicon.ico 请求
                if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] === '/favicon.ico') {
                    return true;
                }

                // 可选：排除 wp-login, wp-admin（如果不需要在后台缓存）
                // if (strpos($_SERVER['REQUEST_URI'] ?? '', 'wp-login') !== false) return true;
                // if (is_admin()) return true;
            }

            return false;
        }

        /* ==================== 定时自动刷新缓存（未测试） ==================== */

        /**
         * 为指定缓存项注册定时自动刷新（基于 WP Cron）
         *
         * @param string   $key             缓存键名（不含组前缀）
         * @param string   $group           缓存组名
         * @param callable $callback        重建数据的回调函数（仅支持函数名或静态方法数组）
         * @param int      $interval        刷新间隔（秒），默认 3600（1小时）
         * @param bool     $persistent      是否持久化缓存
         * @return bool                     是否成功注册
         */
        public function schedule_refresh(
            string $key,
            string $group,
                   $callback, // 注意：不加 callable 类型声明以避免 PHP 7.0 下闭包问题
            int $interval = 3600,
            bool $persistent = true
        ) {
            // 验证 callback 类型（禁止匿名函数）
            if (!is_string($callback) && !(is_array($callback) && count($callback) === 2)) {
                if (function_exists('_doing_it_wrong')) {
                    _doing_it_wrong(
                        __METHOD__,
                        'Callback must be a function name (string) or static method array (e.g. ["Class", "method"]). Anonymous functions are not supported for cron.',
                        '1.0'
                    );
                }
                return false;
            }

            if (!is_callable($callback)) {
                return false;
            }

            $hook = $this->get_refresh_hook($key, $group);

            // 清除已有任务，避免重复
            if (wp_next_scheduled($hook)) {
                wp_clear_scheduled_hook($hook);
            }

            // 存储回调（使用 maybe_serialize 兼容性更好）
            $cb_key = "maosiji_lu_cache_refresh_cb_{$group}_{$key}";
            set_transient($cb_key, maybe_serialize($callback), $interval * 2);

            // 注册单次事件（由 all 钩子统一处理）
            $timestamp = time() + $interval;
            $args = [
                'key'        => $key,
                'group'      => $group,
                'interval'   => $interval,
                'persistent' => $persistent
            ];

            return wp_schedule_single_event($timestamp, $hook, [$args]) !== false;
        }

        /**
         * 取消指定缓存项的定时自动刷新任务
         *
         * @param string $key   缓存键名（不含组前缀）
         * @param string $group 缓存组名
         * @return bool         是否成功取消
         */
        public function unschedule_refresh(string $key, string $group)
        {
            $hook = $this->get_refresh_hook($key, $group);
            $cb_transient_key = "maosiji_lu_cache_refresh_cb_{$group}_{$key}";

            // 清除 WP Cron 任务
            $cleared = wp_clear_scheduled_hook($hook);

            // 删除存储的回调函数
            delete_transient($cb_transient_key);

            return $cleared !== false;
        }

        /**
         * 获取刷新任务的 hook 名称
         */
        private function get_refresh_hook(string $key, string $group)
        {
            return "maosiji_lu_hook_cache_refresh_{$group}_{$key}";
        }

        /**
         * 处理缓存刷新（由 WP Cron 调用）
         *
         * @internal 不要直接调用！
         */
        public function handle_refresh($args)
        {
            if (!is_array($args)) {
                return;
            }

            $key        = $args['key'] ?? '';
            $group      = $args['group'] ?? '';
            $interval   = $args['interval'] ?? 3600;
            $persistent = $args['persistent'] ?? true;

            if (!$key || !$group) {
                return;
            }

            $cb_key = "maosiji_lu_cache_refresh_cb_{$group}_{$key}";
            $serialized_cb = get_transient($cb_key);

            if ($serialized_cb === false) {
                // 回调已过期，清除任务
                wp_clear_scheduled_hook($this->get_refresh_hook($key, $group));
                return;
            }

            $callback = maybe_unserialize($serialized_cb);
            if (!is_callable($callback)) {
                error_log("LUWPCache: Invalid or non-callable callback for refresh task {$group}/{$key}");
                wp_clear_scheduled_hook($this->get_refresh_hook($key, $group));
                return;
            }

            // 使用并发保护重建缓存
            $this->get_or_rebuild($key, $group, $callback, $interval, 5, $persistent);

            // 重新调度下一次刷新
            $next_hook = $this->get_refresh_hook($key, $group);
            if (!wp_next_scheduled($next_hook)) {
                wp_schedule_single_event(time() + $interval, $next_hook, [$args]);
            }
        }


        /* ==================== 调试方法 ==================== */

        /**
         * 查看某组有哪些瞬态缓存
         * @param string $group
         * @return array
         */
        public function get_transients( string $group='' ): array
        {
            global $wpdb;

            $pattern = $group ? '_transient_' . $wpdb->esc_like($group) . '_%' : '_transient_%';

            $results = $wpdb->get_col($wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                $pattern
            ));

            // 移除前缀，只保留真实 name
            return array_map(function($name) {
                return str_replace('_transient_', '', $name);
            }, $results);
        }

        /**
         * 检查指定缓存项是否已注册自动刷新任务
         *
         * @param string $key
         * @param string $group
         * @return bool
         */
        public function is_refresh_scheduled( string $key, string $group ): bool
        {
            $hook = $this->get_refresh_hook($key, $group);
            return (bool) wp_next_scheduled($hook);
        }


    }
}

/* ==================== 使用示例 ==================== */

// 获取缓存实例
//$cache = LUWPCache::get_instance();

// 示例1：清空所有对象缓存
//$cache->delete_object_cache();

// 示例2：清空所有瞬态缓存
//$cache->delete_transients();

// 示例3：清空指定前缀的瞬态缓存
//$cache->delete_transients_by_prefix('myplugin_');

// 示例4：清空所有缓存（内存+对象+瞬态）
//$result = $cache->flush_all();
/*
返回结果示例：
[
    'memory' => null,
    'object' => true,
    'transients' => 42
]
*/

// 注册每日清理过期缓存
//$cache->schedule_refresh(
//    'cleanup_expired',
//    function() use ($cache) {
//        // 只清理过期的瞬态
//        global $wpdb;
//        $wpdb->query(
//            "DELETE FROM {$wpdb->options}
//            WHERE option_name LIKE '_transient_timeout_%'
//            AND option_value < UNIX_TIMESTAMP()"
//        );
//    },
//    'daily'
//);


// 插件主文件中的 Cron 动态拦截器
// 动态捕获所有 luwp_cache_refresh_* 的 WP Cron 事件
//add_action('all', function ($hook_name) {
//    // 仅处理 LUWPCache 的刷新任务
//    if (strpos($hook_name, 'maosiji_lu_hook_cache_refresh_') !== 0) {
//        return;
//    }
//
//    // 确保类存在
//    if (!class_exists('LUWPCache')) {
//        return;
//    }
//
//    // 获取传递的参数（WP Cron 会将数组作为第一个参数传入）
//    $all_args = func_get_args();
//    array_shift($all_args); // 移除 $hook_name
//
//    if (empty($all_args) || !is_array($all_args[0])) {
//        return;
//    }
//
//    $cache = LUWPCache::get_instance();
//    $cache->handle_refresh($all_args[0]);
//});

// 使用示例 1 普通函数
//function fetch_hot_posts() {
//    return get_posts(['numberposts' => 5, 'orderby' => 'comment_count']);
//}
//
//$cache = LUWPCache::get_instance();
//// 启用自动刷新：每 30 分钟更新一次
//$cache->schedule_refresh(
//    'hot_posts',
//    'xyysd',
//    'fetch_hot_posts', // 函数名字符串
//    1800, // 30分钟
//    true
//);

// 使用示例 2  静态函数
//class DataService {
//    public static function get_api_data() {
//        return wp_remote_get('https://api.example.com/data');
//    }
//}
//
//$cache->schedule_refresh(
//    'api_response',
//    'external',
//    ['DataService', 'get_api_data'],
//    3600,
//    true
//);

// 停止自动刷新
//$cache = LUWPCache::get_instance();
//$cache->unschedule_refresh('hot_posts', 'xyysd');
