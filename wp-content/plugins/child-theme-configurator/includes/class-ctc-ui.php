<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
/*
    Class: Child_Theme_Configurator_UI
    Plugin URI: http://www.childthemeconfigurator.com/
    Description: Handles the plugin User Interface
    Version: 2.0.0
    Author: Lilaea Media
    Author URI: http://www.lilaeamedia.com/
    Text Domain: chld_thm_cfg
    Domain Path: /lang
    License: GPLv2
    Copyright (C) 2014-2015 Lilaea Media
*/
class ChildThemeConfiguratorUI {

    var $warnings = array();
    var $swatch_txt;
    var $colors;
    
    function __construct() {
        add_action( 'admin_enqueue_scripts',            array( $this, 'enqueue_scripts' ), 99 );
        add_filter( 'chld_thm_cfg_files_tab_filter',    array( $this, 'render_files_tab_options' ) );
        add_action( 'chld_thm_cfg_tabs',                array( $this, 'render_addl_tabs' ), 10, 4 );
        add_action( 'chld_thm_cfg_panels',              array( $this, 'render_addl_panels' ), 10, 4 );
        add_action( 'chld_thm_cfg_related_links',       array( $this, 'lilaea_plug' ) );
        add_action( 'admin_notices',                    array( $this, 'get_colors' ) );
        // temporary hook until Pro is updated
        add_filter( 'chld_thm_cfg_localize_array',      array( $this, 'filter_localize_array' ) );
        if ( $this->ctc()->is_debug ):
            //$this->ctc()->debug( 'adding new debug action...', __FUNCTION__ );
            add_action( 'chld_thm_cfg_print_debug', array( $this->ctc(), 'print_debug' ) );
        endif;
        $this->swatch_txt = __( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'child-theme-configurator' );
    }
    // helper function to globalize ctc object
    function ctc() {
        return ChildThemeConfigurator::ctc();
    }
    function css() {
        return ChildThemeConfigurator::ctc()->css;
    }
    function get_colors(){
        global $_wp_admin_css_colors;
        $user_admin_color = get_user_meta( get_current_user_id(), 'admin_color', TRUE );
        $this->colors = $_wp_admin_css_colors[ $user_admin_color ]->colors;
        
    }
    function render() {
        // load web fonts for this theme
        if ( $imports = $this->css()->get_prop( 'imports' ) ):
            $ext = 0;
            foreach ( $imports as $import ):
                $this->ctc()->convert_import_to_enqueue( $import, ++$ext, TRUE );
            endforeach;
        endif;
        $themes     = $this->ctc()->themes;
        $child      = $this->css()->get_prop( 'child' );
        $hidechild  = ( count( $themes[ 'child' ] ) ? '' : 'style="display:none"' );
        $enqueueset = ( isset( $this->css()->enqueue ) && $child );
        $this->ctc()->debug( 'Enqueue set: ' . ( $enqueueset ? 'TRUE' : 'FALSE' ), __FUNCTION__ );
        $imports    = $this->css()->get_prop( 'imports' );
        $id         = 0;
        $this->ctc()->fs_method = get_filesystem_method();
        add_thickbox();
        include ( CHLD_THM_CFG_DIR . '/includes/forms/main.php' ); 
    } 

    function render_theme_menu( $template = 'child', $selected = NULL ) {
        
         ?>
        <select class="ctc-select" id="ctc_theme_<?php echo $template; ?>" name="ctc_theme_<?php echo $template; ?>" 
            style="visibility:hidden" <?php echo $this->ctc()->is_theme() ? '' : ' disabled '; ?> autocomplete="off" >
            <?php
            uasort( $this->ctc()->themes[ $template ], array( $this, 'cmp_theme' ) );
            foreach ( $this->ctc()->themes[ $template ] as $slug => $theme )
                echo '<option value="' . $slug . '"' . ( $slug == $selected ? ' selected' : '' ) . '>' 
                    . esc_attr( $theme[ 'Name' ] ) . '</option>' . LF; 
        ?>
        </select>
        <div style="display:none">
        <?php 
        foreach ( $this->ctc()->themes[ $template ] as $slug => $theme )
            include ( CHLD_THM_CFG_DIR . '/includes/forms/themepreview.php' ); ?>
        </div>
        <?php
    }
    
