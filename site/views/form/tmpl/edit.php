<?php
/*--------------------------------------------------------------------------------------------------------|  www.vdm.io  |------/
    __      __       _     _____                 _                                  _     __  __      _   _               _
    \ \    / /      | |   |  __ \               | |                                | |   |  \/  |    | | | |             | |
     \ \  / /_ _ ___| |_  | |  | | _____   _____| | ___  _ __  _ __ ___   ___ _ __ | |_  | \  / | ___| |_| |__   ___   __| |
      \ \/ / _` / __| __| | |  | |/ _ \ \ / / _ \ |/ _ \| '_ \| '_ ` _ \ / _ \ '_ \| __| | |\/| |/ _ \ __| '_ \ / _ \ / _` |
       \  / (_| \__ \ |_  | |__| |  __/\ V /  __/ | (_) | |_) | | | | | |  __/ | | | |_  | |  | |  __/ |_| | | | (_) | (_| |
        \/ \__,_|___/\__| |_____/ \___| \_/ \___|_|\___/| .__/|_| |_| |_|\___|_| |_|\__| |_|  |_|\___|\__|_| |_|\___/ \__,_|
                                                        | |
                                                        |_|
/-------------------------------------------------------------------------------------------------------------------------------/

	@version		2.0.6
	@build			25th February, 2020
	@created		16th June, 2017
	@package		Sentinel
	@subpackage		edit.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>
	@copyright		Copyright (C) 2020. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

	Sentinel Server.

/-----------------------------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.keepalive');
JHtml::_('behavior.tabstate');
JHtml::_('behavior.calendar');
$componentParams = $this->params; // will be removed just use $this->params instead
?>
<?php echo $this->toolbar->render(); ?>
<form action="<?php echo JRoute::_('index.php?option=com_sentinel&layout=edit&id='. (int) $this->item->id . $this->referral); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">

	<?php echo JLayoutHelper::render('form.station_above', $this); ?>
<div class="form-horizontal">

	<?php echo JHtml::_('bootstrap.startTabSet', 'formTab', array('active' => 'station')); ?>

	<?php echo JHtml::_('bootstrap.addTab', 'formTab', 'station', JText::_('COM_SENTINEL_FORM_STATION', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span6">
				<?php echo JLayoutHelper::render('form.station_left', $this); ?>
			</div>
			<div class="span6">
				<?php echo JLayoutHelper::render('form.station_right', $this); ?>
			</div>
		</div>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">
				<?php echo JLayoutHelper::render('form.station_fullwidth', $this); ?>
			</div>
		</div>
	<?php echo JHtml::_('bootstrap.endTab'); ?>

	<?php $this->ignore_fieldsets = array('details','metadata','vdmmetadata','accesscontrol'); ?>
	<?php $this->tab_name = 'formTab'; ?>
	<?php echo JLayoutHelper::render('joomla.edit.params', $this); ?>

	<?php if ($this->canDo->get('form.delete') || $this->canDo->get('form.edit.created_by') || $this->canDo->get('form.edit.state') || $this->canDo->get('form.edit.created')) : ?>
	<?php echo JHtml::_('bootstrap.addTab', 'formTab', 'publishing', JText::_('COM_SENTINEL_FORM_PUBLISHING', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span6">
				<?php echo JLayoutHelper::render('form.publishing', $this); ?>
			</div>
			<div class="span6">
				<?php echo JLayoutHelper::render('form.publlshing', $this); ?>
			</div>
		</div>
	<?php echo JHtml::_('bootstrap.endTab'); ?>
	<?php endif; ?>

	<?php if ($this->canDo->get('core.admin')) : ?>
	<?php echo JHtml::_('bootstrap.addTab', 'formTab', 'permissions', JText::_('COM_SENTINEL_FORM_PERMISSION', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">
				<fieldset class="adminform">
					<div class="adminformlist">
					<?php foreach ($this->form->getFieldset('accesscontrol') as $field): ?>
						<div>
							<?php echo $field->label; echo $field->input;?>
						</div>
						<div class="clearfix"></div>
					<?php endforeach; ?>
					</div>
				</fieldset>
			</div>
		</div>
	<?php echo JHtml::_('bootstrap.endTab'); ?>
	<?php endif; ?>

	<?php echo JHtml::_('bootstrap.endTabSet'); ?>

	<div>
		<input type="hidden" name="task" value="form.edit" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</div>
</form>

<script type="text/javascript">




		function printMe(name, printDivId) {
			printWindow = window.open('','printwindow', "location=1,status=1,scrollbars=1");
			if(!printWindow)alert('<?php echo JText::_('COM_SENTINEL_PLEASE_ENABLE_POPUPS_IN_YOUR_BROWSER_FOR_THIS_WEBSITE_TO_PRINT_THESE_DETAILS'); ?>');
			printWindow.document.write('<html moznomarginboxes mozdisallowselectionprint><head><title>'+name+'</title><link rel="stylesheet" type="text/css" href="<?php echo JURI::root(); ?>media/com_sentinel/uikit-v2/css/uikit.css">');
			printWindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo JURI::root(); ?>media/com_sentinel/css/A4.print.css">');
			//Print and cancel button
			printWindow.document.write('</head><body >');
			printWindow.document.write('<div class="uk-button-group uk-width-1-1 no-print"><button type="button" class="uk-button uk-width-1-2 uk-button-success" onclick="window.print(); window.close();" ><i class="uk-icon-print"></i> <?php echo JText::_('COM_SENTINEL_PRINT_CLOSE'); ?></button>');
			printWindow.document.write('<button type="button" class="uk-button uk-width-1-2 uk-button-danger" onclick="window.close();"><i class="uk-icon-close"></i> <?php echo JText::_('COM_SENTINEL_CLOSE'); ?></button></div><page size="A4">');
			printWindow.document.write(jQuery('#'+printDivId).html());
			printWindow.document.write('</page></body></html>');
			printWindow.document.close();
			printWindow.focus()
		}
</script>
