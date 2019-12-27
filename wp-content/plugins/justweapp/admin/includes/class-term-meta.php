<?php defined( 'ABSPATH' ) || exit;
class WPCOM_Plugin_Term_Meta {
    public function __construct( $tax, $plugin ) {
        $this->tax = $tax;
        $this->plugin = $plugin;
        add_action( $tax . '_add_form_fields', array($this, 'add'), 10, 2 );
        add_action( $tax . '_edit_form_fields', array($this, 'edit'), 10, 2 );
        add_action( 'created_' . $tax, array($this, 'save'), 50, 2 );
        add_action( 'edited_' . $tax, array($this, 'save'), 50, 2 );
    }

    function add(){
        global $is_wpcom_plugin_panel;
        require_once WPCOM_ADMIN_PATH . 'includes/class-utils.php';
        wp_enqueue_style("plugin-panel", WPCOM_ADMIN_URI . "css/panel.css", false, WPCOM_ADMIN_VERSION, "all");
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script("plugin-panel", WPCOM_ADMIN_URI . "js/panel.js", array('jquery', 'wp-color-picker'), WPCOM_ADMIN_VERSION, true);
        wp_enqueue_media(); ?>
        <script>if(typeof _plugins_options === 'undefined') _plugins_options = []; _plugins_options.push(<?php echo $this->get_term_metas(0);?>);</script>
        <?php if(!class_exists('WPCOM_Meta') && !$is_wpcom_plugin_panel){ $is_wpcom_plugin_panel = 1;?>
        <div id="wpcom-plugin-panel" class="wpcom-term-wrap"><term-panel :ready="ready"/></div>
        <div style="display: none;"><?php wp_editor( 'EDITOR', 'WPCOM-EDITOR', WPCOM_ADMIN_UTILS::editor_settings(array('textarea_name'=>'EDITOR-NAME')) );?></div>
    <?php } }

    function edit($term){
        global $is_wpcom_plugin_panel;
        require_once WPCOM_ADMIN_PATH . 'includes/class-utils.php';
        wp_enqueue_style("plugin-panel", WPCOM_ADMIN_URI . "css/panel.css", false, WPCOM_ADMIN_VERSION, "all");
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script("plugin-panel", WPCOM_ADMIN_URI . "js/panel.js", array('jquery', 'jquery-ui-core', 'wp-color-picker'), WPCOM_ADMIN_VERSION, true);
        wp_enqueue_media();?>
        <script>if(typeof _plugins_options === 'undefined') _plugins_options = []; _plugins_options.push(<?php echo $this->get_term_metas($term->term_id);?>);</script>
        <?php if(!class_exists('WPCOM_Meta') && !$is_wpcom_plugin_panel){ $is_wpcom_plugin_panel = 1;?>
        <tr id="wpcom-plugin-panel" class="wpcom-term-wrap"><td colspan="2"><term-panel :ready="ready"/></td></tr>
        <tr style="display: none;"><th></th><td><div style="display: none;"><?php wp_editor( 'EDITOR', 'WPCOM-EDITOR', WPCOM_ADMIN_UTILS::editor_settings(array('textarea_name'=>'EDITOR-NAME')) );?></div>
        </td></tr>
    <?php } }

    function get_term_metas($term_id){
        $res = array('type' => 'taxonomy', 'tax' => $this->tax);
        if($term_id){
            $res['options'] = get_term_meta( $term_id, '_wpcom_metas', true );
        }
        $res['filters'] = apply_filters( $this->plugin['slug'] . '_tax_metas', array());
        $res['ver'] = $this->plugin['ver'];
        $res['plugin-id'] = $this->plugin['plugin_id'];
        $res['plugin-slug'] = $this->plugin['slug'];
        $res['framework_ver'] = WPCOM_ADMIN_VERSION;
        return json_encode($res);
    }

    function save($term_id){
        global $is_wpcom_plugin_panel_save;
        if($is_wpcom_plugin_panel_save) return false;
        $is_wpcom_plugin_panel_save = 1;
        $values = array();
        $_post = $_POST;
        foreach($_post as $key => $value) {
            if (preg_match('/^wpcom_/i', $key)) {
                $name = preg_replace('/^wpcom_/i', '', $key);
                $values[$name] = $value;
            }
        }
        if(!empty($values)){
            $metas = get_term_meta( $term_id, '_wpcom_metas', true );
            if($metas){
                foreach ($metas as $key => $value) {
                    $values[$key] = $value;
                }
            }
            update_term_meta( $term_id, '_wpcom_metas', $values );
        }
    }
}

add_action( 'wp_ajax_wpcom_plugin_get_taxs', 'wpcom_plugin_get_taxs' );
function wpcom_plugin_get_taxs(){
    $taxs = $_REQUEST['taxs'];
    $res = array();
    if( current_user_can( 'edit_posts' ) ){
        require_once WPCOM_ADMIN_PATH . 'includes/class-utils.php';
        foreach ($taxs as $tax){
            if($tax) $res[$tax] = WPCOM_ADMIN_UTILS::category($tax);
        }
    }
    echo json_encode($res);
    exit;
}