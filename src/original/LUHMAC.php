<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-12 03:05
 * update               : 
 * project              : luphp
 * description          :
 *
 *      HMAC（Hash-based Message Authentication Code）是一种基于密码学哈希函数的消息认证码（MAC）算法.
 *      通过一个共享密钥和哈希函数（如 SHA-256、MD5 等）生成一个固定长度的验证码，用于同时验证消息的完整性和身份认证
 *      完整性：确保消息在传输过程中未被篡改。
 *      认证性：只有持有相同密钥的通信双方才能生成或验证 HMAC。
 *
 *      密钥强度：密钥应为高质量随机数，长度不低于哈希输出长度（如 SHA-256 建议 32 字节）。
 *      哈希函数选择：避免使用已破解的哈希函数（如 MD5、SHA-1），推荐 SHA-256 或 SHA-3。
 *      密钥保密：HMAC 安全性完全依赖密钥的保密性。
 *      防止时序攻击：比较 HMAC 时应使用恒定时间比较函数（如 hash_equals）。
 */

namespace MAOSIJI\LU;

class LUHMAC {

    /**
     * @var string 共享密钥
     */
    private $key;

    /**
     * @var string 哈希算法名称（例如 'sha256', 'sha512'）
     */
    private $algo;

    /**
     * 构造函数
     *
     * @param string $key  共享密钥（建议长度 ≥ 32）
     * @param string $algo 哈希算法，默认为 'sha256'，可选 'sha512'
     *
     *          sha256，返回签名字符串为 64 字符
     *          sha512，返回签名字符串为 128 字符
     *
     * @throws \InvalidArgumentException 当密钥长度不足 16 字节时抛出
     */
    public function __construct( string $key, string $algo = 'sha256' )
    {
        if (strlen($key) < 32) {
            throw new \InvalidArgumentException('密钥长度至少为 32 字节');
        }
        $this->key  = $key;
        $this->algo = $algo;
    }

    /**
     * 生成数据的 HMAC 签名（十六进制字符串形式）
     *
     * @param string $data 待签名的原始数据
     *
     * @return string 十六进制格式的签名字符串
     */
    public function sign( string $data ): string
    {
        return hash_hmac($this->algo, $data, $this->key);
    }

    /**
     * 验证给定的签名（十六进制字符串形式）是否与数据匹配
     *
     * 使用 hash_equals() 进行恒定时间比较，防止时序攻击。
     *
     * @param string $data      原始数据
     * @param string $signature 待验证的签名（十六进制字符串）
     *
     * @return bool 签名有效返回 true，否则返回 false
     */
    public function verify( string $data, string $signature ): bool
    {
        $expected = $this->sign($data);
        return hash_equals($expected, $signature);
    }

    /**
     * 生成数据的 HMAC 签名（原始二进制形式）
     *
     * @param string $data 待签名的原始数据
     *
     * @return string 原始二进制签名字符串
     */
    public function signRaw( string $data ): string
    {
        return hash_hmac($this->algo, $data, $this->key, true);
    }

    /**
     * 验证给定的签名（原始二进制形式）是否与数据匹配
     *
     * 使用 hash_equals() 进行恒定时间比较，防止时序攻击。
     *
     * @param string $data      原始数据
     * @param string $signature 待验证的签名（原始二进制形式）
     *
     * @return bool 签名有效返回 true，否则返回 false
     */
    public function verifyRaw( string $data, string $signature ): bool
    {
        $expected = $this->signRaw($data);
        return hash_equals($expected, $signature);
    }


}