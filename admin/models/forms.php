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
	@subpackage		forms.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>
	@copyright		Copyright (C) 2020. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

	Sentinel Server.

/-----------------------------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Forms Model
 */
class SentinelModelForms extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
        {
			$config['filter_fields'] = array(
				'a.id','id',
				'a.published','published',
				'a.ordering','ordering',
				'a.created_by','created_by',
				'a.modified_by','modified_by',
				'a.name','name'
			);
		}

		parent::__construct($config);
	}
	
	/**
	 * Method to auto-populate the model state.
	 *
	 * @return  void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication();

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}
		$name = $this->getUserStateFromRequest($this->context . '.filter.name', 'filter_name');
		$this->setState('filter.name', $name);
        
		$sorting = $this->getUserStateFromRequest($this->context . '.filter.sorting', 'filter_sorting', 0, 'int');
		$this->setState('filter.sorting', $sorting);
        
		$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', 0, 'int');
		$this->setState('filter.access', $access);
        
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);
        
		$created_by = $this->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by', '');
		$this->setState('filter.created_by', $created_by);

		$created = $this->getUserStateFromRequest($this->context . '.filter.created', 'filter_created');
		$this->setState('filter.created', $created);

		// List state information.
		parent::populateState($ordering, $direction);
	}
	
	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 */
	public function getItems()
	{
		// check in items
		$this->checkInNow();

		// load parent items
		$items = parent::getItems();

		// Set values to display correctly.
		if (SentinelHelper::checkArray($items))
		{
			// Get the user object if not set.
			if (!isset($user) || !SentinelHelper::checkObject($user))
			{
				$user = JFactory::getUser();
			}
			foreach ($items as $nr => &$item)
			{
				// Remove items the user can't access.
				$access = ($user->authorise('form.access', 'com_sentinel.form.' . (int) $item->id) && $user->authorise('form.access', 'com_sentinel'));
				if (!$access)
				{
					unset($items[$nr]);
					continue;
				}

			}
		}
        
		// return items
		return $items;
	}
	
	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return	string	An SQL query
	 */
	protected function getListQuery()
	{
		// Get the user object.
		$user = JFactory::getUser();
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		// Select some fields
		$query->select('a.*');

		// From the sentinel_item table
		$query->from($db->quoteName('#__sentinel_form', 'a'));

		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published))
		{
			$query->where('a.published = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.published = 0 OR a.published = 1)');
		}

		// Join over the asset groups.
		$query->select('ag.title AS access_level');
		$query->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');
		// Filter by access level.
		if ($access = $this->getState('filter.access'))
		{
			$query->where('a.access = ' . (int) $access);
		}
		// Implement View Level Access
		if (!$user->authorise('core.options', 'com_sentinel'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('a.access IN (' . $groups . ')');
		}
		// Filter by search.
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . $db->escape($search) . '%');
				$query->where('(a.name LIKE '.$search.')');
			}
		}


		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering', 'a.id');
		$orderDirn = $this->state->get('list.direction', 'asc');	
		if ($orderCol != '')
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Method to get list export data.
	 *
	 * @param   array  $pks  The ids of the items to get
	 * @param   JUser  $user  The user making the request
	 *
	 * @return mixed  An array of data items on success, false on failure.
	 */
	public function getExportData($pks, $user = null)
	{
		// setup the query
		if (SentinelHelper::checkArray($pks))
		{
			// Set a value to know this is export method. (USE IN CUSTOM CODE TO ALTER OUTCOME)
			$_export = true;
			// Get the user object if not set.
			if (!isset($user) || !SentinelHelper::checkObject($user))
			{
				$user = JFactory::getUser();
			}
			// Create a new query object.
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);

			// Select some fields
			$query->select('a.*');

			// From the sentinel_form table
			$query->from($db->quoteName('#__sentinel_form', 'a'));
			$query->where('a.id IN (' . implode(',',$pks) . ')');
			// Implement View Level Access
			if (!$user->authorise('core.options', 'com_sentinel'))
			{
				$groups = implode(',', $user->getAuthorisedViewLevels());
				$query->where('a.access IN (' . $groups . ')');
			}

			// Order the results by ordering
			$query->order('a.ordering  ASC');

			// Load the items
			$db->setQuery($query);
			$db->execute();
			if ($db->getNumRows())
			{
				$items = $db->loadObjectList();

				// Set values to display correctly.
				if (SentinelHelper::checkArray($items))
				{
					foreach ($items as $nr => &$item)
					{
						// Remove items the user can't access.
						$access = ($user->authorise('form.access', 'com_sentinel.form.' . (int) $item->id) && $user->authorise('form.access', 'com_sentinel'));
						if (!$access)
						{
							unset($items[$nr]);
							continue;
						}

						
						if (!empty($item->hostkey))
						{
							// decrypt field
							$item->hostkey = SentinelHelper::decrypt($item->hostkey);
						}
						
						if (!empty($item->trustkey))
						{
							// decrypt field
							$item->trustkey = SentinelHelper::decrypt($item->trustkey);
						}
						// unset the values we don't want exported.
						unset($item->asset_id);
						unset($item->checked_out);
						unset($item->checked_out_time);
					}
				}
				// Add headers to items array.
				$headers = $this->getExImPortHeaders();
				if (SentinelHelper::checkObject($headers))
				{
					array_unshift($items,$headers);
				}
				return $items;
			}
		}
		return false;
	}

	/**
	* Method to get header.
	*
	* @return mixed  An array of data items on success, false on failure.
	*/
	public function getExImPortHeaders()
	{
		// Get a db connection.
		$db = JFactory::getDbo();
		// get the columns
		$columns = $db->getTableColumns("#__sentinel_form");
		if (SentinelHelper::checkArray($columns))
		{
			// remove the headers you don't import/export.
			unset($columns['asset_id']);
			unset($columns['checked_out']);
			unset($columns['checked_out_time']);
			$headers = new stdClass();
			foreach ($columns as $column => $type)
			{
				$headers->{$column} = $column;
			}
			return $headers;
		}
		return false;
	}

	/**
	 * Method to get data during an export request.
	 *
	 * @param   array  $pks  The ids of the items to get
	 * @param   JUser  $user  The user making the request
	 *
	 * @return mixed  An array of data items on success, false on failure.
	 */
	public function getPrivacyExport($pks, $user = null)
	{
		// setup the query
		if (SentinelHelper::checkArray($pks))
		{
			// Set a value to know this is privacy method. (USE IN CUSTOM CODE TO ALTER OUTCOME)
			$_privacy = true;
			// Get the user object if not set.
			if (!isset($user) || !SentinelHelper::checkObject($user))
			{
				$user = JFactory::getUser();
			}
			// Create a new query object.
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);

			// Select some fields
			$query->select('a.*');

			// From the sentinel_form table
			$query->from($db->quoteName('#__sentinel_form', 'a'));
			$query->where('a.id IN (' . implode(',',$pks) . ')');
			// Get global switch to activate text only export
			$export_text_only = JComponentHelper::getParams('com_sentinel')->get('export_text_only', 0);
			// Add these queries only if text only is required
			if ($export_text_only)
			{

				// From the membersmanager_member table.
				$query->select($db->quoteName('g.token','member'));
				$query->join('LEFT', $db->quoteName('#__membersmanager_member', 'g') . ' ON (' . $db->quoteName('a.member') . ' = ' . $db->quoteName('g.id') . ')');
			}
			// Implement View Level Access
			if (!$user->authorise('core.options', 'com_sentinel'))
			{
				$groups = implode(',', $user->getAuthorisedViewLevels());
				$query->where('a.access IN (' . $groups . ')');
			}

			// Order the results by ordering
			$query->order('a.ordering  ASC');

			// Load the items
			$db->setQuery($query);
			$db->execute();
			if ($db->getNumRows())
			{
				$items = $db->loadObjectList();

				// Set values to display correctly.
				if (SentinelHelper::checkArray($items))
				{
					// Get the user object if not set.
					if (!isset($user) || !SentinelHelper::checkObject($user))
					{
						$user = JFactory::getUser();
					}
					// Get global permissional control activation. (default is inactive)
					$strict_permission_per_field = JComponentHelper::getParams('com_sentinel')->get('strict_permission_per_field', 0);

					foreach ($items as $nr => &$item)
					{
						// Remove items the user can't access.
						$access = ($user->authorise('form.access', 'com_sentinel.form.' . (int) $item->id) && $user->authorise('form.access', 'com_sentinel'));
						if (!$access)
						{
							unset($items[$nr]);
							continue;
						}

						// use permissional control if globaly set.
						if ($strict_permission_per_field)
						{
							// set access permissional control for name value.
							if (isset($item->name) && (!$user->authorise('form.access.name', 'com_sentinel.form.' . (int) $item->id)
								|| !$user->authorise('form.access.name', 'com_sentinel')))
							{
								// We JUST empty the value (do you have a better idea)
								$item->name = '';
							}
							// set view permissional control for name value.
							if (isset($item->name) && (!$user->authorise('form.view.name', 'com_sentinel.form.' . (int) $item->id)
								|| !$user->authorise('form.view.name', 'com_sentinel')))
							{
								// We JUST empty the value (do you have a better idea)
								$item->name = '';
							}
							// set access permissional control for guid value.
							if (isset($item->guid) && (!$user->authorise('form.access.guid', 'com_sentinel.form.' . (int) $item->id)
								|| !$user->authorise('form.access.guid', 'com_sentinel')))
							{
								// We JUST empty the value (do you have a better idea)
								$item->guid = '';
							}
							// set view permissional control for guid value.
							if (isset($item->guid) && (!$user->authorise('form.view.guid', 'com_sentinel.form.' . (int) $item->id)
								|| !$user->authorise('form.view.guid', 'com_sentinel')))
							{
								// We JUST empty the value (do you have a better idea)
								$item->guid = '';
							}
							// set access permissional control for alias value.
							if (isset($item->alias) && (!$user->authorise('form.access.alias', 'com_sentinel.form.' . (int) $item->id)
								|| !$user->authorise('form.access.alias', 'com_sentinel')))
							{
								// We JUST empty the value (do you have a better idea)
								$item->alias = '';
							}
							// set view permissional control for alias value.
							if (isset($item->alias) && (!$user->authorise('form.view.alias', 'com_sentinel.form.' . (int) $item->id)
								|| !$user->authorise('form.view.alias', 'com_sentinel')))
							{
								// We JUST empty the value (do you have a better idea)
								$item->alias = '';
							}
							// set access permissional control for hostkey value.
							if (isset($item->hostkey) && (!$user->authorise('form.access.hostkey', 'com_sentinel.form.' . (int) $item->id)
								|| !$user->authorise('form.access.hostkey', 'com_sentinel')))
							{
								// We JUST empty the value (do you have a better idea)
								$item->hostkey = '';
							}
							// set view permissional control for hostkey value.
							if (isset($item->hostkey) && (!$user->authorise('form.view.hostkey', 'com_sentinel.form.' . (int) $item->id)
								|| !$user->authorise('form.view.hostkey', 'com_sentinel')))
							{
								// We JUST empty the value (do you have a better idea)
								$item->hostkey = '';
							}
							// set access permissional control for member value.
							if (isset($item->member) && (!$user->authorise('form.access.member', 'com_sentinel.form.' . (int) $item->id)
								|| !$user->authorise('form.access.member', 'com_sentinel')))
							{
								// We JUST empty the value (do you have a better idea)
								$item->member = '';
							}
							// set view permissional control for member value.
							if (isset($item->member) && (!$user->authorise('form.view.member', 'com_sentinel.form.' . (int) $item->id)
								|| !$user->authorise('form.view.member', 'com_sentinel')))
							{
								// We JUST empty the value (do you have a better idea)
								$item->member = '';
							}
							// set access permissional control for trustkey value.
							if (isset($item->trustkey) && (!$user->authorise('form.access.trustkey', 'com_sentinel.form.' . (int) $item->id)
								|| !$user->authorise('form.access.trustkey', 'com_sentinel')))
							{
								// We JUST empty the value (do you have a better idea)
								$item->trustkey = '';
							}
							// set view permissional control for trustkey value.
							if (isset($item->trustkey) && (!$user->authorise('form.view.trustkey', 'com_sentinel.form.' . (int) $item->id)
								|| !$user->authorise('form.view.trustkey', 'com_sentinel')))
							{
								// We JUST empty the value (do you have a better idea)
								$item->trustkey = '';
							}
						}
						
						if (!empty($item->hostkey))
						{
							// decrypt field
							$item->hostkey = SentinelHelper::decrypt($item->hostkey);
						}
						
						if (!empty($item->trustkey))
						{
							// decrypt field
							$item->trustkey = SentinelHelper::decrypt($item->trustkey);
						}
					}
				}
				return json_decode(json_encode($items), true);
			}
		}
		return false;
	}
	
	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * @return  string  A store id.
	 *
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.id');
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.ordering');
		$id .= ':' . $this->getState('filter.created_by');
		$id .= ':' . $this->getState('filter.modified_by');
		$id .= ':' . $this->getState('filter.name');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to checkin all items left checked out longer then a set time.
	 *
	 * @return  a bool
	 *
	 */
	protected function checkInNow()
	{
		// Get set check in time
		$time = JComponentHelper::getParams('com_sentinel')->get('check_in');

		if ($time)
		{

			// Get a db connection.
			$db = JFactory::getDbo();
			// reset query
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__sentinel_form'));
			$db->setQuery($query);
			$db->execute();
			if ($db->getNumRows())
			{
				// Get Yesterdays date
				$date = JFactory::getDate()->modify($time)->toSql();
				// reset query
				$query = $db->getQuery(true);

				// Fields to update.
				$fields = array(
					$db->quoteName('checked_out_time') . '=\'0000-00-00 00:00:00\'',
					$db->quoteName('checked_out') . '=0'
				);

				// Conditions for which records should be updated.
				$conditions = array(
					$db->quoteName('checked_out') . '!=0', 
					$db->quoteName('checked_out_time') . '<\''.$date.'\''
				);

				// Check table
				$query->update($db->quoteName('#__sentinel_form'))->set($fields)->where($conditions); 

				$db->setQuery($query);

				$db->execute();
			}
		}

		return false;
	}
}
