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

        /**
         * 判断一个 PHP 数组的类型，只处理 int、string 的 key
         *
         * @param array $arr 要判断的数组
         * @return string 返回数组类型，可能是：
         *                - '空数组' empty
         *                - '索引数组'（所有键都是整数）index
         *                - '关联数组'（所有键都是字符串）relate
         *                - '混合数组'（既有整数键，也有字符串键）mix
         */
        public function get_array_type( array $arr ): string {
            $count = count($arr);

            if ($count === 0) {
                return 'empty';
            }

            $keys = array_keys($arr);
            $hasIntKey = false;
            $hasStringKey = false;

            foreach ($keys as $key) {
                if (is_int($key)) {
                    $hasIntKey = true;
                } elseif (is_string($key)) {
                    $hasStringKey = true;
                } else {
                    // 极少情况：键可能是其他类型（如 float、bool），这里简化处理，可归为混合
                    $hasStringKey = true;
                }
            }

            if ($hasIntKey && !$hasStringKey) {
                return 'index';
            } elseif ($hasStringKey && !$hasIntKey) {
                return 'relate';
            } else {
                // 既有整数键，也有字符串键，或者有其他类型键
                return 'mix';
            }
        }

        /**
         * 检测多个数组的嵌套结构和每层元素个数是否完全一致（忽略值，只看键名、嵌套层级和每层元素个数）
         * 递归比较两个数组的结构（键、数量、嵌套），忽略值
         *
         * @param array ...$arrays 要比较的多个数组
         * @return bool 是否结构一致
         */
        public function arrays_structure_match(...$arrays) {
            if (count($arrays) < 2) {
                return true;
            }

            $reference = $arrays[0];
            $others = array_slice($arrays, 1);

            foreach ($others as $arr) {
                if (!$this->_compare_structure($reference, $arr)) {
                    return false;
                }
            }

            return true;
        }
        private function _compare_structure($a, $b) {
            // 如果不是数组，结构一致（我们不关心值）
            if (!is_array($a) || !is_array($b)) {
                // 都不是数组 → 结构一致
                // 一个是数组一个不是 → 结构不一致
                return is_array($a) === is_array($b);
            }

            // 数组长度不同 → 结构不同
            if (count($a) !== count($b)) {
                return false;
            }

            // 检查每个键是否存在，并递归比较子结构
            foreach ($a as $key => $value) {
                if (!array_key_exists($key, $b)) {
                    return false;
                }
                if (!$this->_compare_structure($value, $b[$key])) {
                    return false;
                }
            }

            return true;
        }

        /**
         * 比较多个数组的结构形状是否一致（忽略键名和值，只看嵌套层级和每层元素个数）
         * 递归比较两个值的结构形状是否一致
         *
         * @param mixed ...$arrays 要比较的多个数组或值
         * @return bool 所有数组结构形状是否一致
         */
        public function arrays_shape_match(...$arrays) {
            // 如果少于2个，视为一致
            if (count($arrays) < 2) {
                return true;
            }

            // 以第一个为基准
            $reference = array_shift($arrays);

            // 与其他每一个比较
            foreach ($arrays as $arr) {
                if (!$this->_compare_shape($reference, $arr)) {
                    return false;
                }
            }

            return true;
        }
        private function _compare_shape($a, $b) {
            // 如果都不是数组，结构视为一致
            if (!is_array($a) || !is_array($b)) {
                return is_array($a) === is_array($b);
            }

            // 数组元素个数不同 → 结构不同
            if (count($a) !== count($b)) {
                return false;
            }

            // 提取值，按顺序递归比较（忽略键名）
            $values_a = array_values($a);
            $values_b = array_values($b);

            foreach ($values_a as $index => $value_a) {
                $value_b = $values_b[$index] ?? null;

                if (!$this->_compare_shape($value_a, $value_b)) {
                    return false;
                }
            }

            return true;
        }

        /**
         * 根据数据结构和键名映射，生成占位符数组
         * 用途：数据库语句占位符数组的自动生成
         * 规则：
         *  - 纯数值键数组（索引数组）：子元素使用父键名映射
         *  - 含字符串键数组（关联数组）：子元素使用自己的键名映射
         *
         * @param array $data 源数据
         * @param array $typeMap 键名 → 占位符映射表
         * @param mixed $default 默认占位符
         * @return array 占位符数组
         */
        public function generate_placeholder_array( array $data, array $typeMap, $default = '%s' ) {
            $result = [];

            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    if ($this->is_numeric_key_array($value)) {
                        // ✅ 纯数值键数组：子元素继承当前 $key 的类型
                        $placeholder = $typeMap[$key] ?? $default;
                        $subArray = [];
                        foreach ($value as $_) {
                            if (is_array($_)) {
                                // 嵌套数组，递归处理
                                $subArray[] = $this->generate_placeholder_array($_, $typeMap, $default);
                            } else {
                                $subArray[] = $placeholder;
                            }
                        }
                        $result[] = $subArray;
                    } else {
                        // ✅ 关联数组（含字符串键）：递归处理，子项用自己的 key 查表
                        $result[] = $this->generate_placeholder_array($value, $typeMap, $default);
                    }
                } else {
                    // 叶子节点：使用自己的 key 查表
                    $result[] = $typeMap[$key] ?? $default;
                }
            }

            return $result;
        }

        /**
         * 判断数组是否是索引数组
         * @param $array
         * @return bool
         */
        public function is_numeric_key_array( array $array ) {
            if (!is_array($array)) return false;
            foreach (array_keys($array) as $key) {
                if (!is_int($key)) {
                    return false;
                }
            }
            return true;
        }

        /**
         * 获取数组的第一个 key （兼容 PHP 5.6+）
         *
         * @param array $array
         * @return mixed 第一个 key，如果数组为空则返回 null
         */
        public function get_first_key($array) {
            if (!is_array($array) || empty($array)) {
                return null;
            }

            // PHP 7.3+ 优先使用
            if (function_exists('array_key_first')) {
                return array_key_first($array);
            }

            // 老版本 fallback
            return key(reset($array));
        }



    }
}
