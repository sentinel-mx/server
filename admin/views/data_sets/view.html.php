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
	@subpackage		view.html.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>
	@copyright		Copyright (C) 2020. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

	Sentinel Server.

/-----------------------------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Sentinel View class for the Data_sets
 */
class SentinelViewData_sets extends JViewLegacy
{
	/**
	 * Data_sets view display method
	 * @return void
	 */
	function display($tpl = null)
	{
		if ($this->getLayout() !== 'modal')
		{
			// Include helper submenu
			SentinelHelper::addSubmenu('data_sets');
		}

		// Assign data to the view
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->user = JFactory::getUser();
		$this->listOrder = $this->escape($this->state->get('list.ordering'));
		$this->listDirn = $this->escape($this->state->get('list.direction'));
		$this->saveOrder = $this->listOrder == 'ordering';
		// set the return here value
		$this->return_here = urlencode(base64_encode((string) JUri::getInstance()));
		// get global action permissions
		$this->canDo = SentinelHelper::getActions('data_set');
		$this->canEdit = $this->canDo->get('data_set.edit');
		$this->canState = $this->canDo->get('data_set.edit.state');
		$this->canCreate = $this->canDo->get('data_set.create');
		$this->canDelete = $this->canDo->get('data_set.delete');
		$this->canBatch = $this->canDo->get('core.batch');

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal')
		{
			$this->addToolbar();
			$this->sidebar = JHtmlSidebar::render();
			// load the batch html
			if ($this->canCreate && $this->canEdit && $this->canState)
			{
				$this->batchDisplay = JHtmlBatch_::render();
			}
		}
		
		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		// Display the template
		parent::display($tpl);

		// Set the document
		$this->setDocument();
	}

	/**
	 * Setting the toolbar
	 */
	protected function addToolBar()
	{
		JToolBarHelper::title(JText::_('COM_SENTINEL_DATA_SETS'), 'cube');
		JHtmlSidebar::setAction('index.php?option=com_sentinel&view=data_sets');
		JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

		if ($this->canCreate)
		{
			JToolBarHelper::addNew('data_set.add');
		}

		// Only load if there are items
		if (SentinelHelper::checkArray($this->items))
		{
			if ($this->canEdit)
			{
				JToolBarHelper::editList('data_set.edit');
			}

			if ($this->canState)
			{
				JToolBarHelper::publishList('data_sets.publish');
				JToolBarHelper::unpublishList('data_sets.unpublish');
				JToolBarHelper::archiveList('data_sets.archive');

				if ($this->canDo->get('core.admin'))
				{
					JToolBarHelper::checkin('data_sets.checkin');
				}
			}

			// Add a batch button
			if ($this->canBatch && $this->canCreate && $this->canEdit && $this->canState)
			{
				// Get the toolbar object instance
				$bar = JToolBar::getInstance('toolbar');
				// set the batch button name
				$title = JText::_('JTOOLBAR_BATCH');
				// Instantiate a new JLayoutFile instance and render the batch button
				$layout = new JLayoutFile('joomla.toolbar.batch');
				// add the button to the page
				$dhtml = $layout->render(array('title' => $title));
				$bar->appendButton('Custom', $dhtml, 'batch');
			}

			if ($this->state->get('filter.published') == -2 && ($this->canState && $this->canDelete))
			{
				JToolbarHelper::deleteList('', 'data_sets.delete', 'JTOOLBAR_EMPTY_TRASH');
			}
			elseif ($this->canState && $this->canDelete)
			{
				JToolbarHelper::trash('data_sets.trash');
			}

			if ($this->canDo->get('core.export') && $this->canDo->get('data_set.export'))
			{
				JToolBarHelper::custom('data_sets.exportData', 'download', '', 'COM_SENTINEL_EXPORT_DATA', true);
			}
		}

		if ($this->canDo->get('core.import') && $this->canDo->get('data_set.import'))
		{
			JToolBarHelper::custom('data_sets.importData', 'upload', '', 'COM_SENTINEL_IMPORT_DATA', false);
		}

		// set help url for this view if found
		$help_url = SentinelHelper::getHelpUrl('data_sets');
		if (SentinelHelper::checkString($help_url))
		{
				JToolbarHelper::help('COM_SENTINEL_HELP_MANAGER', false, $help_url);
		}

		// add the options comp button
		if ($this->canDo->get('core.admin') || $this->canDo->get('core.options'))
		{
			JToolBarHelper::preferences('com_sentinel');
		}

		if ($this->canState)
		{
			JHtmlSidebar::addFilter(
				JText::_('JOPTION_SELECT_PUBLISHED'),
				'filter_published',
				JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
			);
			// only load if batch allowed
			if ($this->canBatch)
			{
				JHtmlBatch_::addListSelection(
					JText::_('COM_SENTINEL_KEEP_ORIGINAL_STATE'),
					'batch[published]',
					JHtml::_('select.options', JHtml::_('jgrid.publishedOptions', array('all' => false)), 'value', 'text', '', true)
				);
			}
		}

		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_ACCESS'),
			'filter_access',
			JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access'))
		);

