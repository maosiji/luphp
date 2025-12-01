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
if ( !class_exists( 'LUUrl' ) ) {
    class LUUrl
    {
        public function __construct()
        {
        }
        private function __clone()
        {
        }



        /**
         * 获取当前链接的域名部分
         * @param string|null $url
         * @return string
         */
        public function get_host( string $url=null ): string
        {
            if ( $url!==null ) {
                $host = parse_url( $url, PHP_URL_HOST );
            } else {
                $host = $_SERVER['HTTP_HOST'];
            }

            return $host;
        }

        /**
         * 获取当前链接
         *
         * @param bool $isFilterParams 是否过滤查询参数，默认为 false
         *                              - true：返回不带查询参数的链接
         *                              - false：返回完整的链接（包括查询参数）
         * @return string 当前链接
         */
        public function get( bool $isFilterParams = false ): string
        {
            // 获取协议（http 或 https）
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            ($_SERVER['SERVER_PORT'] ?? '') == '443' ? 'https://' : 'http://';

            // 获取主机名
            $host = $_SERVER['HTTP_HOST'] ?? '';

            // 获取请求路径
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $scriptName = $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? '';
            $queryString = $_SERVER['QUERY_STRING'] ?? '';

            // 构建相对路径
            if ($isFilterParams) {
                // 如果需要过滤查询参数，则去掉查询字符串部分
                $relateUrl = strtok($requestUri ?: $scriptName, '?');
            } else {
                // 否则保留完整的请求路径（包括查询字符串）
                $relateUrl = $requestUri ?: $scriptName . ($queryString ? '?' . $queryString : '');
            }

            // 拼接完整的 URL
            return $protocol . $host . $relateUrl;
        }

        /**
         * 向 URL 中添加或更新参数
         *
         * @param array $arr 要添加或更新的参数数组（键为参数名，值为参数值）
         * @param string $url 指定链接。为空，则默认当前链接
         * @return string 返回更新后的完整 URL
         */
        public function update_params( array $arr, string $url = '' ): string
        {
            // 如果输入的参数数组为空，直接返回原始 URL
            if (empty($arr)) {
                return $url ?: $this->get();
            }

            // 如果未指定 URL，则使用当前 URL
            $url = !empty($url) ? $url : $this->get();

            // 解析 URL 中的查询参数
            $query = parse_url($url, PHP_URL_QUERY);
            parse_str($query ?? '', $params);

            // 更新或添加参数
            foreach ($arr as $key => $value) {
                $params[$key] = $value;
            }

            // 构建新的查询字符串
            $newQuery = http_build_query($params);

            // 获取 URL 的基础部分（去掉查询字符串）
            $baseUrl = strtok($url, '?');

            // 返回更新后的完整 URL
            return $baseUrl . ($newQuery ? '?' . $newQuery : '');
        }

        /**
         * 从 URL 中删除指定参数
         *
         * @param array $arr 需要删除的参数名数组。为空，则返回原始链接
         * @param string $url 指定链接。为空，则默认当前链接
         * @return string 删除指定参数后的链接
         */
        public function delete_params( array $arr, string $url = '' ): string
        {
            // 如果需要删除的参数数组为空，直接返回原始链接
            if (empty($arr)) {
                return $url ?: $this->get();
            }

            // 如果未指定链接，则使用当前链接
            $url = !empty($url) ? $url : $this->get();

            // 解析 URL 中的查询参数
            $query = parse_url($url, PHP_URL_QUERY);
            parse_str($query ?? '', $params);

            // 删除参数
            foreach ($arr as $key=>$value) {
                unset($params[$key]);
            }

            // 构建新的查询字符串
            $newQuery = http_build_query($params);

            // 获取 URL 的基础部分（去掉查询字符串）
            $baseUrl = strtok($url, '?');

            // 返回更新后的完整 URL
            return $baseUrl . ($newQuery ? '?' . $newQuery : '');
        }

        /**
         * 判断当前访问或指定 URL 是否为 HTTPS
         * @param string|null $url 可选，指定要判断的 URL；若为空或协议相对，则按当前访问协议判断。如 传入//a.com，没有协议，则按照当前网站链接来判断。
         * @return bool
         */
        public function is_https( $url = null ) {

            if ($url !== null) {
                $parsed = parse_url($url);
                if (isset($parsed['scheme'])) {
                    return strtolower($parsed['scheme']) === 'https';
                }
                // 如果 URL 无协议（如 //example.com），按当前访问协议判断
                // fall through to current request check
            }

            // 判断当前访问是否为 HTTPS
            if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)) {
                return true;
            } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
                return true;
            } elseif (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && $_SERVER['HTTP_FRONT_END_HTTPS'] === 'on') {
                return true;
            }

            return false;
        }


    }
}