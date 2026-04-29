<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-12 04:06
 * update               : 
 * project              : luphp
 * description          : 服务器间 API 请求签名与验证工具类
 *                        支持参数排序、自定义分隔符、HMAC 签名及可配置的防重放机制。
 */

namespace MAOSIJI\LU;

use MAOSIJI\LU\EXCEPTION\LUApiSignerException;
use MAOSIJI\LU\EXCEPTION\LURandomException;

/**
 * Class LUApiSigner
 *
 * 用于生成和验证 API 请求签名的工具类。
 *
 * 主要功能：
 *   - 根据配置将请求参数排序并拼接为签名字符串。
 *   - 使用 HMAC 算法（依赖 LUHMAC 类）生成和验证签名。
 *   - 提供可选的防重放机制（timestamp 与 nonce），可配置化启用。
 *
 * 签名原文构造规则（默认）：
 *   1. 移除 sign 字段（可通过 ignore_keys 配置其他忽略字段）。
 *   2. 将剩余参数按键名 ASCII 升序排序（可通过 sort_type 自定义）。
 *   3. 拼接为 `key1=value1|key2=value2|...` 格式（分隔符可配置）。
 *   4. 若启用防重放，timestamp 和 nonce 将自动加入签名计算。
 *
 * 典型用法（启用防重放）：
 *   // 客户端
 *   $signer = new LUApiSigner('secret-key', 'sha256', [
 *       'use_timestamp' => true,
 *       'use_nonce'     => true,
 *   ]);
 *   $params = ['order_id' => '123', 'amount' => '100'];
 *   $params['sign'] = $signer->sign($params); // 自动附加 timestamp/nonce
 *
 *   // 服务端
 *   $signer = new LUApiSigner('secret-key', 'sha256', [
 *       'use_timestamp' => true,
 *       'use_nonce'     => true,
 *   ]);
 *   if ($signer->verify($_POST)) { // 自动校验时间戳
 *       // 验签通过，可选进一步校验 nonce 是否重复（需业务层实现）
 *          检查该 nonce 是否已被使用过：
 *          若未使用，则将其存入临时存储（如 Redis），并设置过期时间（与时间戳容忍度一致，如 300 秒）。
 *          若已存在，则判定为重放攻击，拒绝请求。
 *   }
 *
 * @package MAOSIJI\LU
 */
class LUApiSigner
{
    /**
     * 按键名升序排序（默认）
     */
    const SORT_KEY_ASC  = 'key_asc';

    /**
     * 按键名降序排序
     */
    const SORT_KEY_DESC = 'key_desc';

    /**
     * 按值升序排序
     */
    const SORT_VAL_ASC  = 'val_asc';

    /**
     * 按值降序排序
     */
    const SORT_VAL_DESC = 'val_desc';

    /**
     * @var LUHMAC HMAC 计算实例
     */
    private $hmac;

    /**
     * @var array 签名全局配置
     */
    private $options = [
        // 签名构造规则
        'sort_type'   => self::SORT_KEY_ASC, // 排序方式
        'separator'   => '|',                // 字段连接符
        'ignore_keys' => ['sign'],           // 签名时忽略的字段名

        // 防重放配置
        'use_timestamp'      => true,       // 是否启用时间戳防重放
        'use_nonce'          => true,       // 是否启用随机数防重放
        'timestamp_tolerance'=> 300,        // 时间戳容忍秒数（use_timestamp = true 时有效）
    ];

    /**
     * LUApiSigner 构造函数。
     *
     * @param string $secretKey   共享密钥，长度建议不低于 32 字节。
     * @param string $algo        哈希算法名称，如 'sha256'、'sha512'，默认 'sha256'。
     * @param array  $signOptions 可选的签名配置项，支持覆盖 sort_type, separator, ignore_keys,
     *                            use_timestamp, use_nonce, timestamp_tolerance。
     *
     * @throws \InvalidArgumentException 当密钥长度不足 32 字节时由 LUHMAC 抛出。
     */
    public function __construct( string $secretKey, string $algo = 'sha256', array $signOptions = [] )
    {
        $this->hmac = new LUHMAC($secretKey, $algo);
        $this->options = array_merge($this->options, $signOptions);
    }

