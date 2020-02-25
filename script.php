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
	@subpackage		script.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>
	@copyright		Copyright (C) 2020. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

	Sentinel Server.

/-----------------------------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHTML::_('behavior.modal');

/**
 * Script File of Sentinel Component
 */
class com_sentinelInstallerScript
{
	/**
	 * Constructor
	 *
	 * @param   JAdapterInstance  $parent  The object responsible for running this script
	 */
	public function __construct(JAdapterInstance $parent) {}

	/**
	 * Called on installation
	 *
	 * @param   JAdapterInstance  $parent  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function install(JAdapterInstance $parent) {}

	/**
	 * Called on uninstallation
	 *
	 * @param   JAdapterInstance  $parent  The object responsible for running this script
	 */
	public function uninstall(JAdapterInstance $parent)
	{
		// Get Application object
		$app = JFactory::getApplication();

		// Get The Database object
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);
		// Select id from content type table
		$query->select($db->quoteName('type_id'));
		$query->from($db->quoteName('#__content_types'));
		// Where Form alias is found
		$query->where( $db->quoteName('type_alias') . ' = '. $db->quote('com_sentinel.form') );
		$db->setQuery($query);
		// Execute query to see if alias is found
		$db->execute();
		$form_found = $db->getNumRows();
		// Now check if there were any rows
		if ($form_found)
		{
			// Since there are load the needed  form type ids
			$form_ids = $db->loadColumn();
			// Remove Form from the content type table
			$form_condition = array( $db->quoteName('type_alias') . ' = '. $db->quote('com_sentinel.form') );
			// Create a new query object.
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__content_types'));
			$query->where($form_condition);
			$db->setQuery($query);
			// Execute the query to remove Form items
			$form_done = $db->execute();
			if ($form_done)
			{
				// If succesfully remove Form add queued success message.
				$app->enqueueMessage(JText::_('The (com_sentinel.form) type alias was removed from the <b>#__content_type</b> table'));
			}

			// Remove Form items from the contentitem tag map table
			$form_condition = array( $db->quoteName('type_alias') . ' = '. $db->quote('com_sentinel.form') );
			// Create a new query object.
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__contentitem_tag_map'));
			$query->where($form_condition);
			$db->setQuery($query);
			// Execute the query to remove Form items
			$form_done = $db->execute();
			if ($form_done)
			{
				// If succesfully remove Form add queued success message.
				$app->enqueueMessage(JText::_('The (com_sentinel.form) type alias was removed from the <b>#__contentitem_tag_map</b> table'));
			}

			// Remove Form items from the ucm content table
			$form_condition = array( $db->quoteName('core_type_alias') . ' = ' . $db->quote('com_sentinel.form') );
			// Create a new query object.
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__ucm_content'));
			$query->where($form_condition);
			$db->setQuery($query);
			// Execute the query to remove Form items
			$form_done = $db->execute();
			if ($form_done)
			{
				// If succesfully remove Form add queued success message.
				$app->enqueueMessage(JText::_('The (com_sentinel.form) type alias was removed from the <b>#__ucm_content</b> table'));
			}

			// Make sure that all the Form items are cleared from DB
			foreach ($form_ids as $form_id)
			{
				// Remove Form items from the ucm base table
				$form_condition = array( $db->quoteName('ucm_type_id') . ' = ' . $form_id);
				// Create a new query object.
				$query = $db->getQuery(true);
				$query->delete($db->quoteName('#__ucm_base'));
				$query->where($form_condition);
				$db->setQuery($query);
				// Execute the query to remove Form items
				$db->execute();

				// Remove Form items from the ucm history table
				$form_condition = array( $db->quoteName('ucm_type_id') . ' = ' . $form_id);
				// Create a new query object.
				$query = $db->getQuery(true);
				$query->delete($db->quoteName('#__ucm_history'));
				$query->where($form_condition);
				$db->setQuery($query);
				// Execute the query to remove Form items
				$db->execute();
			}
		}

