<?php
//
//  SETTINGS CONFIGURATION CLASS
//
//  By Olly Benson / v 1.2 / 13 July 2011 / http://code.olib.co.uk
//  Modified / Bugfix by Karl Cohrs / 17 July 2011 / http://karlcohrs.com
//
//  HOW TO USE
//  * add a include() to this file in your plugin.
//  * amend the config class below to add your own settings requirements.
//  * to avoid potential conflicts recommended you do a global search/replace on this page to replace 'sandstormmedia_elasticsearch_settings' with something unique
//  * Full details of how to use Settings see here: http://codex.wordpress.org/Settings_API


function sandstormmedia_elasticsearch_rebuildIndexCheckbox($config) {
	echo '<input type="checkbox" name="'. $config['name'] .'" value="1" />';
}

class sandstormmedia_elasticsearch_settings_config {

// MAIN CONFIGURATION SETTINGS

var $group = "sandstormmedia_elasticsearch"; // defines setting groups (should be bespoke to your settings)
var $page_name = "ob_display"; // defines which pages settings will appear on. Either bespoke or media/discussion/reading etc

//  DISPLAY SETTINGS
//  (only used if bespoke page_name)

var $title = "ElasticSearch Connector";  // page title that is displayed
var $intro_text = "Configuration of ElasticSearch connector"; // text below title
var $nav_title = "ElasticSearch"; // how page is listed on left-hand Settings panel

//  SECTIONS
//  Each section should be own array within $sections.
//  Should contatin title, description and fields, which should be array of all fields.
//  Fields array should contain:
//  * label: the displayed label of the field. Required.
//  * description: the field description, displayed under the field. Optional
//  * suffix: displays right of the field entry. Optional
//  * default_value: default value if field is empty. Optional
//  * dropdown: allows you to offer dropdown functionality on field. Value is array listed below. Optional
//  * function: will call function other than default text field to display options. Option
//  * callback: will run callback function to validate field. Optional
//  * All variables are sent to display function as array, therefore other variables can be added if needed for display purposes

var $sections = array(
    'server' => array(
        'title' => "ElasticSearch Server Options",
        'description' => "Configure ElasticSearch Server",
        'fields' => array (
          'elasticSearchUri' => array (
              'label' => "URL",
              'description' => "ElasticSearch Endpoint URL, WITH slash at the end!",
              'length' => "100",
              'suffix' => "",
              'default_value' => "http://localhost:9200/public/wordpress/"
              ),
            )

        ),
      'rebuild' => array(
          'title' => 'Rebuild Index',
          'description' => "If checked, will rebuild the index on next hit",
          'fields' => array(
            'rebuild' => array (
              'function' => 'sandstormmedia_elasticsearch_rebuildIndexCheckbox',
              'label' => "Rebuild Index",
              )
            )
          )
    );

 // DROPDOWN OPTIONS
 // For drop down choices.  Each set of choices should be unique array
 // Use key => value to indicate name => display name
 // For default_value in options field use key, not value
 // You can have multiple instances of the same dropdown options

var $dropdown_options = array (
    'dd_colour' => array (
        '#f00' => "Red",
        '#0f0' => "Green",
        '#00f' => "Blue",
        '#fff' => "White",
        '#000' => "Black",
        '#aaa' => "Gray",
        )
    );

//  end class
};