    /**
     * 生成参数的 HMAC 签名（十六进制字符串格式）。
     *
     * 根据配置自动附加防重放字段（如果未提供）：
     *   - 若 $this->options['use_timestamp'] = true 且参数中无 timestamp，则添加当前时间戳。
     *   - 若 $this->options['use_nonce'] = true 且参数中无 nonce，则生成随机串。
     *
     * @param array $params 待签名的参数数组（可以包含 sign 字段，会被自动忽略）。
     *
     * @return array 包含签名的数组
     */
    public function sign( array $params ): array
    {
        // 根据配置自动附加防重放字段（不修改原数组，避免副作用）
        $params = $this->_autoAttachReplayProtection($params);

        $signString = $this->_buildPayload($params);
        $params['sign'] = $this->hmac->sign($signString);

        return $params;
    }

    /**
     * 验证签名是否正确。
     *
     * 若启用了防重放配置，将自动进行时间戳校验：
     *   - $this->options['use_timestamp'] = true 时，会调用 _verifyTimestamp() 检查时效性。
     *   - Nonce 的重复性校验需由业务层自行处理（例如存储到 Redis 并检查是否已使用）。
     *
     * 注意：内部使用 hash_equals 进行恒定时间比较，防止时序攻击。
     *
     * @param array       $params    包含 sign 字段的完整参数数组。
     * @param string|null $signature 待验证的签名字符串；若为 null，则从 $params['sign'] 获取。
     *
     * @return bool 签名有效且（若启用）时间戳有效返回 true，否则返回 false。
     */
    public function verify(array $params, $signature = null): bool
    {
        // 1. 防重放校验（时间戳）
        if (!empty($this->options['use_timestamp']) && !$this->_verifyTimestamp($params)) {
            return false;
        }
        // 注意：nonce 的重复校验建议由调用方基于 Redis 等实现，此处仅提供生成能力。

        // 2. 签名校验
        $clientSign = $signature ?? $params['sign'] ?? '';
        if (empty($clientSign)) {
            return false;
        }
        $signString = $this->_buildPayload($params);
        $expectedSign = $this->hmac->sign($signString);

        return $this->hmac->verify($expectedSign, $clientSign);
    }


    /**
     * 构造签名原文字符串。
     *
     * 生成规则：
     *   1. 根据 ignore_keys 移除指定字段。
     *   2. 按 sort_type 对剩余参数排序。
     *   3. 以 `key=value` 格式用 separator 连接。
     *
     * 注意：若启用防重放，timestamp 和 nonce 字段会在调用本方法前已存在于参数中。
     *
     * @param array $params 请求参数关联数组。
     *
     * @return string 用于 HMAC 计算的原始字符串。
     */
    private function _buildPayload( array $params ): string
    {
        // 移除忽略字段
        foreach ($this->options['ignore_keys'] as $key) {
            unset($params[$key]);
        }

        // 执行排序
        $params = $this->_sortParams($params);

        // 拼接为 key=value 并用分隔符连接
        // 对键和值进行原始编码（保留语义且防止注入）
        $encoded = [];
        foreach ($params as $k => $v) {
            $encoded[] = rawurlencode($k) . '=' . rawurlencode($v);
        }

        return implode($this->options['separator'], $encoded);
    }