    function cmp_theme( $a, $b ) {
        return strcmp( strtolower( $a[ 'Name' ] ), strtolower( $b[ 'Name' ] ) );
    }
        
    function render_file_form( $template = 'parnt' ) {
        global $wp_filesystem; 
        if ( $theme = $this->ctc()->css->get_prop( $template ) ):
            $themeroot  = trailingslashit( get_theme_root() ) . trailingslashit( $theme );
            $files      = $this->ctc()->get_files( $theme );
            $counter    = 0;
            sort( $files );
            ob_start();
            foreach ( $files as $file ):
                $templatefile = preg_replace( '%\.php$%', '', $file );
                include ( CHLD_THM_CFG_DIR . '/includes/forms/file.php' );            
            endforeach;
            if ( 'child' == $template && ( $backups = $this->ctc()->get_files( $theme, 'backup,pluginbackup' ) ) ):
                foreach ( $backups as $backup => $label ):
                    $templatefile = preg_replace( '%\.css$%', '', $backup );
                    include ( CHLD_THM_CFG_DIR . '/includes/forms/backup.php' );            
                endforeach;
            endif;
            $inputs = ob_get_contents();
            ob_end_clean();
            if ( $counter ):
                include ( CHLD_THM_CFG_DIR . '/includes/forms/fileform.php' );            
            endif;
        else:
            echo $template . ' theme not set.';
        endif;
    }
    
    function render_image_form() {
         
        if ( $theme = $this->ctc()->css->get_prop( 'child' ) ):
            $themeuri   = trailingslashit( get_theme_root_uri() ) . trailingslashit( $theme );
            $files = $this->ctc()->get_files( $theme, 'img' );
            
            $counter = 0;
            sort( $files );
            ob_start();
            foreach ( $files as $file ): 
                $templatefile = preg_replace( '/^images\//', '', $file );
                include( CHLD_THM_CFG_DIR . '/includes/forms/image.php' );             
            endforeach;
            $inputs = ob_get_contents();
            ob_end_clean();
            if ( $counter ) include( CHLD_THM_CFG_DIR . '/includes/forms/images.php' );
        endif;
    }
    
    function get_theme_screenshot() {
        
        foreach ( array_keys( $this->ctc()->imgmimes ) as $extreg ): 
            foreach ( explode( '|', $extreg ) as $ext ):
                if ( $screenshot = $this->ctc()->css->is_file_ok( $this->ctc()->css->get_child_target( 'screenshot.' . $ext ) ) ):
                    $screenshot = trailingslashit( get_theme_root_uri() ) . $this->ctc()->theme_basename( '', $screenshot );
                    return $screenshot . '?' . time();
                endif;
            endforeach; 
        endforeach;
        return FALSE;
    }
    
    function settings_errors() {
        
        if ( count( $this->ctc()->errors ) ):
            echo '<div class="error notice is-dismissible"><ul>' . LF;
            foreach ( $this->ctc()->errors as $err ):
                echo '<li>' . $err . '</li>' . LF;
            endforeach;
            echo '</ul></div>' . LF;
        elseif ( isset( $_GET[ 'updated' ] ) ):
            $child_theme = wp_get_theme( $this->ctc()->css->get_prop( 'child' ) );
            echo '<div class="updated notice is-dismissible">' . LF;
            if ( 8 == $_GET[ 'updated' ] ):
                echo '<p>' . __( 'Child Theme files modified successfully.', 'child-theme-configurator' ) . '</p>' . LF;
            elseif ( 4 == $_GET[ 'updated' ] ):
                echo '<p>' . sprintf( __( 'Child Theme <strong>%s</strong> has been reset. Please configure it using the settings below.', 'child-theme-configurator' ), $child_theme->Name ) . '</p>' . LF;
            else:
                echo '<p class="ctc-success-response">' . apply_filters( 'chld_thm_cfg_update_msg', sprintf( __( 'Child Theme <strong>%s</strong> has been generated successfully.', 'child-theme-configurator' ), $child_theme->Name ), $this->ctc() ) . LF;
                if ( $this->ctc()->is_theme() ):
                    echo '<strong>' . __( 'IMPORTANT:', 'child-theme-configurator' ) . LF;
                    if ( is_multisite() && !$child_theme->is_allowed() ): 
                        echo 'You must <a href="' . network_admin_url( '/themes.php' ) . '" title="' . __( 'Go to Themes', 'child-theme-configurator' ) . '" class="ctc-live-preview">' . __( 'Network enable', 'child-theme-configurator' ) . '</a> ' . __( 'your child theme.', 'child-theme-configurator' );
                    else: 
                        echo '<a href="' . admin_url( '/customize.php?theme=' . $this->ctc()->css->get_prop( 'child' ) ) . '" title="' . __( 'Live Preview', 'child-theme-configurator' ) . '" class="ctc-live-preview">' . __( 'Preview your child theme', 'child-theme-configurator' ) . '</a> ' . __( 'before activating.', 'child-theme-configurator' );
                    endif;
                    echo '</strong></p>' . LF;
                endif;
             endif;
            echo '</div>' . LF;
        endif;
    }
    
