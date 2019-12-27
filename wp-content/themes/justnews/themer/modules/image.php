<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Module_image extends WPCOM_Module{
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'image' => array(
                    'name' => '图片',
                    'type' => 'u',
                    'desc' => '图片将会根据模块宽度100%显示'
                ),
                'alt' => array(
                    'name' => '替代文本',
                    'desc' => '可选，图片alt属性，图片替代文本，利于SEO'
                ),
                'url' => array(
                    'name' => '链接地址',
                    'desc' => '可选'
                ),
                'target' => array(
                    'name' => '打开方式',
                    'type' => 's',
                    'desc' => '链接打开方式',
                    'value'  => '0',
                    "o" => array(
                        "0" => "当前窗口",
                        "1" => "新窗口打开",
                    )
                )
            ),
            array(
                'tab-name' => '风格样式',
                'margin-top' => array(
                    'name' => '上外边距',
                    'desc' => '模块离上一个模块/元素的间距，单位建议为px。即 margin-top 值，例如： 10px',
                    'value'  => '0'
                ),
                'margin-bottom' => array(
                    'name' => '下外边距',
                    'desc' => '模块离上一个模块/元素的间距，单位建议为px。即 margin-bottom 值，例如： 10px',
                    'value'  => '20px'
                )
            )
        );
        parent::__construct( 'image', '图片', $options, 'mti:panorama' );
    }

    function template($atts, $depth){
        if(isset($atts['url']) && $atts['url']){ ?>
            <a href="<?php echo esc_url($atts['url']);?>"<?php echo isset($atts['target']) && $atts['target']=='1'?' target="_blank"':''?>>
                <?php echo wpcom_lazyimg($atts['image'], isset($atts['alt'])?$atts['alt']:'');?>
            </a>
        <?php } else { ?>
            <?php echo wpcom_lazyimg($atts['image'], isset($atts['alt'])?$atts['alt']:'');?>
        <?php }
    }
}

register_module( 'WPCOM_Module_image' );