    /**
     * 根据排序类型对参数进行排序。
     *
     * @param array           $params   待排序的参数数组。
     *
     * @return array 排序后的参数数组。
     *
     * @throws \InvalidArgumentException 当传入不支持的排序类型常量时。
     */
    private function _sortParams( array $params ): array
    {
        $sortType = $this->options['sort_type'];

        // 自定义回调处理
        if (is_callable($sortType)) {
            return call_user_func($sortType, $params);
        }

        switch ($sortType) {
            case self::SORT_KEY_ASC:
                ksort($params, SORT_STRING);
                break;
            case self::SORT_KEY_DESC:
                krsort($params, SORT_STRING);
                break;
            case self::SORT_VAL_ASC:
                asort($params, SORT_STRING);
                break;
            case self::SORT_VAL_DESC:
                arsort($params, SORT_STRING);
                break;
            default:
                throw new \InvalidArgumentException("不支持的排序类型: {$sortType}");
        }

        return $params;
    }

    /**
     * 根据配置自动附加防重放字段到参数副本中。
     *
     * @param array $params  原始参数。
     * @param array $options 当前生效的配置。
     *
     * @return array 附加字段后的参数副本。
     */
    private function _autoAttachReplayProtection( array $params ): array
    {
        $options = $this->options;

        if (!empty($options['use_timestamp']) && !isset($params['timestamp'])) {
            $params['timestamp'] = time();
        }
        if (!empty($options['use_nonce']) && !isset($params['nonce'])) {
            $params['nonce'] = $this->_generateNonce(16);
        }

        return $params;
    }

    // ---------- 公开的防重放辅助方法（亦可手动调用） ----------

    /**
     * 验证请求时间戳是否在允许的有效期内（防重放）。
     *
     * @param array $params    包含 timestamp 字段的参数数组。
     *
     * @return bool 时间戳有效时返回 true。
     */
    private function _verifyTimestamp(array $params): bool
    {
        $tolerance = $this->options['timestamp_tolerance'];

        // 缺少防重放时间戳 timestamp
        if (!isset($params['timestamp'])) {
            return false;
        }

        return abs(time() - (int)$params['timestamp']) <= $tolerance;
    }

    /**
     * 生成指定长度的随机十六进制字符串（用作 nonce）。
     *
     * @param int $length 期望的字符串长度（字节数），默认 16。
     *
     * @return string 十六进制随机字符串，实际长度为 $length。
     */
    private function _generateNonce( int $length = 16 ): string
    {
        try {
            $secureStr = (new LURandom())->generateSecureBytes($length / 2 );
        } catch ( LURandomException $e ) {
            throw new LUApiSignerException( '生成 nonce 失败：随机数不可用', LUApiSignerException::CODE_SYSTEM_ERROR, $e );
        }

        return $secureStr;
    }

}

//$secretKey = '共享密钥';
//$algo ='sha256';
//$signOptions = [
//    'sort_type'   => 'key_asc',          // 排序方式
//    'separator'   => '|',                // 字段连接符
//    'ignore_keys' => ['sign'],           // 签名时忽略的字段名
//
//    // 防重放配置
//    'use_timestamp'      => true,       // 是否启用时间戳防重放
//    'timestamp_tolerance'=> 300,        // 时间戳容忍秒数（use_timestamp = true 时有效）
//    'use_nonce'          => true,       // 是否启用随机数防重放
//];
//$apisigner = new LUApiSigner( $secretKey, $algo, $signOptions );
//
//$params = [
//    'a' => '要传递的数据',
//    'b' => '要传递的数据'
//];
//$sign = $apisigner->sign( $params );
//$params['sign'] = $sign;
//
// 完整的 body 参数
//$params = [
//    'id'            => 33423,
//    'time'          => 173839494, // 时间戳
//    'name'          => '姓名',
//    'phone'         => '13555555555',
//    'idcard'        => '56554554556652112',
//    'source'        => '',
//    'service_type'  => '',
//    'yb_card'       => '',
//    'description'   => '',
//    'note'          => '',
//    // 以下数据 认证 用
//    'sign'          => '计算好的签名',
//    'timestamp'     => '发送时间',  // 接收服务器判断，在五分钟内有效，保存数据；否则丢弃。
//    'nonce'         => '保证唯一性' // 生命周期为 五分钟。
//                                  // 若接收服务器在五分钟内收到多次一样的 nonce，
//                                  // 则只保存第一次数据，舍弃其他的。
//];

