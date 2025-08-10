<?php
namespace MAOSIJI\LU;
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-09-20 17:50
 * update               :
 * project              : luphp
 */
if (!class_exists('LUCurl')) {
    class LUCurl
    {
        const DEFAULT_HEADERS = [
            "Accept: application/json",
            "Content-type: application/json;charset=utf-8"
        ];

        public function __construct() {}

        private function __clone() {}

        /**
         * 合并或覆盖默认头信息
         *
         * @param array $defaultHeaders 默认头数组
         * @param array $newHeaders 新头数组
         * @param bool $overwrite 是否覆盖
         * @return array 合并后的头数组
         */
        private function getHeaderArray(array $defaultHeaders, array $newHeaders, bool $overwrite): array
        {
            return $overwrite ? $newHeaders : array_merge($defaultHeaders, $newHeaders);
        }

        /**
         * 发起 HTTP 请求
         *
         * @param string $method 请求方法 (GET, POST, PUT, DELETE, PATCH)
         * @param string $url 请求 URL
         * @param array $data 请求数据（仅对 POST, PUT, PATCH, DELETE 有效）
         * @param array $headers 自定义头信息
         * @param bool $overwrite 是否覆盖默认头信息
         * @param string $dataStr :请求字符串数据。当存在时，忽略 $data
         * @return array 返回解码后的响应数据
         */
        private function request(string $method, string $url, array $data = [], array $headers = [], bool $overwrite = false, string $dataStr=''): array
        {
            // 合并头信息
            $headerArray = $this->getHeaderArray(self::DEFAULT_HEADERS, $headers, $overwrite);

            // 初始化 cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);

            // 根据请求方法设置选项
            if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH' || $method === 'DELETE') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $dataStr ? $dataStr : json_encode($data) );
            }

            // 执行请求并关闭连接
            $output = curl_exec($ch);
            curl_close($ch);

            // 如果是json数据，则解析；否则，不解析
            if ( $this->isStrictJson($output) ) {
                // 返回解码后的 JSON 数据
                return json_decode($output, true) ?? [];
            } else {
                // 返回解码后的 JSON 数据
                return [$output] ?? [];
            }
        }

        /**
         * 发起 GET 请求
         *
         * @param string $url 请求 URL
         * @param array $headers 自定义头信息
         * @param bool $overwrite 是否覆盖默认头信息
         * @return array 解码后的响应数据
         */
        public function get(string $url, array $queryParams=[], array $headers = [], bool $overwrite = false): array
        {
            if ( !empty($queryParams) ) {
                $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . http_build_query($queryParams);
            }

            return $this->request('GET', $url, [], $headers, $overwrite);
        }

        /**
         * 发起 POST 请求
         *
         * @param string $url 请求 URL
         * @param array $data 请求数据
         * @param array $headers 自定义头信息
         * @param bool $overwrite 是否覆盖默认头信息
         * @param string $dataStr :请求字符串数据。当存在时，忽略 $data
         * @return array 解码后的响应数据
         */
        public function post(string $url, array $data, array $headers = [], bool $overwrite = false, string $dataStr=''): array
        {
            return $this->request('POST', $url, $data, $headers, $overwrite, $dataStr );
        }

        /**
         * 发起 PUT 请求
         *
         * @param string $url 请求 URL
         * @param array $data 请求数据
         * @param array $headers 自定义头信息
         * @param bool $overwrite 是否覆盖默认头信息
         * @return array 解码后的响应数据
         */
        public function put(string $url, array $data, array $headers = [], bool $overwrite = false): array
        {
            return $this->request('PUT', $url, $data, $headers, $overwrite);
        }

        /**
         * 发起 DELETE 请求
         *
         * @param string $url 请求 URL
         * @param array $data 请求数据
         * @param array $headers 自定义头信息
         * @param bool $overwrite 是否覆盖默认头信息
         * @return array 解码后的响应数据
         */
        public function delete(string $url, array $data = [], array $headers = [], bool $overwrite = false): array
        {
            return $this->request('DELETE', $url, $data, $headers, $overwrite);
        }

        /**
         * 发起 PATCH 请求
         *
         * @param string $url 请求 URL
         * @param array $data 请求数据
         * @param array $headers 自定义头信息
         * @param bool $overwrite 是否覆盖默认头信息
         * @return array 解码后的响应数据
         */
        public function patch(string $url, array $data, array $headers = [], bool $overwrite = false): array
        {
            return $this->request('PATCH', $url, $data, $headers, $overwrite);
        }

        private function isStrictJson($string) {
            // 确保输入是非空字符串
            if (!is_string($string) || trim($string) === '') {
                return false;
            }

            $decoded = json_decode($string);
            return $decoded !== null && json_last_error() === JSON_ERROR_NONE;
        }


    }
}