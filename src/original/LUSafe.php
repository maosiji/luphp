<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-09-30 12:26
 * update               :
 * project              : luphp
 */


namespace MAOSIJI\LU;

// 启动会话并设置时区
//session_start();
//date_default_timezone_set('Asia/Shanghai');
class LUSafe
{
    // 默认时间间隔（秒）
    const DEFAULT_TIME_INTERVAL = 3;
    const SESSION_KEY_PREFIX = 'lu_rate_limit_';

    /**
     * 构造函数
     */
    public function __construct()
    {

    }

    /**
     * 检查是否请求过于频繁（基于 Session 存储）
     *
     * @param int $timeDiff 时间间隔（秒），必须 >= 1
     * @param string $actionKey 动作标识符（如 'send_sms', 'submit_form'），不同操作独立限流
     * @param bool $autoExit 是否在触发限制时自动退出（输出 JSON 错误消息），为 false 时返回布尔值
     * @param string $errorMessage 自定义错误消息（仅当 $autoExit = true 时使用）
     * @return bool 如果 $autoExit = false，返回 true 表示允许，false 表示被限制
     */
    public function checkTooManyRequests(
        int $timeDiff = self::DEFAULT_TIME_INTERVAL,
        string $actionKey = '',
        bool $autoExit = true,
        string $errorMessage = ''
    ): bool {

        if ($timeDiff < 1) {
            $timeDiff = 3;
        }

        // 使用 LUSession 确保会话已启动
        LUSession::getInstance();

        // 生成唯一的 session 键名
        $sessionKey = $this->getSessionKey($actionKey);

        $currentTime = time();
        $lastTime = $_SESSION[$sessionKey] ?? 0;

        // 检查是否在限制期内
        if ($lastTime > 0 && ($currentTime - $lastTime) < $timeDiff) {
            if ($autoExit) {
                $this->sendRateLimitResponse($errorMessage);
            }
            return false;
        }

        // 更新最后请求时间
        $_SESSION[$sessionKey] = $currentTime;
        return true;
    }

/**
 * 获取当前请求的 Session 键名
 *
 * @param string $actionKey
 * @return string
 */
private function getSessionKey(string $actionKey): string
{
    $userId = $this->getUserId() ?: 'guest';
    $suffix = $actionKey ?: 'default';
    return self::SESSION_KEY_PREFIX . "{$userId}_{$suffix}";
}

/**
 * 获取用户标识（登录用户用用户ID，游客使用 Session ID）
 *
 * @return string
 */
private function getUserId(): string
{
    // 如果有全局用户认证系统，可在这里集成，例如：
    // if (function_exists('wp_get_current_user')) { ... }
    // 此处使用 LUSession 获取当前会话 ID
    return LUSession::getInstance()->get_id() ?: 'unknown';
}

/**
 * 发送频率限制的响应（JSON 格式 + 429 状态码）
 *
 * @param string $customMessage
 */
private function sendRateLimitResponse(string $customMessage = '')
{
    $message = $customMessage ?: '请求过于频繁，请稍后再试。';
    if (!headers_sent()) {
        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['error' => $message, 'code' => 429]);
    exit;
}

/**
 * 清除某个动作的频率限制记录（例如用户操作成功后重置）
 *
 * @param string $actionKey 动作标识符
 * @return
 */
public function clearLimit(string $actionKey = '')
{
    // 确保 Session 已启动
    LUSession::getInstance();
    $sessionKey = $this->getSessionKey($actionKey);
    unset($_SESSION[$sessionKey]);
}




}