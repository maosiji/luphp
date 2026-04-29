<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-04-19 21:03
 * update               :
 * project              : luphp
 */

namespace MAOSIJI\LU;

use MAOSIJI\LU\EXCEPTION\LUURLException;

class LUURL
{
    public function __construct() {}
    private function __clone() {}

    /**
     * 获取当前链接的域名部分，如 maosiji.com
     * @param string|null $url 若提供则解析该 URL，否则使用当前请求域名
     * @return string
     * @throws LUURLException 当传入非法 URL 时抛出
     */
    public function getHost($url = null): string
    {
        if ($url !== null) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host === false) {
                throw new LUURLException(
                    '输入的 URL 不合法',
                    LUURLException::CODE_INVALID_URL,
                    null,
                    $url
                );
            }
            return (string) $host;
        }

        return (string) ($_SERVER['HTTP_HOST'] ?? '');
    }

    /**
     * 获取当前完整 URL 或处理指定 URL
     * @param string|null $url 若为 null 则获取当前页面地址
     * @param bool $isFilterParams 是否去除查询参数
     * @return string
     * @throws LUURLException 当传入的 URL 非法且需要过滤参数时抛出
     */
    public function getURL($url = null, $isFilterParams = false): string
    {
        // 未指定 url 时，从当前环境构造
        if ($url === null) {
            $protocol = 'http://';
            if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (int) ($_SERVER['SERVER_PORT'] ?? 80) === 443) {
                $protocol = 'https://';
            }

            $host = $_SERVER['HTTP_HOST'] ?? '';
            // HTTP_HOST 未携带端口且为非标准端口时补全
            if ($host !== '' && strpos($host, ':') === false) {
                $port = (int) ($_SERVER['SERVER_PORT'] ?? 80);
                if (($protocol === 'https://' && $port !== 443) || ($protocol === 'http://' && $port !== 80)) {
                    $host .= ':' . $port;
                }
            }

            $requestUri  = $_SERVER['REQUEST_URI'] ?? '';
            $scriptName  = $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? '';
            $queryString = $_SERVER['QUERY_STRING'] ?? '';
            $relateUrl   = $requestUri !== '' ? $requestUri : $scriptName . ($queryString ? '?' . $queryString : '');

            $currentUrl = $protocol . $host . $relateUrl;
        } else {
            $currentUrl = $url;
        }

        // 需要过滤参数时，解析并重建
        if ($isFilterParams) {
            $parsed = $this->_parseUrlOrThrow($currentUrl);
            $scheme   = $parsed['scheme'] ?? 'http';
            $hostPart = $parsed['host'] ?? '';
            $portPart = isset($parsed['port']) ? ':' . $parsed['port'] : '';
            $pathPart = $parsed['path'] ?? '/';
            return $scheme . '://' . $hostPart . $portPart . $pathPart;
        }

        return $currentUrl;
    }

    /**
     * 向 URL 中添加或更新查询参数
     * @param array $params 要添加/更新的键值对
     * @param string|null $url 目标 URL，为空时使用当前 URL
     * @return string
     * @throws LUURLException 当最终 URL 非法时抛出
     */
    public function updateParams(array $params, $url = null): string
    {
        // 无操作参数，直接返回原 URL
        if (empty($params)) {
            return $url !== null ? $url : $this->getURL();
        }

        $url    = $this->_resolveUrl($url);
        $parsed = $this->_parseUrlOrThrow($url);

        // 合并现有参数
        $queryParams = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
        }
        $queryParams = array_merge($queryParams, $params);

        return $this->_buildUrlFromParsed($parsed, $queryParams);
    }

    /**
     * 从 URL 中删除指定的查询参数
     * @param array $keys 要删除的参数名数组
     * @param string|null $url 目标 URL，为空时使用当前 URL
     * @return string
     * @throws LUURLException 当最终 URL 非法时抛出
     */
    public function deleteParams(array $keys, $url = null): string
    {
        $url    = $this->_resolveUrl($url);
        $parsed = $this->_parseUrlOrThrow($url);

        $queryParams = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
        }

        foreach ($keys as $key) {
            unset($queryParams[$key]);
        }

        return $this->_buildUrlFromParsed($parsed, $queryParams);
    }

    /**
     * 判断指定 URL（或当前环境）是否为 HTTPS
     * @param string|null $url 若 URL 无协议则按当前环境判断
     * @return bool
     * @throws LUURLException 当传入明显非法的 URL 时抛出
     */
    public function isHTTPS($url = null): bool
    {
        if ($url !== null) {
            $parsed = parse_url($url);
            if ($parsed === false) {
                // 格式严重错误，不能静默处理，直接抛出
                throw new LUURLException(
                    '传入的 URL 不合法，无法判断协议',
                    LUURLException::CODE_INVALID_URL,
                    null,
                    $url
                );
            }
            if (isset($parsed['scheme'])) {
                return strtolower($parsed['scheme']) === 'https';
            }
            // 无 scheme（如 //a.com），继续检查当前环境
        }

        // 当前请求环境检测
        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) {
            return true;
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        if (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && $_SERVER['HTTP_FRONT_END_HTTPS'] === 'on') {
            return true;
        }

        return false;
    }

    // ==================== 私有辅助方法 ====================

    /**
     * 统一解析 URL，失败时抛出异常
     * @param string $url
     * @return array
     * @throws LUURLException
     */
    private function _parseUrlOrThrow(string $url): array
    {
        $parsed = parse_url($url);
        if ($parsed === false) {
            throw new LUURLException(
                '传入的 URL 不合法',
                LUURLException::CODE_INVALID_URL,
                null,
                $url
            );
        }
        return $parsed;
    }

    /**
     * 根据 parse_url 结果和查询参数重建完整 URL
     * @param array $parsed
     * @param array $queryParams
     * @return string
     */
    private function _buildUrlFromParsed(array $parsed, array $queryParams): string
    {
        $scheme   = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
        $host     = $parsed['host'] ?? '';
        $port     = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $user     = $parsed['user'] ?? '';
        $pass     = $parsed['pass'] ?? '';
        $auth     = ($user !== '' || $pass !== '') ? $user . ($pass !== '' ? ':' . $pass : '') . '@' : '';
        $path     = $parsed['path'] ?? '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
        $newQuery = http_build_query($queryParams);

        return $scheme . $auth . $host . $port . $path . ($newQuery !== '' ? '?' . $newQuery : '') . $fragment;
    }

    /**
     * 解析 url 参数：null 或空字符串时返回当前 URL，否则返回原值
     * @param string|null $url
     * @return string
     */
    private function _resolveUrl($url): string
    {
        if ($url === null || $url === '') {
            return $this->getURL();
        }
        return (string) $url;
    }
}