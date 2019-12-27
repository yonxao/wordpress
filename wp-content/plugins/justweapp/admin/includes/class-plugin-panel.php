<?php defined( 'ABSPATH' ) || exit;
class WPCOM_Plugin_Panel{
    function __construct( $args ){
        global $_wpcom_plugins;
        if(!isset($_wpcom_plugins)) $_wpcom_plugins = array();
        $this->info = $args;
        $this->key = isset($this->info['key']) ? $this->info['key'] : '';
        $this->version = isset($this->info['ver']) ? $this->info['ver'] : '';
        $this->basename = isset($this->info['basename']) ? $this->info['basename'] : '';
        $this->plugin_slug = isset($this->info['slug']) ? $this->info['slug'] : '';
        $this->updateName = 'wpcom_update_' . $this->info['plugin_id'];
        $this->automaticCheckDone = false;
        $_wpcom_plugins[$this->info['plugin_id']] = array('slug' => $this->plugin_slug, 'ver' => $this->version);

        add_action( 'wp_ajax_wpcom_callback', array($this, 'wpcom_callback') );
        add_action( 'wp_ajax_nopriv_wpcom_callback', array($this, 'wpcom_callback') );
        add_action( 'delete_site_transient_update_plugins', array($this, 'updated') );
        add_action( 'plugins_loaded', array($this, 'update_filter') );
        add_action( 'admin_menu', array($this, 'init') );
        add_action( 'admin_init', array($this, 'meta_setup') );
        add_action( 'wp_ajax_'.$this->plugin_slug.'_options', array($this, '_options') );
        add_action( 'wp_ajax_'.$this->plugin_slug.'_version', array($this, 'check_version') );
        add_action( 'wp_ajax_'.$this->plugin_slug.'_panel', array($this, 'form_action') );
    }

    function init(){
        $title = isset($this->info['title']) ? $this->info['title'] : '';
        $icon = isset($this->info['icon']) ? $this->info['icon'] : '';
        $position = isset($this->info['position']) ? $this->info['position'] : '85';

        add_menu_page( $title, $title, 'manage_options', $this->plugin_slug, array( &$this, 'options'), $icon, $position);

        if (current_user_can('manage_options' ) && isset($_GET['page']) && $_GET['page'] == $this->plugin_slug ) {
            // Load CSS
            wp_enqueue_style('plugin-panel', WPCOM_ADMIN_URI . 'css/panel.css', false, WPCOM_ADMIN_VERSION, 'all');
            wp_enqueue_style('wp-color-picker');

            // Load JS
            wp_enqueue_script("plugin-panel", WPCOM_ADMIN_URI . "js/panel.js", array('jquery', 'wp-color-picker'), WPCOM_ADMIN_VERSION, true);
            wp_enqueue_media();
        }
    }

    function meta_setup() {
        global $pagenow;
        require_once WPCOM_ADMIN_PATH . 'includes/class-post-meta.php';
        require_once WPCOM_ADMIN_PATH . 'includes/class-term-meta.php';
        if( $this->is_active() ) {
            new WPCOM_Plugin_Post_Meta($this->info);
            if( ($pagenow == 'edit-tags.php' || $pagenow == 'term.php' || (isset($_POST['action']) && $_POST['action']=='add-tag')) ) {
                $exclude_taxonomies = array('nav_menu', 'link_category', 'post_format');
                $taxonomies = get_taxonomies();
                foreach ($taxonomies as $key => $taxonomy) {
                    if (!in_array($key, $exclude_taxonomies)) {
                        new WPCOM_Plugin_Term_Meta($key, $this->info);
                    }
                }
            }
        }
    }

    function options(){
        do_action( 'wpcom_plugin_panel_init' );?>
        <div class="wrap wpcom-wrap" id="wpcom-plugin-panel">
            <div class="wpcom-panel-head">
                <div class="wpcom-panel-copy">WPCOM PLUGIN PANEL V<?php echo WPCOM_ADMIN_VERSION;?></div>
                <div class="wpcom-panel-h1"><i class="wpcom wpcom-logo"></i> 插件设置<small><?php echo isset($this->info['name'])?$this->info['name']:'';?></small></div>
            </div>
            <?php echo $this->build_form();?>
        </div>
    <?php }

