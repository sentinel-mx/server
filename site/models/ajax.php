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
	@subpackage		ajax.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>
	@copyright		Copyright (C) 2020. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

	Sentinel Server.

/-----------------------------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.helper');

/**
 * Sentinel Ajax Model
 */
class SentinelModelAjax extends JModelList
{
	protected $app_params;

	public function __construct()
	{
		parent::__construct();
		// get params
		$this->app_params = JComponentHelper::getParams('com_sentinel');

	}

	// Used in form

	/**
	 * get the placeholder
	 *
	 * @param   string  $getType    Name get type
	 *
	 * @return  string  The html string of placeholders
	 *
	 */
	public function getPlaceHolders($getType)
	{
		// check if we should add a header
		if (method_exists(__CLASS__, 'getPlaceHolderHeaders') && ($string = $this->getPlaceHolderHeaders($getType)) !== false)
		{
			$string = JText::_($string) . ' ';
			$header = '<h4>' . $string . '</h4>';
		}
		else
		{
			$string = '';
			$header = '';
		}
		// get placeholders
		if ($placeholders = SentinelHelper::getPlaceHolders($getType))
		{
			return '<div>' . $header . '<code style="display: inline-block; padding: 2px; margin: 3px;">' .
				implode('</code> <code style="display: inline-block; padding: 2px; margin: 3px;">', $placeholders) .
				'</code></div>';
		}
		// not found
		return '<div class="alert alert-error"><h4 class="alert-heading">' .
			$string . JText::_('COM_SENTINEL_PLACEHOLDERS_NOT_FOUND') .
			'!</h4><div class="alert-message">' .
			JText::_('COM_SENTINEL_THERE_WAS_AN_ERROR_PLEASE_TRY_AGAIN_LATER_IF_THIS_ERROR_CONTINUES_CONTACT_YOUR_SYSTEM_ADMINISTRATOR') .
			'</div></div>';
	}

}
