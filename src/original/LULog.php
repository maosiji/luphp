<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-07-25 01:05
 * update               : 
 * project              : luphp
 * official website     : xiaoyingyong.cn
 * official name        : 小应用商店
 * official email       : 1211806667@qq.com
 * official WeChat      : 1211806667
 * description          : 
 * read me              : 感谢您使用 小应用商店 的产品。您的支持，是我们最大的动力；您的反对，是我们最大的阻力
 * remind               ：使用盗版，存在风险；支持正版，将会有跟多的产品与您见面
 */

namespace MAOSIJI\LU;

class LULog
{
    public function __construct()
    {
    }
    private function __clone()
    {
    }

    /**
     * 写入日志文件（带时间戳）
     *
     * @param array|string $message 日志内容
     * @param string       $file    文件名（如 'lu.txt'）
     */
    public function print($message, string $file)
    {
        // 日志根目录：网站根目录/log/日期/
        $log_dir = $_SERVER['DOCUMENT_ROOT'] . "/log/" . date('Ymd') . "/";
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0777, true);
        }

        $file = $log_dir . $file;
        $timePrefix = '[' . date('Y-m-d H:i:s') . '] ';  // 统一的时间前缀

        if (is_array($message)) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if (strtolower($ext) === 'php') {
                // PHP 文件：保持可执行结构，时间信息以注释方式写入
                $content = "<?php // Logged at " . date('Y-m-d H:i:s') . "\n"
                    . "return " . var_export($message, true) . ";\n";
                error_log($content, 3, $file);
                return;
            }
            // 非 PHP 文件：前缀 + 导出的字符串
            error_log($timePrefix . var_export($message, true) . "\n", 3, $file);
        } else {
            // 字符串：直接加前缀
            error_log($timePrefix . $message . "\n\n", 3, $file);
        }
    }
}