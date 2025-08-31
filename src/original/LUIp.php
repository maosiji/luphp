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

        /**
         * 获取用户IP
         * @return string
         */
        public function get_ip()
        {
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
//                $ip = 'ip 地址未获取到';
                $ip = NULL;
            }

            // 再次验证 IP 地址是否合法
            if (!$this->is_ip($ip)) {
//                $ip = 'ip 地址不合法';
                $ip = NULL;
            }

            return $ip;
        }

        /**
         * 判断是不是 IPV4 地址
         * @param string $ip
         * @return bool
         */
        public function is_ipv4(string $ip ): bool
        {
            return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
        }

        /**
         * 判断是不是 IPV6 地址
         * @param string $ip
         * @return bool
         */
        public function is_ipv6(string $ip ): bool
        {
            return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        }

        /**
         * 判断是不是 IP 地址
         * @param string $ip
         * @return bool
         */
        public function is_ip(string $ip ): bool
        {
            return filter_var($ip, FILTER_VALIDATE_IP) !== false;
        }

        /**
         * 判断是不是公网 IP 地址
         * @param string $ip
         * @return bool
         *
         * FILTER_FLAG_NO_PRIV_RANGE，排除私有地址（如 192.168.x.x, 10.x.x.x, 172.16-31.x.x）
         * FILTER_FLAG_NO_RES_RANGE，排除保留地址（如 127.x.x.x 回环，169.254.x.x 链路本地，0.0.0.0 等）
         */
        public function is_public_ip(string $ip ): bool
        {
            return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false;
        }





    }
}