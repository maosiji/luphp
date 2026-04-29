<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-04 19:54
 * update               :
 * project              : luphp
 */

namespace MAOSIJI\LU\WP;

if ( ! defined( 'ABSPATH' ) ) { die; }
class LUWPSafe
{
    function __construct()
    {

    }
    private function __clone()
    {
    }

    // 默认时间间隔（秒）
    const DEFAULT_TIME_INTERVAL = 3;

    /**
     * 检查请求频率，支持独立操作限流、自定义错误，双模式输出
     *
     * @param int    $timediff     冷却时间（秒），默认 3
     * @param bool   $auto__bail    超限时是否自动终止（默认 true）
     *                            true  => 直接终止（无返回）
     *                            false => 返回 bool（true=受限，false=放行）
     * @param string $action_key   操作标识（如 'send_sms', 'submit_form'），用于不同操作独立限流
     *                             留空则全局共用限流
     * @param string $error_message 自动终止时的自定义错误消息（支持 HTML）
     *                             仅当 $auto__bail = true 时使用
     *
     * @return bool|null           $auto__bail=false 时返回 bool，否则无返回
     */
    public function checkTooManyRequests(
        int $timediff     = self::DEFAULT_TIME_INTERVAL,
        bool $auto__bail    = true,
        string $action_key   = '',
        string $error_message = ''
    ) {
        $identifier    = $this->_getIdentifier();
        $transient_key = 'luwpsafe_rate_' . md5( $identifier . $action_key );

        if ( get_transient( $transient_key ) ) {
            if ( $auto__bail ) {
                $this->_bail( $error_message );
            }
            return true;
        }

        set_transient( $transient_key, 1, (int) $timediff );
        return false;
    }

    private function _getIdentifier(): string
    {
        $user_id = get_current_user_id();
        if ( $user_id ) {
            return 'uid_' . $user_id;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        return 'ip_' . md5( $ip . $ua );
    }

    /**
     * 终止请求
     *
     * @param string $message 自定义错误消息，为空时使用默认提示
     */
    private function _bail( string $message = '' )
    {
        $default_message = 'Too Many Requests. Please slow down.';

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            wp_send_json_error( [
                'message' => $message ?: $default_message,
                'code'    => 'rate_limit',
            ], 429 );
            exit;
        }

        wp_die(
            $message ?: $default_message,
            'Rate Limit Exceeded',
            [ 'response' => 429 ]
        );
    }

}