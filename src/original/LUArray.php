<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-06-16 13:10
 * update               : 
 * project              : luphp
 */

namespace MAOSIJI\LU;
if ( !class_exists('LUArray') ) {
    class LUArray
    {
        public function __construct()
        {
        }

        /**
         * 限制数组最多保留指定数量的元素，超出部分根据 limit 正负值方向截取，并控制是否保留原始键名
         *
         * @param array $array 要处理的数组
         * @param int $limit 截取数量。大于 0 表示从头开始截取；小于 0 表示从尾开始截取，默认为 -10
         * @param bool $preserve_keys 是否保留原始键名，默认为 false（即重置索引）
         * @return array
         */
        public function slice( array $array, int $limit = -10, bool $preserve_keys = false ): array
        {
            $count = count($array);
            $absLimit = abs($limit);

            if ($count <= $absLimit) {
                return $preserve_keys ? $array : array_values($array);
            }

            if ($limit > 0) {
                $result = array_slice($array, 0, $absLimit, $preserve_keys);
            } else {
                $result = array_slice($array, -$absLimit, null, $preserve_keys);
            }

            return $preserve_keys ? $result : array_values($result);
        }

        /**
         * 数组的value是否包含某个值
         * @param array $array      :数组
         * @param $needle           :值
         * @param bool $is_strict   :模式，默认 false，都转为字符串比较，宽松模式。true，严格模式。
         * @return bool
         */
        public function is_contain( array $array, $needle, bool $is_strict=false ): bool
        {
            foreach ($array as $value) {
                if ( $is_strict ) {
                    if ( strpos($value, $needle) !== false ) {
                        return true;
                    }
                }
                else {
                    if (is_scalar($value) && strpos((string)$value, (string)$needle) !== false) {
                        return true;
                    }
                }
            }

            return false;
        }





    }
}
