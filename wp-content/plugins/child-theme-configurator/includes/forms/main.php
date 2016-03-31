<?php
if ( !defined( 'ABSPATH' ) ) exit;
// backward compat
$css = $this->css();
// main CTC Page 
?>
<style type="text/css">
.ctc-step-number {
    background-color: <?php echo $this->colors[ 3 ]; ?>;
}
.ctc-step-letter {
    background-color: <?php echo $this->colors[ 2 ]; ?>;
}
.ctc-step + strong {
    color: <?php echo $this->colors[ 1 ]; ?>;
}
.ctc-status-icon.success {
 background:url(<?php echo admin_url( 'images/yes.png' );
?>) no-repeat;
}

.ctc-status-icon.failure {
background:url(<?php echo admin_url( 'images/no.png' );
?>) no-repeat;
}

.ctc-exit {
background:#f9f9f9 url(<?php echo includes_url( 'images/xit-2x.gif' );
?>) left top no-repeat;
}
</style>
<div class="wrap" id="ctc_main">
  <?php do_action( 'chld_thm_cfg_related_links' ); ?>
  <h2><?php echo apply_filters( 'chld_thm_cfg_header', __( 'Child Theme Configurator', 'child-theme-configurator' ) . ' ' . __( 'version', 'child-theme-configurator' ) . ' ' . CHLD_THM_CFG_VERSION );  ?></h2>
  <label for="ctc_is_debug" style="float:right;font-size:9px">
    <input class="ctc_checkbox" id="ctc_is_debug" name="ctc_is_debug"  type="checkbox" 
            value="1" <?php echo checked( $this->ctc()->is_debug, 1 ); ?> autocomplete="off" />
    <?php _e( 'Debug', 'chld_thm_cfg_plugins' ); ?>
  </label><?php if ( $childname = $this->css()->get_prop( 'child_name' ) ): ?>
  <h3>
    <?php _e( 'Currently Loaded', 'child-theme-configurator' ); echo ': ' . $childname;?>
  </h3>
  <?php endif; 
if ( $this->ctc()->is_post && !$this->ctc()->fs ):
    echo $this->ctc()->fs_prompt;
else: ?>
  <div id="ctc_error_notice">
    <?php $this->settings_errors(); ?>
  </div>
  <?php 
    // if flag has been set because an action is required, do not render interface
    if ( !$this->ctc()->skip_form ):
    include ( CHLD_THM_CFG_DIR . '/includes/forms/tabs.php' ); 
?>
  <div id="ctc_option_panel_wrapper" style="position:relative">
    <div class="ctc-option-panel-container">
      <?php 
    //$parent_child_panel = apply_filters( 'chld_thm_cfg_pc_panel', 
    //    CHLD_THM_CFG_DIR . '/includes/forms/parent-child.php' );
    //include ( $parent_child_panel ); 
    include ( CHLD_THM_CFG_DIR . '/includes/forms/parent-child.php' );
    if ( $enqueueset ):
        include ( CHLD_THM_CFG_DIR . '/includes/forms/rule-value.php' ); 
        include ( CHLD_THM_CFG_DIR . '/includes/forms/query-selector.php' ); 
        include ( CHLD_THM_CFG_DIR . '/includes/forms/webfonts.php' ); ?>
      <div id="view_child_options_panel" 
        class="ctc-option-panel<?php echo 'view_child_options' == $active_tab ? ' ctc-option-panel-active' : ''; ?>" <?php echo $hidechild; ?>> </div>
      <div id="view_parnt_options_panel" 
        class="ctc-option-panel<?php echo 'view_parnt_options' == $active_tab ? ' ctc-option-panel-active' : ''; ?>" <?php echo $hidechild; ?>> </div>
      <?php 
        if ( '' == $hidechild ): 
            include ( CHLD_THM_CFG_DIR . '/includes/forms/files.php' );
        endif; 
        do_action( 'chld_thm_cfg_panels', $this->ctc(), $active_tab, $hidechild ); 
    endif; 
    ?>
    </div>
    <?php do_action( 'chld_thm_cfg_sidebar' ); ?>
  </div>
  <?php
  endif;
endif;
?>
  <div id="ctc_debug_container">
    <?php do_action( 'chld_thm_cfg_print_debug' ); ?>
  </div>
</div>
