<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-03-11 10:31
 * update               :
 * project              : luphp
 */
namespace MAOSIJI\LU;
if (!class_exists('LUIdcard')) {
    class LUIdcard
    {
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
         * 判断身份证是否合法
         *
         * @param string $idCard 身份证号码
         * @return bool 是否合法
         */
        public function is(string $idCard): bool
        {
            // 基本格式校验
            if (!preg_match('/^[\d]{17}[\dX]$/i', $idCard)) {
                return false;
            }

            // 提取关键信息
            $provinceCode = substr($idCard, 0, 2);
            $birthDate = substr($idCard, 6, 8);
            $sequenceCode = substr($idCard, 14, 3);
            $checkCode = strtoupper(substr($idCard, 17, 1));

            // 校验省份代码（11-82为有效行政区划代码）
            if ($provinceCode < '11' || $provinceCode > '82') {
                return false;
            }

            // 校验出生日期
            $year = substr($birthDate, 0, 4);
            $month = substr($birthDate, 4, 2);
            $day = substr($birthDate, 6, 2);
            if (!checkdate((int)$month, (int)$day, (int)$year)) {
                return false;
            }

            // 校验顺序码
            if (!ctype_digit($sequenceCode)) {
                return false; // 顺序码必须全为数字
            }

            // 校验码验证
            $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
            $map = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];

            $sum = 0;
            for ($i = 0; $i < 17; $i++) {
                $sum += (int)$idCard[$i] * $factor[$i];
            }

            return $map[$sum % 11] === $checkCode;
        }

        /**
         * 获取性别（1 男，2 女）
         *
         * @param string $idCard 身份证号码
         * @return int 性别（1 表示男性，2 表示女性）
         */
        public function sex(string $idCard): int
        {
            // 获取第17位数字
            $genderDigit = (int)substr($idCard, 16, 1);

            // 判断性别
            return ($genderDigit % 2 === 0) ? 2 : 1;
        }

        /**
         * 获取生日
         *
         * @param string $idCard 身份证号码
         * @return string 生日（格式：YYYY-MM-DD）
         */
        public function birthday(string $idCard): string
        {
            // 提取出生日期部分（第7位到第14位）
            $birthdayPart = substr($idCard, 6, 8);

            // 格式化输出
            $year = substr($birthdayPart, 0, 4);
            $month = substr($birthdayPart, 4, 2);
            $day = substr($birthdayPart, 6, 2);

            return "$year-$month-$day";
        }

        /**
         * 获取省份
         *
         * @param string $idCard 身份证号码
         * @return string 省份名称
         */
        public function province(string $idCard): string
        {
            // 地址码前两位表示省份
            $provinceCode = substr($idCard, 0, 2);

            // 省份编码映射表
            $provinceMap = [
                '11' => '北京市',
                '12' => '天津市',
                '13' => '河北省',
                '14' => '山西省',
                '15' => '内蒙古自治区',
                '21' => '辽宁省',
                '22' => '吉林省',
                '23' => '黑龙江省',
                '31' => '上海市',
                '32' => '江苏省',
                '33' => '浙江省',
                '34' => '安徽省',
                '35' => '福建省',
                '36' => '江西省',
                '37' => '山东省',
                '41' => '河南省',
                '42' => '湖北省',
                '43' => '湖南省',
                '44' => '广东省',
                '45' => '广西壮族自治区',
                '46' => '海南省',
                '50' => '重庆市',
                '51' => '四川省',
                '52' => '贵州省',
                '53' => '云南省',
                '54' => '西藏自治区',
                '61' => '陕西省',
                '62' => '甘肃省',
                '63' => '青海省',
                '64' => '宁夏回族自治区',
                '65' => '新疆维吾尔自治区',
                '71' => '台湾省',
                '81' => '香港特别行政区',
                '82' => '澳门特别行政区',
            ];

            // 返回省份名称，默认返回空字符串
            return $provinceMap[$provinceCode] ?? '';
        }

        /**
         * 获取城市
         *
         * @param string $idCard 身份证号码
         * @return string 城市名称
         */
        private function city(string $idCard): string
        {
            // 地址码前四位表示省市
            $cityCode = substr($idCard, 0, 4);

            // 城市编码映射表（示例，仅列出部分常见城市）
            $cityMap = [
                '1101' => '北京市市辖区',
                '1201' => '天津市市辖区',
                '1301' => '石家庄市',
                '1302' => '唐山市',
                '1401' => '太原市',
                '1501' => '呼和浩特市',
                '2101' => '沈阳市',
                '2201' => '长春市',
                '2301' => '哈尔滨市',
                '3101' => '上海市市辖区',
                '3201' => '南京市',
                '3301' => '杭州市',
                '3401' => '合肥市',
                '3501' => '福州市',
                '3601' => '南昌市',
                '3701' => '济南市',
                '4101' => '郑州市',
                '4201' => '武汉市',
                '4301' => '长沙市',
                '4401' => '广州市',
                '4501' => '南宁市',
                '4601' => '海口市',
                '5001' => '重庆市市辖区',
                '5101' => '成都市',
                '5201' => '贵阳市',
                '5301' => '昆明市',
                '5401' => '拉萨市',
                '6101' => '西安市',
                '6201' => '兰州市',
                '6301' => '西宁市',
                '6401' => '银川市',
                '6501' => '乌鲁木齐市',
            ];

            // 返回城市名称，默认返回空字符串
            return $cityMap[$cityCode] ?? '';
        }


    }
}