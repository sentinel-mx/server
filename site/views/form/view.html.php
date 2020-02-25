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
 * Form View class
 */
class SentinelViewForm extends JViewLegacy
{
	/**
	 * display method of View
	 * @return void
	 */
	public function display($tpl = null)
	{
		// set params
		$this->params = JComponentHelper::getParams('com_sentinel');
		// Assign the variables
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		$this->script = $this->get('Script');
		$this->state = $this->get('State');
		// get action permissions
		$this->canDo = SentinelHelper::getActions('form', $this->item);
		// get input
		$jinput = JFactory::getApplication()->input;
		$this->ref = $jinput->get('ref', 0, 'word');
		$this->refid = $jinput->get('refid', 0, 'int');
		$return = $jinput->get('return', null, 'base64');
		// set the referral string
		$this->referral = '';
		if ($this->refid && $this->ref)
		{
			// return to the item that referred to this item
			$this->referral = '&ref=' . (string)$this->ref . '&refid=' . (int)$this->refid;
		}
		elseif($this->ref)
		{
			// return to the list view that referred to this item
			$this->referral = '&ref=' . (string)$this->ref;
		}
		// check return value
		if (!is_null($return))
		{
			// add the return value
			$this->referral .= '&return=' . (string)$return;
		}

		// Set the toolbar
		$this->addToolBar();
		
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
		// adding the joomla edit toolbar to the front
		JLoader::register('JToolbarHelper', JPATH_ADMINISTRATOR.'/includes/toolbar.php');
		JFactory::getApplication()->input->set('hidemainmenu', true);
		$user = JFactory::getUser();
		$userId	= $user->id;
		$isNew = $this->item->id == 0;

		JToolbarHelper::title( JText::_($isNew ? 'COM_SENTINEL_FORM_NEW' : 'COM_SENTINEL_FORM_EDIT'), 'pencil-2 article-add');
		// Built the actions for new and existing records.
		if (SentinelHelper::checkString($this->referral))
		{
			if ($this->canDo->get('form.create') && $isNew)
			{
				// We can create the record.
				JToolBarHelper::save('form.save', 'JTOOLBAR_SAVE');
			}
			elseif ($this->canDo->get('form.edit'))
			{
				// We can save the record.
				JToolBarHelper::save('form.save', 'JTOOLBAR_SAVE');
			}
			if ($isNew)
			{
				// Do not creat but cancel.
				JToolBarHelper::cancel('form.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				// We can close it.
				JToolBarHelper::cancel('form.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		else
		{
			if ($isNew)
			{
				// For new records, check the create permission.
				if ($this->canDo->get('form.create'))
				{
					JToolBarHelper::apply('form.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('form.save', 'JTOOLBAR_SAVE');
					JToolBarHelper::custom('form.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
				};
				JToolBarHelper::cancel('form.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				if ($this->canDo->get('form.edit'))
				{
					// We can save the new record
					JToolBarHelper::apply('form.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('form.save', 'JTOOLBAR_SAVE');
					// We can save this record, but check the create permission to see
					// if we can return to make a new one.
					if ($this->canDo->get('form.create'))
					{
						JToolBarHelper::custom('form.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
					}
				}
				$canVersion = ($this->canDo->get('core.version') && $this->canDo->get('form.version'));
				if ($this->state->params->get('save_history', 1) && $this->canDo->get('form.edit') && $canVersion)
				{
					JToolbarHelper::versions('com_sentinel.form', $this->item->id);
				}
				if ($this->canDo->get('form.create'))
				{
					JToolBarHelper::custom('form.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
				}
				JToolBarHelper::cancel('form.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		JToolbarHelper::divider();
		// set help url for this view if found
		$help_url = SentinelHelper::getHelpUrl('form');
		if (SentinelHelper::checkString($help_url))
		{
			JToolbarHelper::help('COM_SENTINEL_HELP_MANAGER', false, $help_url);
		}
		// now initiate the toolbar
		$this->toolbar = JToolbar::getInstance();
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
		if(strlen($var) > 30)
		{
    			// use the helper htmlEscape method instead and shorten the string
			return SentinelHelper::htmlEscape($var, $this->_charset, true, 30);
		}
		// use the helper htmlEscape method instead.
		return SentinelHelper::htmlEscape($var, $this->_charset);
	}

	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 */
	protected function setDocument()
	{
		$isNew = ($this->item->id < 1);
		if (!isset($this->document))
		{
			$this->document = JFactory::getDocument();
		}
		$this->document->setTitle(JText::_($isNew ? 'COM_SENTINEL_FORM_NEW' : 'COM_SENTINEL_FORM_EDIT'));
		// only add the ISIS template css & js if needed (default is 1 = true)
		// you can override this in the global component options
		// just add a (radio yes/no field) with a name called add_isis_template
		// to your components config area
		if ($this->params->get('add_isis_template', 1))
		{
			// we need this to fix the form display (TODO)
			$this->document->addStyleSheet(JURI::root() . "administrator/templates/isis/css/template.css", (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css');
			$this->document->addScript(JURI::root() . "administrator/templates/isis/js/template.js", (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript');
		}
		// the default style of this view
		$this->document->addStyleSheet(JURI::root()."components/com_sentinel/assets/css/form.css", (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css');
		// Add Ajax Token
		$this->document->addScriptDeclaration("var token = '".JSession::getFormToken()."';");
		// default javascript of this view
		$this->document->addScript(JURI::root(). $this->script, (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript');
		$this->document->addScript(JURI::root(). "components/com_sentinel/views/form/submitbutton.js", (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript'); 

		// get Uikit Version
		$this->uikitVersion = $this->params->get('uikit_version', 2);
		// Load uikit options.
		$uikit = $this->params->get('uikit_load');
		$isAdmin = JFactory::getApplication()->isClient('administrator');
		// Set script size.
		$size = $this->params->get('uikit_min');
		// Use Uikit Version 2
		if (2 == $this->uikitVersion && ($isAdmin || $uikit != 2))
		{
			// Set css style.
			$style = $this->params->get('uikit_style');
			// only load if needed
			if ($isAdmin || $uikit != 3)
			{
				// add the style sheets
				$this->document->addStyleSheet( JURI::root(true) .'/media/com_sentinel/uikit-v2/css/uikit' . $style . $size . '.css' , (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css');
			}
			// add the style sheets
			$this->document->addStyleSheet( JURI::root(true) .'/media/com_sentinel/uikit-v2/css/components/accordion' . $style . $size . '.css' , (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css');
			$this->document->addStyleSheet( JURI::root(true) .'/media/com_sentinel/uikit-v2/css/components/tooltip' . $style . $size . '.css' , (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css');
			$this->document->addStyleSheet( JURI::root(true) .'/media/com_sentinel/uikit-v2/css/components/notify' . $style . $size . '.css' , (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css');
			$this->document->addStyleSheet( JURI::root(true) .'/media/com_sentinel/uikit-v2/css/components/form-file' . $style . $size . '.css' , (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css');
			$this->document->addStyleSheet( JURI::root(true) .'/media/com_sentinel/uikit-v2/css/components/progress' . $style . $size . '.css' , (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css');
			$this->document->addStyleSheet( JURI::root(true) .'/media/com_sentinel/uikit-v2/css/components/placeholder' . $style . $size . '.css' , (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css');
			$this->document->addStyleSheet( JURI::root(true) .'/media/com_sentinel/uikit-v2//css/components/upload' . $style . $size . '.css' , (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css');
			// only load if needed
			if ($isAdmin || $uikit != 3)
			{
				// add JavaScripts
				$this->document->addScript( JURI::root(true) .'/media/com_sentinel/uikit-v2/js/uikit' . $size . '.js', (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript');
			}
			// add JavaScripts
			$this->document->addScript( JURI::root(true) .'/media/com_sentinel/uikit-v2/js/components/accordion' . $size . '.js', (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript');
			$this->document->addScript( JURI::root(true) .'/media/com_sentinel/uikit-v2/js/components/tooltip' . $size . '.js', (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript');
			$this->document->addScript( JURI::root(true) .'/media/com_sentinel/uikit-v2/js/components/lightbox' . $size . '.js', (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript');
			$this->document->addScript( JURI::root(true) .'/media/com_sentinel/uikit-v2/js/components/notify' . $size . '.js', (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript');
			$this->document->addScript( JURI::root(true) .'/media/com_sentinel/uikit-v2/js/components/upload' . $size . '.js', (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript');
		}
		// Use Uikit Version 3
		elseif (3 == $this->uikitVersion && ($isAdmin || $uikit != 2))
		{
			// add the style sheets
			$this->document->addStyleSheet( JURI::root(true) .'/media/com_sentinel/uikit-v3/css/uikit'.$size.'.css', (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css');
			// add JavaScripts
			$this->document->addScript( JURI::root(true) .'/media/com_sentinel/uikit-v3/js/uikit'.$size.'.js', (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript');
			// add icons
			$this->document->addScript( JURI::root(true) .'/media/com_sentinel/uikit-v3/js/uikit-icons'.$size.'.js', (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript');
		}
		// load the google lib
		$this->document->addScript('https://www.gstatic.com/charts/loader.js', (SentinelHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript');
		// load the chart current
		$this->document->addScriptDeclaration("google.charts.load('current');");
		JText::script('view not acceptable. Error');
	}
}
