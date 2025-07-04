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
session_start();
date_default_timezone_set('Asia/Shanghai');
if (!class_exists('LUSafe')) {
    class LUSafe
    {
        // 默认时间间隔（秒）
        const DEFAULT_TIME_INTERVAL = 3;

        /**
         * 构造函数
         */
        public function __construct()
        {
            // 确保会话已启动
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
        }

        /**
         * 检查是否连续点击 AJAX 按钮，并禁止
         *
         * @param int $timediff 时间间隔（秒），默认 3 秒
         * @param string $errorMessage 自定义错误信息
         */
        public function check_too_many_requests(int $timediff = self::DEFAULT_TIME_INTERVAL)
        {
            // 检查 session 中是否有上一次请求的时间戳
            if (isset($_SESSION['last_request_time'])) {
                $currentTime = time();
                $timeDifference = $currentTime - $_SESSION['last_request_time'];

                // 如果时间差小于设定值，返回 429 Too Many Requests
                if ($timeDifference < $timediff) {
                    http_response_code(429);
                    exit;
                }
            }

            // 更新 session 中的请求时间
            $_SESSION['last_request_time'] = time();
        }

        /**
         * 发送错误响应
         *
         * @param int $statusCode HTTP 状态码
         * @param string $message 错误信息
         * @return
         */
//        private function sendErrorResponse(int $statusCode, string $message)
//        {
//            http_response_code($statusCode);
//            header('Content-Type: application/json');
//            echo json_encode(['error' => $message]);
//            exit;
//        }




    }
}