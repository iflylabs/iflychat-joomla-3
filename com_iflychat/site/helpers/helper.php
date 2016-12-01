<?php
/**
 * @package   iFlyChat
 * @copyright Copyright (C) 2014 iFlyChat. All rights reserved.
 * @license   GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author    iFlyChat Team
 * @link      https://iflychat.com
 */

// no direct access
defined('_JEXEC') or die;
if (!defined('IFLYCHAT_EXTERNAL_HOST')) define('IFLYCHAT_EXTERNAL_HOST', 'http://api.iflychat.com');
if (!defined('IFLYCHAT_EXTERNAL_PORT')) define('IFLYCHAT_EXTERNAL_PORT', '80');
if (!defined('IFLYCHAT_EXTERNAL_A_HOST')) define('IFLYCHAT_EXTERNAL_A_HOST', 'https://api.iflychat.com');
if (!defined('IFLYCHAT_EXTERNAL_A_PORT')) define('IFLYCHAT_EXTERNAL_A_PORT', '443');
define('IFLYCHAT_PLUGIN_VERSION', 'Joomla-2.0.3');

class iflychatHelper {

  private function roleArr() {
    $db = JFactory::getDBO();
    $db->setQuery($db->getQuery(TRUE)
      ->select(array('id', 'title'))
      ->from("#__usergroups")
    );
    $groups = $db->loadObjectList();
    $roleArr = array();
    for ($i = 0; $i < sizeof($groups); $i++) {
      $roleArr += array($groups[$i]->id => $groups[$i]->title);
    }
    return $roleArr;
  }
  private function getRole($groups){
    $allRoles = $this->roleArr();
    $output = array();
    for($j = 0 ; $j < sizeof($groups);$j++){
      $output[(string)$groups[$j]] = $allRoles[$groups[$j]];
    }
    return $output;
  }
  public function getToken($api_key) {
    if (!isset($api_key)) {
      return 'Invalid api key';
    }
    $comp = JComponentHelper::getParams('com_iflychat'); //getting component details
    $user = JFactory::getUser(); //getting user details
    $data = array(
      'api_key' => $api_key,
      'app_id' => $comp->get('iflychat_app_id', ''),
      'version' => IFLYCHAT_PLUGIN_VERSION
    );
    $user_data = $this->iflychat_get_user_auth();
    $data = array_merge($data, $user_data);
    $data = json_encode($data);
    $_SESSION['user_data'] = json_encode($user_data);
    try {
//HTTP request
      jimport('joomla.http');
      $headers = array(
        'Content-Type' => 'application/json'
      );
//      $options = new Joomla\Registry\Registry();
      $http = JHttpFactory::getHttp();
      $response = $http->post(IFLYCHAT_EXTERNAL_A_HOST . ':' . IFLYCHAT_EXTERNAL_A_PORT . '/api/1.1/token/generate',$data,$headers);
//      print_r($response);
      if ($response->code === 200) {
        $json = json_decode($response->body);
        if ($comp->get('iflychat_enable_session_caching', '2') === '1') {
          $_SESSION['token'] = $json->key;
        }
        return $response;
      }
      else {
        return $response;
      }
    } catch (Exception $e) {
      $var = array(
        'user_name' => $user->name,
        'user_id' => $user->id
      );
      return (json_encode($var));
    }
  }

  /**
   * function to get user_details
   */
  public function iflychat_get_user_auth() {
    $comp = JComponentHelper::getParams('com_iflychat'); //getting component details
    $user = JFactory::getUser(); //getting user details
    $isroot = $user->authorise('core.admin');
    $chat_role = "participant";
    if ($this->iflychat_check_chat_admin()) {
      $chat_role = "admin";
    }
    else {
      if ($this->iflychat_check_chat_moderator()) {
        $chat_role = "moderator";
      }
    }
    $groups = JAccess::getGroupsByUser($user->id, false);
    if ($isroot) {
      $role = "admin";
      $chat_role = "admin";
    }else {
      if (!empty($groups)) {
        $role = $this->getRole($groups);  //fix roles
      } else {
        $role = array( '0' => 'all users');
      }
    }
//data array
    if ($user->id) {
      $data = array(
        'user_name' => $user->name,
        'user_id' => $user->id,
        'user_roles' => $role,
        'chat_role' => $chat_role,
        'user_list_filter' => 'all',
        'user_status' => TRUE,
      );
//Send roles in data array if role is admin
      if ($role == 'admin') {
        $data['user_site_roles'] = $this->roleArr();
      }
//Get friend's id
      if (file_exists(JPATH_ROOT . '/components/com_community/libraries/core.php')) {
        if ($comp->get('iflychat_enable_friends', 1) == 2) {
          require_once(JPATH_ROOT . '/components/com_community/libraries/core.php');
          $data['user_list_filter'] = 'friend';
          $final_list = array();
          $final_list['1']['name'] = 'friend';
          $final_list['1']['plural'] = 'friends';
          $final_list['1']['valid_uids'] = CFactory::getUser($user->id)
            ->getFriendIds();
          $data['valid_uids'] = $final_list;
        }
      }
      $data['user_avatar_url'] = $this->iflychat_get_user_pic_url();
      $data['user_profile_url'] = $this->iflychat_get_user_profile_url();
    }
    else {
      $data = array();
    }

    return $data;
  }