    private function build_form(){
        require_once WPCOM_ADMIN_PATH . 'includes/class-utils.php';
        if($this->is_active()){ ?>
            <form action="" method="post" class="wpcom-panel-form form-horizontal">
                <?php wp_nonce_field( $this->key . '_options', $this->key . '_nonce', true );?>
                <div class="wpcom-panel-main">
                    <plugin-panel :ready="ready"/>
                    <div class="wpcom-panel-wrap"><div class="wpcom-panel-loading">正在加载...</div></div>
                </div>
                <div class="wpcom-panel-save clearfix">
                    <div class="col-xs-7" id="alert-info"></div>
                    <div class="col-xs-5 wpcom-panel-btn">
                        <button id="wpcom-panel-submit" type="button"  data-loading-text="正在保存..." class="button button-primary">保存设置</button>
                    </div>
                </div><!--.wpcom-panel-save-->
            </form>
            <script>_plugins_options = [<?php echo $this->init_plugin_options();?>];</script>
            <div style="display: none;"><?php wp_editor( 'EDITOR', 'WPCOM-EDITOR', WPCOM_ADMIN_UTILS::editor_settings(array('textarea_name'=>'EDITOR-NAME')) );?></div>
        <?php }else{
            $this->active_form();
            $this->updated();
        }
    }

    private function init_plugin_options(){
        require_once WPCOM_ADMIN_PATH . 'includes/class-utils.php';
        $res = array(
            'type' =>  'plugin',
            'ver' => $this->version,
            'plugin-id' => $this->info['plugin_id'],
            'plugin-slug' => $this->plugin_slug,
            'options' => get_option($this->key),
            'pages' => WPCOM_ADMIN_UTILS::get_all_pages(),
            'framework_ver' => WPCOM_ADMIN_VERSION,
            'filters' => apply_filters( $this->plugin_slug . '_settings', array() )
        );
        $res = apply_filters( 'wpcom_init_plugin_options', $res );
        $settings = $this->_get_extras();
        if(isset($settings->requires) && $settings->requires){
            $res['requires'] = array();
            foreach ($settings->requires as $req){
                $res['requires'][$req] = !!function_exists($req);
            }
        }
        return json_encode($res);
    }

