<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-04-23 14:28
 * update               :
 * project              : luphp
 */
namespace MAOSIJI\LU\WP;
if ( ! defined( 'ABSPATH' ) ) { die; }
if (!class_exists('LUWPArray')) {
    class LUWPArray
    {
        public function __construct()
        {
        }

        private function __clone()
        {
        }

        /**
         * 递归清理数组值中的html标签 sanitize_text_field
         * @param array $arr :要清理的数组，多维数组也可以
         * @return array
         */
        public function value_clean_html( array $arr ): array
        {
            if ( empty($arr) ) { return $arr; }

            $cleanedArray = [];

            foreach ($arr as $key => $value) {
                if ( is_array($value) ) {
                    // 如果是数组，递归处理
                    $cleanedArray[$key] = $this->value_clean_html($value);
                } else {
                    // 如果是字符串，去除 HTML 标签
                    $cleanedArray[$key] = sanitize_text_field($value);
                }
            }

            return $cleanedArray;
        }



    }
}