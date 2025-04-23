<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-02-28 12:16
 * update               : 
 * project              : luphp
 */
namespace MAOSIJI\LU;
if ( !class_exists('FormRoute') ) {
    class FormRoute
    {
        function __construct()
        {

        }

        /** 获取表单条目数组
         * @param FormData $instance        :实现 FormData 的 Form 实例
         * @param string $node              :Form 实例中要使用的方法用的参数
         * @param string $state             :Form 实例中要使用的方法用的参数 write（默认）/ modify / view
         * @param string $event             :Form 实例中要使用的方法用的参数，可以自定义，可以指向不同的响应方法，但需要在相应的 form 表里做操作。
         * @param bool $isEnc               :Form 中的提交按钮是否加密 branch/node/state
         * @param array $args               :其他参数，自由发挥。
         * @return array
         */
        public function getData( FormData $instance, string $node='', string $state='write', string $event='', bool $isEnc=true, array $args=[] ): array
        {
            return $this->execute( $instance, 'form_data', $node, $state, $event, $isEnc, $args );
        }

        /** 获取表单全部数组，用于构建 HTML
         * @param FormData $instance        :实现 FormData 的 Form 实例
         * @param string $node              :Form 实例中要使用的方法用的参数
         * @param string $state             :Form 实例中要使用的方法用的参数 write（默认）/ modify / view
         * @param string $event             :Form 实例中要使用的方法用的参数，可以自定义，可以指向不同的响应方法，但需要在相应的 form 表里做操作。
         * @param bool $isEnc               :Form 中的提交按钮是否加密 branch/node/state
         * @param array $args               :其他参数，自由发挥。
         * @return array
         */
        private function getFormData( FormData $instance, string $node='', string $state='write', string $event='', bool $isEnc=true, array $args=[] ): array
        {
            return $this->execute( $instance, 'form', $node, $state, $event, $isEnc, $args );
        }

        /** 获取表单 HTML
         * @param FormData $instance        :实现 FormData 的 Form 实例
         * @param string $node              :Form 实例中要使用的方法用的参数
         * @param string $state             :Form 实例中要使用的方法用的参数 write（默认）/ modify / view
         * @param string $event             :Form 实例中要使用的方法用的参数，可以自定义，可以指向不同的响应方法，但需要在相应的 form 表里做操作。
         * @param bool $isEnc               :Form 中的提交按钮是否加密 branch/node/state
         * @param array $defaultData        :默认填充数据
         * @param bool $flag                :构建 Form HTML 要使用的参数 返回类型
         * @param array $args               :其他参数，自由发挥。
         *
         * @return array|string
         */
        public function getHtml( FormData $instance, string $node='', string $state='write', string $event='', bool $isEnc=true, array $defaultData=[], bool $flag=false, array $args=[] )
        {
            $htmlData = $this->getFormData( $instance, $node, $state, $event, $isEnc, $args );
            $formHtml = new FormHtml();

//            return $htmlData;

            return $formHtml->createForm( $htmlData, $defaultData, $node, $state, $event, $isEnc, $flag, $instance );
        }

        private function getBtnData( FormData $instance, string $node='', string $state='write', string $event='show', bool $isEnc=true, array $args=[] )
        {
            return $this->execute( $instance, 'form_btn', $node, $state, $event, $isEnc, $args );
        }
        public function getBtnHtml( FormData $instance, string $node='', string $state='write', string $event='show', bool $isEnc=true, array $defaultAttrData=[], array $args=[] )
        {
            $btnData = $this->getBtnData( $instance, $node, $state, $event, $isEnc, $args );
            $btnHtml = new FormHtml();

            return $btnHtml->createBtn( $btnData, $defaultAttrData );
        }

        /**
         * @param FormData $instance        :实现 FormData 的 Form 实例
         * @param string $methodName        :Form 实例中要使用的方法名
         * @param string $node              :Form 实例中要使用的方法用的参数
         * @param string $state             :Form 实例中要使用的方法用的参数 write（默认）/ modify / view
         * @param string $event             :Form 实例中要使用的方法用的参数，可以自定义，可以指向不同的响应方法，但需要在相应的 form 表里做操作。
         * @param bool $isEnc               :Form 中的提交按钮是否加密 branch/node/state
         * @param array $args               :其他参数，自由发挥。
         * @return mixed|string
         */
        private function execute( FormData $instance, string $methodName='form', string $node='', string $state='write', string $event='', bool $isEnc=true, array $args=[] )
        {
            try {

//                if ( !($instance instanceof FormData) ) {
//                    return 'Form Error 1 ${FormRoute} does not implement FormData';
//                }

                $defaultArgs = array(
                    'state'     => $state,
                    'node'      => $node,
                    'isEnc'     => $isEnc,
                    'event'     => $event,
                );

                $args = array_merge( $args, $defaultArgs );

                if ( method_exists($instance, $methodName) ) {
                    return count($args) ? call_user_func_array( [$instance, $methodName], [$args] ) : $instance->$methodName();
                }

            } catch (\Exception $exception) {
                return 'FormRoute Error 2 '.$exception->getMessage();
            }

            return 'FormRoute Error 3';
        }


    }
}