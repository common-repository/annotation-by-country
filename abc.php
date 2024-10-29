<?php
/*
Plugin Name: Annotation By Country
Plugin URI: 
Description: This plugin will select annotations depending on the access source of the country. With this plugin, annotations can be written in a post directly by surrounding them with the specific shortcode tag. GeoIP2 or GeoLite2, and GeoLite2-php are required.
Version: 0.2.0
Author: pandanote.info
Author URI: https://pandanote.info/
License: GPLv2
*/
/*  Copyright 2018-2021 pandanote.info (email : panda@pandanote.info)
 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
     published by the Free Software Foundation.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
// 設定項目
class AnnotationByCountrySettingsPage
{
	const VERSION = '0.2.0';

	const DEFAULT_GEOLITE2_COUNTRY_DATABASE_PATH = '/usr/share/GeoIP/GeoLite2-Country.mmdb';
	const DEFAULT_COUNTRY_CODE = 'JP';
	const DEFAULT_SHORT_FORM = 0;

	const SETTINGS_PAGE = 'settings_page_of_annotation_by_country';
	const SETTINGS_SECTION_ID = 'settings_section_of_annotation_by_country_id';
	
	/** 設定値 */
	private $options;

	/**
	 * 初期化処理です。
     */
	public function __construct()
	{
		add_action('admin_menu', array($this, 'add_plugin_page'));
		add_action('admin_init', array($this, 'page_init'));
	}

	/**
     * 設定メニューのサブメニューとしてメニューを追加します。
     */
	public function add_plugin_page()
	{
		$page_hook_suffix = add_options_page('Annotation By Country','Annotation By Country','manage_options','settings_of_annotation_by_country',array($this,'create_admin_page'));
		add_action('admin_print_styles-'.$page_hook_suffix, array($this,'admin_print_styles'));
	}

	public function admin_print_styles()
	{
		global $wp_version;
		if ( version_compare( $wp_version, '3.8', '>=' ) ) {
			wp_enqueue_style('annotation-by-country', plugins_url('',__FILE__).'/css/annotation-by-country-options.css', array(), self::VERSION);
		}
	}

	/**
     * 設定ページの初期化を行います。
     */
	public function page_init()
	{
		register_setting('settings_of_annotation_by_country',
		                 '_settings_of_annotation_by_country',
		                 array($this,'sanitize'));
        // Section
		add_settings_section(self::SETTINGS_SECTION_ID,
		                     'Settings',
		                     array($this,'print_section_info'),
		                     self::SETTINGS_PAGE);
        // GeoLite2のデータベースのありか。
		add_settings_field('geolite2_database_path',
		                     'Path to GeoLite2 database',
		                     array($this,'setup_geolite2_database_path'),
		                     self::SETTINGS_PAGE,
		                     self::SETTINGS_SECTION_ID);
        // GeoLite2-phpのありか。
		add_settings_field('abc_geolite2_php_path',
		                   'Path to GeoIP2-php library',
		                   array($this,'setup_geolite2_php_path'),
		                   self::SETTINGS_PAGE,
		                   self::SETTINGS_SECTION_ID);
        // デフォルトの国
		add_settings_field('abc_country_code_as_default_value',
		                   'Default country code (in 2 letters)',
		                   array($this,'setup_country_code'),
		                   self::SETTINGS_PAGE,
		                   self::SETTINGS_SECTION_ID);
        // 短縮タグ名を使うか否か。
		add_settings_field('abc_tagname_in_short_form',
		                   'Use "abc" as the tag name in short form?',
		                   array($this,'setup_tagname_in_short_form'),
		                   self::SETTINGS_PAGE,
		                   self::SETTINGS_SECTION_ID);		
	}

	public function create_admin_page()
	{
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		$this->options = get_option('_settings_of_annotation_by_country');
		?>
		<div class="wrap wrap-annotation-by-country">
		   <h2>Annotation By Country</h2>
		   <form id="wrap-annotation-by-country-form" method="post" action="options.php">
               <?php settings_fields('settings_of_annotation_by_country'); ?>
               <?php do_settings_sections(self::SETTINGS_PAGE); ?>
               <?php submit_button(); ?>
		   </form>
		</div>
		<?php
	}

	function sanitize($input) {
		$this->options = get_option('_settings_of_annotation_by_country');
		$new_input = array();
		if (isset($input['geolite2_database_path']) &&
		    trim($input['geolite2_database_path']) !== '') {
			$sanitized_path = sanitize_text_field($input['geolite2_database_path']);
			if (file_exists($sanitized_path)) {
				$new_input['geolite2_database_path'] = $sanitized_path;
			} else {
				add_settings_error('settings_of_annotation_by_country','geolite2_database_path','Specify the existing path to GeoLite2 database.');
				$new_input['geolite2_database_path'] = $this->options['geolite2_database_path'];
			}
		} else {
			add_settings_error('settings_of_annotation_by_country','geolite2_database_path','Specify the path to GeoLite2 database.');
			$new_input['geolite2_database_path'] = $this->options['geolite2_database_path'];
		}
		if (isset($input['geolite2_php_path']) &&
		    trim($input['geolite2_php_path']) !== '') {
			$sanitized_php_path = sanitize_text_field($input['geolite2_php_path']);
			if (file_exists($sanitized_php_path."/vendor/autoload.php")) {
				$new_input['geolite2_php_path'] = $sanitized_php_path;
			} else {
				add_settings_error('settings_of_annotation_by_country','geolite2_php_path','Specify the existing path to GeoIP2-php.');
				$new_input['geolite2_php_path'] = $this->options['geolite2_php_path'];				
			}
		} else {
			add_settings_error('settings_of_annotation_by_country','geolite2_php_path','Specify the path to GeoIP2-php.');
			$new_input['geolite2_php_path'] = $this->options['geolite2_php_path'];
		}
		if (isset($input['geolite2_default_country_code']) &&
		    trim($input['geolite2_default_country_code']) !== '') {
			$sanitized_text = sanitize_text_field($input['geolite2_default_country_code']);
			if (preg_match('/^[A-Za-z]{2}$/',$sanitized_text)) {
				$new_input['geolite2_default_country_code'] = strtoupper($sanitized_text);
			} else {
				add_settings_error('settings_of_annotation_by_country','geolite2_default_country_code_should_be_2_letters','Specify the default country code which should be specified in 2 letters.');
				$new_input['geolite2_default_country_code'] = $this->options['geolite2_default_country_code'];
			}
		} else {
			add_settings_error('settings_of_annotation_by_country','geolite2_default_country_code','Specify the default country code.');
			$new_input['geolite2_default_country_code'] = $this->options['geolite2_default_country_code'];
		}
		$new_input['geolite2_tagname_in_short_form'] = intval($input['geolite2_tagname_in_short_form']);
		return $new_input;
	}

	function print_section_info() {
		return '';
	}

	function setup_geolite2_database_path() {
		printf('<input type="text" id="geolite2_database_path" name="_settings_of_annotation_by_country[geolite2_database_path]" value="%s" />',
		isset($this->options['geolite2_database_path'])?esc_attr($this->options['geolite2_database_path']):self::DEFAULT_GEOLITE2_COUNTRY_DATABASE_PATH);
	}

	function setup_geolite2_php_path() {
		printf('<input type="text" id="geolite2_php_path" name="_settings_of_annotation_by_country[geolite2_php_path]" value="%s" />',
		isset($this->options['geolite2_php_path'])?esc_attr($this->options['geolite2_php_path']):"/usr/share/php/GeoIP2-php");
	}

	function setup_country_code() {
		printf('<input type="text" id="geolite2_default_country_code" name="_settings_of_annotation_by_country[geolite2_default_country_code]" value="%s" />',
		isset($this->options['geolite2_default_country_code'])?esc_attr($this->options['geolite2_default_country_code']):self::DEFAULT_COUNTRY_CODE);
	}

	function setup_tagname_in_short_form() {
		printf('<input type="checkbox" id="geolite2_tagname_in_short_form" name="_settings_of_annotation_by_country[geolite2_tagname_in_short_form]" value="1" %s/>',
		(isset($this->options['geolite2_tagname_in_short_form']) &&
		 $this->options['geolite2_tagname_in_short_form'] == 1)?'checked="checked" ':'');
	}
}