    function render_help_content() {
	    global $wp_version;
	    if ( version_compare( $wp_version, '3.3' ) >= 0 ):
		    $screen = get_current_screen();
                
            // load help content via output buffer so we can use plain html for updates
            // then use regex to parse for help tab parameter values
            
            $regex_sidebar = '/' . preg_quote( '<!-- BEGIN sidebar -->' ) . '(.*?)' . preg_quote( '<!-- END sidebar -->' ) . '/s';
            $regex_tab = '/' . preg_quote( '<!-- BEGIN tab -->' ) . '\s*<h\d id="(.*?)">(.*?)<\/h\d>(.*?)' . preg_quote( '<!-- END tab -->' ) . '/s';
            $locale = get_locale();
            $dir = CHLD_THM_CFG_DIR . '/includes/help/';
            $file = $dir . $locale . '.php';
            if ( !is_readable( $file ) ) $file = $dir . 'en_US.php';
            ob_start();
            include( $file );
            $help_raw = ob_get_contents();
            ob_end_clean();
            // parse raw html for tokens
            preg_match( $regex_sidebar, $help_raw, $sidebar );
            preg_match_all( $regex_tab, $help_raw, $tabs );

    		// Add help tabs
            if ( isset( $tabs[ 1 ] ) ):
                $priority = 0;
                while( count( $tabs[ 1 ] ) ):
                    $id         = array_shift( $tabs[ 1 ] );
                    $title      = array_shift( $tabs[ 2 ] );
                    $content    = array_shift( $tabs[ 3 ] );
                    $tab = array(
	    	    	    'id'        => $id,
    		    	    'title'     => $title,
	    		        'content'   => $content, 
                        'priority'  => ++$priority,
                    );
	    	        $screen->add_help_tab( $tab );
                endwhile;
            endif;
            if ( isset( $sidebar[ 1 ] ) )
                $screen->set_help_sidebar( $sidebar[ 1 ] );
//die( print_r( $screen, TRUE ) );
        endif;
    }
    
    function render_addl_tabs( $ctc, $active_tab = NULL, $hidechild = '' ) {
        include ( CHLD_THM_CFG_DIR . '/includes/forms/addl_tabs.php' );            
    }

    function render_addl_panels( $ctc, $active_tab = NULL, $hidechild = '' ) {
        include ( CHLD_THM_CFG_DIR . '/includes/forms/addl_panels.php' );            
    }

    function lilaea_plug() {
        include ( CHLD_THM_CFG_DIR . '/includes/forms/related.php' );
    }
    
    function render_files_tab_options( $output ) {
        $regex = '%<div class="ctc\-input\-cell clear">.*?(</form>).*%s';
        $output = preg_replace( $regex, "$1", $output );
        return $output;
    }
    
