<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-26 01:19
 * update               : 
 * project              : luphp
 */

namespace MAOSIJI\LU;

use MAOSIJI\LU\EXCEPTION\LUVerifiableNumber19Exception;

/**
 * 生成可验证的 19 位数字（基于身份证校验原理，男奇女偶，无 X）
 * 结构：第1-16位（前缀+随机） + 第17位（性别位） + 第18-19位（校验码）
 */
class LUVerifiableNumber19
{
    // 17 位加权因子（与身份证一致）
    const WEIGHTS = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];

    // 余数 0~10 → 两位校验码
    const CHECK_MAP = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10'];

    // 存储反转的 CHECK_MAP
    const CHECK_MAP_ASSOC = [
        '00' => 0, '01' => 1, '02' => 2, '03' => 3, '04' => 4,
        '05' => 5, '06' => 6, '07' => 7, '08' => 8, '09' => 9,
        '10' => 10
    ];

    /**
     * 生成 19 位可验证数字
     *
     * @param string $prefix 自定义前缀（仅保留非零部分，长度应 ≤ 16）
     * @param bool $gender 性别：true = 男（奇数位），false = 女（偶数位）
     * @return string 19 位纯数字字符串
     * @throws LUVerifiableNumber19Exception
     */
    public function generate(string $prefix = '0755', bool $gender = false): string
    {
        // 前缀处理：去左侧零，保证至少 1 位非零
        $prefix = ltrim($prefix, '0');

        if ( !ctype_digit($prefix) ) {
            throw new LUVerifiableNumber19Exception('前缀必须为纯数字', LUVerifiableNumber19Exception::CODE_INVALID_PREFIX);
        }

        if ($prefix === '') {
            // 没有任何有效前缀：直接生成16位随机数（首位非0）
            $first16 = $this->_randomDigits(16, false);
        }
        // 有有效前缀，但不超过16位，后面补随机数字
        elseif (strlen($prefix) >= 16) {
            $first16 = substr($prefix, 0, 16);
        }
        else {
            $randomLen = 16 - strlen($prefix);
            $first16 = $prefix . $this->_randomDigits($randomLen, true);
        }

        // 性别位（第17位）：男奇女偶
            $sexDigit = $gender
                ? (string)($this->_secureRandomInt(0, 4) * 2 + 1)   // 奇数：1,3,5,7,9
                : (string)($this->_secureRandomInt(0, 4) * 2);       // 偶数：0,2,4,6,8

        // 完整主体（17位）
        $mainBody = $first16 . $sexDigit;

        // 计算校验码（两位）
        $checkCode = self::CHECK_MAP[$this->_calcMod11($mainBody)];

        return $mainBody . $checkCode;
    }

    /**
     * 验证 19 位数字是否合法（仅校验长度、数字、校验码）
     *
     * @param string $numberChar 待验证的 19 位数字
     * @return bool
     */
    public function verify(string $numberChar): bool
    {
        // 长度 + 全数字检查
        if (strlen($numberChar) !== 19 || !ctype_digit($numberChar)) {
            return false;
        }

        $mainBody = substr($numberChar, 0, 17);
        $givenCheck = substr($numberChar, 17, 2);

        // 校验码必须在 00~10 范围内
        // 这样更高效
        if ( !isset(self::CHECK_MAP_ASSOC[$givenCheck])) {
        //if (!in_array($givenCheck, self::CHECK_MAP, true)) {
            return false;
        }

        return $givenCheck === self::CHECK_MAP[$this->_calcMod11($mainBody)];
    }

    /**
     * 获取性别 true = 男（奇数位），false = 女（偶数位）
     * @param string $numberChar
     * @return bool
     * @throws LUVerifiableNumber19Exception 传入的19位号码不是有效字符串（长度不足/非全数字）
     */
    public function getGender(string $numberChar ): bool
    {
        if (!$this->verify($numberChar)) {
            throw new LUVerifiableNumber19Exception('传入的19位号码不是有效字符串（长度不足/非全数字）', LUVerifiableNumber19Exception::CODE_INVALID_NUMBER, null, $numberChar);
        }

        $sexDigit = (int)$numberChar[16]; // 第17位（下标16）
        return ($sexDigit % 2 === 1);
    }

    /**
     * 计算 17 位主体的加权模 11 余数 (0~10)
     */
    private function _calcMod11(string $digits17): int
    {
        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $sum += (int)$digits17[$i] * self::WEIGHTS[$i];
        }
        return $sum % 11;
    }

    /**
     * 生成指定长度的随机数字字符串（内部前导可为零）
     */
    private function _randomDigits(int $length, bool $allowLeadingZero = true): string
    {
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            if ($i === 0 && !$allowLeadingZero) {
                $result .= (string)$this->_secureRandomInt(1, 9);
            } else {
                $result .= (string)$this->_secureRandomInt(0, 9);
            }
        }
        return $result;
    }

    /**
     * 生成随机数
     * @param int $min
     * @param int $max
     * @return int
     * @throws LUVerifiableNumber19Exception 系统环境不支持安全随机数
     */
    private function _secureRandomInt(int $min, int $max): int
    {
        try {
            return random_int($min, $max);
        } catch (\Exception $e) {
            throw new LUVerifiableNumber19Exception('系统环境不支持安全随机数', LUVerifiableNumber19Exception::CODE_SYSTEM_ERROR);
        }
    }
}