		if ($this->canBatch && $this->canCreate && $this->canEdit)
		{
			JHtmlBatch_::addListSelection(
				JText::_('COM_SENTINEL_KEEP_ORIGINAL_ACCESS'),
				'batch[access]',
				JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text')
			);
		}

		// Set Station Name Selection
		$this->stationNameOptions = JFormHelper::loadFieldType('Stations')->options;
		// We do some sanitation for Station Name filter
		if (SentinelHelper::checkArray($this->stationNameOptions) &&
			isset($this->stationNameOptions[0]->value) &&
			!SentinelHelper::checkString($this->stationNameOptions[0]->value))
		{
			unset($this->stationNameOptions[0]);
		}
		// Only load Station Name filter if it has values
		if (SentinelHelper::checkArray($this->stationNameOptions))
		{
			// Station Name Filter
			JHtmlSidebar::addFilter(
				'- Select '.JText::_('COM_SENTINEL_DATA_SET_STATION_LABEL').' -',
				'filter_station',
				JHtml::_('select.options', $this->stationNameOptions, 'value', 'text', $this->state->get('filter.station'))
			);

			if ($this->canBatch && $this->canCreate && $this->canEdit)
			{
				// Station Name Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_SENTINEL_DATA_SET_STATION_LABEL').' -',
					'batch[station]',
					JHtml::_('select.options', $this->stationNameOptions, 'value', 'text')
				);
			}
		}
	}

	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 */
	protected function setDocument()
	{
		if (!isset($this->document))
		{
			$this->document = JFactory::getDocument();
		}
		$this->document->setTitle(JText::_('COM_SENTINEL_DATA_SETS'));
		$this->document->addStyleSheet(JURI::root() . "administrator/components/com_sentinel/assets/css/data_sets.css", (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css');
	}

	/**
	 * Escapes a value for output in a view script.
	 *
	 * @param   mixed  $var  The output to escape.
	 *
	 * @return  mixed  The escaped value.
	 */
	public function escape($var)
	{
		if(strlen($var) > 50)
		{
			// use the helper htmlEscape method instead and shorten the string
			return SentinelHelper::htmlEscape($var, $this->_charset, true);
		}
		// use the helper htmlEscape method instead.
		return SentinelHelper::htmlEscape($var, $this->_charset);
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 */
	protected function getSortFields()
	{
		return array(
			'a.sorting' => JText::_('JGRID_HEADING_ORDERING'),
			'a.published' => JText::_('JSTATUS'),
			'g.name' => JText::_('COM_SENTINEL_DATA_SET_STATION_LABEL'),
			'a.id' => JText::_('JGRID_HEADING_ID')
		);
	}
}
