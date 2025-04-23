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
use MAOSIJI\LU\WP\LUNonce;
use WUTI\XYYSD\XYYSDNonce;
if ( !class_exists('FormHtml') ) {
    class FormHtml
    {

        /**示例，一个完整的form
         * @var array
         */
        private $htmlArr = array(
            'attr'      => array(
                'id'        => '',
                'class'     => '',
                'action'    => '',
                'method'    => '',
                /*当使用 method="post" 时，指定如何编码表单数据。
                 *
                 * application/x-www-form-urlencoded：默认编码方式
                 * multipart/form-data：用于上传文件
                 * text/plain：纯文本格式
                 * */
                'enctype'   => '',
//            用于控制表单或输入字段是否启用自动完成功能，默认 on，开启；off，禁止。还可以指定自定义的，如 name,email,zxs_name
                'autocomplete'  => '',
            ),
//            标签外面的前面
            'before'        => 'HTML标签',
//            标签外面的后面
            'after'         => 'HTML标签',
//            标签里面的前面
            'prepend'       => '',
//            标签里面的外面
            'append'        => '',
            'content' => array(
                array(
                    'title'         => 'HTML标签 h2 h3 h4 h5 h6',
//            标签外面的前面
                    'before'        => 'HTML标签',
//            标签外面的后面
                    'after'         => 'HTML标签',
//            标签里面的前面
                    'prepend'       => '',
//            标签里面的外面
                    'append'        => '',
                    'style'         => '',
                    'attr'          => array(
                        'id'            => '',
                        'class'         => '.lu-form-basic-verify',
                        // 可选，supergroup/
                        'lu-form'       => '',
                    ),
                    'content'       => array(
                        array(
                            // select/input/textarea/submit
                            'element'           => 'input',
                            'title'             => '名称',
                            'desc'              => '说明文字，比如 年龄必须大于18',
                            'before'            => '',
                            'after'             => '',
                            'prepend'           => '',
                            'append'            => '',
//                    单选、多选、select、input 的默认值
                            'value'             => '',
//                    单选、多选、select 的所有选项
                            'values'            => array('值1','值2'),
//                    单选、多选 的排序： row 横排 col 竖排
                            'display'           => '',
                            // 存到数据库时的类型
                            'type'              => 'string',
                            'style'             => '',
                            'attr'  => array(
                                'id'                => '',
                                'class'             => '',
                                // text/file/checkbox/radio/password/number/email
                                'type'              => 'text',
                                'name'              => 'lf_sex',
                                // 自动填充，默认 on，还可以选择 off，或自定义字符，如 name,email,zxs_name
                                'autocomplete'      => '',
                                'placeholder'       => '',
                                // textarea 行
                                'rows'              => '',
                                // textarea 列
                                'cols'              => '',
                                // file 的 accept 值
                                'accept'            => 'image/x-png,image/gif,image/jpeg,image/bmp',
                                // 多选，适用于 checkbox file，空，则不显示，0 不限制，1 只能选1个，以此类推
                                'multiple'          => '',
                                // 特殊用途，比如 时间、统计字数、上传
                                // date/datetime/time/
                                'lu-action'         => 'date',

                                // 用于强制验证
                                'lu-verify-key'     => '!=',
                                'lu-verify-value'   => "''",
                                'lu-verify-message' => '必须选择',

                                // 用于 notify 软性提示
                                'lu-notify-icon'    => '',
                                'lu-notify-duration'=> '',
                                'lu-notify-position'=> '',
                                'lu-notify-message' => '',

                                // 用于 submit 按钮，ajax
                                'xyysd'         => '',
                                'xyysd-action'  => '',
                                'xyysd-branch'  => '',
                                'xyysd-nonce'   => '',
                                // 用于 submit 按钮，验证哪些表单元素
                                'lu-verifies'   => '',
                            ),
                        ),
                    ),
                ),
            )
        );

        /** 创建form
         * @param array $htmlArr            :表单数组数据
         * @param array $defaultData        :默认填充数据
         * @param string $state             :write,默认，填写状态；view，查看状态（可删除）；modify 修改状态
         * @param bool $flag                :返回数据类型，默认 false。true 时返回 string $html，false 时返回 array('id'=>$id,'html'=>$html)
         * @param bool $isEnc               :Form 中的提交按钮是否加密 branch/node/state
         * @param FormData $instance        :实现 FormData 的 Form 实例，用于 修改表时，删除里面的 图片 等
         * @return
         */
        public function createForm( array $htmlArr, array $defaultData=array(), string $node='', string $state='write', string $event='', bool $isEnc=true, bool $flag=false, FormData $instance=null )
        {
            $html = '';
            if ( empty($htmlArr) || !is_array($htmlArr) ) { return $html;}

            $before     = isset($htmlArr['before']) ? $htmlArr['before'] : '';
            $after      = isset($htmlArr['after']) ? $htmlArr['after'] : '';
            $prepend    = isset($htmlArr['prepend']) ? $htmlArr['prepend'] : '';
            $append     = isset($htmlArr['append']) ? $htmlArr['append'] : '';

            $attr       = isset($htmlArr['attr']) ? $htmlArr['attr'] : array();
            $attrHTML   = $this->createAttr( $attr );

            $id         = $htmlArr['attr']['id'] ?? '';

            $content    = isset($htmlArr['content']) ? $htmlArr['content'] : array();
            $cHTML      = $this->createGroup( $content, $defaultData, $node, $state, $event, $isEnc, $instance );

            $html       .= $before;
            $html       .= '<form '.$attrHTML.' >';
            $html       .= $prepend;
            $html       .= $cHTML;
            $html       .= $append;
            $html       .= '</form>';
            $html       .= $after;

            return !$flag ? array('id'=>$id, 'html'=>$html) : $html;
        }

        /**创建组
         * @param array $htmlArr
         * @return void
         */
        private function createGroup( array $htmlArr, array $defaultData, string $node, string $state, string $event, $isEnc, FormData $instance=null ): string
        {
            $html = '';
            if ( empty($htmlArr) || !is_array($htmlArr) ) { return $html;}

            foreach ( $htmlArr as $k => $v ) {

                $before     = isset($v['before']) ? $v['before'] : '';
                $after      = isset($v['after']) ? $v['after'] : '';
                $prepend    = isset($v['prepend']) ? $v['prepend'] : '';
                $append     = isset($v['append']) ? $v['append'] : '';

                $title      = isset($v['title']) ? $v['title'] : '';
                $content    = isset($v['content']) ? $v['content'] : array();

                $attr       = isset($v['attr']) ? $v['attr'] : array();
                $attrHTML   = $this->createAttr( $attr );

                $style      = isset($v['style']) ? 'style="'.$v['style'].'"' : '';

                $html .= $before;
                $html .= '<section '.$attrHTML.' '.$style.'>';
                $html .= $prepend;
                $html .= $title;
                if ( !empty($content) ) {
                    foreach ( $content as $ct ) {
                        $html .= $this->createElement( $ct, $defaultData, $node, $state, $event, $isEnc, $instance );
                    }
                }
                $html .= $append;
                $html .= '</section>';
                $html .= $after;
            }

            return $html;
        }

        /**表单自动生成
         * @param array $content
         * @return string
         */
        private function createElement( array $htmlArr, array $defaultData, string $node, string $state, string $event, $isEnc, FormData $instance=null ): string
        {
            $html = '';
            if ( empty($htmlArr) || !is_array($htmlArr) ) { return $html;}

            $element    = isset($htmlArr['element']) ? $htmlArr['element'] : '';
            $title      = isset($htmlArr['title']) ? $htmlArr['title'] : '';
            $desc       = isset($htmlArr['desc']) ? $htmlArr['desc'] : '';
            $value      = isset($htmlArr['value']) ? $htmlArr['value'] : '';
            $values     = isset($htmlArr['values']) ? $htmlArr['values'] : '';
            $display    = isset($htmlArr['display']) ? $htmlArr['display'] : 'col';
            $before     = isset($htmlArr['before']) ? $htmlArr['before'] : '';
            $after      = isset($htmlArr['after']) ? $htmlArr['after'] : '';
            $prepend    = isset($htmlArr['prepend']) ? $htmlArr['prepend'] : '';
            $append     = isset($htmlArr['append']) ? $htmlArr['append'] : '';
            $style      = isset($htmlArr['style']) ? 'style="'.$htmlArr['style'].'"' : '';
            $text       = isset($htmlArr['text']) ? $htmlArr['text'] : '';

            $attr       = isset($htmlArr['attr']) ? $htmlArr['attr'] : array();

            // 查看状态下，不添加按钮
            if ( $state==='view' && $element==='submit' ) {
                return '';
            }

            // 如果是修改模式，则添加 编号 no
            if ( $state==='modify' ) {
                $dvNo = isset($defaultData['no']) ? $defaultData['no'] : '';
                $attr['xyysd-no'] = $dvNo;
            }
            // 如果是查看模式，强制不可修改
            if ( $state==='view' ) {
                $attr['disabled'] = 'disabled';
            }

            $attrHTML   = $this->createAttr( $attr );

            $type       = isset($htmlArr['attr']['type']) ? $htmlArr['attr']['type'] : '';

            if ( empty($element) || !array_key_exists('name', $attr) ) { return 'name 和 element 不能为空'; }
            if ( ($type==='checkbox' || $type==='radio' || $element==='select' ) && (!is_array($values) || count($values)<1) ) { return '当 type=radio、checkbox、element=select 时，values 必须多于1个值'; }

            // 是否有默认值数组，如果有，则使用
            $name       = $attr['name'];
//            // 快速填写
//            if ( $_GET['f_wid'] && $name==='f_wid' ) {
//                $value = $_GET['f_wid'];
//            }
//            if ( $_GET['a_wid'] && $name==='a_wid' ) {
//                $value = $_GET['a_wid'];
//            }
//            if ( $_GET['no'] && $name==='no' ) {
//                $value = $_GET['no'];
//            }

            $value      = isset($defaultData[$name]) ? $defaultData[$name] : $value;

//            是否显示必选标识
            $requiredHtml = array_key_exists('lu-verify-key', $attr) ? '<span class="lu-form-required">*</span>' : '';

            $html .= $before;
            $html .= '<div class="lu-form-group" '.$style.'>';
            $html .= $prepend;
            $html .= '<div class="lu-form-group-title">';
            $html .= '<span>' . $title . ' '.$requiredHtml.'</span>';
            $html .= '</div>';
            $html .= '<div class="lu-form-group-content">';
            if ($element === 'input') {
                if ($type === 'checkbox') {
                    $displayHtml = $display === 'col' ? 'lu-block' : '';
                    foreach ($values as $k=>$v) {
                        $html .= '<label class="' . $displayHtml . '">';
                        $html .= '<input '.$attrHTML.' value="' . $k . '" ' . ( (int)$k === (int)$value ? 'checked' : '') . '>' . $v;
                        $html .= '</label>';
                    }
                }
                if ($type === 'radio') {
                    $displayHtml = $display === 'col' ? 'lu-block' : '';
                    foreach ($values as $k=>$v) {
                        $html .= '<label class="' . $displayHtml . '">';
                        $html .= '<input '.$attrHTML.' value="' . $k . '" ' . ( (int)$k === (int)$value ? 'checked' : '') . '>' . $v;
                        $html .= '</label>';
                    }
                }
                if ($type === 'file' && strpos($attrHTML, 'lu-action="upload_image"') ) {

                    $imgHTML = '';
                    if ( !is_array($value) && strpos($value, ',') ) {
                        $value = explode(',', $value);
                    }
                    if ( is_array($value) && !empty($value) ) {
                        foreach ( $value as $link ) {
                            $imgHTML .= '<img src="' . $link . '" />';
                        }
                    }
                    $html .= '<div class="lu-form-file">';
                    $html .= '<input '.$attrHTML.'>';
                    $html .= '<div class="lu-form-file-mask"><div>'.$imgHTML.'</div><span>点此上传</span></div>';
                    $html .= '</div>';
                }
                if ($type === 'file' && strpos($attrHTML, 'lu-action="upload_image_can_delete"') ) {

                    $imgHTML = '';
                    if ( !is_array($value) && strpos($value, ',') ) {
                        $value = explode(',', $value);
                    }
                    if ( is_array($value) && !empty($value) ) {
                        foreach ( $value as $link ) {
                            $linkExt = strtolower(pathinfo($link, PATHINFO_EXTENSION));
                            $linkName = strtolower(pathinfo($link, PATHINFO_BASENAME));
                            $imgHtml_btn = '';
                            $imgHtml_show = '';
                            $imgHTML .= '<div class="file-item">';
                            $imgHTML .= '<div class="file-item-btns">';
                            // 判断是文件还是图片
                            if ( in_array($linkExt, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']) ) {
                                $imgHtml_btn .= '<a class="big-btn" title="放大" lu-action="show-image" lu-image="'.$link.'" lu-image-width="450"><i class="lu-iconfont lu-icon-search lu-color-fff"></i></a>';
                                $imgHtml_show .= '<div class="show-image" style="background-image: url('.$link.')"></div>';
                            } else {
                                $imgHtml_btn .= '<a class="suffix-name" title="'.$linkExt.' 文件">'.$linkExt.'</a>';
                                $imgHtml_show .= '<div class="show-txt lu-four-line">'.$linkName.'</div>';
                            }

                            $xyysdBranch     = (new \ReflectionClass($instance))->getShortName();
                            $xyysdNode       = $node;
                            $xyysdState      = $state;
                            $xyysdEvent      = 'single';
                            $xyysdNonce      = (new LUNonce())->create_nonce($xyysdBranch.'-'.$xyysdNode.'-'.$xyysdState);
                            if ( $isEnc ) {
                                $className       = get_class($instance);
                                $xyysdBranch     = xyysh_form_convert($className);
                                $xyysdNode       = xyysh_form_convert($node);
                                $xyysdState      = xyysh_form_convert($state);
                                $xyysdNonce      = (new XYYSDNonce())->createNonce($className.'-'.$node.'-'.$state);
                            }

                            $imgHTML .= $imgHtml_btn;
                            $imgHTML .= $state==='modify' ? '<a class="remove-btn" title="移除" xyysd="shanhu" xyysd-action="shanhu_route" xyysd-branch="'.$xyysdBranch.'" xyysd-node="'.$xyysdNode.'" xyysd-state="'.$xyysdState.'" xyysd-event="'.$xyysdEvent.'" xyysd-no="'.$dvNo.'" xyysd-nonce="'.$xyysdNonce.'" lu-verifies="0" lu-loading="loading-2" lu-loading-txt="正在删除" xyysd-key="'.$attr['name'].'" xyysd-value="'.$link.'"><i class="lu-iconfont lu-icon-error lu-color-fff"></i></a>' : '';
                            $imgHTML .= '</div>';
                            $imgHTML .= $imgHtml_show;
                            $imgHTML .= '</div>';
                        }
                    }
                    $html .= '<div class="lu-form-file-can-delete">';
                    $html .= '<div class="lu-form-file-mask">';
                    $html .= '<span>点此上传</span>';
                    $html .= '<input '.$attrHTML.' '.(is_array($value) && !empty($value) ? 'lu-no' : '').'>';
                    $html .= '</div>';
                    $html .= '<div class="lu-form-file-show">';
                    $html .= $imgHTML;
                    $html .= '</div>';
                    $html .= '</div>';
                }
                if ($type === 'text') {
                    $html .= '<input '.$attrHTML.' value="'.$value.'">';
                }
                if ($type === 'password') {
                    $html .= '<input '.$attrHTML.' value="'.$value.'">';
                }
                if ($type === 'number') {
                    $html .= '<input '.$attrHTML.' value="'.$value.'">';
                }
                if ($type === 'email') {
                    $html .= '<input '.$attrHTML.' value="'.$value.'">';
                }
                if ($type === 'hidden') {
                    $html .= '<input '.$attrHTML.' value="'.$value.'">';
                }
            }
            if ($element === 'select') {
                $html .= '<select '.$attrHTML.'>';
                $html .= '<option value="0">请选择</option>';
                foreach ($values as $k => $v) {
                    $html .= '<option value="' . $k . '" ' . ( (int)$k === (int)$value ? 'selected' : '') . '>' . $v . '</option>';
                }
                $html .= '</select>';
            }
            if ($element === 'textarea') {
                $html .= '<textarea '.$attrHTML.'>'.$value.'</textarea>';
            }
            if ($element==='submit') {
                $html .= '<a href="javascript:;" '.$attrHTML.' >'.$text.'</a>';
            }
            $html .= '</div>';
            $html .= '<div class="lu-form-group-info">' . $desc . '</div>';
            $html .= $append;
            $html .= '</div>';
            $html .= $after;

            return $html;
        }

        /**属性自动生成
         * @param array $htmlArr
         * @return string
         */
        private function createAttr( array $attrArr ): string
        {
            $html = '';
            if ( empty($attrArr) || !is_array($attrArr) ) { return $html;}

            foreach ( $attrArr as $k => $v ) {
                $html .= ' '.$k.'="'.$v.'" ';
            }

            return $html;
        }



        public function createBtn( array $btnArr, array $defaultAttrData=[] )
        {
            $html = '';
            $argsHtml = '';
            if ( empty($btnArr) || !is_array($btnArr) ) { return $html; }

            if ( !empty($defaultAttrData) ) {
                foreach ( $defaultAttrData as $k=>$v ) {
                    $argsHtml .= ' '.$k.'="'.$v.'" ';
                }
            }

            foreach ( $btnArr as $key=>$value ) {
                $element    = isset($value['element']) ? $value['element'] : '';
                $text       = isset($value['text']) ? $value['text'] : '';
                $attr       = isset($value['attr']) ? $value['attr'] : array();
                $attrHTML   = $this->createBtnAttr( $attr );

                if ($element==='submit') {
                    $html .= '<a href="javascript:;" '.$attrHTML.' '.$argsHtml.'>'.$text.'</a>';
                }
            }

            return $html;
        }
        private function createBtnAttr( array $attrArr ): string
        {
            $html = '';
            if ( empty($attrArr) || !is_array($attrArr) ) { return $html;}

            foreach ( $attrArr as $k => $v ) {
                if ( strpos($k, 'xyysd')===0 ) {
                    $html .= ' '.$k.'="'.$v.'" ';
                }
            }

            return $html;
        }


    }
}