<?php

class FrmCptUpdate{
    var $plugin_nicename;
    var $plugin_name;
    var $pro_check_interval;
    var $pro_last_checked_store;
    var $version;

    function FrmCptUpdate(){
        if(!class_exists('FrmUpdatesController') and !class_exists('FrmUpdate')) return;
        
        // Where all the vitals are defined for this plugin
        $this->plugin_nicename      = 'formidable-math-captcha';
        $this->plugin_name          = 'formidable-math-captcha/formidable-math-captcha.php';
        $this->pro_last_checked_store = 'frmcpt_last_check';
        $this->pro_check_interval = 60*60*24; // Checking every 24 hours

        add_filter('site_transient_update_plugins', array( &$this, 'queue_update' ) );
        
        if(method_exists('FrmAppHelper', 'plugin_version')){
            $this->version = FrmAppHelper::plugin_version();
        }else{
            global $frm_version;
            $this->version = $frm_version;
        }
        
        if(version_compare($this->version, '1.07.0rc1', '<'))
            add_filter('pre_set_site_transient_update_plugins', array( &$this, 'queue_old_update' ) ); //Deprecated
    }

    function queue_update($transient, $force=false){
        if(version_compare($this->version, '1.07.0rc1', '<'))
            return $transient;

        $plugin = $this;
        global $frm_update;
        if ( $frm_update ){
            return $frm_update->queue_addon_update($transient, $plugin, $force);
        } else {
            $updates = new FrmUpdatesController();
            return $updates->queue_addon_update($transient, $plugin, $force);
        }
    }

    function queue_old_update($transient, $force=false){
        if(method_exists('FrmUpdate', 'queue_addon_update')){
            global $frm_update;
            $plugin = $this;
            return $frm_update->queue_addon_update($transient, $plugin, $force);
        }
        
        if(!is_object($transient) or empty($transient->checked))
            return $transient;

        global $frmpro_is_installed;
        if($frmpro_is_installed){
            $expired = true;
            if(!$force){
                $update = get_site_transient($this->pro_last_checked_store);
                if($update)
                    $expired = false;
            }

            if(!$update)
                $update = $this->get_current_info( $transient->checked[ $this->plugin_name ], $force, $this->plugin_nicename );

            if( $update and !empty( $update ) ){
                $update = (object) $update;
                $transient->response[ $this->plugin_name ] = $update;
                
                //only check periodically
                if($expired)
                    set_site_transient($this->pro_last_checked_store, $update, $this->pro_check_interval );
            }
        }
        
        return $transient;
    }
    
    public function get_current_info($version, $force=false, $plugin=false){
        include_once( ABSPATH . 'wp-includes/class-IXR.php' );

        global $frm_update;
        
        $client = new IXR_Client( $frm_update->pro_mothership_xmlrpc_url, false, 80, $frm_update->timeout );

        $force = $force ? 'true' : 'false';
        $plugin = $plugin ? $plugin : $this->plugin_nicename;
        
        if( !$client->query( 'proplug.get_current_info', $frm_update->pro_username, $frm_update->pro_password, $version, $force, 
            get_option('siteurl'), $plugin) )
            return false;

        return $client->getResponse();
    }
}