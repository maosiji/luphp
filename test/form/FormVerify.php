<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-12-05 20:41
 * update               :
 * project              : luphp
 */
namespace MAOSIJI\LU;
use WUTI\XYYSD\XYYSDNonce;
if ( !class_exists('FormVerify') ) {
    class FormVerify
    {
        public function __construct()
        {

        }

        private static $notify = array(
            'nonce-error'   => '安全验证失败，请刷新页面重试！',
//            'sex-null'      => '性别必须填写',
//            'sex-error'     => '性别填写错误，请刷新页面重试！',
//            'edu-null'      => '学历必须填写',
//            'edu-error'     => '学历填写错误，请刷新页面重试！',
            'branch-error'  => 'FV001 安全验证失败，请刷新页面重试！',
            'value-error'   => 'FV002 安全验证失败，请刷新页面重试！',
            'autoload-error'   => 'FV003 安全验证失败，请刷新页面重试！',
            'state-error'  => 'FV004 安全验证失败，请刷新页面重试！',
//            'success'       => '请复制保存，系统不会记录',
//            'selfcom-null'  => '所属企业必须填写',
//            'selfcom-error' => '所属企业填写错误，请刷新页面重试！',
//            'com-null'      => '服务企业必须填写',
//            'com-error'     => '服务企业填写错误，请刷新页面重试！',
//            'com_com-null'  => '服务项目必须填写',
//            'com_com-error' => '服务项目填写错误，请刷新页面重试！',
        );

        /**临时 返回数组
         * @param $code
         * @param $msg
         * @param $data
         * @return array
         */
        private function linshiArr( $code, $msg, $data='' )
        {
            return array( 'code'=>$code, 'msg'=>$msg, 'data'=>$data );
        }

        private function submitAttrVerify( string $namespace='' ): array
        {
            $nonce              = isset($_POST['xyysd-nonce']) ? sanitize_text_field( $_POST['xyysd-nonce'] ) : '';
            $branch             = isset($_POST['xyysd-branch']) ? sanitize_text_field( $_POST['xyysd-branch'] ) : '';
            $node               = isset($_POST['xyysd-node']) ? sanitize_text_field( $_POST['xyysd-node'] ) : '';
            $state              = isset($_POST['xyysd-state']) ? sanitize_text_field( $_POST['xyysd-state'] ) : '';
            $branchClassName    = $branch;
            $branchClass        = $namespace.$branch;
            $isEnc              = false;

            if ( empty($namespace) ) {

                $branch             = xyysh_form_convert_get($branch, 'branch');
                $node               = xyysh_form_convert_get($node, 'node');
                $state              = xyysh_form_convert_get($state, 'state');
                // 有没有命名空间都可正常使用 去除 命名空间，只剩下 类名
                $branchClassName    = basename(str_replace('\\', '/', $branch));
                $branchClass        = $branch;
                $isEnc              = true;
            }

            return array(
                'nonce'             => $nonce,
                'branch'            => $branch,
                'node'              => $node,
                'state'             => $state,
                'branchClass'       => $branchClass,
                'branchClassName'   => $branchClassName,
                'isEnc'             => $isEnc,
            );
        }

        /**自动验证并返回数组
         * @param string $namespace :默认空。不填写，则是加密提交；填写，则是非加密提交。验证数据用到的From类的命名空间，只写命名空间，不写类名；注意前后写\。如：\WUTI\XYYSD\SHANHU\
         * @return array
         */
        public function autoVerify( string $namespace='' ): array
        {
            header('Content-type:application/json; Charset=utf-8');
            date_default_timezone_set('Asia/Shanghai');

            $submitAttr = $this->submitAttrVerify($namespace);
            $branch = $submitAttr['branch'] ?? '';
            $branchClass = $submitAttr['branchClass'] ?? '';
            $branchClassName = $submitAttr['branchClassName'] ?? '';
            $node = $submitAttr['node'] ?? 'main';
            $state = $submitAttr['state'] ?? '';
            $isEnc = $submitAttr['isEnc'] ?? true;
            $nonce = $submitAttr['nonce'] ?? '';

//            return $this->linshiArr(0, $branchClass, '');

            if ( empty($branchClassName) || empty($branchClass) ) {
                return $this->linshiArr(0, self::$notify['branch-error'], $branchClass);
            }
            if ( empty($state) ) {
                return $this->linshiArr(0, self::$notify['state-error']);
            }

            if ( !(new XYYSDNonce())->verifyNonce($nonce, $branch.'-'.$node.'-'.$state) ) {
                return $this->linshiArr(0, self::$notify['nonce-error'], '');
            }

            $event = isset($_POST['xyysd-event']) ? sanitize_text_field( $_POST['xyysd-event'] ) : '';

            $key = isset($_POST['xyysd-key']) ? sanitize_text_field( $_POST['xyysd-key'] ) : '';
            $value = isset($_POST['xyysd-value']) ? sanitize_text_field( $_POST['xyysd-value'] ) : '';
            $operate = isset($_POST['xyysd-operate']) ? sanitize_text_field( $_POST['xyysd-operate'] ) : '';

            $popup = isset($_POST['xyysd-popup']) ? sanitize_text_field( $_POST['xyysd-popup'] ) : '';

            $submitData = array(
                'xyysd-branchClass' =>$branchClass,
                'xyysd-branch'      =>$branchClassName,
                'xyysd-node'        =>$node,
                'xyysd-state'       =>$state,
                'xyysd-isEnc'       =>$isEnc,
                'xyysd-event'       =>$event,
                'xyysd-key'         =>$key,
                'xyysd-value'       =>$value,
                'xyysd-operate'     =>$operate,
                'xyysd-popup'       =>$popup,
            );

            // 当 state 不为 create 时，
            if ( $state!=='create' ) {
                if ( $event ) {
                    return $this->linshiArr(1, 'event 验证通过', $submitData);
                }
            }

//            $autoload = isset($_POST['autoload']) ? sanitize_text_field( $_POST['autoload'] ) : 0;
//            if ( $autoload==='' ) {
//                return $this->linshiArr(1, 'autoload 验证通过', $submitData);
//            }

//            return $this->linshiArr(0, '测试', '\\'.$namespace.'\\'.$branch);

            // branch / node 根据这两个选项来获取原始数据
            $formData = array();
            $formData = FormRoute::getData( new $branchClass(), $state, $node, $event, $isEnc );

//            return $this->linshiArr(0, '测试', $formData);

            // 确定按钮 的 name
            // 如果确定按里 有 lu-verifies，它的等级高于 lu-admin-no-verify 、lu-admin-no
            $level = 0;
            $name = isset($_POST['name']) && $_POST['name']!='' ? sanitize_text_field($_POST['name']) : '';
            // 如果有 lu-verifies，则重新生成 formData
            if ( !empty($name) && isset($formData[$name]['attr']['lu-verifies']) && !empty($formData[$name]['attr']['lu-verifies']) ) {
                $luVerifies = $formData[$name]['attr']['lu-verifies'];
                $luVerifies = explode(',',$luVerifies);
                $flippedKeys = array_flip($luVerifies);
                $formData = array_intersect_key($formData, $flippedKeys);
                $level = 1;
            }

//            return $this->linshiArr(0, 'bbbbb', $formData);

            if ( !empty($formData) ) {
                foreach ( $formData as $key=>$value ) {

                    // 提交按钮不验证
                    if ( isset($value['element']) && $value['element']!=='submit' ) {

                        $attr = isset($value['attr']) ? $value['attr'] : '';

                        if ( $value['type']==='html' ) {
                            $arg = isset($_POST[$key]) ? esc_html( $_POST[$key] ) : '';
                        }
                        else {
                            $arg = isset($_POST[$key]) ? esc_sql( $_POST[$key] ) : '';
                        }

                        // 后端不验证，但是添加到数据里
                        if ( isset($value['attr']['lu-admin-no-verify']) && !$level ) {
                            $submitData[$key] = $arg;
                            continue;
                        }

                        // 后端不验证，也不添加数据
                        if ( isset($value['attr']['lu-admin-no']) && !$level ) {
                            continue;
                        }

                        // 图片处理
                        if ( $value['element']==='input' && $attr['type']==='file' ) {
                            $arg = isset($_FILES[$key]) ? count($_FILES[$key]['name']) : 0;

//                            return $this->linshiArr(0, 'ccccc', $arg );
                        }

                        $luVerifyKey = isset($attr['lu-verify-key']) ? $attr['lu-verify-key'] : '';
                        $luVerifyValue = isset($attr['lu-verify-value']) ? $attr['lu-verify-value'] : '';
                        $luVerifyMessage = isset($attr['lu-verify-message']) ? $attr['lu-verify-message'] : '';

                        // 验证 lu-verify-key 是否是 username email 等关键字
                        $msg = $this->verify_key($luVerifyKey);
                        if ( $msg ) {
                            if ( !$this->verify_key( $luVerifyKey, $luVerifyValue ) ) {
                                return $this->linshiArr(0, $value['title'].$msg);
                            }
                        }

                        // 如果有选择的 values，即 checkbox、radio、select，判断选中的值是否在给出的值数组中，如果没有，则判定前端修改了数据
                        if ( !empty($value['values']) && !array_key_exists($arg, $value['values']) ) {
                            return $this->linshiArr(0, self::$notify['value-error'], $value['values']);
                        }

//                        验证 lu-verify-key 是否包含比较运算符
                        if ( $this->have_comparison_operator($luVerifyKey) ) {

                            $luVerifyKeyArr = (strpos($luVerifyKey, '|') !== false) ? explode('|', $luVerifyKey) : array($luVerifyKey);
                            $luVerifyValueArr = (strpos($luVerifyValue, '|') !== false) ? explode('|', $luVerifyValue) : array($luVerifyValue);

                            if ( count($luVerifyKeyArr)!==count($luVerifyValueArr) ) {
                                return $this->linshiArr(0, '<span class="lu-color-red">'.$value['title'].'</span>请刷新页面。');
                            }

//                            return $this->linshiArr(0, '验证通过'.$arg, $luVerifyValueArr);

                            if ( !empty($luVerifyKeyArr) ) {
                                foreach ( $luVerifyKeyArr as $k=>$v ) {

                                    $luVerifyKeyArrArr = (strpos($luVerifyValueArr[$k], ',') !== false) ? explode(',', $luVerifyValueArr[$k]) : array($luVerifyValueArr[$k]);

                                    if ( !empty($luVerifyKeyArrArr) ) {
                                        foreach ( $luVerifyKeyArrArr as $lvvaa ) {
                                            if ( $attr['lu-action']==='get_string_length' ) {
                                                $arglength = strlen($arg);
                                                $luVerifyStatus = $this->lu_verify( $arglength, $v, trim($lvvaa) );
                                            } else {
                                                $luVerifyStatus = $this->lu_verify( $arg, $v, trim($lvvaa) );
                                            }
                                            if ( !$luVerifyStatus ) {
                                                return $this->linshiArr(0, '<span class="lu-color-red">'.$value['title'].'</span>'.$luVerifyMessage);
                                            }
                                        }
                                    }

                                }
                            }
                        }

                        // 更改 com com_com 的值，使其符合传入数据库
                        if ( $value['element']==='select' && strpos($arg, '-') ) {
                            $argArr = explode('-', $arg );
                            $arg = end($argArr);
                        }

                        $submitData[$key] = $arg;
                    }
                }
            }

            return $this->linshiArr(1, '验证通过', $submitData);
        }

        /**验证 lu-verify-key 是否为保留字
         * @param string $type
         * @param string $op
         * @return false|int|string
         */
        private function verify_key(string $type, string $op='' )
        {
            switch ($type) {
                case 'username':
                    return $op ? preg_match('/^[a-zA-Z][\da-zA-Z]{5,19}$/', $op) : '由 6 到 20 位字母或数字组成，且第一位不能是数字。';
                case 'password':
                    return $op ? preg_match('/^(?=.*\d)(?=.*[a-zA-Z])(?=.*[~!@#$%^&*])[\da-zA-Z~!@#$%^&*]{6,20}$/', $op) : '由 6 到 20 位至少包含一次的字母、数字、特殊符号（!@#$%^&*）组成，';
                case 'email':
                    return $op ? preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $op) : '邮箱格式不正确！';
                case 'nickname':
                    return $op ? preg_match('/^[a-zA-Z0-9\u4e00-\u9fa5]+$/u', $op) : '由 字母 或 数字 或 汉字 组成';
                case 'phone':
                    return $op ? preg_match('/^1[3-9]\d{9}$/', $op) : '手机号格式不正确！';
                default :
                    return false;
            }
        }

        /**验证比较运算符
         * @param $op
         * @return bool
         */
        private function have_comparison_operator( string $op )
        {
            $operators = ['==', '===', '!=', '!==', '>', '<', '>=', '<='];
            foreach ($operators as $operator) {
                if (strpos($op, $operator) !== false) {
                    return true;
                }
            }

            return false;
        }

        /* 验证
         * lu-verify-key        一个值
         * lu-verify-value      一个值
         * */
        private function lu_verify($v, $operator, $compareValue) {

            if ( empty($operator) ) { return false; }

            switch ($operator) {
                case '==':
                    return $v == $compareValue;
                case '===':
                    return $v === $compareValue;
                case '!=':
                    return $v != $compareValue;
                case '!==':
                    return $v !== $compareValue;
                case '>':
                    return $v > $compareValue;
                case '<':
                    return $v < $compareValue;
                case '>=':
                    return $v >= $compareValue;
                case '<=':
                    return $v <= $compareValue;
                default:
                    return false;
            }
        }



    }

}