class sandstormmedia_elasticsearch_settings {

function sandstormmedia_elasticsearch_settings($settings_class) {
    global $sandstormmedia_elasticsearch_settings;
    $sandstormmedia_elasticsearch_settings = get_class_vars($settings_class);

    if (function_exists('add_action')) :
      add_action('admin_init', array( &$this, 'plugin_admin_init'));
      add_action('admin_menu', array( &$this, 'plugin_admin_add_page'));
      endif;
}

function plugin_admin_add_page() {
  global $sandstormmedia_elasticsearch_settings;
  add_options_page($sandstormmedia_elasticsearch_settings['title'], $sandstormmedia_elasticsearch_settings['nav_title'], 'manage_options', $sandstormmedia_elasticsearch_settings['page_name'], array( &$this,'plugin_options_page'));
  }

function plugin_options_page() {
  global $sandstormmedia_elasticsearch_settings;
printf('</pre>
<div>
<h2>%s</h2>
%s
<form action="options.php" method="post">',$sandstormmedia_elasticsearch_settings['title'],$sandstormmedia_elasticsearch_settings['intro_text']);
 settings_fields($sandstormmedia_elasticsearch_settings['group']);
 do_settings_sections($sandstormmedia_elasticsearch_settings['page_name']);
 printf('<input type="submit" name="Submit" value="%s" /></form></div>
<pre>
',__('Save Changes'));
  }

function plugin_admin_init(){
  global $sandstormmedia_elasticsearch_settings;
  foreach ($sandstormmedia_elasticsearch_settings["sections"] AS $section_key=>$section_value) :
    add_settings_section($section_key, $section_value['title'], array( &$this, 'plugin_section_text'), $sandstormmedia_elasticsearch_settings['page_name'], $section_value);
    foreach ($section_value['fields'] AS $field_key=>$field_value) :
      $function = (!empty($field_value['dropdown'])) ? array( &$this, 'plugin_setting_dropdown' ) : array( &$this, 'plugin_setting_string' );
      $function = (!empty($field_value['function'])) ? $field_value['function'] : $function;
      $callback = (!empty($field_value['callback'])) ? $field_value['callback'] : NULL;
      add_settings_field($sandstormmedia_elasticsearch_settings['group'].'_'.$field_key, $field_value['label'], $function, $sandstormmedia_elasticsearch_settings['page_name'], $section_key,array_merge($field_value,array('name' => $sandstormmedia_elasticsearch_settings['group'].'_'.$field_key)));
      register_setting($sandstormmedia_elasticsearch_settings['group'], $sandstormmedia_elasticsearch_settings['group'].'_'.$field_key,$callback);
      endforeach;
    endforeach;
  }

function plugin_section_text($value = NULL) {
  global $sandstormmedia_elasticsearch_settings;
  printf("
%s

",$sandstormmedia_elasticsearch_settings['sections'][$value['id']]['description']);
}

function plugin_setting_string($value = NULL) {
  $options = get_option($value['name']);
  $default_value = (!empty ($value['default_value'])) ? $value['default_value'] : NULL;
  printf('<input id="%s" type="text" name="%1$s[text_string]" value="%2$s" size="40" /> %3$s%4$s',
    $value['name'],
    (!empty ($options['text_string'])) ? $options['text_string'] : $default_value,
    (!empty ($value['suffix'])) ? $value['suffix'] : NULL,
    (!empty ($value['description'])) ? sprintf("<em>%s</em>",$value['description']) : NULL);
  }

function plugin_setting_dropdown($value = NULL) {
  global $sandstormmedia_elasticsearch_settings;
  $options = get_option($value['name']);
  $default_value = (!empty ($value['default_value'])) ? $value['default_value'] : NULL;
  $current_value = ($options['text_string']) ? $options['text_string'] : $default_value;
    $chooseFrom = "";
    $choices = $sandstormmedia_elasticsearch_settings['dropdown_options'][$value['dropdown']];
  foreach($choices AS $key=>$option) :
    $chooseFrom .= sprintf('<option value="%s" %s>%s</option>',
      $key,($current_value == $key ) ? ' selected="selected"' : NULL,$option);
    endforeach;
    printf('
<select id="%s" name="%1$s[text_string]">%2$s</select>
%3$s',$value['name'],$chooseFrom,
  (!empty ($value['description'])) ? sprintf("<em>%s</em>",$value['description']) : NULL);
  }


//end class
}

$sandstormmedia_elasticsearch_settings_init = new sandstormmedia_elasticsearch_settings('sandstormmedia_elasticsearch_settings_config');
?>