    private function active_form(){
        if(isset($_POST['email'])){
            $email = trim($_POST['email']);
            $token = trim($_POST['token']);
            $err = false;
            if($email==''){
                $err = true;
                $err_email = '登录邮箱不能为空';
            }else if(!is_email( $email )){
                $err = true;
                $err_email = '登录邮箱格式不正确';
            }
            if($token==''){
                $err = true;
                $err_token = '激活码不能为空';
            }else if(strlen($token)!=32){
                $err = true;
                $err_token = '激活码不正确';
            }
            if($err==false){
                $hash_token = wp_hash_password($token);
                update_option( $this->plugin_slug . '_email', $email );
                update_option( $this->plugin_slug . '_token', $hash_token );

                $body = array('email'=>$email, 'token'=>$token, 'version'=>$this->version, 'home'=>get_option('siteurl'), 'themer' => WPCOM_ADMIN_VERSION, 'hash' => $hash_token);
                $result_body = json_decode( $this->send_request('active', $body));
                if( isset($result_body->result) && ($result_body->result=='0'||$result_body->result=='1') ){
                    $active = $result_body;
                    echo '<meta http-equiv="refresh" content="0">';
                }else if(isset($result_body->result)){
                    $active = $result_body;
                }else{
                    $active = new stdClass();
                    $active->result = 10;
                    $active->msg = '激活失败，请稍后再试！';
                }
            }
        }else if ( get_option($this->plugin_slug . '_email') && get_option($this->plugin_slug . '_token') ){
            $res = $this->plugin_update();
            if($res=='success') echo '<meta http-equiv="refresh" content="1">';
        } ?>
        <form class="form-horizontal active-form" id="wpcom-panel-form" method="post" action="">
            <h2 class="active-title">插件激活</h2>
            <div id="wpcom-panel-main" class="clearfix">
                <div class="form-horizontal">
                    <?php if (isset($active)) { ?><div class="form-group" style="margin-bottom: 0;"><p class="col-xs-offset-3 col-xs-9" style="    margin-left: 120px;<?php echo ($active->result==0||$active->result==1?'color:green;':'color:#F33A3A;');?>"><?php echo $active->msg; ?></p></div><?php } ?>
                    <div class="form-group">
                        <label for="email" class="col-xs-3 control-label">登录邮箱</label>
                        <div class="col-xs-9">
                            <input type="email" name="email" class="form-control" id="email" value="<?php echo isset($email)?$email:''; ?>" placeholder="请输入WPCOM登录邮箱">
                            <?php if(isset($err_email)){ ?><div class="j-msg" style="color:#F33A3A;font-size:12px;margin-top:3px;margin-left:3px;"><?php echo $err_email;?></div><?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="token" class="col-xs-3 control-label">激活码</label>
                        <div class="col-xs-9">
                            <input type="password" name="token" class="form-control" id="token" value="<?php echo isset($token)?$token:'';?>" placeholder="请输入激活码" autocomplete="off">
                            <?php if(isset($err_token)){ ?><div class="j-msg" style="color:#F33A3A;font-size:12px;margin-top:3px;margin-left:3px;"><?php echo $err_token;?></div><?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-3 control-label"></label>
                        <div class="col-xs-9">
                            <input type="submit" class="button button-primary button-active" value="提 交">
                        </div>
                    </div>
                </div>
            </div><!--#wpcom-panel-main-->
        </form>
    <?php }

    public function form_action(){
        $post = isset($_POST['data']) ? $_POST['data'] : '';
        wp_parse_str($post, $data);

        if ( ! isset( $data[$this->key . '_nonce'] ) )
            return ;

        $nonce = $data[$this->key . '_nonce'];

        if ( ! wp_verify_nonce( $nonce, $this->key . '_options' ) )
            return ;

        unset($data[$this->key . '_nonce']);
        unset($data['_wp_http_referer']);

        if($this->set_options( $data )){
            $output = array(
                'errcode' => 0,
                'errmsg' => '设置保存成功~'
            );
            do_action( $this->plugin_slug.'_options_updated' );
        }else{
            $output = array(
                'errcode' => 1,
                'errmsg' => '额，你好像什么也没改呢？'
            );
        }
        echo wp_json_encode($output);
        exit;
    }

    function set_options($data){
        $options = get_option($this->key);
        if(!$options) $options = array();
        foreach($data as $key => $value){
            $options[$key] = $value;
        }
        return update_option( $this->key, $options );
    }

    private function _get_extras(){
        $ops = base64_decode(get_option( $this->info['plugin_id'] . '_extras' ));
        $token = get_option($this->plugin_slug . "_token");
        $ops = base64_decode(str_replace(md5($token), '', $ops));
        return json_decode($ops);
    }

    private function send_request($type, $body, $method='POST') {
        $url = 'http://www.wpcom.cn/authentication/'.$type.'/' . $this->info['plugin_id'];
        $result = wp_remote_request($url, array('method' => $method, 'body'=>$body));
        if(is_array($result)){
            return $result['body'];
        }
    }

    public function is_active(){
        if(isset($this->is_active) && $this->is_active) return true;
        $this->is_active = false;
        if( !isset($this->_extras)) $this->_extras = $this->_get_extras();
        if($this->_extras){
            $domain = $this->_extras->domain;
            $home = parse_url(get_option('siteurl'));
            $host = $home['host'];
        }
        if( $this->_extras && $host==$domain && get_option($this->plugin_slug . "_token")) $this->is_active = true;
        return $this->is_active;
    }

    public function _options(){
        $res = array();
        if( current_user_can( 'publish_posts' ) ){
            if(current_user_can( 'edit_theme_options' )) {
                $this->plugin_update();
            }
            $res['o'] = get_option( $this->info['plugin_id'] . '_options' );
        }
        echo json_encode($res);
        exit;
    }

    public function wpcom_callback(){
        global $_wpcom_plugins;
        $post = $_POST;

        $data = isset($post['data']) ? $post['data'] : '';
        $data = maybe_unserialize(stripcslashes($data));

        if(!$data){
            echo 'Data error';
            exit;
        }

        $plugin_id = $data['theme'];
        if( !isset($data['theme']) || !isset($_wpcom_plugins[$plugin_id]) ) {
            echo 'plugin id is null';
            exit;
        }

        $token = get_option($_wpcom_plugins[$plugin_id]['slug'] . "_token");

        if(!wp_check_password($data['token'], $token)){
            echo 'Token error';
            exit;
        }

        if( isset($data['options']) && isset($data['themer']) && version_compare($data['themer'], WPCOM_ADMIN_VERSION) <= 0 ) {
            @$this->update_option( $plugin_id . "_extras", $data['extras'], 'no' );
            @$this->update_option( $plugin_id . "_options", $data['options'], 'no' );
            wp_cache_delete ( 'alloptions', 'options' );            
        }else if(isset($data['package'])){
            $state = get_option('wpcom_update_' . $plugin_id);
            if ( empty($state) ){
                $state = new StdClass;
                $state->lastCheck = time();
                $state->checkedVersion = $_wpcom_plugins[$plugin_id]['ver'];
                $state->update = null;
            }
            if(version_compare($_wpcom_plugins[$plugin_id]['ver'], $data['version'])<0) {
                $state->update = new StdClass;
                $state->update->version = $data['version'];
                $state->update->url = $data['url'];
                $state->update->package = $data['package'];
                $this->update_option('wpcom_update_' . $plugin_id, $state);
            }
        }
        echo 'success';
        exit;
    }

    private function update_option($option_name, $value, $autoload='yes'){
        $res = update_option($option_name, $value, $autoload );
        if( !$res ){
            global $wpdb;
            $option = @$wpdb->get_row( "SELECT * FROM $wpdb->options WHERE option_name = $option_name" );
            if(null !== $option) {
                $wpdb->update($wpdb->options,
                    array('option_value' => $value, 'autoload' => $autoload),
                    array('option_name' => $option_name)
                );
            }else{
                $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option_name, $value, $autoload ) );
            }
        }
    }