  public function getDashboardUrl($api_key) {
    $dashboardUrl = '';
    $iflychat_host = IFLYCHAT_EXTERNAL_A_HOST;
    $host = explode("/", $iflychat_host);
    $host_name = $host[2];
    if (isset($_SESSION['token']) && !empty($_SESSION['token'])) {
      $token = $_SESSION['token'];
      $dashboardUrl = "//cdn.iflychat.com/apps/dashboard/#/app-settings?sessid=" . $token . "&hostName=" . $host_name . "&hostPort=" . IFLYCHAT_EXTERNAL_A_PORT;

    }
    else {
//			print_r(gettype(json_decode($this->getToken($api_key)->body)));
      $response = $this->getToken($api_key);

      if (isset($response->code) && $response->code === 200) {
        $body = json_decode($response->body);
        $token = $body->key;
        $dashboardUrl = "//cdn.iflychat.com/apps/dashboard/#/app-settings?sessid=" . $token . "&hostName=" . $host_name . "&hostPort=" . IFLYCHAT_EXTERNAL_A_PORT;
      }
    }
    return $dashboardUrl;
  }

  private function iflychat_get_user_pic_url() {
    $url = '';
    if (file_exists(JPATH_ROOT . '/components/com_community/libraries/core.php')) {
      require_once(JPATH_ROOT . '/components/com_community/libraries/core.php');
      $user = JFactory::getUser()->id;
      $xml = simplexml_load_file(JPATH_SITE . '/administrator/components/com_community/community.xml');
      $version = (string) $xml->version;

      if ($version[0] == '3') {
        $url = CFactory::getUser($user)->getAvatar();

        return $url;
      }
      elseif ($version[0] == '4') {
        $url = JURI::base() . CFactory::getUser($user)->getAvatar();

        $var = explode("/", $url);
        $result = sizeof($var) - 2;
        if ($var[$result] == 'assets') {
          $host = JURI::getInstance()->getHost();
          $url = $var[0] . '//' . $host . CFactory::getUser($user)->getAvatar();

          return $url;
        }
        else {
          return $url;
        }
      }
    }
    else {
      if ((file_exists(JPATH_SITE . '/libraries/CBLib/CBLib/Core/CBLib.php')) || (file_exists(JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php'))) {
        require_once(JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php');
        $user = JFactory::getUser();
        if (!$user->id == '0') {
          $cbUser = &CBuser::getInstance($user->id);
          $cbUser->_getCbTabs(FALSE);

          // print_r($cbUser->getField( 'avatar', null, 'csv', 'none', 'list'));
          return $cbUser->getField('avatar', NULL, 'csv', 'none', 'list');

        }
        else {
          $module = JModuleHelper::getModule('mod_iflychat');
          $comp = JComponentHelper::getParams('com_iflychat');
          if ($comp->get('iflychat_theme', 1) == 1) {
            $iflychat_theme = 'light';
          }
          else {
            $iflychat_theme = 'dark';
          }
          $url = JURI::base() . 'modules/' . $module->module . '/themes/' . $iflychat_theme . '/images/default_avatar.png';
          $pos = strpos($url, ':');
          if ($pos !== FALSE) {
            $url = substr($url, $pos + 1);
          }

          return $url;
        }
      }
      else {
        $module = JModuleHelper::getModule('mod_iflychat');
        $comp = JComponentHelper::getParams('com_iflychat');
        if ($comp->get('iflychat_theme', 1) == 1) {
          $iflychat_theme = 'light';
        }
        else {
          $iflychat_theme = 'dark';
        }
        $url = JURI::base() . 'modules/' . $module->module . '/themes/' . $iflychat_theme . '/images/default_avatar.png';
        $pos = strpos($url, ':');
        if ($pos !== FALSE) {
          $url = substr($url, $pos + 1);
        }
        //print_r($url);
        return $url;
      }
    }
  }

  private function iflychat_get_user_profile_url() {
    if (file_exists(JPATH_ROOT . '/components/com_community/libraries/core.php')) {

      require_once(JPATH_ROOT . '/components/com_community/libraries/core.php');
      $user = JFactory::getUser()->id;
      $host = JURI::getInstance()->getHost();
      $url = JURI::base();
      $var = explode(":", $url);
      $profileLink = CUrlHelper::userLink($user);
      $upl = $var[0] . '://' . $host . $profileLink;

      return $upl;
    }
    else {
      if ((file_exists(JPATH_SITE . '/libraries/CBLib/CBLib/Core/CBLib.php')) || (file_exists(JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php'))) {
        require_once(JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php');
        global $_CB_framework;
        $user = JFactory::getUser()->id;
        if ($user !== '0') {
          $id = $_CB_framework->displayedUser($user);

          $cbUser =& CBuser::getInstance($user);
          $profilLink = cbSef('index.php?option=com_comprofiler&amp;task=userProfile&amp;user=' . $id . getCBprofileItemid(), TRUE);

          //print_r($profilLink);
          return $profilLink;
        }
      }
      else {
        $upl = 'javascript:void()';
        return $upl;
      }
    }
  }

  private function iflychat_check_chat_admin() {
    $user = JFactory::getUser();
    $comp = JComponentHelper::getParams('com_iflychat');
    $a = $comp->get('iflychat_admin', '');
    if (!empty($a) && ($user->id)) {
      $a_names = explode(",", $a);
      foreach ($a_names as $an) {
        $aa = trim($an);
        if (($aa == $user->username) || ($aa == $user->name)) {
          return TRUE;
          break;
        }
      }
    }
    return FALSE;
  }

  private function iflychat_check_chat_moderator() {
    $user = JFactory::getUser();
    $comp = JComponentHelper::getParams('com_iflychat');
    $a = $comp->get('iflychat_mod', '');
    if (!empty($a) && ($user->id)) {
      $a_names = explode(",", $a);
      foreach ($a_names as $an) {
        $aa = trim($an);
        if (($aa == $user->username) || ($aa == $user->name)) {
          return TRUE;
          break;
        }
      }
    }
    return FALSE;
  }
}