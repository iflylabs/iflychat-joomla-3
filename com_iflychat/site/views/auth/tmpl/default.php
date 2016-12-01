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
error_reporting(2);
require(JPATH_ROOT . '/components/com_iflychat/helpers/helper.php');

$helper = new iflychatHelper;
$comp = JComponentHelper::getParams('com_iflychat');

$response = $helper->getToken($comp->get('iflychat_external_api_key'));
$document =& JFactory::getDocument();
// Set the MIME type for JSON output.
$document->setMimeEncoding('application/json');

if ($response->code === 200) {
  print $response->body;
}