    function update_filter(){
        if(is_admin()) {
            $options = get_option($this->key);
            if ($this->is_active() && (isset($options['auto_check_update']) && $options['auto_check_update'] == '1')) {
                add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
            } else {
                delete_option($this->updateName);
            }
        }
    }

    private function plugin_update(){
        global $plugin_updated;
        if(isset($plugin_updated) && $plugin_updated){ // 防多次请求
            return false;
        }else{
            $plugin_updated = 1;
        }
        $version = $this->_get_version();
        $current_ver = $this->get_version();
        if(is_admin() && version_compare($version, $current_ver)<0){
            $email = get_option($this->plugin_slug . '_email');
            $token = get_option($this->plugin_slug . '_token');
            if($email && $token){
                $body = array('email'=>$email, 'token'=>$token, 'version'=>$current_ver, 'home'=>get_option('siteurl'), 'themer' => $this->framework_version());
                $this->send_request('update', $body);
            }
        }
    }

    private function get_version(){
        if( function_exists('file_get_contents') ){
            $files = @file_get_contents( WP_PLUGIN_DIR . '/' . $this->basename );
            preg_match('/define\s*?\(\s*?[\'|"][^\s]*_VERSION[\'|"],\s*?[\'|"](.*)[\'|"].*?\)/i', $files, $matches);
            if( isset($matches[1]) && $matches[1] ){
                return trim($matches[1]);
            }
        }
        return $this->version;
    }

    private function framework_version(){
        if( function_exists('file_get_contents') ){
            $files = @file_get_contents( WPCOM_ADMIN_PATH . 'load.php' );
            preg_match('/define\s*?\(\s*?[\'|"]WPCOM_ADMIN_VERSION[\'|"],\s*?[\'|"](.*)[\'|"].*?\)/i', $files, $matches);
            if( isset($matches[1]) && $matches[1] ){
                return trim($matches[1]);
            }
        }
        return WPCOM_ADMIN_VERSION;
    }

    public function updated(){
        flush_rewrite_rules();
        delete_option($this->updateName);
        $this->plugin_update();
    }

    private function _get_version(){
        if($settings = $this->_get_extras()){
            return $settings->version;
        }else if($ops = base64_decode(get_option('wpcom_' . $this->info['plugin_id']))){
            $token = get_option($this->plugin_slug . "_token");
            $ops = base64_decode(str_replace(md5($token.strtolower($this->plugin_slug)), '', $ops));
            $settings = json_decode($ops);
            if(isset($settings->version)) {
                return $settings->version;
            }
        }
    }

    public function check_version(){
        $options = get_option($this->key);
        $body = array('version'=>$this->version,'email' => get_option($this->plugin_slug . "_email"),'home' => get_option('siteurl'),'themer' => WPCOM_ADMIN_VERSION);
        echo $this->send_request('check', $body);
        if(isset($options['auto_check_update']) && $options['auto_check_update']=='1')
            $this->check_update(0);
        exit;
    }

    public function check_update($value){
        if ($value && empty( $value->checked ) )
            return $value;

        if ( !current_user_can('update_plugins' ) )
            return $value;

        if ( !$this->automaticCheckDone ) {
            $body = array('email' => get_option($this->plugin_slug . "_email"), 'token' => get_option($this->plugin_slug . "_token"), 'version' => $this->version, 'home' => get_option('siteurl'), 'themer' => WPCOM_ADMIN_VERSION);
            $req = $this->send_request('notify', $body);
            $this->automaticCheckDone = true;

            $this->plugin_update();
        }

        if ( !$value ) { // 手动点击更新
            $last_update = get_site_transient( 'update_plugins' );
            if ( ! is_object($last_update) ) $last_update = new stdClass;
            if ( !isset($last_update->checked) || !$last_update->checked ) {
                $plugins = get_plugins();
                $checked = array();
                foreach ( $plugins as $file => $p ) {
                    $checked[ $file ] = $p['Version'];
                }
                $last_update->checked = $checked;
                if(!isset($last_update->last_checked)) $last_update->last_checked = time();
            }

            return set_site_transient( 'update_plugins', $last_update, 3 * HOUR_IN_SECONDS );
        }

        $plugin_update_state = get_option($this->updateName);

        if ( !empty($plugin_update_state) && isset($plugin_update_state->update) && !empty($plugin_update_state->update) ){
            $update = $plugin_update_state->update;
            $value->response[$this->basename] = array(
                'slug' => $this->info['slug'],
                'plugin' => $this->info['basename'],
                'new_version' => $update->version,
                'url' => $update->url,
                'package' => $update->package,
                'upgrade_notice' => ''
            );

            $value->response[$this->basename] = json_decode(json_encode($value->response[$this->basename]));
        }

        return $value;
    }
}