		// Create a new query object.
		$query = $db->getQuery(true);
		// Select id from content type table
		$query->select($db->quoteName('type_id'));
		$query->from($db->quoteName('#__content_types'));
		// Where Data_set alias is found
		$query->where( $db->quoteName('type_alias') . ' = '. $db->quote('com_sentinel.data_set') );
		$db->setQuery($query);
		// Execute query to see if alias is found
		$db->execute();
		$data_set_found = $db->getNumRows();
		// Now check if there were any rows
		if ($data_set_found)
		{
			// Since there are load the needed  data_set type ids
			$data_set_ids = $db->loadColumn();
			// Remove Data_set from the content type table
			$data_set_condition = array( $db->quoteName('type_alias') . ' = '. $db->quote('com_sentinel.data_set') );
			// Create a new query object.
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__content_types'));
			$query->where($data_set_condition);
			$db->setQuery($query);
			// Execute the query to remove Data_set items
			$data_set_done = $db->execute();
			if ($data_set_done)
			{
				// If succesfully remove Data_set add queued success message.
				$app->enqueueMessage(JText::_('The (com_sentinel.data_set) type alias was removed from the <b>#__content_type</b> table'));
			}

			// Remove Data_set items from the contentitem tag map table
			$data_set_condition = array( $db->quoteName('type_alias') . ' = '. $db->quote('com_sentinel.data_set') );
			// Create a new query object.
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__contentitem_tag_map'));
			$query->where($data_set_condition);
			$db->setQuery($query);
			// Execute the query to remove Data_set items
			$data_set_done = $db->execute();
			if ($data_set_done)
			{
				// If succesfully remove Data_set add queued success message.
				$app->enqueueMessage(JText::_('The (com_sentinel.data_set) type alias was removed from the <b>#__contentitem_tag_map</b> table'));
			}

			// Remove Data_set items from the ucm content table
			$data_set_condition = array( $db->quoteName('core_type_alias') . ' = ' . $db->quote('com_sentinel.data_set') );
			// Create a new query object.
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__ucm_content'));
			$query->where($data_set_condition);
			$db->setQuery($query);
			// Execute the query to remove Data_set items
			$data_set_done = $db->execute();
			if ($data_set_done)
			{
				// If succesfully remove Data_set add queued success message.
				$app->enqueueMessage(JText::_('The (com_sentinel.data_set) type alias was removed from the <b>#__ucm_content</b> table'));
			}

			// Make sure that all the Data_set items are cleared from DB
			foreach ($data_set_ids as $data_set_id)
			{
				// Remove Data_set items from the ucm base table
				$data_set_condition = array( $db->quoteName('ucm_type_id') . ' = ' . $data_set_id);
				// Create a new query object.
				$query = $db->getQuery(true);
				$query->delete($db->quoteName('#__ucm_base'));
				$query->where($data_set_condition);
				$db->setQuery($query);
				// Execute the query to remove Data_set items
				$db->execute();

				// Remove Data_set items from the ucm history table
				$data_set_condition = array( $db->quoteName('ucm_type_id') . ' = ' . $data_set_id);
				// Create a new query object.
				$query = $db->getQuery(true);
				$query->delete($db->quoteName('#__ucm_history'));
				$query->where($data_set_condition);
				$db->setQuery($query);
				// Execute the query to remove Data_set items
				$db->execute();
			}
		}

		// Create a new query object.
		$query = $db->getQuery(true);
		// Select id from content type table
		$query->select($db->quoteName('type_id'));
		$query->from($db->quoteName('#__content_types'));
		// Where Type alias is found
		$query->where( $db->quoteName('type_alias') . ' = '. $db->quote('com_sentinel.type') );
		$db->setQuery($query);
		// Execute query to see if alias is found
		$db->execute();
		$type_found = $db->getNumRows();
		// Now check if there were any rows
		if ($type_found)
		{
			// Since there are load the needed  type type ids
			$type_ids = $db->loadColumn();
			// Remove Type from the content type table
			$type_condition = array( $db->quoteName('type_alias') . ' = '. $db->quote('com_sentinel.type') );
			// Create a new query object.
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__content_types'));
			$query->where($type_condition);
			$db->setQuery($query);
			// Execute the query to remove Type items
			$type_done = $db->execute();
			if ($type_done)
			{
				// If succesfully remove Type add queued success message.
				$app->enqueueMessage(JText::_('The (com_sentinel.type) type alias was removed from the <b>#__content_type</b> table'));
			}

			// Remove Type items from the contentitem tag map table
			$type_condition = array( $db->quoteName('type_alias') . ' = '. $db->quote('com_sentinel.type') );
			// Create a new query object.
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__contentitem_tag_map'));
			$query->where($type_condition);
			$db->setQuery($query);
			// Execute the query to remove Type items
			$type_done = $db->execute();
			if ($type_done)
			{
				// If succesfully remove Type add queued success message.
				$app->enqueueMessage(JText::_('The (com_sentinel.type) type alias was removed from the <b>#__contentitem_tag_map</b> table'));
			}

			// Remove Type items from the ucm content table
			$type_condition = array( $db->quoteName('core_type_alias') . ' = ' . $db->quote('com_sentinel.type') );
			// Create a new query object.
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__ucm_content'));
			$query->where($type_condition);
			$db->setQuery($query);
			// Execute the query to remove Type items
			$type_done = $db->execute();
			if ($type_done)
			{
				// If succesfully remove Type add queued success message.
				$app->enqueueMessage(JText::_('The (com_sentinel.type) type alias was removed from the <b>#__ucm_content</b> table'));
			}

			// Make sure that all the Type items are cleared from DB
			foreach ($type_ids as $type_id)
			{
				// Remove Type items from the ucm base table
				$type_condition = array( $db->quoteName('ucm_type_id') . ' = ' . $type_id);
				// Create a new query object.
				$query = $db->getQuery(true);
				$query->delete($db->quoteName('#__ucm_base'));
				$query->where($type_condition);
				$db->setQuery($query);
				// Execute the query to remove Type items
				$db->execute();

				// Remove Type items from the ucm history table
				$type_condition = array( $db->quoteName('ucm_type_id') . ' = ' . $type_id);
				// Create a new query object.
				$query = $db->getQuery(true);
				$query->delete($db->quoteName('#__ucm_history'));
				$query->where($type_condition);
				$db->setQuery($query);
				// Execute the query to remove Type items
				$db->execute();
			}
		}

