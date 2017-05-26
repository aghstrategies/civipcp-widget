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

add_action('wp_ajax_nopriv_search_civipcp_names', 'lets_search_civipcp_names');
add_action('wp_ajax_search_civipcp_names', 'lets_search_civipcp_names');
add_shortcode('civipcp_shortcode', 'civipcp_process_shortcode');
civicrm_initialize();

function civipcp_process_shortcode($attributes, $content = NULL) {
  wp_register_script('civipcp-widget-js', plugins_url('js/civipcp-widget.js', __FILE__), array('jquery', 'underscore'));
  wp_enqueue_style('civipcp-widget-css', plugins_url('css/civipcp-widget.css', __FILE__));
  $bounce = array(
    'ajaxurl' => admin_url('admin-ajax.php'),
  );
  wp_localize_script('civipcp-widget-js', 'civipcpdir', $bounce);
  wp_enqueue_script('civipcp-widget-js');
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
    'contact' => '',
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
      if ($key == 'contact') {
        $params['return'][] = 'contact_id.display_name';
      }
      $params['return'][] = $key;
    }
  }
  $pcps = civipcp_find_pcps($params);
  $eventTitle = civipcp_get_event_title($page_type, $page_id);
  $formattedContent = civipcp_format_directory($pcps, $optionalParams, $eventTitle);
  $searchDiv = '
  <div class="post-filter centered">
      <h3>Search For a Campaign:</h3>
      <form method="post" action="<?php the_permalink(); ?>" id="civipcp_dir_form">
        <label for="md-search">Search By Name:</label>
        <input type="text" name="cp-name-search" id="cp-name-search" placeholder="Enter Name to Search on"/>
      <div class="buttons">
        <button class="pcplink" id="dir">Search</button>
        <button class="pcplink" id="clear">Clear Filters</button>
      <br />
      </div>
      </form>
    </div>';
  return "$searchDiv <div id='resultsdiv'>$formattedContent</div>";
}

function civipcp_get_event_title($page_type, $page_id) {
  $eventTitle = NULL;
  if ($page_type == 'event') {
    try {
      $event = civicrm_api3('Event', 'getsingle', array(
        'return' => array("title"),
        'id' => $page_id,
      ));
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(ts('API Error %1', array(
        'domain' => 'civipcp_widget',
        1 => $error,
      )));
    }
    if (!empty($event['title'])) {
      $eventTitle = $event['title'];
    }
    return $eventTitle;
  }
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

function civipcp_format_directory($result, $optionalParams, $eventTitle = NULL) {
  $totalRaised = 0;
  $content = '';
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
          <a class='pcpa' href='http://crm.artsunbound.org/civicrm/?page=CiviCRM&q=civicrm/pcp/info&reset=1&id=" . $pcp['id'] . "&ap=0'><div class='pcplink'>Donate</div></a>
        </div>";
      }
    }
    $totalRaised = CRM_Utils_Money::format($totalRaised);
    $content .= "</div>";
    $content = "
    <div class='pcpwidget'>
      <h1>$eventTitle</h1>
      <div class='total'><label>Total:</label>  $totalRaised</div>" . $content;
  }

  return $content;
}

function lets_search_civipcp_names() {
  $nameSearch = $_POST['cpnamesearch'];
  $params['contact_id.display_name'] = array('LIKE' => "%{$nameSearch}%");
  // print_r($params); die();
  $searchResults = array(
    'html' => $params,
  );
  echo $_REQUEST['cpnamesearch'];
  exit;
}
