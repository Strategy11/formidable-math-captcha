<?php

class FrmCptUpdate{
    var $plugin_nicename;
    var $plugin_name;
    var $pro_check_interval;
    var $pro_last_checked_store;
    var $version;

    function __construct(){
        if ( !class_exists('FrmUpdatesController') ) {
            return;
        }
        
        // Where all the vitals are defined for this plugin
        $this->plugin_nicename      = 'formidable-math-captcha';
        $this->plugin_name          = basename(dirname(__FILE__)) .'/formidable-math-captcha.php';
        $this->pro_last_checked_store = 'frmcpt_last_check';
        $this->pro_check_interval = 60*60*24; // Checking every 24 hours

        add_filter('site_transient_update_plugins', array( &$this, 'queue_update' ) );
    }

    function queue_update($transient, $force=false){
        $plugin = $this;
        global $frm_update;
        if ( $frm_update ){
            return $frm_update->queue_addon_update($transient, $plugin, $force);
        } else {
            $updates = new FrmUpdatesController();
            return $updates->queue_addon_update($transient, $plugin, $force);
        }
    }
}