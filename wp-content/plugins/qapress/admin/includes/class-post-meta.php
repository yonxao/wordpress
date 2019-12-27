<?php defined( 'ABSPATH' ) || exit;
class WPCOM_Plugin_Post_Meta {
    private $settings;

    public function __construct($plugin) {
        $this->plugin = $plugin;
        add_action( 'load-post.php', array( $this, 'call_meta' ));
        add_action( 'load-post-new.php', array( $this, 'call_meta' ));
        add_action( 'add_meta_boxes', array( $this, 'set_metabox' ) );
        add_action( 'save_post', array( $this, 'save_metabox' ), 50 );
        add_action( 'wp_ajax_wpcom_plugin_get_keys_value', array( $this, 'get_keys_value' ) );
        add_action( 'wp_ajax_wpcom_plugin_get_attachments', array( $this, 'get_attachments' ) );
        add_action( 'admin_footer', array( $this, 'plugins_options' ) );
    }

    public function call_meta() {
        wp_enqueue_style("plugin-panel", WPCOM_ADMIN_URI . "css/panel.css", false, WPCOM_ADMIN_VERSION, "all");
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script("plugin-panel", WPCOM_ADMIN_URI . "js/panel.js", array('jquery', 'wp-color-picker'), WPCOM_ADMIN_VERSION, true);
    }

    /**
     * Add meta box for all post type if options exist.
     *
     * @uses add_meta_box
     */
    public function set_metabox(){
        global $wp_post_types;
        if(!class_exists('WPCOM_Meta')){
            $exclude_types = array( 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'um_form', 'um_role', 'um_directory', 'shop_order', 'shop_coupon' );
            foreach( $wp_post_types as $type => $args ){
                if( ! in_array( $type , $exclude_types ) ){
                    add_meta_box('wpcom-plugin-metas', '<i class="wpcom wpcom-logo"></i> 设置选项', array($this, 'metabox_html'), $type, 'normal', 'high', array());
                }
            }
        }
    }

    function plugins_options(){
        global $post, $pagenow;
        if( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) { ?>
        <script>if(typeof _plugins_options === 'undefined') _plugins_options = []; _plugins_options.push(<?php echo $this->get_post_metas($post);?>);</script>
    <?php } }

    public function metabox_html( $post ){
        require_once WPCOM_ADMIN_PATH . 'includes/class-utils.php';
        // Add an nonce field
        wp_nonce_field( 'wpcom_meta_box', 'wpcom_meta_box_nonce' );
        $editor = post_type_supports($post->post_type, 'editor');
        ?>
        <div id="wpcom-plugin-panel" class="wpcom-post-metas"><post-panel :ready="ready" /></div>
        <?php if(!$editor){ ?><div style="display: none;"><?php wp_editor( 'EDITOR', 'WPCOM-EDITOR', WPCOM_ADMIN_UTILS::editor_settings(array('textarea_name'=>'EDITOR-NAME')) );?></div><?php } ?>
    <?php }

    private function get_post_metas( $post ){
        global $options;
        $res = array('type' => 'post', 'post_type' => $post->post_type);
        $res['options'] = get_post_meta($post->ID, '_wpcom_metas', true);
        $res['filters'] = apply_filters( $this->plugin['slug'] . '_post_metas', array() );
        $res['post_id'] = $post->ID;
        $res['ver'] = $this->plugin['ver'];
        $res['plugin-id'] = $this->plugin['plugin_id'];
        $res['plugin-slug'] = $this->plugin['slug'];
        $res['framework_ver'] = WPCOM_ADMIN_VERSION;
        return json_encode($res);
    }

    public function get_keys_value(){
        $post_id = $_GET['id'];
        $keys = $_GET['keys'];
        $res = array();
        if( current_user_can( 'edit_posts', $post_id ) ){
            foreach ($keys as $key){
                $res[$key] = get_post_meta($post_id, $key, true);
            }
        }
        echo json_encode($res);
        exit;
    }

    public function get_attachments(){
        $ids = $_REQUEST['ids'];
        $res = array();
        if( current_user_can( 'edit_posts' ) ){
            foreach ($ids as $id){
                $img = wp_get_attachment_url( $id );
                if($img) $res[$id] = $img;
            }
        }
        echo json_encode($res);
        exit;
    }

    /**
     * Save the meta when the post is saved.
     */
    public function save_metabox($post_id){
        global $post, $is_wpcom_plugin_panel_save;
        if($post && $post->ID!=$post_id) return false;
        if($is_wpcom_plugin_panel_save) return false;
        $is_wpcom_plugin_panel_save = 1;

        if(isset($_POST['post_type'])){
            foreach($_POST as $key => $value) {
                if (preg_match('/^_wpcom_/i', $key)) {
                    $meta_boxes[] = preg_replace('/^_wpcom_/i', '', $key);
                }
            }
        }

        if(!isset($meta_boxes)||!$meta_boxes) return false;

        // Check if our nonce is set.
        if ( ! isset( $_POST['wpcom_meta_box_nonce'] ) )
            return $post_id;

        $nonce = $_POST['wpcom_meta_box_nonce'];

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, 'wpcom_meta_box' ) )
            return $post_id;

        // If this is an autosave, our form has not been submitted,
        // so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return $post_id;

        // Check the user's permissions.
        if ( 'page' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) )
                return $post_id;
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) )
                return $post_id;
        }

        $metas = get_post_meta( $post_id, '_wpcom_metas', true);
        $metas = is_array($metas) ? $metas : array();
        foreach ($meta_boxes as $meta) {
            if(preg_match('/^_/', $meta)){
                update_post_meta($post_id, $meta, stripslashes_deep( $_POST['_wpcom_'.$meta] ) );
            }else{
                $value = stripslashes_deep( $_POST['_wpcom_'.$meta] );

                if ( $value!='' )
                    $metas[$meta] = $value;
                else if ( isset($metas[$meta]) )
                    unset($metas[$meta]);

                update_post_meta($post_id, '_wpcom_metas', $metas );
            }
        }
    }
}