		// If All related items was removed queued success message.
		$app->enqueueMessage(JText::_('All related items was removed from the <b>#__ucm_base</b> table'));
		$app->enqueueMessage(JText::_('All related items was removed from the <b>#__ucm_history</b> table'));

		// Remove sentinel assets from the assets table
		$sentinel_condition = array( $db->quoteName('name') . ' LIKE ' . $db->quote('com_sentinel%') );

		// Create a new query object.
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__assets'));
		$query->where($sentinel_condition);
		$db->setQuery($query);
		$type_done = $db->execute();
		if ($type_done)
		{
			// If succesfully remove sentinel add queued success message.
			$app->enqueueMessage(JText::_('All related items was removed from the <b>#__assets</b> table'));
		}


		// Set db if not set already.
		if (!isset($db))
		{
			$db = JFactory::getDbo();
		}
		// Set app if not set already.
		if (!isset($app))
		{
			$app = JFactory::getApplication();
		}
		// Remove Sentinel from the action_logs_extensions table
		$sentinel_action_logs_extensions = array( $db->quoteName('extension') . ' = ' . $db->quote('com_sentinel') );
		// Create a new query object.
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__action_logs_extensions'));
		$query->where($sentinel_action_logs_extensions);
		$db->setQuery($query);
		// Execute the query to remove Sentinel
		$sentinel_removed_done = $db->execute();
		if ($sentinel_removed_done)
		{
			// If successfully remove Sentinel add queued success message.
			$app->enqueueMessage(JText::_('The com_sentinel extension was removed from the <b>#__action_logs_extensions</b> table'));
		}

		// Set db if not set already.
		if (!isset($db))
		{
			$db = JFactory::getDbo();
		}
		// Set app if not set already.
		if (!isset($app))
		{
			$app = JFactory::getApplication();
		}
		// Remove Sentinel Form from the action_log_config table
		$form_action_log_config = array( $db->quoteName('type_alias') . ' = '. $db->quote('com_sentinel.form') );
		// Create a new query object.
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__action_log_config'));
		$query->where($form_action_log_config);
		$db->setQuery($query);
		// Execute the query to remove com_sentinel.form
		$form_action_log_config_done = $db->execute();
		if ($form_action_log_config_done)
		{
			// If successfully removed Sentinel Form add queued success message.
			$app->enqueueMessage(JText::_('The com_sentinel.form type alias was removed from the <b>#__action_log_config</b> table'));
		}

		// Set db if not set already.
		if (!isset($db))
		{
			$db = JFactory::getDbo();
		}
		// Set app if not set already.
		if (!isset($app))
		{
			$app = JFactory::getApplication();
		}
		// Remove Sentinel Data_set from the action_log_config table
		$data_set_action_log_config = array( $db->quoteName('type_alias') . ' = '. $db->quote('com_sentinel.data_set') );
		// Create a new query object.
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__action_log_config'));
		$query->where($data_set_action_log_config);
		$db->setQuery($query);
		// Execute the query to remove com_sentinel.data_set
		$data_set_action_log_config_done = $db->execute();
		if ($data_set_action_log_config_done)
		{
			// If successfully removed Sentinel Data_set add queued success message.
			$app->enqueueMessage(JText::_('The com_sentinel.data_set type alias was removed from the <b>#__action_log_config</b> table'));
		}

