<?php
/*
Plugin Name: Gravity Forms - PDF Addon
Plugin URI: http://www.gravityforms.com
Description: A Gravity Form add-on to provide the export of data as PDF.
Version: 0.1
Author: Muammad Haseeb
Author URI: http://www.mhaseeb.com
*/

define( 'GF_PDF_ADDON_VERSION', '0.1' );
define( 'GF_PDF_ADDON_PATH', plugin_dir_path( __FILE__ ) );
define( 'GF_PDF_ADDON_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initiating & Registering Addon for Gravity Forms
 */
add_action( 'gform_loaded', array( 'GF_PDF_AddOn_Init', 'load' ), 1);

class GF_PDF_AddOn_Init {

    public static function load() {

        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }
        
        GFForms::include_addon_framework();
        require_once( GF_PDF_ADDON_PATH . 'inc/class-GFPDFAddOn.php' );
        require_once( GF_PDF_ADDON_PATH . 'inc/class-GFPDFGenerator.php' );
        require_once( GF_PDF_ADDON_PATH . '/inc/vendor/autoload.php' );
        
        GFAddOn::register( 'GFPDFAddOn' );
    }

}

/**
 * Get Instance of addon
 * 
 * @return GFPDFAddON
 */
function gf_pdf_addon() {
    return GFPDFAddOn::get_instance();
}

