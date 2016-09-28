<?php
/*
Plugin Name: NES GForm Processor
Plugin URI: https://bitbucket.org/nlk/nes-gform-processor
Description: Ninthlink Enrichment System "nes" routes submissions in a particular Gravity Form for Lead Enrichment through TowerData. Some assembly may be required.
Version: 1.0
Author: Ninthlink, Inc.
Author URI: http://www.ninthlink.com
Documentation: http://www.gravityhelp.com/documentation/page/GFAddOn

*/

//exit if accessed directly
if(!defined('ABSPATH')) exit;

//------------------------------------------
if (class_exists("GFForms")) {
    GFForms::include_feed_addon_framework();

    class GFNESAddon extends GFFeedAddOn {

        protected $_version = "1.0";
        protected $_min_gravityforms_version = "1.7.9999";
        protected $_slug = "nes-gform-processor";
        protected $_path = "nes-gform-processor/nes-gform-processor.php";
        protected $_full_path = __FILE__;
        protected $_title = "NES GForm Processor";
        protected $_short_title = "NES";

        // custom data vars for use outside class?
        public $_nes_result = array();

        public $_debug;

        /**
         *  Feed Settings Fields
         *
         *  Each form uses unique feed settings to connect for Enrichment.
         *
         **/
        public function feed_settings_fields() {

            // array of settings fields
            $a = array(
                array(
                    "title"  => "Lead Enrichment Settings",
                    "fields" => array(
                        array(
                            "name" => "nesMappedFields_Contact",
                            "label" => "Map Lead Fields",
                            "type" => "field_map",
                            "tooltip" => "Map NES fields for Enrichment",
                            "field_map" => array(
                                array("name" => "Email","label" => "Email","required" => 0),
                            )
                        ),
                        array(
                            "name" => "nesMappedFields_TowerData",
                            "label" => "Map TowerData Fields",
                            "type" => "field_map",
                            "tooltip" => "Map NES fields for Enrichment",
                            "field_map" => array(
                                array("name" => "DateEnriched","label" => "Date Enriched","required" => 0),
                                array("name" => "TimeEnriched","label" => "Time Enriched","required" => 0),
                                array("name" => "td_marital_status","label" => "Marital Status","required" => 0),
                                array("name" => "td_net_worth","label" => "Net Worth","required" => 0),
                                array("name" => "td_occupation","label" => "Occupation","required" => 0),
                                array("name" => "td_education","label" => "Education","required" => 0),
                                array("name" => "td_home_market_value","label" => "Home Market Value","required" => 0),
                                array("name" => "td_gender","label" => "Gender","required" => 0),
                                array("name" => "td_length_of_residence","label" => "Length of Residence","required" => 0),
                                array("name" => "td_household_income","label" => "Household Income","required" => 0),
                                array("name" => "td_age","label" => "Age","required" => 0),
                                array("name" => "td_home_owner_status","label" => "Home Owner Status","required" => 0),
                                array("name" => "td_presence_of_children","label" => "Presence of Children","required" => 0),
                            ),
                        ),
                    )
                ),
                array(
                    "title"  => "Feed Settings",
                    "fields" => array(
                        array(
                            "name" => "nesCondition",
                            "label" => __("Conditional", "nes-gform-processor"),
                            "type" => "feed_condition",
                            "checkbox_label" => __("Enable Feed Condition", "nes-gform-processor"),
                            "instructions" => __("Process this Feed if...", "nes-gform-processor")
                        ),
                    )
                )
            );

            return $a;
        }

        /**
         *  Columns displayed on Feed overview / list page
         *
         **/
        public function feed_list_columns() {
            // #todo
            return array(
                'nesFeedName' => __('Name', 'nes-gform-processor'),
                'nesCondition' => __('Condition(s)', 'nes-gform-processor'),
            );
        }
        // customize the value of mytext before it is rendered to the list
        public function get_column_value_nesCondition( $feed ){
            $output = 'N/A';
            $rules = array();
            if ( $feed['meta']['feed_condition_conditional_logic'] == 1 ) {
                foreach ( $feed['meta']['feed_condition_conditional_logic_object']['conditionalLogic']['rules'] as $key => $value ) {
                    $rules[] = sprintf( 'field_%d %s %s' , $value['fieldId'], ( $value['operator'] === 'is' ? 'is' : 'is not' ), $value['value'] );
                }
                $andor = $feed['meta']['feed_condition_conditional_logic_object']['conditionalLogic']['logicType'] === 'any' ? 'or' : 'and';
                $output = implode(', ' . $andor . ' ', $rules);
            }
            return $output;
        }

        /**
         *  Output on overview page for human readability
         *
         **/
        public function get_column_value_nesFeedName($feed) {
            return "<b>" . $feed["meta"]["nesFeedName"] ."</b>";
        }

        /**
         *  Plugin Settings Fields
         *
         *  These setting apply to entire plugin, not just individual feeds
         *
         *
         **/
        public function plugin_settings_fields() {
            return array(
                array(
                    "title"  => "Ninthlink Enrichment System API Settings",
                    "fields" => array(
                        array(
                            "name"    => "nes_tdapi",
                            "label"   => "TowerData API Key",
                            "type"    => "text",
                            "class"   => "medium"
                        ),
                        // is that it?
                    ),
                ),
            );
        }

        /**
         *  Feed Processor
         *
         *  This is the nuts and bolts: all actions to happen on form submit happen here
         *  Feed processing happens after submit, but before page redirect/thanks message
         *
         **/
        public function process_feed($feed, $entry, $form){

            // working vars
            //$nesFeedSubmit = $feed['meta']['nesFeedSubmit'];

      			// current user info
      			global $current_user;
      			get_currentuserinfo();

            // get submit to location, and exit if none
            $url = $this->get_plugin_setting('nes_apiUrl');
            if ( $url == '' ) :
              return false; // do nothing - GForm submits as normal
            endif;

            // else
            if ( $url != '' ) {
              $url = trailingslashit( esc_url_raw( $url ) ) ."gravityformsapi/forms/1/submissions";
            }

            // we will use Google Analytics cookies for some data if available
            if ( isset($_COOKIE['__utmz']) && !empty($_COOKIE['__utmz']) )
                $ga_cookie = $this->parse_ga_cookie( $_COOKIE['__utmz'] );

            // full data array for Lead Enrichment
            $jsonObj = new stdClass();
            $jsonObj->input_values = new stdClass();

            // set the Side ID Key
            $jsonObj->input_values->input_28 = $this->get_plugin_setting('nes_siteID');
            // set default Run
            $jsonObj->input_values->input_29 = $this->get_plugin_setting('nes_defaultEnrichment');

            $input_map = array(
              'FirstName' => 'input_1_3',
              'LastName' => 'input_1_6',
              'Email' => 'input_2',
              'Phone' => 'input_23',
              'Address1' => 'input_24_1',
              'Address2' => 'input_24_2',
              'City' => 'input_24_3',
              'State' => 'input_24_4',
              'PostalCode' => 'input_24_5',
              'Country' => 'input_24_6',
              'AddressInput' => 'input_26',
              'SourceURL' => 'input_5',
              'RefURL' => 'input_33',
              'UserIP' => 'input_6',
              'UserAgent' => 'input_8',
            );

            // iterate over meta data mapped fields (from feed fields) and apply to the big array above
            foreach ($feed['meta'] as $k => $v) {
              switch ( $k ) {
                case 'nesAd':
                  $jsonObj->input_values->input_10 = $v;
                  break;
                case 'nesRun':
                  $jsonObj->input_values->input_29 = $v;
                  break;
                default:
                  $l = explode("_", $k);
                  if ( $l[0] == 'nesMappedFields' ) {
                    if ( array_key_exists( $l[2], $input_map ) && !empty( $v ) ) {
                      $jsonObj->input_values->$input_map[ $l[2] ] = $entry[ $v ];
                    }
                  }
                  break;
                }
            }

            // json encode to string for sending
            $jsonString = wp_json_encode( $jsonObj );

            // cURL :: this sends off the data
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonString);
            curl_setopt($ch, CURLOPT_PROXY, null);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json', 'Content-Length: ' . strlen($jsonString) ) );
            $apiResult = curl_exec($ch);
            $httpResult = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $result = array( 0 => $httpResult, 1 => $apiResult );

            // debug things
            if ( $this->get_plugin_setting('nes_debugMode') == 1 )
            {
                $this->_nes_result['cURL'] = $result;
                $this->_nes_result['JSONOBJ'] = $jsonObj;
                $this->_nes_result['JSONSTR'] = $jsonString;
                $this->_nes_result['FEED'] = $feed;
                $this->_nes_result['ENTRY'] = $entry;
                add_action('wp_footer', array( $this, 'nes_debug') );
                add_filter("gform_confirmation", "nes_debug_confirm", 10, 4);
            }

        }

        /**
         *  helper functions
         *
         *  Useful functions for parsing data, formatting, etc.
         *
         **/

        // Debug builder
        public function nes_debug()
        {
            $arrays = $this->_nes_result;
            $o = '<style type="text/css">#nes-gform-debug { background: #ccc; color: #000 }</style>';
            $o .= '<div id="nes-gform-debug"><h3>Lead Enrichment Debug Details</h3><hr>';
            foreach ($arrays as $array => $value)
            {
                $o .='<h4>'.$array.'</h4><pre>'.print_r($value, true).'</pre><hr>';
            }
            $o .= '</div>';
            if ( current_user_can( 'activate_plugins' ) )
                print($o);
        }
        public function nes_debug_confirm($confirmation, $form, $lead, $ajax)
        {
            $arrays = $this->_nes_result;
            $o = '<div id="nes-gform-debug" class="nes_confirm"><h3>Lead Enrichment Debug Details</h3><hr>';
            foreach ($arrays as $array => $value)
            {
                $o .='<h4>'.$array.'</h4><pre>'.print_r($value, true).'</pre><hr>';
            }
            $o .= '</div>';
            if ( current_user_can( 'activate_plugins' ) )
                return $o;
            return false;
        }

        // Phone number formatter
        public function format_phone( $phone = '', $format='standard', $convert = true, $trim = true )
        {
            if ( empty( $phone ) ) {
                return false;
            }
            // Strip out non alphanumeric
            $phone = preg_replace( "/[^0-9A-Za-z]/", "", $phone );
            // Keep original phone in case of problems later on but without special characters
            $originalPhone = $phone;
            // If we have a number longer than 11 digits cut the string down to only 11
            // This is also only ran if we want to limit only to 11 characters
            if ( $trim == true && strlen( $phone ) > 11 ) {
                $phone = substr( $phone, 0, 11 );
            }
            // letters to their number equivalent
            if ( $convert == true && !is_numeric( $phone ) ) {
                $replace = array(
                    '2'=>array('a','b','c'),
                    '3'=>array('d','e','f'),
                    '4'=>array('g','h','i'),
                    '5'=>array('j','k','l'),
                    '6'=>array('m','n','o'),
                    '7'=>array('p','q','r','s'),
                    '8'=>array('t','u','v'),
                    '9'=>array('w','x','y','z'),
                    );
                foreach ( $replace as $digit => $letters ) {
                    $phone = str_ireplace( $letters, $digit, $phone );
                }
            }
            $a = $b = $c = $d = null;
            switch ( $format ) {
                case 'decimal':
                case 'period':
                    $a = '';
                    $b = '.';
                    $c = '.';
                    $d = '.';
                    break;
                case 'hypen':
                case 'dash':
                    $a = '';
                    $b = '-';
                    $c = '-';
                    $d = '-';
                    break;
                case 'space':
                    $a = '';
                    $b = ' ';
                    $c = ' ';
                    $d = ' ';
                    break;
                case 'standard':
                default:
                    $a = '(';
                    $b = ') ';
                    $c = '-';
                    $d = '(';
                    break;
            }
            $length = strlen( $phone );
            // Perform phone number formatting here
            switch ( $length ) {
                case 7:
                    // Format: xxx-xxxx / xxx.xxxx / xxx-xxxx / xxx xxxx
                    return preg_replace( "/([0-9a-zA-Z]{3})([0-9a-zA-Z]{4})/", "$1$c$2", $phone );
                case 10:
                    // Format: (xxx) xxx-xxxx / xxx.xxx.xxxx / xxx-xxx-xxxx / xxx xxx xxxx
                    return preg_replace( "/([0-9a-zA-Z]{3})([0-9a-zA-Z]{3})([0-9a-zA-Z]{4})/", "$a$1$b$2$c$3", $phone );
                case 11:
                    // Format: x(xxx) xxx-xxxx / x.xxx.xxx.xxxx / x-xxx-xxx-xxxx / x xxx xxx xxxx
                    return preg_replace( "/([0-9a-zA-Z]{1})([0-9a-zA-Z]{3})([0-9a-zA-Z]{3})([0-9a-zA-Z]{4})/", "$1$d$2$b$3$c$4", $phone );
                default:
                    // Return original phone if not 7, 10 or 11 digits long
                    return $originalPhone;
            }
        }

        /**
         *  END of ADD-ON CLASS
         *
         **/
    }

    // Instantiate the class - this triggers everything, makes the magic happen
    $gfa = new GFNESAddon();
}