		// Set db if not set already.
		if (!isset($db))
		{
			$db = JFactory::getDbo();
		}
		// Set app if not set already.
		if (!isset($app))
		{
			$app = JFactory::getApplication();
		}
		// Remove Sentinel Type from the action_log_config table
		$type_action_log_config = array( $db->quoteName('type_alias') . ' = '. $db->quote('com_sentinel.type') );
		// Create a new query object.
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__action_log_config'));
		$query->where($type_action_log_config);
		$db->setQuery($query);
		// Execute the query to remove com_sentinel.type
		$type_action_log_config_done = $db->execute();
		if ($type_action_log_config_done)
		{
			// If successfully removed Sentinel Type add queued success message.
			$app->enqueueMessage(JText::_('The com_sentinel.type type alias was removed from the <b>#__action_log_config</b> table'));
		}
		// little notice as after service, in case of bad experience with component.
		echo '<h2>Did something go wrong? Are you disappointed?</h2>
		<p>Please let me know at <a href="mailto:info@vdm.io">info@vdm.io</a>.
		<br />We at Vast Development Method are committed to building extensions that performs proficiently! You can help us, really!
		<br />Send me your thoughts on improvements that is needed, trust me, I will be very grateful!
		<br />Visit us at <a href="https://www.vdm.io/" target="_blank">https://www.vdm.io/</a> today!</p>';
	}

	/**
	 * Called on update
	 *
	 * @param   JAdapterInstance  $parent  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function update(JAdapterInstance $parent){}

	/**
	 * Called before any type of action
	 *
	 * @param   string  $type  Which action is happening (install|uninstall|discover_install|update)
	 * @param   JAdapterInstance  $parent  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function preflight($type, JAdapterInstance $parent)
	{
		// get application
		$app = JFactory::getApplication();
		// is redundant or so it seems ...hmmm let me know if it works again
		if ($type === 'uninstall')
		{
			return true;
		}
		// the default for both install and update
		$jversion = new JVersion();
		if (!$jversion->isCompatible('3.8.0'))
		{
			$app->enqueueMessage('Please upgrade to at least Joomla! 3.8.0 before continuing!', 'error');
			return false;
		}
		// do any updates needed
		if ($type === 'update')
		{
		}
		// do any install needed
		if ($type === 'install')
		{

		// check that membersmanager is installed
		$pathToCore = JPATH_ADMINISTRATOR . '/components/com_membersmanager/helpers/membersmanager.php';
		if (!JFile::exists($pathToCore))
		{
			$app->enqueueMessage('membersmanager must first be installed from <a href="https://www.vdm.io/membersmanager/" target="_blank">Vast Development Method</a>.', 'error');
			return false;
		}
		}
		return true;
	}

	/**
	 * Called after any type of action
	 *
	 * @param   string  $type  Which action is happening (install|uninstall|discover_install|update)
	 * @param   JAdapterInstance  $parent  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function postflight($type, JAdapterInstance $parent)
	{
		// get application
		$app = JFactory::getApplication();
		// We check if we have dynamic folders to copy
		$this->setDynamicF0ld3rs($app, $parent);
		// set the default component settings
		if ($type === 'install')
		{

			// Get The Database object
			$db = JFactory::getDbo();

			// Create the form content type object.
			$form = new stdClass();
			$form->type_title = 'Sentinel Form';
			$form->type_alias = 'com_sentinel.form';
			$form->table = '{"special": {"dbtable": "#__sentinel_form","key": "id","type": "Form","prefix": "sentinelTable","config": "array()"},"common": {"dbtable": "#__ucm_content","key": "ucm_id","type": "Corecontent","prefix": "JTable","config": "array()"}}';
			$form->field_mappings = '{"common": {"core_content_item_id": "id","core_title": "name","core_state": "published","core_alias": "alias","core_created_time": "created","core_modified_time": "modified","core_body": "null","core_hits": "hits","core_publish_up": "null","core_publish_down": "null","core_access": "access","core_params": "params","core_featured": "null","core_metadata": "null","core_language": "null","core_images": "null","core_urls": "null","core_version": "version","core_ordering": "ordering","core_metakey": "null","core_metadesc": "null","core_catid": "null","core_xreference": "null","asset_id": "asset_id"},"special": {"name":"name","guid":"guid","alias":"alias","hostkey":"hostkey","member":"member","trustkey":"trustkey"}}';
			$form->router = 'SentinelHelperRoute::getFormRoute';
			$form->content_history_options = '{"formFile": "administrator/components/com_sentinel/models/forms/form.xml","hideFields": ["asset_id","checked_out","checked_out_time","version"],"ignoreChanges": ["modified_by","modified","checked_out","checked_out_time","version","hits"],"convertToInt": ["published","ordering","member"],"displayLookup": [{"sourceColumn": "created_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"},{"sourceColumn": "access","targetTable": "#__viewlevels","targetColumn": "id","displayColumn": "title"},{"sourceColumn": "modified_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"},{"sourceColumn": "member","targetTable": "#__membersmanager_member","targetColumn": "id","displayColumn": "token"}]}';

			// Set the object into the content types table.
			$form_Inserted = $db->insertObject('#__content_types', $form);

			// Create the data_set content type object.
			$data_set = new stdClass();
			$data_set->type_title = 'Sentinel Data_set';
			$data_set->type_alias = 'com_sentinel.data_set';
			$data_set->table = '{"special": {"dbtable": "#__sentinel_data_set","key": "id","type": "Data_set","prefix": "sentinelTable","config": "array()"},"common": {"dbtable": "#__ucm_content","key": "ucm_id","type": "Corecontent","prefix": "JTable","config": "array()"}}';
			$data_set->field_mappings = '{"common": {"core_content_item_id": "id","core_title": "station","core_state": "published","core_alias": "null","core_created_time": "created","core_modified_time": "modified","core_body": "null","core_hits": "hits","core_publish_up": "null","core_publish_down": "null","core_access": "access","core_params": "params","core_featured": "null","core_metadata": "null","core_language": "null","core_images": "null","core_urls": "null","core_version": "version","core_ordering": "ordering","core_metakey": "null","core_metadesc": "null","core_catid": "null","core_xreference": "null","asset_id": "asset_id"},"special": {"station":"station","guid":"guid"}}';
			$data_set->router = 'SentinelHelperRoute::getData_setRoute';
			$data_set->content_history_options = '{"formFile": "administrator/components/com_sentinel/models/forms/data_set.xml","hideFields": ["asset_id","checked_out","checked_out_time","version"],"ignoreChanges": ["modified_by","modified","checked_out","checked_out_time","version","hits"],"convertToInt": ["published","ordering"],"displayLookup": [{"sourceColumn": "created_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"},{"sourceColumn": "access","targetTable": "#__viewlevels","targetColumn": "id","displayColumn": "title"},{"sourceColumn": "modified_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"},{"sourceColumn": "station","targetTable": "#__sentinel_form","targetColumn": "guid","displayColumn": "name"}]}';

			// Set the object into the content types table.
			$data_set_Inserted = $db->insertObject('#__content_types', $data_set);

			// Create the type content type object.
			$type = new stdClass();
			$type->type_title = 'Sentinel Type';
			$type->type_alias = 'com_sentinel.type';
			$type->table = '{"special": {"dbtable": "#__sentinel_type","key": "id","type": "Type","prefix": "sentinelTable","config": "array()"},"common": {"dbtable": "#__ucm_content","key": "ucm_id","type": "Corecontent","prefix": "JTable","config": "array()"}}';
			$type->field_mappings = '{"common": {"core_content_item_id": "id","core_title": "name","core_state": "published","core_alias": "alias","core_created_time": "created","core_modified_time": "modified","core_body": "null","core_hits": "hits","core_publish_up": "null","core_publish_down": "null","core_access": "access","core_params": "params","core_featured": "null","core_metadata": "null","core_language": "null","core_images": "null","core_urls": "null","core_version": "version","core_ordering": "ordering","core_metakey": "null","core_metadesc": "null","core_catid": "null","core_xreference": "null","asset_id": "asset_id"},"special": {"name":"name","description":"description","guid":"guid","alias":"alias"}}';
			$type->router = 'SentinelHelperRoute::getTypeRoute';
			$type->content_history_options = '{"formFile": "administrator/components/com_sentinel/models/forms/type.xml","hideFields": ["asset_id","checked_out","checked_out_time","version"],"ignoreChanges": ["modified_by","modified","checked_out","checked_out_time","version","hits"],"convertToInt": ["published","ordering"],"displayLookup": [{"sourceColumn": "created_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"},{"sourceColumn": "access","targetTable": "#__viewlevels","targetColumn": "id","displayColumn": "title"},{"sourceColumn": "modified_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"}]}';

			// Set the object into the content types table.
			$type_Inserted = $db->insertObject('#__content_types', $type);


			// Install the global extenstion params.
			$query = $db->getQuery(true);
			// Field to update.
			$fields = array(
				$db->quoteName('params') . ' = ' . $db->quote('{"autorName":"Llewellyn van der Merwe","autorEmail":"info@vdm.io","default_accesslevel":"1","add_isis_template":"1","activate_membersmanager_info":"0","membersmanager_relation_type":"1","placeholder_prefix":"sentinel","check_in":"-1 day","save_history":"1","history_limit":"10","uikit_version":"2","uikit_load":"1","uikit_min":"","uikit_style":""}'),
			);
			// Condition.
			$conditions = array(
				$db->quoteName('element') . ' = ' . $db->quote('com_sentinel')
			);
			$query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$allDone = $db->execute();

			echo '<a target="_blank" href="https://www.vdm.io/" title="Sentinel">
				<img src="components/com_sentinel/assets/images/vdm-component.jpg"/>
				</a>';

			// Set db if not set already.
			if (!isset($db))
			{
				$db = JFactory::getDbo();
			}
			// Create the sentinel action logs extensions object.
			$sentinel_action_logs_extensions = new stdClass();
			$sentinel_action_logs_extensions->extension = 'com_sentinel';

			// Set the object into the action logs extensions table.
			$sentinel_action_logs_extensions_Inserted = $db->insertObject('#__action_logs_extensions', $sentinel_action_logs_extensions);

			// Set db if not set already.
			if (!isset($db))
			{
				$db = JFactory::getDbo();
			}
			// Create the form action log config object.
			$form_action_log_config = new stdClass();
			$form_action_log_config->type_title = 'FORM';
			$form_action_log_config->type_alias = 'com_sentinel.form';
			$form_action_log_config->id_holder = 'id';
			$form_action_log_config->title_holder = 'name';
			$form_action_log_config->table_name = '#__sentinel_form';
			$form_action_log_config->text_prefix = 'COM_SENTINEL';

			// Set the object into the action log config table.
			$form_Inserted = $db->insertObject('#__action_log_config', $form_action_log_config);

			// Set db if not set already.
			if (!isset($db))
			{
				$db = JFactory::getDbo();
			}
			// Create the data_set action log config object.
			$data_set_action_log_config = new stdClass();
			$data_set_action_log_config->type_title = 'DATA_SET';
			$data_set_action_log_config->type_alias = 'com_sentinel.data_set';
			$data_set_action_log_config->id_holder = 'id';
			$data_set_action_log_config->title_holder = 'station';
			$data_set_action_log_config->table_name = '#__sentinel_data_set';
			$data_set_action_log_config->text_prefix = 'COM_SENTINEL';

			// Set the object into the action log config table.
			$data_set_Inserted = $db->insertObject('#__action_log_config', $data_set_action_log_config);

			// Set db if not set already.
			if (!isset($db))
			{
				$db = JFactory::getDbo();
			}
			// Create the type action log config object.
			$type_action_log_config = new stdClass();
			$type_action_log_config->type_title = 'TYPE';
			$type_action_log_config->type_alias = 'com_sentinel.type';
			$type_action_log_config->id_holder = 'id';
			$type_action_log_config->title_holder = 'name';
			$type_action_log_config->table_name = '#__sentinel_type';
			$type_action_log_config->text_prefix = 'COM_SENTINEL';

			// Set the object into the action log config table.
			$type_Inserted = $db->insertObject('#__action_log_config', $type_action_log_config);
		}
		// do any updates needed
		if ($type === 'update')
		{

			// Get The Database object
			$db = JFactory::getDbo();

			// Create the form content type object.
			$form = new stdClass();
			$form->type_title = 'Sentinel Form';
			$form->type_alias = 'com_sentinel.form';
			$form->table = '{"special": {"dbtable": "#__sentinel_form","key": "id","type": "Form","prefix": "sentinelTable","config": "array()"},"common": {"dbtable": "#__ucm_content","key": "ucm_id","type": "Corecontent","prefix": "JTable","config": "array()"}}';
			$form->field_mappings = '{"common": {"core_content_item_id": "id","core_title": "name","core_state": "published","core_alias": "alias","core_created_time": "created","core_modified_time": "modified","core_body": "null","core_hits": "hits","core_publish_up": "null","core_publish_down": "null","core_access": "access","core_params": "params","core_featured": "null","core_metadata": "null","core_language": "null","core_images": "null","core_urls": "null","core_version": "version","core_ordering": "ordering","core_metakey": "null","core_metadesc": "null","core_catid": "null","core_xreference": "null","asset_id": "asset_id"},"special": {"name":"name","guid":"guid","alias":"alias","hostkey":"hostkey","member":"member","trustkey":"trustkey"}}';
			$form->router = 'SentinelHelperRoute::getFormRoute';
			$form->content_history_options = '{"formFile": "administrator/components/com_sentinel/models/forms/form.xml","hideFields": ["asset_id","checked_out","checked_out_time","version"],"ignoreChanges": ["modified_by","modified","checked_out","checked_out_time","version","hits"],"convertToInt": ["published","ordering","member"],"displayLookup": [{"sourceColumn": "created_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"},{"sourceColumn": "access","targetTable": "#__viewlevels","targetColumn": "id","displayColumn": "title"},{"sourceColumn": "modified_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"},{"sourceColumn": "member","targetTable": "#__membersmanager_member","targetColumn": "id","displayColumn": "token"}]}';

			// Check if form type is already in content_type DB.
			$form_id = null;
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('type_id')));
			$query->from($db->quoteName('#__content_types'));
			$query->where($db->quoteName('type_alias') . ' LIKE '. $db->quote($form->type_alias));
			$db->setQuery($query);
			$db->execute();

			// Set the object into the content types table.
			if ($db->getNumRows())
			{
				$form->type_id = $db->loadResult();
				$form_Updated = $db->updateObject('#__content_types', $form, 'type_id');
			}
			else
			{
				$form_Inserted = $db->insertObject('#__content_types', $form);
			}

			// Create the data_set content type object.
			$data_set = new stdClass();
			$data_set->type_title = 'Sentinel Data_set';
			$data_set->type_alias = 'com_sentinel.data_set';
			$data_set->table = '{"special": {"dbtable": "#__sentinel_data_set","key": "id","type": "Data_set","prefix": "sentinelTable","config": "array()"},"common": {"dbtable": "#__ucm_content","key": "ucm_id","type": "Corecontent","prefix": "JTable","config": "array()"}}';
			$data_set->field_mappings = '{"common": {"core_content_item_id": "id","core_title": "station","core_state": "published","core_alias": "null","core_created_time": "created","core_modified_time": "modified","core_body": "null","core_hits": "hits","core_publish_up": "null","core_publish_down": "null","core_access": "access","core_params": "params","core_featured": "null","core_metadata": "null","core_language": "null","core_images": "null","core_urls": "null","core_version": "version","core_ordering": "ordering","core_metakey": "null","core_metadesc": "null","core_catid": "null","core_xreference": "null","asset_id": "asset_id"},"special": {"station":"station","guid":"guid"}}';
			$data_set->router = 'SentinelHelperRoute::getData_setRoute';
			$data_set->content_history_options = '{"formFile": "administrator/components/com_sentinel/models/forms/data_set.xml","hideFields": ["asset_id","checked_out","checked_out_time","version"],"ignoreChanges": ["modified_by","modified","checked_out","checked_out_time","version","hits"],"convertToInt": ["published","ordering"],"displayLookup": [{"sourceColumn": "created_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"},{"sourceColumn": "access","targetTable": "#__viewlevels","targetColumn": "id","displayColumn": "title"},{"sourceColumn": "modified_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"},{"sourceColumn": "station","targetTable": "#__sentinel_form","targetColumn": "guid","displayColumn": "name"}]}';

			// Check if data_set type is already in content_type DB.
			$data_set_id = null;
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('type_id')));
			$query->from($db->quoteName('#__content_types'));
			$query->where($db->quoteName('type_alias') . ' LIKE '. $db->quote($data_set->type_alias));
			$db->setQuery($query);
			$db->execute();

			// Set the object into the content types table.
			if ($db->getNumRows())
			{
				$data_set->type_id = $db->loadResult();
				$data_set_Updated = $db->updateObject('#__content_types', $data_set, 'type_id');
			}
			else
			{
				$data_set_Inserted = $db->insertObject('#__content_types', $data_set);
			}

			// Create the type content type object.
			$type = new stdClass();
			$type->type_title = 'Sentinel Type';
			$type->type_alias = 'com_sentinel.type';
			$type->table = '{"special": {"dbtable": "#__sentinel_type","key": "id","type": "Type","prefix": "sentinelTable","config": "array()"},"common": {"dbtable": "#__ucm_content","key": "ucm_id","type": "Corecontent","prefix": "JTable","config": "array()"}}';
			$type->field_mappings = '{"common": {"core_content_item_id": "id","core_title": "name","core_state": "published","core_alias": "alias","core_created_time": "created","core_modified_time": "modified","core_body": "null","core_hits": "hits","core_publish_up": "null","core_publish_down": "null","core_access": "access","core_params": "params","core_featured": "null","core_metadata": "null","core_language": "null","core_images": "null","core_urls": "null","core_version": "version","core_ordering": "ordering","core_metakey": "null","core_metadesc": "null","core_catid": "null","core_xreference": "null","asset_id": "asset_id"},"special": {"name":"name","description":"description","guid":"guid","alias":"alias"}}';
			$type->router = 'SentinelHelperRoute::getTypeRoute';
			$type->content_history_options = '{"formFile": "administrator/components/com_sentinel/models/forms/type.xml","hideFields": ["asset_id","checked_out","checked_out_time","version"],"ignoreChanges": ["modified_by","modified","checked_out","checked_out_time","version","hits"],"convertToInt": ["published","ordering"],"displayLookup": [{"sourceColumn": "created_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"},{"sourceColumn": "access","targetTable": "#__viewlevels","targetColumn": "id","displayColumn": "title"},{"sourceColumn": "modified_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"}]}';

			// Check if type type is already in content_type DB.
			$type_id = null;
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('type_id')));
			$query->from($db->quoteName('#__content_types'));
			$query->where($db->quoteName('type_alias') . ' LIKE '. $db->quote($type->type_alias));
			$db->setQuery($query);
			$db->execute();

			// Set the object into the content types table.
			if ($db->getNumRows())
			{
				$type->type_id = $db->loadResult();
				$type_Updated = $db->updateObject('#__content_types', $type, 'type_id');
			}
			else
			{
				$type_Inserted = $db->insertObject('#__content_types', $type);
			}


			echo '<a target="_blank" href="https://www.vdm.io/" title="Sentinel">
				<img src="components/com_sentinel/assets/images/vdm-component.jpg"/>
				</a>
				<h3>Upgrade to Version 2.0.6 Was Successful! Let us know if anything is not working as expected.</h3>';

			// Set db if not set already.
			if (!isset($db))
			{
				$db = JFactory::getDbo();
			}
			// Create the sentinel action logs extensions object.
			$sentinel_action_logs_extensions = new stdClass();
			$sentinel_action_logs_extensions->extension = 'com_sentinel';

			// Check if sentinel action log extension is already in action logs extensions DB.
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('id')));
			$query->from($db->quoteName('#__action_logs_extensions'));
			$query->where($db->quoteName('extension') . ' LIKE '. $db->quote($sentinel_action_logs_extensions->extension));
			$db->setQuery($query);
			$db->execute();

			// Set the object into the action logs extensions table if not found.
			if (!$db->getNumRows())
			{
				$sentinel_action_logs_extensions_Inserted = $db->insertObject('#__action_logs_extensions', $sentinel_action_logs_extensions);
			}

			// Set db if not set already.
			if (!isset($db))
			{
				$db = JFactory::getDbo();
			}
			// Create the form action log config object.
			$form_action_log_config = new stdClass();
			$form_action_log_config->id = null;
			$form_action_log_config->type_title = 'FORM';
			$form_action_log_config->type_alias = 'com_sentinel.form';
			$form_action_log_config->id_holder = 'id';
			$form_action_log_config->title_holder = 'name';
			$form_action_log_config->table_name = '#__sentinel_form';
			$form_action_log_config->text_prefix = 'COM_SENTINEL';

			// Check if form action log config is already in action_log_config DB.
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('id')));
			$query->from($db->quoteName('#__action_log_config'));
			$query->where($db->quoteName('type_alias') . ' LIKE '. $db->quote($form_action_log_config->type_alias));
			$db->setQuery($query);
			$db->execute();

			// Set the object into the content types table.
			if ($db->getNumRows())
			{
				$form_action_log_config->id = $db->loadResult();
				$form_action_log_config_Updated = $db->updateObject('#__action_log_config', $form_action_log_config, 'id');
			}
			else
			{
				$form_action_log_config_Inserted = $db->insertObject('#__action_log_config', $form_action_log_config);
			}

			// Set db if not set already.
			if (!isset($db))
			{
				$db = JFactory::getDbo();
			}
			// Create the data_set action log config object.
			$data_set_action_log_config = new stdClass();
			$data_set_action_log_config->id = null;
			$data_set_action_log_config->type_title = 'DATA_SET';
			$data_set_action_log_config->type_alias = 'com_sentinel.data_set';
			$data_set_action_log_config->id_holder = 'id';
			$data_set_action_log_config->title_holder = 'station';
			$data_set_action_log_config->table_name = '#__sentinel_data_set';
			$data_set_action_log_config->text_prefix = 'COM_SENTINEL';

			// Check if data_set action log config is already in action_log_config DB.
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('id')));
			$query->from($db->quoteName('#__action_log_config'));
			$query->where($db->quoteName('type_alias') . ' LIKE '. $db->quote($data_set_action_log_config->type_alias));
			$db->setQuery($query);
			$db->execute();

			// Set the object into the content types table.
			if ($db->getNumRows())
			{
				$data_set_action_log_config->id = $db->loadResult();
				$data_set_action_log_config_Updated = $db->updateObject('#__action_log_config', $data_set_action_log_config, 'id');
			}
			else
			{
				$data_set_action_log_config_Inserted = $db->insertObject('#__action_log_config', $data_set_action_log_config);
			}

			// Set db if not set already.
			if (!isset($db))
			{
				$db = JFactory::getDbo();
			}
			// Create the type action log config object.
			$type_action_log_config = new stdClass();
			$type_action_log_config->id = null;
			$type_action_log_config->type_title = 'TYPE';
			$type_action_log_config->type_alias = 'com_sentinel.type';
			$type_action_log_config->id_holder = 'id';
			$type_action_log_config->title_holder = 'name';
			$type_action_log_config->table_name = '#__sentinel_type';
			$type_action_log_config->text_prefix = 'COM_SENTINEL';

			// Check if type action log config is already in action_log_config DB.
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('id')));
			$query->from($db->quoteName('#__action_log_config'));
			$query->where($db->quoteName('type_alias') . ' LIKE '. $db->quote($type_action_log_config->type_alias));
			$db->setQuery($query);
			$db->execute();

			// Set the object into the content types table.
			if ($db->getNumRows())
			{
				$type_action_log_config->id = $db->loadResult();
				$type_action_log_config_Updated = $db->updateObject('#__action_log_config', $type_action_log_config, 'id');
			}
			else
			{
				$type_action_log_config_Inserted = $db->insertObject('#__action_log_config', $type_action_log_config);
			}
		}
		return true;
	}

	/**
	 * Method to set/copy dynamic folders into place (use with caution)
	 *
	 * @return void
	 */
	protected function setDynamicF0ld3rs($app, $parent)
	{
		// get the instalation path
		$installer = $parent->getParent();
		$installPath = $installer->getPath('source');
		// get all the folders
		$folders = JFolder::folders($installPath);
		// check if we have folders we may want to copy
		$doNotCopy = array('media','admin','site'); // Joomla already deals with these
		if (count((array) $folders) > 1)
		{
			foreach ($folders as $folder)
			{
				// Only copy if not a standard folders
				if (!in_array($folder, $doNotCopy))
				{
					// set the source path
					$src = $installPath.'/'.$folder;
					// set the destination path
					$dest = JPATH_ROOT.'/'.$folder;
					// now try to copy the folder
					if (!JFolder::copy($src, $dest, '', true))
					{
						$app->enqueueMessage('Could not copy '.$folder.' folder into place, please make sure destination is writable!', 'error');
					}
				}
			}
		}
	}
}
