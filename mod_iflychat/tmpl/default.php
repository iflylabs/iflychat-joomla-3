<?php

/**
 * @package iFlyChat
 * @copyright Copyright (C) 2014 iFlyChat. All rights reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author iFlyChat Team
 * @link https://iflychat.com
 */

// no direct access
defined('_JEXEC') or die;
?>

<?php
require(JPATH_ROOT . '/components/com_iflychat/helpers/helper.php');
{
  ?>
  <div class="mod_iflychat">

    <?php
    $compParams = JComponentHelper::getParams('com_iflychat');
    $user = JFactory::getUser();
//    $user->username;
    $helper = new iflychatHelper;
    $r = '';
    $r .= '<script>var iflychat_bundle = document.createElement("script");';
    $r .= 'iflychat_bundle.src = "//cdn.iflychat.com/js/iflychat-v2.min.js?app_id=' . $compParams->get('iflychat_app_id') . '";';
    $r .= 'iflychat_bundle.async="async";';
    $r .= 'document.body.appendChild(iflychat_bundle);';
    $r .= '</script>';
    $user_data = FALSE;
    if ($user->id) {
      $user_data = json_encode($helper->iflychat_get_user_auth());
    };
    if ($compParams->get('iflychat_enable_session_caching') == '1' && isset($_SESSION['user_data']) && $_SESSION['user_data'] == $user_data) {
      if (isset($_SESSION['token']) && !empty($_SESSION['token'])) {
        $r .= '<script>var iflychat_auth_token = "' . $_SESSION['token'] . '";</script>';
      }
    }
    if ($user->id) {
      $r .= '<script>';
      $r .= 'var iflychat_auth_url = "' . JURI::base() . 'index.php?option=com_iflychat&view=auth&format=raw";</script>';
    }
      $r .= '<script>var iFlyChatDiv = document.createElement("div");';
      $r .= 'iFlyChatDiv.className = \'iflychat-popup\';';
      $r .= 'document.body.appendChild(iFlyChatDiv);';
      $r .= '</script>';
    echo "$r";
    ?>
  </div>
  <?php
}
?>