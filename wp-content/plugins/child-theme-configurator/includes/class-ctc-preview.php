<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

    class ChildThemeConfiguratorPreview {
        /**
         * Replaces core function to start preview theme output buffer.
         */
        static function preview_theme() {
            // are we previewing?
            if ( ! isset( $_GET[ 'template' ] ) || !wp_verify_nonce( $_GET['preview_ctc'] ) )
                return;
            // can user preview?
            if ( !current_user_can( 'switch_themes' ) )
                return;
            // hide admin bar in preview
            if ( isset( $_GET[ 'preview_iframe' ] ) )
                show_admin_bar( false );
            // sanitize template param
            $_GET[ 'template' ] = preg_replace( '|[^a-z0-9_./-]|i', '', $_GET[ 'template' ] );
            // check for manipulations
            if ( validate_file( $_GET[ 'template' ] ) )
                return;
            // replace future get_template calls with preview template
            add_filter( 'template', 'ChildThemeConfiguratorPreview::preview_theme_template_filter' );
        
            if ( isset( $_GET[ 'stylesheet' ] ) ):
                // sanitize stylesheet param
                $_GET['stylesheet'] = preg_replace( '|[^a-z0-9_./-]|i', '', $_GET['stylesheet'] );
                // check for manipulations
                if ( validate_file( $_GET['stylesheet'] ) )
                    return;
                // replace future get_stylesheet calls with preview stylesheet
                add_filter( 'stylesheet', 'ChildThemeConfiguratorPreview::preview_theme_stylesheet_filter' );
            endif;
            // swap out theme mods with preview theme mods
            add_filter( 'pre_option_theme_mods_' . get_option( 'stylesheet' ), 
                'ChildThemeConfiguratorPreview::preview_mods' );
            // impossibly high priority to test for stylesheets loaded after wp_head()
            add_action( 'wp_print_styles', 'ChildThemeConfiguratorPreview::test_css', 999999 );
            // pass the wp_styles queue back to use for stylesheet handle verification
            add_action( 'wp_footer', 'ChildThemeConfiguratorPreview::current_queue' );

        }
        
        /**
         * Retrieves child theme mods for preview
         */        
        static function preview_mods() { 
            if ( ! isset( $_GET[ 'stylesheet' ] ) || get_option( 'stylesheet' ) == $_GET[ 'stylesheet' ] ) return false;
            return get_option( 'theme_mods_' . preg_replace('|[^a-z0-9_./-]|i', '', $_GET['stylesheet']) );
        }
        
        /**
         * Function to modify the current template when previewing a theme
         *
         * @return string
         */
        static function preview_theme_template_filter() {
            return ( isset($_GET['template']) && current_user_can( 'switch_themes' ) ) ? $_GET['template'] : '';
        }
        
        /**
         * Function to modify the current stylesheet when previewing a theme
         *
         * @return string
         */
        static function preview_theme_stylesheet_filter() {
            return ( isset( $_GET['stylesheet'] ) && current_user_can( 'switch_themes' ) ) ? $_GET['stylesheet'] : '';
        }
        
        // retrieve wp_styles queue and pass back to configurator to verify stylesheet handles
        static function current_queue() {
            global $wp_styles;
            $queue = implode( "\n", $wp_styles->queue );
            echo "\n\n\n<!-- BEGIN WP QUEUE\n" . $queue . "\nEND WP QUEUE -->\n\n\n";
            self::parse_stylesheet();
        }
        
        // enqueue dummy stylesheet with extremely high priority to test wp_head()
        static function test_css() {
            wp_enqueue_style( 'ctc-test', get_stylesheet_directory_uri() . '/ctc-test.css' );
        }
        
        static function parse_stylesheet() {
            if ( is_child_theme() ):
                $file = get_stylesheet_directory() . '/style.css';
                if ( file_exists( $file ) && ( $styles = @file_get_contents( $file ) ) ):
                    if ( defined( 'CHLD_THM_CFG_IGNORE_PARENT' ) ):
                        echo "\n\n\n<!-- CHLD_THM_CFG_IGNORE_PARENT -->\n\n\n";
                    endif;
                    if ( preg_match( '#\nUpdated: \d\d\d\d\-\d\d\-\d\d \d\d:\d\d:\d\d\n#s', $styles ) ):
                        echo "\n\n\n<!-- IS_CTC_THEME -->\n\n\n";
                    endif;
                    
                    if ( preg_match( '#\@import\s+url\(.+?\/' . preg_quote( get_template() ) . '\/style\.css.*?\);#s', $styles ) ):
                        echo "\n\n\n<!-- HAS_CTC_IMPORT -->\n\n\n";
                    endif;
                endif;
            else:
                $file = get_template_directory() . '/style.css';
                if ( file_exists( $file ) && ( $styles = @file_get_contents( $file ) ) ):
                    $styles = preg_replace( '#\/\*.*?\*\/#s', '', $styles );
                    if ( preg_match( '#\n\s*([\[\.\#\:\w][\w\-\s\(\)\[\]\'\^\*\.\#\+:,"=>]+?)\s*\{(.*?)\}#s', $styles ) ):
                        echo "\n\n\n<!-- HAS_CTC_STYLES -->\n\n\n";
                    endif;
                endif;
            endif;
        }
    }
    
    // replace core preview function with CTCP function for quick preview
    remove_action( 'setup_theme', 'preview_theme' );
    add_action( 'setup_theme', 'ChildThemeConfiguratorPreview::preview_theme' );
    