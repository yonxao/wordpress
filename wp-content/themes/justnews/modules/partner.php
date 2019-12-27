<?php
class WPCOM_Module_partner extends WPCOM_Module {
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'title' => array(
                    'name' => '模块标题',
                    'value'  => '合作伙伴'
                ),
                'sub-title' => array(
                    'name' => '副标题'
                ),
                'more-title' => array(
                    'name' => '更多标题',
                    'value' => '联系我们'
                ),
                'more-url' => array(
                    'name' => '更多链接'
                ),
                'img-cols' => array(
                    'name' => '每行显示',
                    'desc' => '每行显示图片数量',
                    'o' => array(
                        '3' => '3张',
                        '4' => '4张',
                        '5' => '5张',
                        '6' => '6张',
                        '7' => '7张',
                        '8' => '8张',
                        '9' => '9张',
                        '10' => '10张'
                    )
                ),
                'nofollow' => array(
                    'name' => 'nofollow',
                    'desc' => '链接nofollow属性',
                    'type' => 't'
                ),
                'from' => array(
                    'type' => 'r',
                    'name' => '内容来源',
                    'o' => array(
                        '0' => '独立添加',
                        '1' => '使用后台 主题设置>合作伙伴 已有项目'
                    )
                ),
                'partners' => array(
                    'type' => 'rp',
                    'filter' => 'from:0',
                    'o' => array(
                        'img' => array(
                            'name' => '图片',
                            'type' => 'u',
                            'desc' => '图片宽度建议和上面设置的图片宽度选项一致，高度统一即可'
                        ),
                        'alt' => array(
                            'name' => '标题',
                            'desc' => '选填，不会显示，会作为图片的alt属性'
                        ),
                        'url' => array(
                            'name' => '链接',
                            'desc' => '选填'
                        )
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
        parent::__construct('partner', '合作伙伴', $options, 'mti:group');
    }

    function template( $atts, $depth ){
        $from = isset($atts['from']) && $atts['from']=='1' ? 1 : 0;
        if($from==1){
            global $options;
            $atts['partners'] = array();
            $partners = isset($options['pt_img']) && $options['pt_img'] ? $options['pt_img'] : array();
            if($partners && $partners[0]){
                foreach($partners as $x => $pt) {
                    $url = $options['pt_url'] && $options['pt_url'][$x] ? $options['pt_url'][$x] : '';
                    $alt = $options['pt_title'] && $options['pt_title'][$x] ? $options['pt_title'][$x] : '';
                    $atts['partners'][] = array(
                        'url' => $url,
                        'alt' => $alt,
                        'img' => $pt
                    );
                }
            }
        } ?>
        <div class="sec-panel">
            <?php if(isset($atts['title']) && $atts['title']){ ?>
                <div class="sec-panel-head">
                    <h2><span><?php echo $atts['title'];?></span> <small><?php echo $atts['sub-title'];?></small> <?php if($atts['more-url'] && $atts['more-title']){?><a href="<?php echo esc_url($atts['more-url']);?>" target="_blank" class="more"><?php echo $atts['more-title'];?></a><?php } ?></h2>
                </div>
            <?php } ?>
            <div class="sec-panel-body">
                <ul class="list list-partner">
                    <?php
                    $cols = isset($atts['img-cols']) && $atts['img-cols'] ? $atts['img-cols'] : 6;
                    $width = floor(10000/$cols)/100;
                    $follow = isset($atts['nofollow']) && $atts['nofollow'];
                    foreach($atts['partners'] as $partner){
                        $url = isset($partner['url']) ? $partner['url'] : '';
                        $alt = isset($partner['alt']) ? $partner['alt'] : '';
                        $img = isset($partner['img']) ? $partner['img'] : '';
                        if($img){ ?>
                        <li style="width:<?php echo $width;?>%">
                            <?php if($url){ ?><a target="_blank" title="<?php echo esc_attr($alt);?>" href="<?php echo esc_url($url);?>"<?php echo $follow ? ' rel="nofollow"':'';?>><?php } ?>
                                <?php echo wpcom_lazyimg($img, $alt?$alt:$atts['title']);?>
                            <?php if($url){ ?></a><?php } ?>
                        </li>
                    <?php } } ?>
                </ul>
            </div>
        </div>
    <?php }
}
register_module( 'WPCOM_Module_partner' );