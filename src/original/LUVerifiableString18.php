<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-26 01:36
 * update               : 
 * project              : luphp
 */

namespace MAOSIJI\LU;

use MAOSIJI\LU\EXCEPTION\LUVerifiableNumber19Exception;
use MAOSIJI\LU\EXCEPTION\LUVerifiableString18Exception;

/**
 * 在 LUVerifiableNumber19 基础上生成可验证的 18 位字母串
 * 验证时还原为 19 位数字，直接复用数字类的验证方法
 */
class LUVerifiableString18
{
    /** @var array<int, string> 数字→字母映射 (0~10) */
    private $numberLetterMap;

    /** @var array<string, int> 字母→数字反向映射 */
    private $letterNumberMap;

    /** @var LUVerifiableNumber19 底层 19 位数字生成器 */
    private $numberGenerator;

    /**
     * @param array<int, string>|null $numberLetterMap 自定义映射，索引 0~10 的数组，每个值为单字符。
     *                                           默认：['o','a','b','c','d','e','f','g','h','i','j']
     * @throws \InvalidArgumentException 如果映射不合法
     */
    public function __construct( array $numberLetterMap = [] )
    {
        if ( empty($numberLetterMap) ) {
            $this->numberLetterMap = ['o', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j'];
        } else {
            $this->_validateLetterMap($numberLetterMap);
            $this->numberLetterMap = $numberLetterMap;
        }

        $this->letterNumberMap = array_flip($this->numberLetterMap);
        $this->numberGenerator = new LUVerifiableNumber19();
    }

    /**
     * 生成 18 位字母串
     *
     * @param string $prefix 自定义前缀（仅保留非零数字，长度应 ≤ 16）
     * @return string 18 位字母串
     * @throws LUVerifiableNumber19Exception
     */
    public function generate(string $prefix = '0755'): string
    {
        // 1. 生成 19 位数字
        $number19 = $this->numberGenerator->generate($prefix, mt_rand(0,1));

        // 2. 提取主体并计算校验余数
        $mainBody = substr($number19, 0, 17);
        $checkCode = substr($number19, 17, 2);
        $mod = (int) $checkCode;

        // 3. 映射为字母
        $letters = '';
        for ($i = 0; $i < 17; $i++) {
            $letters .= $this->numberLetterMap[(int) $mainBody[$i]];
        }
        $letters .= $this->numberLetterMap[$mod];

        return $letters;
    }

    /**
     * 验证 18 位字母串（内部还原为 19 位数字后复用数字验证器）
     *
     * @param string $letters 18 位字母串
     * @return bool
     */
    public function verify(string $letters): bool
    {
        // 长度检查
        if (strlen($letters) !== 18) {
            return false;
        }

        // 字符有效性检查（是否都在映射表中）
        for ($i = 0; $i < 18; $i++) {
            if (!isset($this->letterNumberMap[$letters[$i]])) {
                return false;
            }
        }

        // 分离主体字母与校验字母
        $bodyLetters = substr($letters, 0, 17);
        $checkLetter = $letters[17];

        // 主体 → 17 位数字串
        $mainBody = '';
        for ($i = 0; $i < 17; $i++) {
            $mainBody .= $this->letterNumberMap[$bodyLetters[$i]];
        }

        // 校验字母 → 余数 → 两位校验码
        $mod = $this->letterNumberMap[$checkLetter];
        $checkCode = str_pad($mod, 2, '0', STR_PAD_LEFT);

        // 复用数字验证器
        return $this->numberGenerator->verify($mainBody . $checkCode);
    }

    /**
     * 验证自定义映射数组是否合法
     *
     * @param array $map
     * @throws \InvalidArgumentException
     */
    private function _validateLetterMap(array $map)
    {
        if (count($map) !== 11) {
            throw new LUVerifiableString18Exception('映射数组必须包含 11 个元素，分别对应数字 0~10', LUVerifiableString18Exception::CODE_MAP_COUNT_INVALID, null, implode(',', $map));
        }

        $seen = [];
        for ($i = 0; $i < 11; $i++) {
            if (!isset($map[$i]) || strlen((string) $map[$i]) !== 1) {
                throw new LUVerifiableString18Exception(sprintf(
                    '映射数组必须按顺序包含 0~10 对应的单字符，索引 %d 无效', $i
                ), LUVerifiableString18Exception::CODE_MAP_DUPLICATE_CHAR, null , implode(',', $map));
            }
            if (isset($seen[$map[$i]])) {
                throw new LUVerifiableString18Exception(sprintf(
                    '映射中的字符 "%s" 重复', $map[$i]
                ), LUVerifiableString18Exception::CODE_INVALID_NUMBER, null, implode(',', $map));
            }
            $seen[$map[$i]] = true;
        }


    }


}