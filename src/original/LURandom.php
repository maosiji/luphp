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

if (!class_exists('LURandom')) {
    class LURandom
    {
        /**
         * 构造函数
         */
        public function __construct()
        {
            // 可以在这里初始化必要的逻辑（如果需要）
        }

        /**
         * 防止克隆实例
         */
        private function __clone()
        {
        }


        /**
         * 生成指定长度的随机数字字符串
         *
         * @param int $length 随机数长度
         * @param bool $is_first_zero 首位是否可以为0，默认 true
         *
         * @return string 指定位数的随机数
         */
        public function rand_number(int $length = 6, bool $is_first_zero = true): string
        {
            $is_first_not = $is_first_zero ? '' : '0';
            $str = '';

            switch (mt_rand(0, 2)) {
                case 0:
                    $str .= $this->rand_number_by_mtrand($length);
                    break;
                case 1:
                    $str .= $this->rand_str_by_strshuffle($length, [
                        'type' => ['number'],
                        'is_first_not' => $is_first_not,
                    ]);
                    break;
                case 2:
                    $str .= $this->rand_str_by_shuffle($length, [
                        'type' => ['number'],
                        'is_first_not' => $is_first_not,
                    ]);
                    break;
            }

            return $str;
        }

        /**
         * 使用 mt_rand 生成随机奇数
         *
         * @return int 随机奇数
         */
        public function rand_odd(): int
        {
            return (mt_rand(0, 4) * 2) + 1;
        }

        /**
         * 使用 mt_rand 生成随机偶数
         *
         * @return int 随机偶数
         */
        public function rand_even(): int
        {
            return mt_rand(0, 4) * 2;
        }

        /**
         * 使用 mt_rand 生成指定长度的随机整数，首位永远不会是 0
         *
         * @param int $length 长度
         *
         * @return int 指定长度的随机整数
         */
        private function rand_number_by_mtrand(int $length = 6): int
        {
            return mt_rand(pow(10, $length - 1), pow(10, $length) - 1);
        }

        /**
         * 使用 str_shuffle 生成指定长度的随机字符串
         *
         * @param int $length 长度
         * @param array $params 参数配置
         *                      - type: 字符类型数组
         *                      - is_first_not: 首位不能包含的字符
         *                      - custom: 自定义字符
         *                      - custom_type: 自定义字符的添加方式 ('add', 'override')
         *
         * @return string 指定长度的随机字符串
         */
        private function rand_str_by_strshuffle(int $length = 6, array $params = []): string
        {
            $params = array_merge([
                'type' => ['lowercase', 'uppercase', 'number'],
                'is_first_not' => '',
                'custom' => '',
                'custom_type' => '',
            ], $params);

            $char_pool = $this->get_char_pool($params);
            $first_char = $this->get_first_char($char_pool, $params['is_first_not']);
            $remaining_length = $length - strlen($first_char);

            // 生成剩余部分的随机字符串
            $remaining_chars = substr(str_shuffle(str_repeat($char_pool, ceil($remaining_length / strlen($char_pool)))), 0, $remaining_length);

            return $first_char . $remaining_chars;
        }

        /**
         * 使用 shuffle 生成指定长度的随机字符串
         *
         * @param int $length 长度
         * @param array $params 参数配置
         *                      - type: 字符类型数组
         *                      - is_first_not: 首位不能包含的字符
         *                      - custom: 自定义字符
         *                      - custom_type: 自定义字符的添加方式 ('add', 'override')
         *
         * @return string 指定长度的随机字符串
         */
        private function rand_str_by_shuffle(int $length = 6, array $params = []): string
        {
            $params = array_merge([
                'type' => ['lowercase', 'uppercase', 'number'],
                'is_first_not' => '',
                'custom' => '',
                'custom_type' => '',
            ], $params);

            $char_pool = str_split($this->get_char_pool($params));
            $result = '';

            while (strlen($result) < $length) {
                if ($params['is_first_not'] !== '' && strlen($result) === 0) {
                    $filtered_chars = array_filter($char_pool, function ($char) use ($params) {
                        return strpos($params['is_first_not'], $char) === false;
                    });
                    shuffle($filtered_chars);
                    $result .= $filtered_chars[array_rand($filtered_chars)];
                } else {
                    shuffle($char_pool);
                    $result .= $char_pool[array_rand($char_pool)];
                }
            }

            return $result;
        }

        /**
         * 获取字符池
         *
         * @param array $params 参数配置
         *
         * @return string 字符池
         */
        private function get_char_pool(array $params): string
        {
            $char_pool = '';

            if (!empty($params['type'])) {
                $char_pool .= implode('', array_map(function ($type) {
                    return $this->get_str_map()[$type] ?? '';
                }, $params['type']));
            }

            if (!empty($params['custom'])) {
                if ($params['custom_type'] === 'add') {
                    $char_pool .= $params['custom'];
                } elseif ($params['custom_type'] === 'override') {
                    $char_pool = $params['custom'];
                }
            }

            return $char_pool;
        }

        /**
         * 获取首位字符
         *
         * @param string $char_pool 字符池
         * @param string $is_first_not 首位不能包含的字符
         *
         * @return string 首位字符
         */
        private function get_first_char(string $char_pool, string $is_first_not): string
        {
            if ($is_first_not !== '') {
                $filtered_chars = array_filter(str_split($char_pool), function ($char) use ($is_first_not) {
                    return strpos($is_first_not, $char) === false;
                });

                if (empty($filtered_chars)) {
                    throw new \InvalidArgumentException("No valid characters available for the first character.");
                }

                return $filtered_chars[array_rand($filtered_chars)];
            }

            return $char_pool[array_rand(str_split($char_pool))];
        }

        /**
         * 获取字符映射表
         *
         * @return array 字符映射表
         */
        private function get_str_map(): array
        {
            return [
                'lowercase' => 'abcdefghijklmnopqrstuvwxyz',
                'uppercase' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                'number' => '0123456789',
                'special' => '~!@#$%^&*_+?',
            ];
        }
    }
}