    function enqueue_scripts() {
        wp_enqueue_style( 'chld-thm-cfg-admin', CHLD_THM_CFG_URL . 'css/chldthmcfg.css', array(), '2.0.0' );
        
        // we need to use local jQuery UI Widget/Menu/Selectmenu 1.11.2 because selectmenu is not included in < 1.11.2
        // this will be updated in a later release to use WP Core scripts when it is widely adopted
        if ( !wp_script_is( 'jquery-ui-selectmenu', 'registered' ) ): // selectmenu.min.js
            wp_enqueue_script( 'jquery-ui-selectmenu', CHLD_THM_CFG_URL . 'js/selectmenu.min.js', 
                array( 'jquery','jquery-ui-core','jquery-ui-position' ), FALSE, TRUE );
        endif;
        wp_enqueue_script( 'chld-thm-cfg-spectrum', CHLD_THM_CFG_URL . 'js/spectrum.min.js', array( 'jquery' ), FALSE, TRUE );
        wp_enqueue_script( 'chld-thm-cfg-ctcgrad', CHLD_THM_CFG_URL . 'js/ctcgrad.min.js', array( 'jquery' ), FALSE, TRUE );
        wp_enqueue_script( 'chld-thm-cfg-admin', CHLD_THM_CFG_URL . 'js/chldthmcfg.min.js',
            array(
                'jquery-ui-autocomplete', 
                'jquery-ui-selectmenu',   
                'chld-thm-cfg-spectrum',
                'chld-thm-cfg-ctcgrad'
            ), FALSE, TRUE );
            
        $localize_array = apply_filters( 'chld_thm_cfg_localize_script', array(
            'converted'         => $this->css()->get_prop( 'converted' ),
            'ssl'               => is_ssl(),
            'homeurl'           => get_home_url() . '?preview_ctc=' . wp_create_nonce(),
            'ajaxurl'           => admin_url( 'admin-ajax.php' ),
            'theme_uri'         => get_theme_root_uri(),
            'page'              => CHLD_THM_CFG_MENU,
            'themes'            => $this->ctc()->themes,
            'source'            => apply_filters( 'chld_thm_cfg_source_uri', get_theme_root_uri() . '/' 
                                    . $this->css()->get_prop( 'parnt' ) . '/style.css', $this->css() ),
            'target'            => apply_filters( 'chld_thm_cfg_target_uri', get_theme_root_uri() . '/' 
                                    . $this->css()->get_prop( 'child' ) . '/style.css', $this->css() ),
            'parnt'             => $this->css()->get_prop( 'parnt' ),
            'child'             => $this->css()->get_prop( 'child' ),
            'addl_css'          => $this->css()->get_prop( 'addl_css' ),
            'imports'           => $this->css()->get_prop( 'imports' ),
            'converted'         => $this->css()->get_prop( 'converted' ),
            'is_debug'          => $this->ctc()->is_debug,
            '_background_url_txt'       => __( 'URL/None', 'child-theme-configurator' ),
            '_background_origin_txt'    => __( 'Origin', 'child-theme-configurator' ),
            '_background_color1_txt'    => __( 'Color 1', 'child-theme-configurator' ),
            '_background_color2_txt'    => __( 'Color 2', 'child-theme-configurator' ),
            '_border_width_txt'         => __( 'Width/None', 'child-theme-configurator' ),
            '_border_style_txt'         => __( 'Style', 'child-theme-configurator' ),
            '_border_color_txt'         => __( 'Color', 'child-theme-configurator' ),
            'swatch_txt'        => $this->swatch_txt,
            'load_txt'          => __( 'Are you sure you wish to RESET? This will destroy any work you have done in the Configurator.', 'child-theme-configurator' ),
            'important_txt'     => __( '<span style="font-size:10px">!</span>', 'child-theme-configurator' ),
            'selector_txt'      => __( 'Selectors', 'child-theme-configurator' ),
            'close_txt'         => __( 'Close', 'child-theme-configurator' ),
            'edit_txt'          => __( 'Edit Selector', 'child-theme-configurator' ),
            'cancel_txt'        => __( 'Cancel', 'child-theme-configurator' ),
            'rename_txt'        => __( 'Rename', 'child-theme-configurator' ),
            'css_fail_txt'      => __( 'The stylesheet cannot be displayed.', 'child-theme-configurator' ),
            'child_only_txt'    => __( '(Child Only)', 'child-theme-configurator' ),
            'inval_theme_txt'   => __( 'Please enter a valid Child Theme.', 'child-theme-configurator' ),
            'inval_name_txt'    => __( 'Please enter a valid Child Theme name.', 'child-theme-configurator' ),
            'theme_exists_txt'  => __( '<strong>%s</strong> exists. Please enter a different Child Theme', 'child-theme-configurator' ),
            'js_txt'            => __( 'The page could not be loaded correctly.', 'child-theme-configurator' ),
            'jquery_txt'        => __( 'Conflicting or out-of-date jQuery libraries were loaded by another plugin:', 'child-theme-configurator' ),
            'plugin_txt'        => __( 'Deactivating or replacing plugins may resolve this issue.', 'child-theme-configurator' ),
            'contact_txt'       => sprintf( __( '%sWhy am I seeing this?%s', 'child-theme-configurator' ),
                '<a target="_blank" href="' . CHLD_THM_CFG_DOCS_URL . '/how-to-use/#script_dep">',
                '</a>' ),
            'nosels_txt'        => __( 'No Styles Available. Check Parent/Child settings.', 'child-theme-configurator' ),
            'anlz1_txt'         => __( 'Updating', 'child-theme-configurator' ),
            'anlz2_txt'         => __( 'Checking', 'child-theme-configurator' ),
            'anlz3_txt'         => __( 'The theme "%s" generated unexpected PHP debug output.', 'child-theme-configurator' ),
            'anlz4_txt'         => __( 'The theme "%s" could not be loaded.', 'child-theme-configurator' ),
            'anlz5_txt'         => __( '<p>Please verify the theme has been installed correctly.</p>', 'child-theme-configurator' ),
            'anlz6_txt'         => __( 'Show Debug Output', 'child-theme-configurator' ),
            'anlz7_txt'         => __( "<p>You may not be able to use this Theme as a Child Theme while these conditions exist.</p><p>It is possible that this theme has specific requirements to work correctly as a child theme. Check your theme's documentation for more information.</p><p>Please make sure you are using the latest version of this theme. If so, please contact this Theme's author and report the error list above.</p>", 'child-theme-configurator' ),
            'anlz8_txt'         => __( 'Do Not Activate "%s"! A PHP FATAL ERROR has been detected.', 'child-theme-configurator' ),
            'anlz9_txt'         => __( 'This theme loads stylesheets after the wp_styles queue.', 'child-theme-configurator' ),
            'anlz10_txt'        => __( '<p>This makes it difficult for plugins to override these styles. You can try to resolve this using the  "Repair header template" option (Step 6, "Additional handling options", below).</p>', 'child-theme-configurator' ),
            'anlz11_txt'        => __( "This theme loads the parent theme's <code>style.css</code> file outside the wp_styles queue.", 'child-theme-configurator' ),
            'anlz12_txt'        => __( '<p>This is common with older themes but requires the use of <code>@import</code>, which is no longer recommended. You can try to resolve this using the "Repair header template" option (see step 6, "Additional handling options", below).</p>', 'child-theme-configurator' ),
            'anlz13_txt'        => __( 'This child theme does not load a Configurator stylesheet.', 'child-theme-configurator' ),
            'anlz14_txt'        => __( '<p>If you want to customize styles using this plugin, please click "Configure Child Theme" again to add this to the settings.</p>', 'child-theme-configurator' ),
            'anlz15_txt'        => __( "This child theme uses the parent stylesheet but does not load the parent theme's <code>style.css</code> file.", 'child-theme-configurator' ),
            'anlz16_txt'        => __( '<p>Please select a stylesheet handling method or check "Ignore parent theme stylesheets" (see step 6, below).</p>', 'child-theme-configurator' ),
            'anlz17_txt'        => __( 'This child theme appears to be functioning correctly.', 'child-theme-configurator' ),
            'anlz18_txt'        => __( 'This theme appears OK to use as a Child theme.', 'child-theme-configurator' ),
            'anlz19_txt'        => __( 'This Child Theme has not been configured for this plugin.', 'child-theme-configurator' ),
            'anlz20_txt'        => __( '<p>The Configurator makes significant modifications to the child theme, including stylesheet changes and additional php functions. Please consider using the DUPLICATE child theme option (see step 1, above) and keeping the original as a backup.</p>', 'child-theme-configurator' ),
            'anlz21_txt'        => __( "This child theme uses <code>@import</code> to load the parent theme's <code>style.css</code> file.", 'child-theme-configurator' ),
            'anlz22_txt'        => __( '<p>Please consider selecting "Use the WordPress style queue" for the parent stylesheet handling option (see step 6, below).</p>', 'child-theme-configurator' ),
            'anlz23_txt'        => __( 'This theme loads additional stylesheets after the <code>style.css</code> file:', 'child-theme-configurator' ),
            'anlz24_txt'        => __( '<p>Consider saving new custom styles to a "Separate stylesheet" (see step 5, below) so that you can customize these styles.</p>', 'child-theme-configurator' ),
            'anlz25_txt'        => __( "The parent theme's <code>style.css</code> file is being loaded automatically.", 'child-theme-configurator' ),
            'anlz26_txt'        => __( '<p>"Do not add any parent stylesheet handling" has been selected for "Parent stylesheet handling" (see step 6, below).</p>', 'child-theme-configurator' ),
            'anlz27_txt'        => __( "This theme does not require the parent theme's <code>style.css</code> file for its appearance.", 'child-theme-configurator' ),
            'anlz28_txt'        => __( "This Child Theme was configured with an earlier version.", 'child-theme-configurator' ),
            'anlz29_txt'        => __( '<p>The selected stylesheet handling method is no longer used. Please update the configuration using the "Repair header template" option (see step 6, "Additional handling options", below).</p>', 'child-theme-configurator' ),
            'anlz30_txt'         => __( 'Show Analysis Object', 'child-theme-configurator' ),
            'anlz31_txt'         => __( 'This child theme was configured using the CTC Pro "Genesis stylesheet handling" method.', 'child-theme-configurator' ),
            'anlz32_txt'         => __( '<p>This method has been replaced by the "Separate stylesheet" and "Ignore Parent Theme" options ( selected below ) for broader framework compatability.</p>', 'child-theme-configurator' ),
        ) );
        wp_localize_script(
            'chld-thm-cfg-admin', 
            'ctcAjax', 
            apply_filters( 'chld_thm_cfg_localize_array', $localize_array )
        );
    }
    
