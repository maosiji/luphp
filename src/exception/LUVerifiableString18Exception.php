<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-27 12:10
 * update               : 
 * project              : luphp
 */

namespace MAOSIJI\LU\EXCEPTION;

class LUVerifiableString18Exception extends LUBaseException
{
    /**
     * 映射数组必须包含 11 个元素，分别对应数字 0~10
     * */
    const CODE_MAP_COUNT_INVALID = 141100;

    /**
     * 映射数组必须按顺序包含 0~10 对应的单字符
     * */
    const CODE_MAP_DUPLICATE_CHAR = 141101;

    /**
     * 映射中的字符不可重复
     * */
    const CODE_INVALID_NUMBER = 141102;

    private $numberLetterMap;

    public function __construct(
        string     $message = '随机字母生成失败',
        int        $code = 0,
        \Throwable $previous = null,
        string     $numberLetterMap = ''
    )
    {
        $this->numberLetterMap = $numberLetterMap;
        parent::__construct($message, $code, $previous);
    }

    public function getNumberLetterMap(): string
    {
        return $this->numberLetterMap;
    }

    public function getLogContext(): array
    {
        return array_merge(
            parent::getLogContext(),
            [
                'numberLetterMap' => $this->numberLetterMap,
            ]
        );
    }
}