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
	@subpackage		profilefields.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>
	@copyright		Copyright (C) 2020. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

	Sentinel Server.

/-----------------------------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Profilefields Form Field class for the Sentinel component
 */
class JFormFieldProfilefields extends JFormFieldList
{
	/**
	 * The profilefields field type.
	 *
	 * @var		string
	 */
	public $type = 'profilefields';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array    An array of JHtml options.
	 */
	protected function getOptions()
	{
					$db = JFactory::getDBO();
			// get the database columns of this table
			$columns = $db->getTableColumns('#__sentinel_form', false);
			// always remove these (not public)
			$remove = array('id', 'member', 'asset_id', 'checked_out', 'checked_out_time', 'created', 'created_by', 'modified', 'modified_by', 'hits', 'version', 'access', 'ordering', 'published');
			// remove
			foreach ($remove as $key)
			{
				unset($columns[$key]);
			}
			// add email if component is contactdetails :)
			if ('sentinel' === 'contactdetails' || 'sentinel' === 'coral_contactdetails')
			{
				$columns['email'] = 'email';
			}
			// add age if component is personaldetails :)
			elseif ('sentinel' === 'personaldetails' || 'sentinel' === 'coral_personaldetails')
			{
				$columns['age'] = 'age';
			}
			// prep the columns
			$items = array_keys($columns);
			if ($items)
			{
				foreach($items as $item)
				{
					$options[] = JHtml::_('select.option', $item, $item);
				}
			}
			return $options;
	}
}
