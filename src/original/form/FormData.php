<?php
/* 做一个抽象类，让其他表格生成的类来继承它
* author               : 猫斯基
* url                  : maosiji.com
* email                : 1394846666@qq.com
* wechat               : maosiji-com
* date                 : 2024-12-05 20:41
* update               :
* project              : luphp
*/
namespace MAOSIJI\LU;
if ( !class_exists('FormData') ) {
    interface FormData
    {
        // 表单 name 前缀
        public static function form_name_prefix( string $node='' ): string;

        /** Form 数据，用于构建 HTML
         * @param array $args:
         * @return mixed
         */
        public function form( array $args=array() ): array;

        /** 表单数据，用于验证和构建 HTML 的参数
         * @param array $args
         * @return mixed
         */
        public function form_data( array $args=array() ): array;

        /** 按钮数据，用于构建按钮 HTML，此按钮是点击后显示表单，不是直接对表单操作
         * @param array $args
         * @return array
         */
        public function form_btn(array $args=array() ): array;

    }
}