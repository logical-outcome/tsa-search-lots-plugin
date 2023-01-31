<?php
/**
 * Plugin Name: TSA Search Lots Plugin
 * Description: This was made for The Shed App. The plugin will list the lot by a given zip code.
 * Requires at least: 5.8
 * Requires PHP:      7.0
 * Version:           2.5.1
 * Author:            Mark Tank
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

/*
Updates: 
1.7 coverts everything from jQuery to ES6.
1.8 adds an alert telling the user they have to enter a zip code if they click on a in active lot
1.9 add working incon and fix 1.8
2.0 I forgot the link!!!! Ug!
2.1 Moved notice below input and set a timeOut for the loading icon
2.2 Move the don't click from an alert to text inside the lot box
2.3 Prevent the form from submitting, and check if zipform existed before creating an eventListener.
2.5 add to the doShortCode() so that it will only load the style sheet and the script when the short code is present.
2.5.1 Corrected instructions.
*/

class TSA_Lot_Search {

    function __construct() {
        add_action( 'admin_menu', array($this, 'adminPage'));
        add_action( 'admin_init', array($this, 'settings') );
        add_action( 'init', array($this, 'init_shortcode') );
    }

    function adminPage() {
        add_options_page( 'Set Details Search', 'TSA Lots', 'manage_options', 'tsa-lot-search-plugin', array($this, 'showSettingsPage') ); 
    }

    function settings() {

        add_settings_section( 'tsals_lot_section', 'Lot Information', array($this,'tsals_lot_section_text'), 'tsa-lot-search-plugin' );

        // creates a field for the TSA URL
        add_settings_field( 'tsaURL', 'Enter your URL for The Shed App', array($this, 'tsaHTML'), 'tsa-lot-search-plugin', 'tsals_lot_section' );
        register_setting( 'tsa_lot_search_plugin', 'tsaURL', array('sanitize_callback' => 'sanitize_text_field', 'default' => 0) );

        // creates a field for the API key
        add_settings_field( 'zipcodeAPI', 'Zip Code API. (https://www.zipcodeapi.com)', array($this, 'settingAPIHTML'), 'tsa-lot-search-plugin', 'tsals_lot_section' );
        register_setting( 'tsa_lot_search_plugin', 'zipcodeAPI', array('sanitize_callback' => 'sanitize_text_field', 'default' => 0) );
       
        // creates a field for the lot zip code
        add_settings_field( 'zipRadius', 'Radius from Zip (in miles)', array($this, 'zipRadiusHTML'), 'tsa-lot-search-plugin', 'tsals_lot_section' );
        register_setting( 'tsa_lot_search_plugin', 'zipRadius', array('sanitize_callback' => 'sanitize_text_field', 'default' => 0) );

        //creates a field for the store id
        add_settings_field( 'lotInfo', 'Please enter lots like (Zip Code, City, and Store ID. on a new line for each lot). You can use a spreadsheet to create this and save it as a CSV file, but make sure to use semicolons as the separator. Copy the contents of the CSV file and paste it here.', array($this, 'lotHTML'), 'tsa-lot-search-plugin', 'tsals_lot_section' );
        register_setting( 'tsa_lot_search_plugin', 'lotInfo', array('sanitize_callback' => array($this,'verifyInput'), 'default' => 0) );
    }

    function tsals_lot_section_text() { ?>
<p>Please create the lots</p>
<?php }

    function verifyInput($input) {
        
        $nowArray = explode("\n",$input); // turn the string into an array
        
        $verified = true; // we will start with everything being okay
        
        foreach($nowArray AS $k=>$v) {
            if(!preg_match('/\d+;[0-9a-zA-Z,\s\(\)]+;\d+$/',trim($v),$m)) {
                $verified = false; // if we don't have a match then verfied becomes false
            }
        };
        if($verified) {
            return $input; //if all is good send it
        }
        
        else {
            add_settings_error( 'lotInfo', 'lot-info-error', 'Please verify that lot information is in the correct format.' );
            return get_option('lotInfo'); // if not we will just send the saved data.
        }
    }
function tsaHTML() { ?>
<input type="text" name="tsaURL" value="<?php echo esc_attr( get_option('tsaURL') ) ?>" size="48"
    placeholder="https://app.yourbusiness.com" />
<?php }

    function lotHTML() { ?>
<textarea name="lotInfo" cols="48" rows="30"
    placeholder="Zip Code,Cith,Store ID"><?php echo get_option('lotInfo') ?></textarea>
<?php }

function settingAPIHTML() { ?>
<input type="text" name="zipcodeAPI" value="<?php echo esc_attr( get_option('zipcodeAPI') ) ?>" size="48"
    placeholder="From zipcodeapi.io" />
<?php }

function zipRadiusHTML(){ ?>
<input type="text" name="zipRadius" value="<?php echo esc_attr( get_option('zipRadius') ) ?>" placeholder="50404" />
<?php }

