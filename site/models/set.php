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
	@subpackage		set.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>
	@copyright		Copyright (C) 2020. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

	Sentinel Server.

/-----------------------------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Sentinel Set Model
 */
class SentinelModelSet extends JModelItem
{
	/**
	 * Model context string.
	 *
	 * @var        string
	 */
	protected $_context = 'com_sentinel.set';

	/**
	 * Model user data.
	 *
	 * @var        strings
	 */
	protected $user;
	protected $userId;
	protected $guest;
	protected $groups;
	protected $levels;
	protected $app;
	protected $input;
	protected $uikitComp;

	/**
	 * @var object item
	 */
	protected $item;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since   1.6
	 *
	 * @return void
	 */
	protected function populateState()
	{
		$this->app = JFactory::getApplication();
		$this->input = $this->app->input;
		// Get the itme main id
		$id = $this->input->getInt('id', null);
		$this->setState('set.id', $id);

		// Load the parameters.
		$params = $this->app->getParams();
		$this->setState('params', $params);
		parent::populateState();
	}

	/**
	 * Method to get article data.
	 *
	 * @param   integer  $pk  The id of the article.
	 *
	 * @return  mixed  Menu item data object on success, false on failure.
	 */
	public function getItem($pk = null)
	{
		$this->user = JFactory::getUser();
		$this->userId = $this->user->get('id');
		$this->guest = $this->user->get('guest');
		$this->groups = $this->user->get('groups');
		$this->authorisedGroups = $this->user->getAuthorisedGroups();
		$this->levels = $this->user->getAuthorisedViewLevels();
		$this->initSet = true;

		$pk = (!empty($pk)) ? $pk : (int) $this->getState('set.id');

		// real keyID
		$keyID = 0;

		// get api key
		$key		= $this->input->server->get('HTTP_SENTINEL_KEY', null, 'ALNUM');
		$data	=  $this->input->server->get('HTTP_SENTINEL_DATA', null, 'STRING');
		$trust		=  $this->input->server->get('HTTP_SENTINEL_TRUST', null, 'STRING');

		// check if the key is found
		if (SentinelHelper::checkString($key))
		{
			if (!$keyID)
			{
				$keyID = SentinelHelper::getVar_('form', $key, 'hostkey', 'id');
			}
			// end connection if trust not set
			if (!SentinelHelper::checkString($trust))
			{
				if ($keyID > 0)
				{
					echo 1;
					jexit();
				}
				echo 0;
				jexit();
			}
			// get trusted ID
			if (SentinelHelper::checkString($trust))
			{
				if (($trustID = SentinelHelper::getVar('form', $trust, 'trustkey', 'id')) === false)
				{
					$trustID = SentinelHelper::getVar_('form', $trust, 'trustkey', 'id');
				}
			}
			else
			{
				$trustID = 0;
			}
			// set host key if not found but trust is valid
			if (!$keyID && $trustID > 0)
			{
				// update the hostkey
				$model = SentinelHelper::getModel('form', JPATH_COMPONENT_ADMINISTRATOR);
				// set array
				$client = array();
				$client['id'] = $trustID;
				$client['hostkey'] = $key;
				// save client data
				if ($model->save($client))
				{
					echo 1;
					jexit();
				}
				echo 0;
				jexit();
			}
			elseif ($keyID > 0)
			{
				echo 1;
				jexit();
			}
			elseif (!$keyID)
			{
				echo 0;
				jexit();
			}
			// set the correct ID
			$pk = $keyID;
		}
		
		if ($this->_item === null)
		{
			$this->_item = array();
		}

		if (!isset($this->_item[$pk]))
		{
			try
			{
				// Get a db connection.
				$db = JFactory::getDbo();

				// Create a new query object.
				$query = $db->getQuery(true);

				// Get from #__sentinel_form as a
				$query->select($db->quoteName(
			array('a.id','a.name','a.alias','a.version','a.hits'),
			array('id','name','alias','version','hits')));
				$query->from($db->quoteName('#__sentinel_form', 'a'));
				$query->where('a.id = ' . (int) $pk);
				// Get where a.published is 1
				$query->where('a.published = 1');

				// Reset the query using our newly populated query object.
				$db->setQuery($query);
				// Load the results as a stdClass object.
				$data = $db->loadObject();

				if (empty($data))
				{
					$app = JFactory::getApplication();
					// If no data is found redirect to default page and show warning.
					$app->enqueueMessage(JText::_('COM_SENTINEL_NOT_FOUND_OR_ACCESS_DENIED'), 'warning');
					$app->redirect(JURI::root());
					return false;
				}

				// set data object to item.
				$this->_item[$pk] = $data;
			}
			catch (Exception $e)
			{
				if ($e->getCode() == 404)
				{
					// Need to go thru the error handler to allow Redirect to work.
					JError::raiseWaring(404, $e->getMessage());
				}
				else
				{
					$this->setError($e);
					$this->_item[$pk] = false;
				}
			}
		}

		// set the data (TODO)
		if ($keyID > 0 && isset($this->_item[$keyID]))
		{
			echo 1;
			jexit();
		}

		return $this->_item[$pk];
	}

	/**
	 * Get the uikit needed components
	 *
	 * @return mixed  An array of objects on success.
	 *
	 */
	public function getUikitComp()
	{
		if (isset($this->uikitComp) && SentinelHelper::checkArray($this->uikitComp))
		{
			return $this->uikitComp;
		}
		return false;
	}
}
