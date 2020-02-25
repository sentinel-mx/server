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
	@subpackage		member.php
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
 * Member Form Field class for the Sentinel component
 */
class JFormFieldMember extends JFormFieldList
{
	/**
	 * The member field type.
	 *
	 * @var		string
	 */
	public $type = 'member';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array    An array of JHtml options.
	 */
	protected function getOptions()
	{
		
		// get the user
		$my = JFactory::getUser();
		// get database object
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('a.id','a.token', 'a.user', 'a.name'),array('id','member_token', 'user', 'name')));
		$query->from($db->quoteName('#__membersmanager_member', 'a'));
		$query->where($db->quoteName('a.published') . ' >= 1');
		// check if current user is an admin
		if (!$my->authorise('core.options', 'com_membersmanager'))
		{
			// check if the class and method exist & get user access groups
			if (($helperClass = SentinelHelper::getHelperClass('com_membersmanager')) === false || !method_exists($helperClass, 'getAccess')
				|| ($user_access_types =  $helperClass::getAccess($my)) === false || !SentinelHelper::checkArray($user_access_types))
			{
				return false;
			}
			// filter by type
			$query->join('LEFT', $db->quoteName('#__membersmanager_type_map', 't') . ' ON (' . $db->quoteName('a.id') . ' = ' . $db->quoteName('t.member') . ')');
			$user_access_types = implode(',', $user_access_types);
			$query->where('t.type IN (' . $user_access_types . ')');
			// also filter by access (will keep an eye on this)
			$groups = implode(',', $my->getAuthorisedViewLevels());
			$query->where('a.access IN (' . $groups . ')');
		}
		$query->order('a.token ASC');
		$db->setQuery((string)$query);
		$items = $db->loadObjectList();
		// get the input from url
		$jinput = JFactory::getApplication()->input;
		// get the member field
		$member_field = $jinput->get('ref', null, 'WORD');
		// get the member field
		$member_field = $jinput->get('field', $member_field, 'WORD');
		// make sure this is the correct field
		if ('member' === $member_field)
		{
			// get the member id
			$member = $jinput->getInt('refid', 0);
			// get the member id
			$member = $jinput->getInt('field_id', $member);
		}
		$options = array();
		if ($items)
		{
			// only add if more then one value found
			if (count( (array) $items) > 1)
			{
				$options[] = JHtml::_('select.option', '', 'Select a member');
			}
			foreach($items as $item)
			{
				// check if we current member
				if (isset($member) && $member == $item->id)
				{
					// remove ID
					$member = 0;
				}
				// set the option
				$options[] = JHtml::_('select.option', $item->id, SentinelHelper::getMemberName($item->id, $item->user, $item->name) . ' ( ' . $item->member_token . ' )');
			}
		}
		// add the current user (TODO this is not suppose to happen)
		if (isset($member) && $member > 0)
		{
			// load the current member manual
			$options[] = JHtml::_('select.option', (int) $member, SentinelHelper::getMemberName($member));
		}
		return $options;
	}
}