if (is_admin()) {
	$annotationByCountrySettingsPage = new AnnotationByCountrySettingsPage();
}

$_settings_of_annotation_by_country = get_option('_settings_of_annotation_by_country','');

if ($_settings_of_annotation_by_country !== '' &&
    is_array($_settings_of_annotation_by_country) &&
    array_key_exists('geolite2_php_path',$_settings_of_annotation_by_country) &&
    file_exists($_settings_of_annotation_by_country['geolite2_php_path']."/vendor/autoload.php")) {
	require_once($_settings_of_annotation_by_country['geolite2_php_path']."/vendor/autoload.php");

	function get_country_isocode_() {
		 global $_settings_of_annotation_by_country;
         	 global $_country_isocode;
         	 if (empty($_country_isocode)) {
            	    $reader = new GeoIp2\Database\Reader($_settings_of_annotation_by_country['geolite2_database_path']);
            	    $record = $reader->country($_SERVER["REMOTE_ADDR"]);
            	    $_country_isocode = $record->country->isoCode;
         	 }
         	 return $_country_isocode;
	}

	function annotation_by_country($atts, $content = "") {
		 global $_settings_of_annotation_by_country;
		 $atts = shortcode_atts(array(
		       	 'for' => $_settings_of_annotation_by_country['geolite2_default_country_code'],
			 'except' => ''), $atts, 'annotation-by-country');
		 try {
		    if ($atts['except'] !== '') {
		        $country_list = explode(',',strtoupper($atts['except']));
		        if (in_array(get_country_isocode_(),$country_list,true)) {
		          return '';
		        } else {
		          return $content;
		        }
		    }
		    if ($atts['for'] !== '') {
		       $country_list = explode(',',strtoupper($atts['for']));
		       if (in_array(get_country_isocode_(),$country_list,true)) {
		         return $content;
		       } else {
		         return '';
		       }
		    }
		 } catch (GeoIp2\Exception\AddressNotFoundException $ex) {
		    error_log($ex->getMessage());
		 }
		 return '';
	}

	add_shortcode('annotation-by-country','annotation_by_country');

	if ($_settings_of_annotation_by_country['geolite2_tagname_in_short_form'] === 1) {
	   add_shortcode('abc','annotation_by_country');
	}
}
?>
