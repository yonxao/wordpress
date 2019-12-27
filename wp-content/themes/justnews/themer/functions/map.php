<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Map{
    public function __construct( $html='', $pos='', $scrollWheelZoom=0, $type=0 ) {
        $this->html = $html;
        $this->pos = $pos ? $pos : '116.403963,39.915119';
        $this->scrollWheelZoom = $scrollWheelZoom;
        $this->type = $type;
        $rand1 = rand(100,999);
        $rand2 = rand(1000,9999);
        $rand3 = rand(10000,99999);
        $this->id = 'map-'.$rand1.$rand2.$rand3; //随机数ID避免重复
    }

    public function init_map(){
        global $options;
        if($this->type){
            $key = isset($options['google_map_key']) && $options['google_map_key'] ? $options['google_map_key'] : '';
            $icon = FRAMEWORK_URI . '/assets/images/marker.png';
        }else{
            $key = isset($options['baidu_map_ak']) && $options['baidu_map_ak'] ? $options['baidu_map_ak'] : '';
            $icon = '';
        }
        return '<div id="'.$this->id.'" style="height: 100%;"></div>' . '<script>jQuery(window).load(function(){ wpcom_map("'.$this->id.'", "'.addslashes($this->html).'", ['.strip_tags($this->pos).'], '.$this->scrollWheelZoom.', "'.$key.'", '.$this->type.', "'.$icon.'");})</script>';
    }
}

function wpcom_map($html='', $pos='', $scrollWheelZoom=0, $echo=true, $type=0){
    $map = new WPCOM_Map($html, $pos, $scrollWheelZoom, $type);
    if($echo){
        echo $map->init_map();
    }else{
        return $map->init_map();
    }
}