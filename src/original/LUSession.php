<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : code@maosiji.cn
 * date                 : 2024-09-30
 * update               : 2025-11-08 — 兼容 PHP 7.0+
 * project              : luphp
 * description          : 安全、轻量的 Session 启动与管理工具类（兼容 PHP 7.0+）
 */

namespace MAOSIJI\LU;

if (!class_exists('LUSession')) {
    /**
     * LUSession - 安全封装 PHP 原生 Session 的单例工具类
     *
     * 功能：
     * - 自动安全配置 Session（仅 Cookie、HttpOnly、Secure 等）
     * - 统一设置合理的过期策略（服务端 + 客户端）
     * - 提供会话销毁、ID 重生成等安全操作
     *
     * 使用说明：
     * - 本类不修改 $_SESSION 结构，用户可直接操作原生 Session
     * - 推荐在应用初始化阶段调用 getInstance() 确保 Session 启动
     */
    class LUSession
    {
        /** @var LUSession|null 单例实例 */
        private static $instance = null;

        /** @var bool 标记 Session 是否已启动 */
        private $started = false;

        /**
         * 私有构造函数，确保只能通过 getInstance() 创建实例
         */
        private function __construct()
        {
            $this->start();
        }

        /**
         * 禁止克隆实例
         */
        private function __clone()
        {
        }

        /**
         * 获取 LUSession 单例实例
         *
         * 首次调用时会自动启动并安全配置 Session。
         *
         * @return self
         */
        public static function getInstance()
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * 安全启动 Session（幂等操作）
         *
         * - 仅当 Session 未启动时执行初始化
         * - 自动检测 HTTPS 并设置 secure Cookie
         * - 设置服务端 GC 过期时间为 2 小时（7200 秒）
         * - 设置客户端 Cookie 生命周期为 2 小时
         *
         * @return void
         */
        private function start()
        {
            if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
                $this->started = true;
                return;
            }

            // 强制 Session ID 仅通过 Cookie 传递（防止 URL 泄露）
            ini_set('session.use_only_cookies', '1');

            // 设置 HttpOnly，防止 XSS 窃取 Cookie
            ini_set('session.cookie_httponly', '1');

            // 若当前连接为 HTTPS，则启用 Secure Cookie
            $isHttps = (new LUUrl())->is_https();
            if ($isHttps) {
                ini_set('session.cookie_secure', '1');
            }

            // 设置服务端 Session 数据最大存活时间（2 小时）
            // 不设置，浏览器关闭时，session失效
//            ini_set('session.gc_maxlifetime', '7200');

            // 兼容 PHP 7.0–7.2：session_set_cookie_params 不支持数组参数
            // 函数签名：session_set_cookie_params(lifetime, path, domain, secure, httponly)
            session_set_cookie_params(
                7200,           // lifetime
                '/',            // path（建议设为根路径）
                '',             // domain（留空表示当前域）
                $isHttps,       // secure
                true            // httponly
            // 注意：samesite 无法在 PHP < 7.3 中通过此函数设置
            );

            // 启动 Session
            session_start();
            $this->started = true;
        }

        /**
         * 销毁当前会话（常用于用户登出）
         *
         * - 清空 $_SESSION 数组
         * - 删除服务器端 Session 文件
         * - 重置内部状态标记
         *
         * @return bool 成功返回 true，若 Session 未激活则返回 false
         */
        public function destroy()
        {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                return false;
            }

            // 清空所有 Session 数据
            $_SESSION = [];

            // 销毁服务器端 Session
            session_destroy();

            // 重置状态
            $this->started = false;

            return true;
        }

        /**
         * 重新生成 Session ID（防范 Session Fixation 攻击）
         *
         * 建议在用户登录成功后调用此方法。
         *
         * @param bool $deleteOld 是否删除旧的 Session 文件（默认 true）
         * @return void
         */
        public function regenerate_id($deleteOld = true)
        {
            if ($this->started) {
                session_regenerate_id($deleteOld);
            }
        }

        /**
         * 获取当前 Session ID
         *
         * 注意：必须在 Session 已启动后调用，否则返回空字符串。
         *
         * @return string 当前会话 ID（如 "abc123def456"）
         */
        public function get_id()
        {
            return session_id();
        }

        /**
         * 用户超过xx分钟没有活动，销毁
         *
         * @param int $expire_seconds :活动时间间隔
         *
         * */
        public function activity_destroy( int $expire_seconds )
        {
            // 每个页面顶部检查
            if (isset($_SESSION['maosiji_lu_last_activity']) && (time() - $_SESSION['maosiji_lu_last_activity']) > $expire_seconds) {
                $this->destroy();
                exit;
            }

            $_SESSION['maosiji_lu_last_activity'] = time(); // 更新活跃时间
        }



    }
}

/*
 * 若是 WordPress 程序，放入以下代码保证执行 session
 * */
//add_action('init', function () {
//    // 防止重复初始化（虽然 LUSession 是单例，但双重保险）
//    static $session_started = false;
//    if ($session_started) {
//        return;
//    }
//
//    // 启动安全 Session
//    LUSession::getInstance();
//
//    $session_started = true;
//}, 1);

