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
use MAOSIJI\LU\EXCEPTION\LUIpException;

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
     *
     * @return string 合法 IP 地址
     * @throws LUIpException 无法获取有效 IP 时抛出
     */
    public function get_ip( array $server = null ): string
    {
        $ip = null;

        $server = $server ?? $_SERVER;

        // 优先检查 CDN 或反向代理传来的头信息
        if (!empty($server['HTTP_CF_CONNECTING_IP'])) {
            // Cloudflare 用户真实 IP
            $ip = $server['HTTP_CF_CONNECTING_IP'];
        }
        elseif (!empty($server['HTTP_X_REAL_IP'])) {
            // Nginx 反向代理设置的真实 IP
            $ip = $server['HTTP_X_REAL_IP'];
        }
        elseif (!empty($server['HTTP_X_FORWARDED_FOR'])) {
            // 代理服务器转发的原始 IP（可能包含多个，用逗号分隔）
            $ipList = explode(',', $server['HTTP_X_FORWARDED_FOR']);
            foreach ($ipList as $proxyIP) {
                $proxyIP = trim($proxyIP);
                if ($this->is_ip($proxyIP)) {
                    $ip = $proxyIP;
                    break;
                }
            }
        }
        elseif (!empty($server['HTTP_CLIENT_IP'])) {
            // 某些代理设置的客户端 IP
            $ip = $server['HTTP_CLIENT_IP'];
        }
        elseif (!empty($server['REMOTE_ADDR'])) {
            // 最后回退到服务器直接获取的 IP
            $ip = $server['REMOTE_ADDR'];
        }

        // 再次验证 IP 地址是否合法
        if ( $ip===null || !$this->is_ip($ip) ) {
            throw new LUIpException( '无法获取有效的客户端 IP 地址', LUIpException::CODE_GET_IP_FAILED );
        }

        return $ip;
    }

    /**
     * 判断是不是 IPV4 地址
     * @param string $ip
     * @return bool
     */
    public function is_ipv4( string $ip ): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * 判断是不是 IPV6 地址
     * @param string $ip
     * @return bool
     */
    public function is_ipv6( string $ip ): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * 判断是不是 IP 地址
     * @param string $ip
     * @return bool
     */
    public function is_ip( string $ip ): bool
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
    public function is_public_ip( string $ip ): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false;
    }





}