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

class civipcp_search_builder {
  //Params as produced by the shortcode (no searching)
  // params that can be sent to shortcode
  var $requiredParams = array(
    'page_type' => '',
    'page_id' => '',
    'options' => array('limit' => 0),
  );
  var $optionalParams = array(
    'page_title' => '',
    'title' => '',
    'goal_amount' => '',
    'intro_text' => '',
    'page_text' => '',
    'is_thermometer' => '',
    'donate_link_text' => '',
    'contact' => '',
    'campaign_id' => '',
  );
  // params to be sent to civi
  var $params = array(
    'sequential' => 1,
    'return' => array('is_active'),
  );

  public function __construct() {

  }

  public function civipcp_find_pcps($params) {
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
    if (!empty($result['values'])) {
      return $result;
    }
  }

  public function civipcp_get_event_title($page_type, $page_id, $campaign, $page_title) {
    $shortcodeTitle = NULL;
    if ($page_title == 'campaign') {
      try {
        $campaignTitle = civicrm_api3('Campaign', 'getsingle', array(
          'sequential' => 1,
          'return' => array("title"),
          'id' => 1,
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
    if (!empty($campaignTitle['title'])) {
      $shortcodeTitle = $campaignTitle['title'];
    }
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
      if (!empty($event['title']) && $shortcodeTitle == NULL) {
        $shortcodeTitle = $event['title'];
      }
      // $sql1 = "SELECT sum(contrib.total_amount) FROM civicrm_contribution as contrib
      //   JOIN civicrm_participant_payment as pp
      //   ON contrib.id = pp.contribution_id
      //   JOIN civicrm_participant part
      //   ON pp.participant_id = part.id
      //   WHERE part.event_id = {$page_id}
      //   AND contrib.contribution_status_id = 1";
      // $dao1 = CRM_Core_DAO::singleValueQuery($sql1);
      $dao2 = 0;
      if (!empty($campaign)) {
        $sql2 = "SELECT sum(contrib.total_amount) FROM civicrm_contribution as contrib
          WHERE contrib.campaign_id = {$campaign}
          AND contrib.contribution_status_id = 1";
        $dao2 = CRM_Core_DAO::singleValueQuery($sql2);
      }
      if ($dao2 > 0) {
        $totalRaised = CRM_Utils_Money::format($dao2);
        $total = "<label>Total:</label> $totalRaised";
      }
      $generalInfo = "
      <div class='generalEventInfo'>
        <h1>$shortcodeTitle</h1>
        <div class='total'>$total</div>
      </div>";
      return $generalInfo;
    }
  }

  public function civipcp_format_directory($result, $optionalParams, $eventTitle = NULL) {
    $content = '';
    if (!empty($result['values'])) {
      foreach ($result['values'] as $key => $pcp) {
        $totalForPCP = CRM_PCP_BAO_PCP::thermoMeter($pcp['id']);
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
      $content = "
      <div id='resultsdiv'>
        <div class='pcpwidget'>
          " . $content . "
        </div></div>";
    }

    return $content;
  }

}

function civipcp_process_shortcode($attributes, $content = NULL) {
  wp_register_script('civipcp-widget-js', plugins_url('js/civipcp-widget.js', __FILE__), array('jquery', 'underscore'));
  wp_enqueue_style('civipcp-widget-css', plugins_url('css/civipcp-widget.css', __FILE__));
  $search = new civipcp_search_builder();
  extract(shortcode_atts(array_merge($search->requiredParams, $search->optionalParams), $attributes));
  foreach ($search->requiredParams as $key => $value) {
    if (!empty($attributes[$key])) {
      $search->params[$key] = $attributes[$key];
    }
  }
  foreach ($search->optionalParams as $key => $value) {
    if ($attributes[$key] == 1) {
      if ($key == 'contact') {
        $search->params['return'][] = 'contact_id.display_name';
        $search->params['options'] = array('sort' => "contact_id.sort_name ASC");
      }
      if ($key == 'campaign_id') {
        $campaign = $key;
      }
      $search->params['return'][] = $key;
    }
  }
  $bounce = array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'params' => $search->params,
  );
  wp_localize_script('civipcp-widget-js', 'civipcpdir', $bounce);
  wp_enqueue_script('civipcp-widget-js');
  $pcps = $search->civipcp_find_pcps($search->params);
  $generalInfo = $search->civipcp_get_event_title($page_type, $page_id, $campaign, $page_title);
  $formattedContent = $search->civipcp_format_directory($pcps, $optionalParams);
  $searchDiv = '
  <div class="post-filter centered">
    ' . $generalInfo . '
    <div class="pcpsearch">
      <form method="post" action="<?php the_permalink(); ?>" id="civipcp_dir_form">
        <label class="md-search" for="md-search">Search for a campaign by fundraiser name:</label>
        <input type="text" name="cp-name-search" id="cp-name-search" placeholder="Search First and/or Last Name"/>
      <div class="buttons">
        <button class="pcplink" id="dir">Search</button>
        <button class="pcplink" id="clear">Clear Filters</button>
      <br />
      </div>
      </form>
      </div>
    </div>';
  echo "$searchDiv $formattedContent";
}

function lets_search_civipcp_names() {
  $search = new civipcp_search_builder();
  $search->params = $_POST['cpparams'];
  $nameSearch = $_POST['cpnamesearch'];
  if ($nameSearch) {
    $search->params['contact_id.display_name'] = array('LIKE' => "%{$nameSearch}%");
  }
  $pcps = $search->civipcp_find_pcps($search->params);
  $formattedContent = $search->civipcp_format_directory($pcps, $optionalParams, $eventTitle);
  $searchResults = array(
    'html' => $search->params,
  );
  echo $formattedContent;
  exit;
}
