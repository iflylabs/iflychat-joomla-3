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
require(JPATH_ROOT . '/components/com_iflychat/helpers/helper.php');

$helper = new iflychatHelper;
$comp = JComponentHelper::getParams('com_iflychat');
$dashBoardUrl = $helper->getDashboardUrl($comp->get('iflychat_external_api_key'));
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');


?>
<script type="text/javascript">
  //    window.addEvent('domready', function() {
  //
  //        var span = document.getElementById('iflychat-form');
  //        if(document.id('jform_iflychat_show_admin_list').value == '2') span.getElementsByTagName('li')[1].style.display="none";
  //        else span.getElementsByTagName('li')[1].style.display="";
  //        document.id('jform_iflychat_show_admin_list').addEvent('change', function(){
  //            if(document.id('jform_iflychat_show_admin_list').value == '2') span.getElementsByTagName('li')[1].style.display="none";
  //            else span.getElementsByTagName('li')[1].style.display="";
  //        });
  //    });

  Joomla.submitbutton = function (task) {
    if (task == 'message.cancel' || document.formvalidator.isValid(document.id('iflychat-form'))) {
      Joomla.submitform(task, document.getElementById('iflychat-form'));
    }
    else {
      alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
    }
  }
</script>

<form
  action="<?php echo JRoute::_('index.php?option=com_iflychat&view=iflychat&layout=edit'); ?>"
  method="post" name="adminForm" id="iflychat-form" class="form-validate">
  <div class="row-fluid">

    <div class="span10">
      <ul class="nav nav-tabs" id="configTabs">
        <?php $fieldSets = $this->form->getFieldsets(); ?>
        <?php foreach ($fieldSets as $name => $fieldSet) : ?>

          <?php $label = empty($fieldSet->label) ? 'COM_CONFIG_' . $name . '_FIELDSET_LABEL' : $fieldSet->label; ?>
          <li><a href="#<?php echo $name; ?>"
                 data-toggle="tab"><?php echo JText::_($label); ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="tab-content">
        <?php $fieldSets = $this->form->getFieldsets(); ?>
        <?php foreach ($fieldSets as $name => $fieldSet) : ?>
          <div class="tab-pane" id="<?php echo $name; ?>">
            <?php
            if (isset($fieldSet->description) && !empty($fieldSet->description)) {
              echo '<p class="tab-description">' . JText::_($fieldSet->description) . '</p>';
            }
            ?>
            <?php if ($fieldSet->name === 'app_setting') { ?>

              <input type="button" class="btn btn-small btn-success" value="Open App Dashboard in new tab" onclick="window.open('<?php echo $dashBoardUrl ?>')">
            <?php
            } ?>
            <?php foreach ($this->form->getFieldset($name) as $field) : ?>
              <?php
              $class = '';
              $rel = '';
              if ($showon = $field->getAttribute('showon')) {
                JHtml::_('jquery.framework');
                JHtml::_('script', 'jui/cms.js', FALSE, TRUE);
                $id = $this->form->getFormControl();
                $showon = explode(':', $showon, 2);
                $class = ' showon_' . implode(' showon_', explode(',', $showon[1]));
                $rel = ' rel="showon_' . $id . '[' . $showon[0] . ']"';
              }
              ?>
              <div
                class="control-group<?php echo $class; ?>"<?php echo $rel; ?>>
                <?php if (!$field->hidden && $name != "permissions") : ?>
                  <div class="control-label">
                    <?php echo $field->label; ?>
                  </div>
                <?php endif; ?>
                <div
                  class="<?php if ($name != "permissions") : ?>controls<?php endif; ?>">
                  <?php echo $field->input; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div>
    <input type="hidden" name="id" value="<?php echo $this->component->id; ?>"/>
    <input type="hidden" name="component"
           value="<?php echo $this->component->option; ?>"/>
    <input type="hidden" name="return" value="<?php echo $this->return; ?>"/>
    <input type="hidden" name="task" value=""/>
    <?php echo JHtml::_('form.token'); ?>
  </div>
</form>
<script type="text/javascript">
  jQuery('#configTabs a:first').tab('show'); // Select first tab
</script>
