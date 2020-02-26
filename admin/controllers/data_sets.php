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
	@build			26th February, 2020
	@created		16th June, 2017
	@package		Sentinel
	@subpackage		data_sets.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>
	@copyright		Copyright (C) 2020. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

	Sentinel Server.

/-----------------------------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Data_sets Controller
 */
class SentinelControllerData_sets extends JControllerAdmin
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $text_prefix = 'COM_SENTINEL_DATA_SETS';

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JModelLegacy  The model.
	 *
	 * @since   1.6
	 */
	public function getModel($name = 'Data_set', $prefix = 'SentinelModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function exportData()
	{
		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
		// check if export is allowed for this user.
		$user = JFactory::getUser();
		if ($user->authorise('data_set.export', 'com_sentinel') && $user->authorise('core.export', 'com_sentinel'))
		{
			// Get the input
			$input = JFactory::getApplication()->input;
			$pks = $input->post->get('cid', array(), 'array');
			// Sanitize the input
			JArrayHelper::toInteger($pks);
			// Get the model
			$model = $this->getModel('Data_sets');
			// get the data to export
			$data = $model->getExportData($pks);
			if (SentinelHelper::checkArray($data))
			{
				// now set the data to the spreadsheet
				$date = JFactory::getDate();
				SentinelHelper::xls($data,'Data_sets_'.$date->format('jS_F_Y'),'Data sets exported ('.$date->format('jS F, Y').')','data sets');
			}
		}
		// Redirect to the list screen with error.
		$message = JText::_('COM_SENTINEL_EXPORT_FAILED');
		$this->setRedirect(JRoute::_('index.php?option=com_sentinel&view=data_sets', false), $message, 'error');
		return;
	}


	public function importData()
	{
		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
		// check if import is allowed for this user.
		$user = JFactory::getUser();
		if ($user->authorise('data_set.import', 'com_sentinel') && $user->authorise('core.import', 'com_sentinel'))
		{
			// Get the import model
			$model = $this->getModel('Data_sets');
			// get the headers to import
			$headers = $model->getExImPortHeaders();
			if (SentinelHelper::checkObject($headers))
			{
				// Load headers to session.
				$session = JFactory::getSession();
				$headers = json_encode($headers);
				$session->set('data_set_VDM_IMPORTHEADERS', $headers);
				$session->set('backto_VDM_IMPORT', 'data_sets');
				$session->set('dataType_VDM_IMPORTINTO', 'data_set');
				// Redirect to import view.
				$message = JText::_('COM_SENTINEL_IMPORT_SELECT_FILE_FOR_DATA_SETS');
				$this->setRedirect(JRoute::_('index.php?option=com_sentinel&view=import', false), $message);
				return;
			}
		}
		// Redirect to the list screen with error.
		$message = JText::_('COM_SENTINEL_IMPORT_FAILED');
		$this->setRedirect(JRoute::_('index.php?option=com_sentinel&view=data_sets', false), $message, 'error');
		return;
	}
}
