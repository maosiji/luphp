<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-07-25 16:40
 * update               : 
 * project              : luphp
 */

namespace MAOSIJI\LU;
if (!class_exists('LUIp')) {
    class LUIp
    {
        public function __construct()
        {
        }
        private function __clone()
        {
        }
        private function __wakeup()
        {
        }

        /**
         * 获取用户IP
         * @return array
         */
        public function get_visitor_ip()
        {
            $ip = '';
            $code = 1;
            $msg = '已获取到 IP';

            // 优先检查 CDN 或反向代理传来的头信息
            if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                // Cloudflare 用户真实 IP
                $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
            } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
                // Nginx 反向代理设置的真实 IP
                $ip = $_SERVER['HTTP_X_REAL_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                // 代理服务器转发的原始 IP（可能包含多个，用逗号分隔）
                $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($ipList as $proxyIP) {
                    $proxyIP = trim($proxyIP);
                    if (filter_var($proxyIP, FILTER_VALIDATE_IP)) {
                        $ip = $proxyIP;
                        break;
                    }
                }
            } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                // 某些代理设置的客户端 IP
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
                // 最后回退到服务器直接获取的 IP
                $ip = $_SERVER['REMOTE_ADDR'];
            } else {

                $code = 0;
                $msg = 'ip 地址未获取到';
                $data = $ip;
                $ip = 'Unknown';
            }

            // 再次验证 IP 地址是否合法
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {

                $code = 0;
                $msg = 'ip 地址不合法';
                $data = $ip;
                $ip = 'Unknown';
            }

            return (new LUSend())->send_array( $code, $msg, $code===0?$data:$ip );
        }





    }
}