    function showSettingsPage() { ?>
<div class="wrap">
    <h1>Lot Search (Please use short code [Search_Lots])</h1>
    <form action="options.php" method="POST">
        <?php 
                settings_fields( 'tsa_lot_search_plugin' );
                do_settings_sections( 'tsa-lot-search-plugin' );
                submit_button( );
            ?>
    </form>
</div>
<?php }

    function init_shortcode() {
       
        add_shortcode( 'Search_Lots', array($this, 'doShortCode') );
    }
    function enqueue_scripts() {

        wp_enqueue_script( 'tsa-search-lots-plugin', plugin_dir_url( __FILE__ ).'tsa-search-lots-plugin.min.js', array('jquery'), 2.3, true);

        wp_enqueue_style( 'tsa-search-lots-plugin', plugin_dir_url( __FILE__ ).'tsa-search-lots-plugin.min.css', false , 2.3, 'all' );
    }

    function doShortCode() {

        global $post;
        if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'Search_Lots') ) {
            $this->enqueue_scripts();
        }

        $formHTML = '<form method="post" action="" id="zipform">
            <div class="formSection">
                <div class="formField">
                    <label for="zipcode">Enter the zip code of where the shed will be placed.</label>
                    <input id="zipcode" type="text" name="zip" value="" maxlength="5"/>
                    <input id="lot-distance" type="hidden" value="'.get_option('zipRadius').'" />
                    <input id="zipAPI" type="hidden" value="'.get_option('zipcodeAPI').'" />
                    <span id="working-icon">
                        <?xml version="1.0" encoding="utf-8"?>
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
    style="margin: auto; background: rgb(255, 255, 255); display: block; shape-rendering: auto;" width="36px"
    height="36px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
    <g transform="translate(50 50)">
        <g>
            <animateTransform attributeName="transform" type="rotate" values="0;45" keyTimes="0;1" dur="0.2s"
                repeatCount="indefinite"></animateTransform>
            <path
                d="M29.491524206117255 -5.5 L37.491524206117255 -5.5 L37.491524206117255 5.5 L29.491524206117255 5.5 A30 30 0 0 1 24.742744050198738 16.964569457146712 L24.742744050198738 16.964569457146712 L30.399598299691117 22.621423706639092 L22.621423706639096 30.399598299691114 L16.964569457146716 24.742744050198734 A30 30 0 0 1 5.5 29.491524206117255 L5.5 29.491524206117255 L5.5 37.491524206117255 L-5.499999999999997 37.491524206117255 L-5.499999999999997 29.491524206117255 A30 30 0 0 1 -16.964569457146705 24.742744050198738 L-16.964569457146705 24.742744050198738 L-22.621423706639085 30.399598299691117 L-30.399598299691117 22.621423706639092 L-24.742744050198738 16.964569457146712 A30 30 0 0 1 -29.491524206117255 5.500000000000009 L-29.491524206117255 5.500000000000009 L-37.491524206117255 5.50000000000001 L-37.491524206117255 -5.500000000000001 L-29.491524206117255 -5.500000000000002 A30 30 0 0 1 -24.742744050198738 -16.964569457146705 L-24.742744050198738 -16.964569457146705 L-30.399598299691117 -22.621423706639085 L-22.621423706639092 -30.399598299691117 L-16.964569457146712 -24.742744050198738 A30 30 0 0 1 -5.500000000000011 -29.491524206117255 L-5.500000000000011 -29.491524206117255 L-5.500000000000012 -37.491524206117255 L5.499999999999998 -37.491524206117255 L5.5 -29.491524206117255 A30 30 0 0 1 16.964569457146702 -24.74274405019874 L16.964569457146702 -24.74274405019874 L22.62142370663908 -30.39959829969112 L30.399598299691117 -22.6214237066391 L24.742744050198738 -16.964569457146716 A30 30 0 0 1 29.491524206117255 -5.500000000000013 M0 -20A20 20 0 1 0 0 20 A20 20 0 1 0 0 -20"
                fill="#7c332a"></path>
        </g>
    </g>
    <!-- [ldio] generated by https://loading.io/ -->
</svg>
</span>
<div id="notice"></div>
</div>
</div>
</form>';

$lotHTML = '';

$lots = explode("\n",get_option('lotInfo'));

foreach($lots AS $lot) {
$alot = explode(';',$lot);
$lotHTML .= '<li class="alot" zip="'.$alot[0].'" storeId="'.$alot[2].'">
    <h1>'.$alot[1].'</h1>
    <div class="imgBox"><a class="inventoryLink" href="#" target="_blank">CHECK INVENTORY<br><span
                style="font-size: .9em">Opens In New Tab<span></a></div>
    <p>'.$alot[0].'</p>
</li>';
}

return $formHTML.'<ul id="inventory-list">'.$lotHTML.'</ul>'.$this->page_content;
}

}

$tsasearch = new TSA_Lot_Search();