    function filter_localize_array( $arr ) {
        $arr[ 'pluginmode' ] = !$this->ctc()->is_theme();
        return $arr;
    }
    function notices( $msg ) { 
    ?>
<div class="notice-warning notice is-dismissible<?php echo ( 'upgrade' == $msg ? ' ctc-upgrade-notice' : '' ); ?>" style="display:block"><?php
        switch( $msg ):
        
            case 'writable': ?>
            
        <div class="ctc-section-toggle" id="ctc_perm_options"><?php _e( 'The child theme is in read-only mode and Child Theme Configurator cannot apply changes. Click to see options', 'child-theme-configurator' ); ?></div><div class="ctc-section-toggle-content" id="ctc_perm_options_content"><p><ol><?php
        $ctcpage = apply_filters( 'chld_thm_cfg_admin_page', CHLD_THM_CFG_MENU );
        if ( 'WIN' != substr( strtoupper( PHP_OS ), 0, 3 ) ):
            _e( '<li>Temporarily set write permissions by clicking the button below. When you are finished editing, revert to read-only by clicking "Make read-only" under the "Files" tab.</li>', 'child-theme-configurator' );
?><form action="?page=<?php echo $ctcpage; ?>" method="post">
    <?php wp_nonce_field( apply_filters( 'chld_thm_cfg_action', 'ctc_update' ) ); ?>
<input name="ctc_set_writable" class="button" type="submit" value="<?php _e( 'Make files writable', 'child-theme-configurator' ); ?>"/></form><?php   endif;
        _e( '<li><a target="_blank"  href="http://codex.wordpress.org/Editing_wp-config.php#WordPress_Upgrade_Constants" title="Editin wp-config.php">Add your FTP/SSH credentials to the WordPress config file</a>.</li>', 'child-theme-configurator' );
        if ( isset( $_SERVER[ 'SERVER_SOFTWARE' ] ) && preg_match( '%iis%i',$_SERVER[ 'SERVER_SOFTWARE' ] ) )
            _e( '<li><a target="_blank" href="http://technet.microsoft.com/en-us/library/cc771170" title="Setting Application Pool Identity">Assign WordPress to an application pool that has write permissions</a> (Windows IIS systems).</li>', 'child-theme-configurator' );
        _e( '<li><a target="_blank" href="http://codex.wordpress.org/Changing_File_Permissions" title="Changing File Permissions">Set write permissions on the server manually</a> (not recommended).</li>', 'child-theme-configurator' );
        if ( 'WIN' != substr( strtoupper( PHP_OS ), 0, 3 ) ):
            _e( '<li>Run PHP under Apache with suEXEC (contact your web host).</li>', 'child-theme-configurator' );
        endif; ?>
        </ol></p></div><?php
                break;
                
                
            case 'owner':
            
                $ctcpage = apply_filters( 'chld_thm_cfg_admin_page', CHLD_THM_CFG_MENU ); // FIXME? ?>
        <p><?php _e( 'This Child Theme is not owned by your website account. It may have been created by a prior version of this plugin or by another program. Moving forward, it must be owned by your website account to make changes. Child Theme Configurator will attempt to correct this when you click the button below.', 'child-theme-configurator' ) ?></p>
<form action="?page=<?php echo $ctcpage; ?>" method="post"><?php 
                wp_nonce_field( apply_filters( 'chld_thm_cfg_action', 'ctc_update' ) ); 
                break;
                
                
            case 'enqueue': ?>
            
        <p><?php _e( 'Child Theme Configurator needs to update its internal data. Please set your preferences below and click "Generate Child Theme Files" to update your configuration.', 'child-theme-configurator' ) ?></p><?php
                break;
                
                
            case 'max_styles':
            
                echo sprintf( __( '<strong>However, some styles could not be parsed due to memory limits.</strong> Try deselecting "Additional Stylesheets" below and click "Generate/Rebuild Child Theme Files". %sWhy am I seeing this?%s', 'child-theme-configurator' ), 
                '<a target="_blank" href="' . LILAEAMEDIA_URL . '/child-theme-configurator#php_memory">',
                '</a>' );
                break;
                
                
            case 'config': ?>
            
        <p><?php _e( 'Child Theme Configurator did not detect any configuration data because a previously configured Child Theme has been removed. Please follow the steps for "CONFIGURE an existing Child Theme" under the "Parent/Child" Tab.', 'child-theme-configurator' ) ?></p><?php
                break;
                
                
            case 'changed': ?>
            
        <p><?php _e( 'Your stylesheet has changed since the last time you used the Configurator. Please follow the steps for "CONFIGURE an existing Child Theme" under the "Parent/Child" Tab or you will lose these changes.', 'child-theme-configurator' ) ?></p><?php
                break;
                
                
            case 'upgrade': 
                $child = $this->css()->get_prop( 'child' );
            ?>
            
<?php if ( $child ): ?>
        <div class="clearfix">
        <div style="width:67%;float:left;margin:0">
<?php endif; ?>
        <h3><?php _e( 'This version of Child Theme Configurator includes significant updates.', 'child-theme-configurator' ); ?></h3>
        <p class="howto"><?php _e( 'A lot of time and testing has gone into this release but there are always edge cases. If you have any questions, please', 'child-theme-configurator' ); ?> <a href="<?php echo LILAEAMEDIA_URL; ?>/contact" target="_blank"><?php _e( 'Contact Us.', 'child-theme-configurator' ); ?></a></p>
        <p class="howto"><?php _e( 'For more information, please open the Help tab at the top right or ', 'child-theme-configurator' ) ?> <a href="http://www.childthemeconfigurator.com/tutorial-videos/" target="_blank"><?php _e( 'click here to view the latest videos.', 'child-theme-configurator' ); ?></a></p>
<?php if ( $child ): ?>
        <p><?php _e( 'It is a good idea to save a Zip Archive of your Child Theme before using this version for the first time (click the button to the right to download). Remember you can always export your child themes from the "Files" Tab.', 'child-theme-configurator' ); ?></p>
        </div>
        <div style="width:33%;margin:0;float:left;text-align:center"><h3><?php _e( 'Backup Child Theme', 'child-theme-configurator' ); ?></h3>
        <?php include ( CHLD_THM_CFG_DIR . '/includes/forms/zipform.php' ); ?></div>
        </div>
<?php endif; ?>
<?php endswitch; ?>
</div><?php
    }

}
?>
