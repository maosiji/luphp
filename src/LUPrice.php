<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-09-20 17:50
 * update               :
 * project              : luphp
 */
namespace MAOSIJI\LUPHP\Price;

if (!class_exists('LUPrice')) {
    class LUPrice
    {
        /**
         * 构造函数
         */
        public function __construct()
        {
            // 初始化逻辑（如果需要）
        }
        private function __clone()
        {
        }
        private function __wakeup()
        {
        }

        /**
         * 格式化价格为两位小数的字符串格式
         *
         * @param mixed $price 输入的价格值
         * @return float 格式化后的价格（如：10.00）
         */
        public function format( $price ): float
        {
            // 检查输入是否为有效数字
            if (!is_numeric($price)) { $price = 0.00; }

            // 转换为浮点数并确保非负
            $price = max(0.00, (float)$price);

            // 确保最多保留两位小数，并向上取整到分
            $price = ceil($price * 100) / 100;

            // 格式化输出为两位小数的字符串
            return sprintf("%.2f", $price);
        }
    }
}