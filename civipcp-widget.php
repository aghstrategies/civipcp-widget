<?php
   /*
   Plugin Name: Civicrm PCP Widget
   Plugin URI: http://my-awesomeness-emporium.com
   Description: a plugin to create awesomeness and spread joy
   Version: 1.0
   Author: AGH Strategies
   Author URI: http://mrtotallyawesome.com
   License: GPL2
   */

add_shortcode('civipcp_shortcode', 'civipcp_process_shortcode');

function civipcp_process_shortcode($attributes, $content = NULL) {
  extract(shortcode_atts(array('pcp' => ''), $attributes));
  if (!empty($pcp)) {
    civipcp_find_pcps($pcp);
  }
  return '<blockquote class="pullquote ' . $pcp . '">' . $content . '</blockquote>';
}

function civipcp_find_pcps($pcpId) {
  civicrm_initialize();
  try {
    $result = civicrm_api3('Pcp', 'get', array(
      'sequential' => 1,
      'page_type' => "",
    ));
  }
  catch (CiviCRM_API3_Exception $e) {
    $error = $e->getMessage();
    CRM_Core_Error::debug_log_message(ts('API Error %1', array(
      'domain' => 'civipcp_widget',
      1 => $error,
    )));
  }
}
