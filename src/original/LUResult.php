<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-13 13:34
 * update               : 
 * project              : luphp
 * description          : API 结果统一返回类。约定：code=0 表示成功，非0表示失败。
 */

namespace MAOSIJI\LU;
use JsonSerializable;

class LUResult implements JsonSerializable
{
    // ==================== 状态码常量 ====================
    const CODE_SUCCESS = 0;                             // 成功
    const CODE_ERROR = 1;                               // 通用错误（未分类）
    const CODE_INVALID_PARAM = 1001;                    // 参数错误
    const CODE_FORBIDDEN = 1002;                        // 权限不足
    const CODE_NOT_FOUND = 1003;                        // 资源不存在
    const CODE_THIRD_PARTY_ERROR = 1004;                // 第三方接口异常

    // ==================== 属性 ====================
    private $code;  // 状态码（0成功，非0失败）
    private $msg;   // 消息描述（成功/失败原因）
    private $data;  // 核心数据（成功时为业务数据，失败时可存错误详情）

    // ==================== 构造与工厂方法 ====================

    /**
     * 私有构造（通过静态方法创建实例）
     */
    private function __construct( int $code, string $msg, $data = null)
    {
        $this->code     = $code;
        $this->msg      = $msg;
        $this->data     = $data;
    }

    /**
     * 成功时创建结果
     * @param mixed $data 业务数据（可为任意类型）
     * @param string $msg 成功描述（默认：'操作成功'）
     * @return LUResult
     */
    public static function success( $data = null, string $msg = '操作成功' ): LUResult
    {
        return new self(self::CODE_SUCCESS, $msg, $data);
    }

    /**
     * 失败时创建结果
     * @param int $code 错误码（建议用类常量）
     * @param string $msg 错误描述（必填）
     * @param mixed $data 错误详情（可选）
     * @return LUResult
     */
    public static function error( int $code, string $msg, $data = null ): LUResult
    {
        return new self($code, $msg, $data);
    }

    /**
     * 将异常转为统一错误结果
     * @param \Throwable $e
     * @return LUResult
     */
    public static function exception(\Throwable $e): LUResult {
        return self::error($e->getCode() ?: self::CODE_ERROR, $e->getMessage());
    }

    /**
     * 输出 JSON，并终止程序
     */
    public function sendJson()
    {
        header('Content-Type: application/json');
        echo $this->toJson();
        exit();
    }

    // ==================== 便捷判断与访问方法 ====================

    /** 是否为成功状态 */
    public function isSuccess(): int
    {
        return $this->code === self::CODE_SUCCESS;
    }

    /** 是否为失败状态 */
    public function isError(): int
    {
        return !$this->isSuccess();
    }

    /** 获取状态码 */
    public function getCode(): int
    {
        return $this->code;
    }

    /** 获取消息描述 */
    public function getMsg(): string
    {
        return $this->msg;
    }

    /** 获取核心数据（成功时） */
    public function getData()
    {
        return $this->data;
    }

    /** 获取错误详情（失败时） */
    public function getErrorData()
    {
        if ($this->isError()) {
            return $this->data;
        }
        return null;
    }

    // ==================== 扩展方法 ====================

    /**
     * 链式设置数据（不可变设计）
     */
    public function withData($data): LUResult
    {
        $clone = clone $this;
        $clone->data = $data;
        return $clone;
    }

    /**
     * 链式设置消息（不可变设计）
     */
    public function withMsg($msg): LUResult
    {
        $clone = clone $this;
        $clone->msg = $msg;
        return $clone;
    }

    // ==================== 序列化支持 ====================

    /**
     * 实现 JsonSerializable 接口
     */
    public function jsonSerialize()
    {
        return array(
            'code' => $this->code,
            'msg' => $this->msg,
            'data' => $this->data,
        );
    }

    /**
     * 转为 JSON 字符串
     */
    public function toJson()
    {
        return json_encode($this, JSON_UNESCAPED_UNICODE);
    }

    // ==================== 安全加固 ====================
    private function __clone()
    {
    }


}