<?php
namespace MAOSIJI\luphp;
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-12-06 14:33
 * update               : 
 * project              : library-luphp
 * description          : 生成可验证的19位数字
 */
if ( !class_exists('LUVerifiable19Digits') ) {
    class LUVerifiable19Digits {

        function __construct() {}

        /**
         * @param string $prefix    19位数字的前置自定义数字
         * @param int $pos          校验码1插入的位置
         * @param int $sex          默认 -1，无视。0 女，偶数；1，男，奇数。
         *
         * @return string           19位数字字符串，且第1位不为0
         */
        public function getNumber(string $prefix='0755', int $pos=1, int $sex=-1 ): string
        {
            $seventeen = $this->_create17Number( $prefix, $sex );
            $checkCode = $this->_getCheckCode( $seventeen, $pos );
            $checkCodeArr = str_split($checkCode);

            return substr_replace( $seventeen, $checkCodeArr[0], $pos, 0 ).$checkCodeArr[1];
        }

        /**
         * @param int $pos 取值范围 0-17
         * @param string $id19Number 19位数字
         * @return bool     验证 19 位数字是否符合规范
         */
        public function verifyNumber( string $id19Number, int $pos=1 ): bool
        {
            if ( strlen($id19Number) != 19 || $pos<=0 || $pos>17 ) {
                return false;
            }

            $arr = $this->_getNumberAndCheckCode( $id19Number, $pos );
            $num = $arr[1];
            $checkDigit = $arr[0];

            // 生成校验码
            $generatedCheckDigit = $this->_getCheckCode($num);

            // 比较生成的校验码和实际的校验码
            return $generatedCheckDigit === $checkDigit;
        }

        /**
         * @param string $prefix 17位数字中固定的前面的数字
         * @param int $sex          默认 -1，无视。0 女，偶数；1，男，奇数。
         *
         * @return string 创建17位数字
         */
        private function _create17Number( string $prefix='0755', int $sex=-1 ): string
        {
            $tool = new LURandom();
//            第1位不能为0
            $first = strtoupper(substr($prefix, 0, 1));

            $sexNum = $tool->getRandNumber(1);
            if ( $sex===0 ) { $sexNum = $tool->getRandEvenByMtRand(); }
            if ( $sex===1 ) { $sexNum = $tool->getRandOddByMtRand(); }

            $number17 = $tool->getRandNumber(3, false).$tool->getRandNumber(3).$tool->getRandNumber(3).$tool->getRandNumber(3).$tool->getRandNumber(4).$sexNum;

//            如果第 1 位为 0，则忽略，重新生成 17 位数字
            if ( $first==='0' ) {
                return $number17;
            } else {
                return strtoupper(substr($prefix.$number17, 0, 17));
            }
        }
        /**
         * @param string $id17Number 剩余的17位数字
         * @return string   校验码
         */
        private function _getCheckCode( string $id17Number ): string
        {
            // 加权因子
            $weights = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
            // 校验码对应表
            $checkDigits = ['01', '00', '10', '09', '08', '07', '06', '05', '04', '03', '02'];

            // 计算加权和
            $sum = 0;
            for ($i = 0; $i < 17; $i++) {
                $sum += intval($id17Number[$i]) * $weights[$i];
            }

            // 取模
            $mod = $sum % 11;

            // 返回校验码
            return $checkDigits[$mod];
        }

        /**
         * @param int $pos 第1个校验码的位置，取值范围 0-17
         * @param string $id19Number 19位数字
         * @return array 返回字符串数组 ['校验码', '剩余的17位数字']
         */
        private function _getNumberAndCheckCode( string $id19Number, int $pos=1 ): array
        {

//            数字段 1 2
            $num1 = strtoupper(substr($id19Number, 0, $pos));
            $num2 = strtoupper(substr($id19Number, $pos+1, 17-$pos));
            $num = $num1 . $num2;

//            校验码1
            $checkDigit1 = strtoupper(substr($id19Number, $pos, 1));
            // 提取最后一位 校验码2
            $checkDigit2 = strtoupper(substr($id19Number, -1, 1));
            $checkDigit = $checkDigit1 . $checkDigit2;

            return [$checkDigit, $num];
        }

    }
}