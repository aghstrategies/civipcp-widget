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
civicrm_initialize();

function civipcp_process_shortcode($attributes, $content = NULL) {
  wp_enqueue_style('civipcp-widget-css', plugins_url('css/civipcp-widget.css', __FILE__));
  // params that can be sent to shortcode
  $requiredParams = array(
    'page_type' => '',
    'page_id' => '',
  );
  $optionalParams = array(
    'title' => '',
    'goal_amount' => '',
    'intro_text' => '',
    'page_text' => '',
    'is_thermometer' => '',
    'donate_link_text' => '',
  );
  // params to be sent to civi
  $params = array(
    'sequential' => 1,
    'return' => array('is_active'),
  );

  extract(shortcode_atts(array_merge($requiredParams, $optionalParams), $attributes));
  foreach ($requiredParams as $key => $value) {
    if (!empty($attributes[$key])) {
      $params[$key] = $attributes[$key];
    }
  }
  foreach ($optionalParams as $key => $value) {
    if ($attributes[$key] == 1) {
      $params['return'][] = $key;
    }
  }
  $pcps = civipcp_find_pcps($params);
  $formattedContent = civipcp_format_directory($pcps, $optionalParams);
  return $formattedContent;
}

function civipcp_find_pcps($params) {
  try {
    $result = civicrm_api3('Pcp', 'get', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    $error = $e->getMessage();
    CRM_Core_Error::debug_log_message(ts('API Error %1', array(
      'domain' => 'civipcp_widget',
      1 => $error,
    )));
  }
  // print_r($result); die();
  if (!empty($result['values'])) {
    return $result;
  }
}

function civipcp_format_directory($result, $optionalParams) {
  $totalRaised = 0;
  $content = "<div class='pcpwidget'><div class='total'><label>Total:</label>  $totalRaised</div>";
  if (!empty($result['values'])) {
    foreach ($result['values'] as $key => $pcp) {
      $totalForPCP = CRM_PCP_BAO_PCP::thermoMeter($pcp['id']);
      $totalRaised = $totalRaised + $totalForPCP;
      if ($pcp['is_active'] == 1) {
        $content .= "<div class=' pcp pcp" . $pcp['id'] . "'>";
        foreach ($pcp as $field => $value) {
          $content .= "<div class=" . $field . ">" . $value . "</div>";
        }
        //TODO don't hardcode the url
        $pcpTotal = CRM_Utils_Money::format($totalForPCP);
        $content .= "
          <div class='pcptotal'><label>Total Raised So Far:</label>  $pcpTotal</div>
          <a class='pcpa' href='http://wpmaster/civicrm/?page=CiviCRM&q=civicrm/pcp/info&reset=1&id=" . $pcp['id'] . "&ap=0'><div class='pcplink'>Donate</div></a>
        </div>";
      }
    }
  }
  $totalRaised = CRM_Utils_Money::format($totalRaised);
  $content .= "</div>";
  return $content;
}
