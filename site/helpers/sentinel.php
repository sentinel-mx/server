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
	@subpackage		sentinel.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>
	@copyright		Copyright (C) 2020. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

	Sentinel Server.

/-----------------------------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Language;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

/**
 * Sentinel component helper
 */
abstract class SentinelHelper
{
	/**
	 * The Main Active Language
	 * 
	 * @var      string
	 */
	public static $langTag;

	public static function getVar_($table, $where = null, $whereString = 'user', $what = 'id')
	{
		// Get a db connection.
		$db = JFactory::getDbo();
		// Create a new query object.
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array($what, $whereString)));
		$query->from($db->quoteName('#__sentinel_'.$table));
		$db->setQuery($query);
		$db->execute();
		if ($db->getNumRows())
		{
			$search = $db->loadObjectList();
			foreach ($search as $item)
			{
				if (isset($item->{$whereString}))
				{
					// basic decrypt data value.
					$item->{$whereString} = self::decrypt($item->{$whereString});
				}
				// see if it is this one
				if (isset($item->{$whereString}) && isset($item->{$what}) && $item->{$whereString} === $where)
				{
					return $item->{$what};
				}
			}
		}
		return false;
	}


	/**
	* the params
	**/
	protected static $params;

	/**
	 * Get selection  based on type
	 *
	 * @param   string   $table     The main table to select
	 * @param   string   $method    The type of values to return
	 * @param   string   $filter    The kind of filter (to return only values required)
	 * @param   object   $db     The database object
	 *
	 * @return array
	 *
	 */
	protected static function getSelection($table = 'form', $method = 'placeholder', $filter = 'none', $db = null)
	{
		// get the global settings
		if (!self::checkObject(self::$params))
		{
			self::$params = JComponentHelper::getParams('com_sentinel');
		}
		// prep for placeholders
		$f = '';
		$b = '';
		if ('placeholder' === $method)
		{
			// get the placeholder prefix
			$prefix = self::$params->get('placeholder_prefix', 'sentinel');
			$f = '[' . $prefix . '_';
			$b = ']';
		}
		// only get what we need
		if ('profile' === $filter)
		{
			// get the fields/columns
			if (($fields = self::$params->get('profile_fields', false)) !== false && self::checkObject($fields))
			{
				$columns = array('id'); // needed for permission check
				foreach ($fields as $field)
				{
					$columns[] = $field->field;
				}
				// add dates for display
				$columns[] = 'created';
				$columns[] = 'modified';
			}
		}
		// set the values you want to remove after modeling
		elseif ('remove' === $filter)
		{
			// remove params always
			$remove = array($f . 'params' . $b);
			return $remove;
		}
		else
		{
			// check if we have the DB object
			if (!self::checkObject($db))
			{
				// get the database object
				$db = JFactory::getDBO();
			}
			// get the database columns of this table
			$columns = $db->getTableColumns("#__sentinel_" . $table, false);
			// always remove these
			$remove = array('asset_id', 'checked_out', 'checked_out_time');
			// if placeholder and none filter then remove some more
			if ('placeholder' === $method && ('report' === $filter || 'email' === $filter))
			{
				$remove[] = 'params';
				$remove[] = 'access';
				$remove[] = 'created_by';
				$remove[] = 'id';
				$remove[] = 'ordering';
				$remove[] = 'version';
				$remove[] = 'published';
				$remove[] = 'modified_by';
				$remove[] = 'modified';
				$remove[] = 'hits';
			}
			// remove
			foreach ($remove as $key)
			{
				unset($columns[$key]);
			}
			// prep the columns
			$columns = array_keys($columns);
		}
		// make sure we have columns
		if (self::checkArray($columns))
		{
			// convert the columns for query selection
			$selection = array();
			foreach ($columns as $column)
			{
				$selection['a.' . $column] = $f . $column . $b;
				// we must add the label
				if ('profile' !== $filter && 'field' !== $filter)
				{
					// set the label for these fields
					$selection['setLabel->' . $column] = $f . 'label_' . $column . $b;
				}
			}
			return $selection;
		}
		return false;
	}

	/**
	 * Get placeholders
	 *
	 * @param   string   $type           The type of placeholders to return
	 * @param   bool     $addCompany     The switch to add the company
	 * @param   string   $table          The table being targeted
	 *
	 * @return array
	 *
	 */
	public static function getPlaceHolders($type = 'report', $addCompany = false, $table = 'form')
	{
		// start loading the placeholders
		$placeholders = array();
		if (method_exists(__CLASS__, 'getSelection'))
		{
			// get form placeholders
			if (('report' === $type || 'chart' === $type) && ($form = self::getSelection($table, 'placeholder', $type)) !== false && self::checkArray($form))
			{
				// always remove params, since it should never be used in placeholders
				unset($form['a.params']);
				// be sure to sort the array
				sort($form);
				// and remove duplicates
				$form = array_unique(array_values($form));
				// check if company should be added
				if ($addCompany)
				{
					$placeholders[] = $form;
				}
				else
				{
					return $form;
				}
			}
		}
		// get company placeholders
		if (('document' === $type || $addCompany) && method_exists(__CLASS__, 'getAnyCompanyDetails') && ($company = self::getAnyCompanyDetails('com_membersmanager', 'placeholder')) !== false && self::checkArray($company))
		{
			if ('document' === $type)
			{
				// just remove the footer and header placeholders
				unset($company['[company_doc_header]']);
				unset($company['[company_doc_footer]']);
			}
			$placeholders[] = array_keys($company);
		}
		// check that we have placeholders
		if (self::checkArray($placeholders))
		{
			return self::mergeArrays($placeholders);
		}
		return false;
	}


	/**
	 * The memory of the form details
	 *
	 * @var     array
	 */
	protected static $formDetails = array();

	/**
	 * The current return number
	 *
	 * @var     int
	 */
	public static $returnNumber;

	/**
	 * The global details key (set per/query)
	 *
	 * @var     string
	 */
	protected static $k3y;

	/**
	 * Get form details
	 *
	 * @param   int      $id         The the form ID
	 * @param   string   $type       The type of ID
	 * @param   string   $table      The table of ID
	 * @param   string   $method     The type of values to return
	 * @param   string   $filter     The kind of filter (to return only values required)
	 * @param   string   $masterkey  The master key for many values in the form table
	 * @param   int      $qty        The qty items to return
	 *
	 * @return array/object   based on $method
	 *
	 */
	public static function getFormDetails($id, $type = 'id', $table = 'form', $method = 'array', $filter = 'none', $masterkey = 'member', $qty = 0)
	{
		// always make sure that we have a form column
		if ($table !== 'form' && $type !== $masterkey)
		{
			// convert to master key
			if (($id = self::getVar((string) $table, (int) $id, $type, $masterkey)) === false)
			{
				return false;
			}
			$type = $masterkey;
			$table = 'form';
		}
		// get the user object
		$user = JFactory::getUser();
		// get database object
		$db = JFactory::getDbo();
		// get the database columns of this table
		$columns = $db->getTableColumns("#__sentinel_form", false);
		// if not id validate column
		if ($type !== 'id' && $type !== $masterkey)
		{
			// check if the type is found
			if (!isset($columns[$type]))
			{
				return false;
			}
		}
		// get the global settings
		if (!self::checkObject(self::$params))
		{
			self::$params = JComponentHelper::getParams('com_sentinel');
		}
		// get the relations (1 = one to one || 2 = one to many)
		$relations = self::$params->get('membersmanager_relation_type', 1);
		// always make sure that we have a masterkey ID
		if ($relations == 2 && $type !== $masterkey)
		{
			// convert to masterkey ID
			if (($id = self::getVar('form', (int) $id, $type, $masterkey)) === false)
			{
				return false;
			}
			// set master key as type of id
			$type = $masterkey;
		}
		// set the global key
		self::$k3y = $id.$method.$filter.$qty; // we will check this (qty) it may not be ideal (TODO)
		// check if we have the form details in memory
		if (is_numeric($id) && $id > 0 && !isset(self::$formDetails[self::$k3y]))
		{
			// get the form details an place it in memory
			$query = $db->getQuery(true);
			// check if we can getSelection
			if (method_exists(__CLASS__, "getSelection"))
			{
				// Select some fields
				if (($selection = self::getSelection('form', $method, $filter, $db)) !== false)
				{
					// strip selection values not direct SQL (does not have point)
					$selection = array_filter(
						$selection,
						function ($key) {
							return strpos($key, '.');
						},
						ARRAY_FILTER_USE_KEY
					);
				}
			}
			// check if we have a selection
			if (isset($selection) && self::checkArray($selection))
			{
				// do permission view purge (TODO)
				// set the selection
				$query->select($db->quoteName(array_keys($selection), array_values($selection)));
				// From the sentinel_form table
				$query->from($db->quoteName('#__sentinel_form', 'a'));
				// check if we have more join tables for the form details
				if (method_exists(__CLASS__, "joinFormDetails"))
				{
					self::joinFormDetails($query, $filter, $db);
				}
				// Implement View Level Access (if set in table)
				if (!$user->authorise('core.options', 'com_sentinel') && isset($columns['access']))
				{
					// ensure to always filter by access
					// $accessGroups = implode(',', $user->getAuthorisedViewLevels()); TODO first fix save to correct access groups
					// $query->where('a.access IN (' . $accessGroups . ')');
				}
				// check if we have more get where details
				if (method_exists(__CLASS__, "whereFormDetails"))
				{
					self::whereFormDetails($query, $filter, $db);
				}
				// check if we have more order details
				if (method_exists(__CLASS__, "orderFormDetails"))
				{
					self::orderFormDetails($query, $filter, $db);
				}
				// always order so to insure last added is first by default
				else
				{
					$query->order('a.id ASC');
				}
				// limit the return 
				if($qty > 1)
				{
					$query->setLimit($qty);
				}
				// get by type ID
				$query->where('a.' . $type . ' = ' . (int) $id);
				$db->setQuery($query);
				$db->execute();
				self::$returnNumber = $db->getNumRows();
				if (self::$returnNumber)
				{
					if ('object' == $method)
					{
						// if one to one
						if ($qty == 1 || $relations == 1 || self::$returnNumber == 1)
						{
							self::$formDetails[self::$k3y] = $db->loadObject();
							// we retrieved only 1
							self::$returnNumber = 1;
						}
						// if one to many (so we must return many)
						else
						{
							self::$formDetails[self::$k3y] = $db->loadObjectList();
						}
					}
					else
					{
						// if one to one
						if ($qty == 1 || $relations == 1 || self::$returnNumber == 1)
						{
							self::$formDetails[self::$k3y] = $db->loadAssoc();
							// we retrieved only 1
							self::$returnNumber = 1;
						}
						// if one to many (so we must return many)
						else
						{
							self::$formDetails[self::$k3y] = $db->loadAssocList();
						}
					}
				}
				// check if we have been able to get the form details
				if (!isset(self::$formDetails[self::$k3y]))
				{
					self::$formDetails[self::$k3y] = false;
				}
				// check if we must model the details
				elseif (method_exists(__CLASS__, "modelFormDetails"))
				{
					self::modelFormDetails($id, $method, $filter, $db);
					// check if we must remove some details after modeling
					if (method_exists(__CLASS__, "removeFormDetails"))
					{
						self::removeFormDetails($id, $method, $filter, $db);
					}
				}
			}
			else
			{
				self::$formDetails[self::$k3y] = false;
			}
		}
		return self::$formDetails[self::$k3y];
	}


	/**
	 * the global chart array
	 *
	 * @var   array
	 *
	 */
	public static $globalFormChartArray = array();

	/**
	 * Model the form details/values
	 *
	 * @param   object   $id          The the member ID
	 * @param   string   $method      The type of values to return
	 * @param   string   $filter      The kind of filter (to return only values required)
	 * @param   object   $db          The database object
	 *
	 * @return void
	 *
	 */
	protected static function modelFormDetails($id, $method, $filter, $db = null)
	{
		// check that we have values
		if (method_exists(__CLASS__, 'getSelection') && isset(self::$formDetails[self::$k3y]) && self::$formDetails[self::$k3y])
		{
			// get language object
			$lang = JFactory::getLanguage();
			// try to load the translation
			$lang->load('com_sentinel', JPATH_ADMINISTRATOR, null, false, true);
			// Select some fields
			$_builder = self::getSelection('form', $method, $filter, $db);
			// check if we have params to model
			if (method_exists(__CLASS__, "paramsModelFormDetails"))
			{
				self::paramsModelFormDetails($_builder, $method);
			}
			// check if we have subforms to model
			if (method_exists(__CLASS__, "subformModelFormDetails"))
			{
				self::subformModelFormDetails($_builder, $method);
			}
			// get values that must be set (not SQL values)
			$builder = array_filter(
				$_builder,
				function ($key) {
					return strpos($key, ':');
				},
				ARRAY_FILTER_USE_KEY
			);
			// start the builder
			if (self::checkArray($builder))
			{
				// prep for placeholders
				$f = '';
				$b = '';
				if ('placeholder' === $method)
				{
					// get the placeholder prefix
					$prefix = self::$params->get('placeholder_prefix', 'sentinel');
					$f = '[' . $prefix . '_';
					$b = ']';
				}
				// loop builder
				foreach ($builder as $build => $set)
				{
					// get function and key
					$_build = explode(':', $build);
					// check the number of values must be two
					if (count((array)$_build) == 2)
					{
						// check if more then one value must be passed
						if (strpos($_build[1], '|') !== false)
						{
							// get all value names
							$valueKeys = explode('|', $_build[1]);
							// continue only if we have values
							if (self::checkArray($valueKeys))
							{
								// start the modeling
								if (self::$returnNumber == 1)
								{
									$object = new JObject;
									foreach ($valueKeys as $valueKey)
									{
										// work with object
										if ('object' === $method && isset(self::$formDetails[self::$k3y]->{$valueKey}))
										{
											// load the properties
											$object->set($valueKey, self::$formDetails[self::$k3y]->{$valueKey});
										}
										// work with array
										elseif (self::checkArray(self::$formDetails[self::$k3y]) && isset(self::$formDetails[self::$k3y][$f.$valueKey.$b]))
										{
											// load the properties
											$object->set($valueKey, self::$formDetails[self::$k3y][$f.$valueKey.$b]);
										}
									}
									// now set the new value
									if ('object' === $method)
									{
										$result = self::{$_build[0]}($object);
										if (self::checkArray($result) || self::checkObject($result))
										{
											foreach ($result as $_key => $_val)
											{
												self::$formDetails[self::$k3y]->{$set . '_' . $_key} = $_val;
											}
										}
										else
										{
											self::$formDetails[self::$k3y]->{$set} = $result;
										}
									}
									// work with array
									else
									{
										$result = self::{$_build[0]}($object);
										if (self::checkArray($result) || self::checkObject($result))
										{
											$set = str_replace(array($f, $b), '', $set);
											foreach ($result as $_key => $_val)
											{
												self::$formDetails[self::$k3y][$f . $set . '_' . $_key . $b] = $_val;
											}
										}
										else
										{
											self::$formDetails[self::$k3y][$set] = $result;
										}
									}
								}
								elseif (self::checkArray(self::$formDetails[self::$k3y]))
								{
									foreach (self::$formDetails[self::$k3y] as $_nr => $details)
									{
										$object = new JObject;
										foreach ($valueKeys as $valueKey)
										{
											// work with object
											if ('object' === $method && isset($details->{$valueKey}))
											{
												// load the properties
												$object->set($valueKey, $details->{$valueKey});
											}
											// work with array
											elseif (self::checkArray($details) && isset($details[$f.$valueKey.$b]))
											{
												// load the properties
												$object->set($valueKey, $details[$f.$valueKey.$b]);
											}
										}
										// now set the new value
										if ('object' === $method)
										{
											$result = self::{$_build[0]}($object);
											if (self::checkArray($result) || self::checkObject($result))
											{
												foreach ($result as $_key => $_val)
												{
													self::$formDetails[self::$k3y][$_nr]->{$set . '_' . $_key} = $_val;
												}
											}
											else
											{
												self::$formDetails[self::$k3y][$_nr]->{$set} = $result;
											}
										}
										// work with array
										else
										{
											$result = self::{$_build[0]}($object);
											if (self::checkArray($result) || self::checkObject($result))
											{
												$set = str_replace(array($f, $b), '', $set);
												foreach ($result as $_key => $_val)
												{
													self::$formDetails[self::$k3y][$_nr][$f . $set . '_' . $_key . $b] = $_val;
												}
											}
											else
											{
												self::$formDetails[self::$k3y][$_nr][$set] = $result;
											}
										}
									}
								}
							}
						}
						else
						{
							if (self::$returnNumber == 1)
							{
								// work with object
								if ('object' === $method && isset(self::$formDetails[self::$k3y]->{$_build[1]}))
								{
									$result = self::{$_build[0]}(self::$formDetails[self::$k3y]->{$_build[1]});
									if (self::checkArray($result) || self::checkObject($result))
									{
										foreach ($result as $_key => $_val)
										{
											self::$formDetails[self::$k3y]->{$set . '_' . $_key} = $_val;
										}
									}
									else
									{
										self::$formDetails[self::$k3y]->{$set} = $result;
									}
								}
								// work with array
								elseif (self::checkArray(self::$formDetails[self::$k3y]) && isset(self::$formDetails[self::$k3y][$f.$_build[1].$b]))
								{
									$result = self::{$_build[0]}(self::$formDetails[self::$k3y][$f.$_build[1].$b]);
									if (self::checkArray($result) || self::checkObject($result))
									{
										$set = str_replace(array($f, $b), '', $set);
										foreach ($result as $_key => $_val)
										{
											self::$formDetails[self::$k3y][$f . $set . '_' . $_key . $b] = $_val;
										}
									}
									else
									{
										self::$formDetails[self::$k3y][$set] = $result;
									}
								}
							}
							elseif (self::checkArray(self::$formDetails[self::$k3y]))
							{
								foreach (self::$formDetails[self::$k3y] as $_nr => $details)
								{
									// work with object
									if ('object' === $method && isset(self::$formDetails[self::$k3y][$_nr]->{$_build[1]}))
									{
										$result = self::{$_build[0]}(self::$formDetails[self::$k3y][$_nr]->{$_build[1]});
										if (self::checkArray($result) || self::checkObject($result))
										{
											foreach ($result as $_key => $_val)
											{
												self::$formDetails[self::$k3y][$_nr]->{$set . '_' . $_key} = $_val;
											}
										}
										else
										{
											self::$formDetails[self::$k3y][$_nr]->{$set} = $result;
										}
									}
									// work with array
									elseif (self::checkArray(self::$formDetails[self::$k3y][$_nr]) && isset(self::$formDetails[self::$k3y][$_nr][$f.$_build[1].$b]))
									{
										$result = self::{$_build[0]}(self::$formDetails[self::$k3y][$_nr][$f.$_build[1].$b]);
										if (self::checkArray($result) || self::checkObject($result))
										{
											$set = str_replace(array($f, $b), '', $set);
											foreach ($result as $_key => $_val)
											{
												self::$formDetails[self::$k3y][$_nr][$f . $set . '_' . $_key . $b] = $_val;
											}
										}
										else
										{
											self::$formDetails[self::$k3y][$_nr][$set] = $result;
										}
									}
								}
							}
						}
					}
				}
			}
			// check if we have labels to model
			if (method_exists(__CLASS__, "labelModelFormDetails"))
			{
				self::labelModelFormDetails($_builder, $method);
			}
			// check if we have templates to model
			if (method_exists(__CLASS__, "templateModelFormDetails") && property_exists(__CLASS__, 'formParams'))
			{
				self::templateModelFormDetails($_builder, $method);
			}
			// check if we have charts to model (must be last after all data is set)
			if (method_exists(__CLASS__, "chartModelFormDetails"))
			{
				self::chartModelFormDetails($_builder, $method, $filter);
			}
			elseif (method_exists(__CLASS__, "multiChartModelFormDetails"))
			{
				self::multiChartModelFormDetails($_builder, $method, $filter);
			}
		}
	}


	/**
	 * Label Model the form details/values
	 *
	 * @param   array    $builder     The selection array
	 * @param   string   $method      The type of values to return
	 *
	 * @return void
	 *
	 */
	protected static function labelModelFormDetails($builder, $method)
	{
		// get values that must be set (not SQL values)
		$builder = array_filter(
			$builder,
			function ($key) {
				return strpos($key, 'Label->');
			},
			ARRAY_FILTER_USE_KEY
		);
		// start the builder
		if (self::checkArray($builder))
		{
			// prep for placeholders
			$f = '';
			$b = '';
			if ('placeholder' === $method)
			{
				// get the placeholder prefix
				$prefix = self::$params->get('placeholder_prefix', 'sentinel');
				$f = '[' . $prefix . '_';
				$b = ']';
			}
			// loop builder
			foreach ($builder as $build => $set)
			{
				// get the label key
				$build = str_replace('setLabel->', '', $build);
				// check if this is a single or multi array
				if (self::$returnNumber == 1)
				{
					// work with object
					if ('object' === $method)
					{
						self::$formDetails[self::$k3y]->{$set} = self::setLabelModelFormDetails($build);
					}
					// work with array
					elseif (self::checkArray(self::$formDetails[self::$k3y]) && isset(self::$formDetails[self::$k3y][$f.$build.$b]))
					{
						self::$formDetails[self::$k3y][$set] = self::setLabelModelFormDetails($build);
					}
				}
				elseif (self::checkArray(self::$formDetails[self::$k3y]))
				{
					foreach (self::$formDetails[self::$k3y] as $_nr => $details)
					{
						// work with object
						if ('object' === $method && isset(self::$formDetails[self::$k3y][$_nr]->{$build}))
						{
							self::$formDetails[self::$k3y][$_nr]->{$set} = self::setLabelModelFormDetails($build);
						}
						// work with array
						elseif (self::checkArray(self::$formDetails[self::$k3y][$_nr]) && isset(self::$formDetails[self::$k3y][$_nr][$f.$build.$b]))
						{
							self::$formDetails[self::$k3y][$_nr][$set] = self::setLabelModelFormDetails($build);
						}
					}
				}
			}
		}
	}

	/**
	 * Set the Label to the form details/values
	 *
	 * @param   string   $key      The key of the setting label
	 *
	 * @return mix
	 *
	 */
	protected static function setLabelModelFormDetails($key)
	{
		// make sure we have the template
		if (property_exists(__CLASS__, 'formParams') && isset(self::$formParams[$key]) && isset(self::$formParams[$key]['label']) && self::checkString(self::$formParams[$key]['label']))
		{
			return JText::_(self::$formParams[$key]['label']);
		}
		// check if this value is part of the admin form
		$LABLE = 'COM_SENTINEL_FORM_' . self::safeString($key, 'U') . '_LABEL';
		$label = JText::_($LABLE);
		// little workaround for now
		if ($LABLE === $label)
		{
			return self::safeString($key, 'Ww');
		}
		return $label;
	}


	/**
	 * The Member Name Memory
	 *
	 * @var   array
	 */
	protected static $memberNames = array();

	/**
	* Get the members name
	* 
	* @param  int        $id    The member ID
	* @param  int        $user  The user ID
	* @param  string     $name  The name
	* @param  string     $surname  The surname
	* @param  string     $default  The default
	*
	* @return  string    the members name
	* 
	*/
	public static function  getMemberName($id = null, $user = null, $name = null, $surname = null, $default = 'No Name')
	{
		// check if member ID is null, then get member ID
		if (!$id || !is_numeric($id))
		{
			if (!$user || !is_numeric($user) || ($id = self::getVar('member', $user, 'user', 'id', '=', 'membersmanager')) === false || !is_numeric($id) || $id == 0)
			{
				// check if a was name given
				if (self::checkstring($name))
				{
					$default = $name;
				}
				// always set surname if given
				if (self::checkString($surname))
				{
					$default += ' ' . $surname;
				}
				return $default;
			}
		}
		// check if name in memory
		if (isset(self::$memberNames[$id]))
		{
			return self::$memberNames[$id];
		}
		// always get surname
		if (!self::checkString($surname))
		{
			if(($surname = self::getVar('member', $id, 'id', 'surname', '=', 'membersmanager')) === false || !self::checkString($surname))
			{
				$surname = '';
			}
		}
		// check name given
		if (self::checkstring($name))
		{
			$memberName = $name . ' ' . $surname;
		}
		// check user given
		elseif ((is_numeric($user) && $user > 0) || (($user = self::getVar('member', $id, 'id', 'user', '=', 'membersmanager')) !== false && $user > 0))
		{
			$memberName = JFactory::getUser($user)->name . ' ' . $surname;
		}
		// get the name
		elseif (($name = self::getVar('member', $id, 'id', 'name', '=', 'membersmanager')) !== false && self::checkstring($name))
		{
			$memberName = $name . ' ' . $surname;
		}
		// load to memory
		if (isset($memberName))
		{
			self::$memberNames[$id] = $memberName;
			// return member name
			return $memberName;
		}
		return $default;
	}

	/**
	 * The Member Email Memory
	 *
	 * @var   array
	 */
	protected static $memberEmails = array();

	/**
	* Get the members email
	* 
	* @param  int        $id    The member ID
	* @param  int        $user  The user ID
	* @param  string     $default  The default
	*
	* @return  string    the members email
	* 
	*/
	public static function  getMemberEmail($id = null, $user = null, $default = '')
	{
		// check if member ID is null, then get member ID
		if (!$id || !is_numeric($id))
		{
			if (!$user || !is_numeric($user) || ($id = self::getVar('member', $user, 'user', 'id')) === false)
			{
				return $default;
			}
		}
		// check if email in memory
		if (isset(self::$memberEmails[$id]))
		{
			return self::$memberEmails[$id];
		}
		// check user given
		if ((is_numeric($user) && $user > 0) || (is_numeric($id) && $id > 0 && ($user = self::getVar('member', $id, 'id', 'user', '=', 'membersmanager')) !== false && $user > 0))
		{
			$memberEmail = JFactory::getUser($user)->email;
		}
		// get the email
		elseif (($email = self::getVar('member', $id, 'id', 'email', '=', 'membersmanager')) !== false && self::checkstring($email))
		{
			$memberEmail = $email;
		}
		// load to memory
		if (isset($memberEmail))
		{
			self::$memberEmails[$id] = $memberEmail;
			// return found email
			return $memberEmail;
		}
		return $default;
	}


	/**
	* 	prepare base64 string for url
	**/
	public static function base64_urlencode($string, $encode = false)
	{
		if ($encode)
		{
			$string = base64_encode($string);
		}
		return str_replace(array('+', '/'), array('-', '_'), $string);
	}

	/**
	* 	prepare base64 string form url
	**/
	public static function base64_urldecode($string, $decode = false)
	{
		$string = str_replace(array('-', '_'), array('+', '/'), $string);
		if ($decode)
		{
			$string = base64_decode($string);
		}
		return $string;
	}


	/**
	 * The Dynamic Data Array
	 *
	 * @var     array
	 */
	protected static $dynamicData = array();

	/**
	 * Set the Dynamic Data
	 *
	 * @param   string   $data             The data to update
	 * @param   array   $placeholders      The placeholders to use to update data
	 *
	 * @return string   of updated data
	 *
	 */
	public static function setDynamicData($data, $placeholders)
	{
		// make sure data is a string & placeholders is an array
		if (self::checkString($data) && self::checkArray($placeholders))
		{
			// store in memory in case it is build multiple times
			$keyMD5 = md5($data.json_encode($placeholders));
			if (!isset(self::$dynamicData[$keyMD5]))
			{
				// remove all values that are not strings (just to be safe)
				$placeholders = array_filter($placeholders, function ($val){ if (self::checkArray($val) || self::checkObject($val)) { return false; } return true; });
				// model (basic) based on logic
				self::setTheIF($data, $placeholders);
				// update the string and store in memory
				self::$dynamicData[$keyMD5] = str_replace(array_keys($placeholders), array_values($placeholders), $data);
			}
			// return updated string
			return self::$dynamicData[$keyMD5];
		}
		return $data;
	}

	/**
	 * Set the IF statements
	 *
	 * @param   string   $string           The string to update
	 * @param   array   $placeholders      The placeholders to use to update string
	 *
	 * @return void
	 *
	 */
	protected static function setTheIF(&$string, $placeholders)
	{		
		// only normal if endif
		$condition 	= '[a-z0-9\_\-]+';
		$inner		= '((?:(?!\[\/?IF)(?!\[\/?ELSE)(?!\[\/?ELSEIF).)*?)';
		$if		= '\[IF\s?('.$condition.')\]';
		$elseif		= '\[ELSEIF\s?('.$condition.')\]';
		$else		= '\[ELSE\]';
		$endif		= '\[ENDIF\]';
		// set the patterns
		$patterns = array();
		// normal if endif
		$patterns[] = '#'.$if.$inner.$endif.'#is';
		// normal if else endif
		$patterns[] = '#'.$if.$inner.$else.$inner.$endif.'#is';
		// dynamic if elseif's endif
		$patterns[] = '#'.$if.$inner.$elseif.$inner.$endif.'#is';
		$patterns[] = '#'.$if.$inner.$elseif.$inner.$elseif.$inner.$endif.'#is';
		$patterns[] = '#'.$if.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$endif.'#is';
		$patterns[] = '#'.$if.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$endif.'#is';
		$patterns[] = '#'.$if.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$endif.'#is';
		$patterns[] = '#'.$if.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$endif.'#is';
		$patterns[] = '#'.$if.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$endif.'#is';
		$patterns[] = '#'.$if.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$endif.'#is';
		// dynamic if elseif's else endif
		$patterns[] = '#'.$if.$inner.$elseif.$inner.$else.$inner.$endif.'#is';
		$patterns[] = '#'.$if.$inner.$elseif.$inner.$elseif.$inner.$else.$inner.$endif.'#is';
		$patterns[] = '#'.$if.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$else.$inner.$endif.'#is';
		$patterns[] = '#'.$if.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$else.$inner.$endif.'#is';
		$patterns[] = '#'.$if.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$else.$inner.$endif.'#is';
		$patterns[] = '#'.$if.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$else.$inner.$endif.'#is';
		$patterns[] = '#'.$if.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$else.$inner.$endif.'#is';
		$patterns[] = '#'.$if.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$elseif.$inner.$else.$inner.$endif.'#is';
		// run the patterns to setup the string
		foreach ($patterns as $pattern)
		{
			while (preg_match($pattern, $string, $match))
			{
				$keep 	= self::remainderIF($match, $placeholders);
				$string	= preg_replace($pattern, $keep, $string, 1);
			}
		}
	}

	/**
	 * Set the remainder IF
	 *
	 * @param   array   $match            The match search
	 * @param   array   $placeholders     The placeholders to use to match
	 *
	 * @return string of remainder
	 *
	 */
	protected static function remainderIF(&$match, &$placeholders)
	{	
		// default we keep nothing
		$keep = '';
		$found = false;
		// get match lenght
		$length = count($match);
		// ranges to check
		$ii = range(2,30,2); // even numbers (content)
		$iii = range(1, 25, 2); // odd numbers (placeholder)
		// if empty value remove whole line else show line but remove all [CODE]
		foreach ($iii as $content => $placeholder)
		{
			if (isset($match[$placeholder]) && empty($placeholders['['.$match[$placeholder].']']))
			{
				// keep nothing or next option
				$keep = '';
			}
			elseif (isset($match[$ii[$content]]))
			{
				$keep = addcslashes($match[$ii[$content]], '$');
				$found = true;
				break;
			}
		}
		// if not found load else if set
		if (!$found && in_array($length, $ii))
		{
			$keep = addcslashes($match[$length - 1], '$');
		}
		return $keep;
	}


	/**
	 * @return array of link options
	 */
	public static function getLinkOptions($lock = 0, $session = 0, $params = null)
	{
		// get the global settings
		if (!self::checkObject(self::$params))
		{
			if (self::checkObject($params))
			{
				self::$params = $params;
			}
			else
			{
				self::$params = JComponentHelper::getParams('com_sentinel');
			}
		}
		$linkoptions = self::$params->get('link_option', null);
		// set the options to array
		$options = array('lock' => $lock, 'session' => $session);
		if (SentinelHelper::checkArray($linkoptions))
		{
			if (in_array(1, $linkoptions))
			{
				// lock the filename
				$options['lock'] = 1;
			}
			if (in_array(2, $linkoptions))
			{
				// add session to the links
				$options['session'] = 1;
			}
		}
		return $options;
	}

	/**
	 * File Extension to Mimetype
	 * https://gist.github.com/Llewellynvdm/74be373357e131b8775a7582c3de508b
	 * http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
	 * 
	 * @var     array
	 **/
	protected static $fileExtensionToMimeType = array(
		'123'			=> 'application/vnd.lotus-1-2-3',
		'3dml'			=> 'text/vnd.in3d.3dml',
		'3ds'			=> 'image/x-3ds',
		'3g2'			=> 'video/3gpp2',
		'3gp'			=> 'video/3gpp',
		'7z'			=> 'application/x-7z-compressed',
		'aab'			=> 'application/x-authorware-bin',
		'aac'			=> 'audio/x-aac',
		'aam'			=> 'application/x-authorware-map',
		'aas'			=> 'application/x-authorware-seg',
		'abw'			=> 'application/x-abiword',
		'ac'			=> 'application/pkix-attr-cert',
		'acc'			=> 'application/vnd.americandynamics.acc',
		'ace'			=> 'application/x-ace-compressed',
		'acu'			=> 'application/vnd.acucobol',
		'acutc'			=> 'application/vnd.acucorp',
		'adp'			=> 'audio/adpcm',
		'aep'			=> 'application/vnd.audiograph',
		'afm'			=> 'application/x-font-type1',
		'afp'			=> 'application/vnd.ibm.modcap',
		'ahead'			=> 'application/vnd.ahead.space',
		'ai'			=> 'application/postscript',
		'aif'			=> 'audio/x-aiff',
		'aifc'			=> 'audio/x-aiff',
		'aiff'			=> 'audio/x-aiff',
		'air'			=> 'application/vnd.adobe.air-application-installer-package+zip',
		'ait'			=> 'application/vnd.dvb.ait',
		'ami'			=> 'application/vnd.amiga.ami',
		'apk'			=> 'application/vnd.android.package-archive',
		'appcache'		=> 'text/cache-manifest',
		'application'	=> 'application/x-ms-application',
		'apr'			=> 'application/vnd.lotus-approach',
		'arc'			=> 'application/x-freearc',
		'asc'			=> 'application/pgp-signature',
		'asf'			=> 'video/x-ms-asf',
		'asm'			=> 'text/x-asm',
		'aso'			=> 'application/vnd.accpac.simply.aso',
		'asx'			=> 'video/x-ms-asf',
		'atc'			=> 'application/vnd.acucorp',
		'atom'			=> 'application/atom+xml',
		'atomcat'		=> 'application/atomcat+xml',
		'atomsvc'		=> 'application/atomsvc+xml',
		'atx'			=> 'application/vnd.antix.game-component',
		'au'			=> 'audio/basic',
		'avi'			=> 'video/x-msvideo',
		'aw'			=> 'application/applixware',
		'azf'			=> 'application/vnd.airzip.filesecure.azf',
		'azs'			=> 'application/vnd.airzip.filesecure.azs',
		'azw'			=> 'application/vnd.amazon.ebook',
		'bat'			=> 'application/x-msdownload',
		'bcpio'			=> 'application/x-bcpio',
		'bdf'			=> 'application/x-font-bdf',
		'bdm'			=> 'application/vnd.syncml.dm+wbxml',
		'bed'			=> 'application/vnd.realvnc.bed',
		'bh2'			=> 'application/vnd.fujitsu.oasysprs',
		'bin'			=> 'application/octet-stream',
		'blb'			=> 'application/x-blorb',
		'blorb'			=> 'application/x-blorb',
		'bmi'			=> 'application/vnd.bmi',
		'bmp'			=> 'image/bmp',
		'book'			=> 'application/vnd.framemaker',
		'box'			=> 'application/vnd.previewsystems.box',
		'boz'			=> 'application/x-bzip2',
		'bpk'			=> 'application/octet-stream',
		'btif'			=> 'image/prs.btif',
		'bz'			=> 'application/x-bzip',
		'bz2'			=> 'application/x-bzip2',
		'c'				=> 'text/x-c',
		'c11amc'		=> 'application/vnd.cluetrust.cartomobile-config',
		'c11amz'		=> 'application/vnd.cluetrust.cartomobile-config-pkg',
		'c4d'			=> 'application/vnd.clonk.c4group',
		'c4f'			=> 'application/vnd.clonk.c4group',
		'c4g'			=> 'application/vnd.clonk.c4group',
		'c4p'			=> 'application/vnd.clonk.c4group',
		'c4u'			=> 'application/vnd.clonk.c4group',
		'cab'			=> 'application/vnd.ms-cab-compressed',
		'caf'			=> 'audio/x-caf',
		'cap'			=> 'application/vnd.tcpdump.pcap',
		'car'			=> 'application/vnd.curl.car',
		'cat'			=> 'application/vnd.ms-pki.seccat',
		'cb7'			=> 'application/x-cbr',
		'cba'			=> 'application/x-cbr',
		'cbr'			=> 'application/x-cbr',
		'cbt'			=> 'application/x-cbr',
		'cbz'			=> 'application/x-cbr',
		'cc'			=> 'text/x-c',
		'cct'			=> 'application/x-director',
		'ccxml'			=> 'application/ccxml+xml',
		'cdbcmsg'		=> 'application/vnd.contact.cmsg',
		'cdf'			=> 'application/x-netcdf',
		'cdkey'			=> 'application/vnd.mediastation.cdkey',
		'cdmia'			=> 'application/cdmi-capability',
		'cdmic'			=> 'application/cdmi-container',
		'cdmid'			=> 'application/cdmi-domain',
		'cdmio'			=> 'application/cdmi-object',
		'cdmiq'			=> 'application/cdmi-queue',
		'cdx'			=> 'chemical/x-cdx',
		'cdxml'			=> 'application/vnd.chemdraw+xml',
		'cdy'			=> 'application/vnd.cinderella',
		'cer'			=> 'application/pkix-cert',
		'cfs'			=> 'application/x-cfs-compressed',
		'cgm'			=> 'image/cgm',
		'chat'			=> 'application/x-chat',
		'chm'			=> 'application/vnd.ms-htmlhelp',
		'chrt'			=> 'application/vnd.kde.kchart',
		'cif'			=> 'chemical/x-cif',
		'cii'			=> 'application/vnd.anser-web-certificate-issue-initiation',
		'cil'			=> 'application/vnd.ms-artgalry',
		'cla'			=> 'application/vnd.claymore',
		'class'			=> 'application/java-vm',
		'clkk'			=> 'application/vnd.crick.clicker.keyboard',
		'clkp'			=> 'application/vnd.crick.clicker.palette',
		'clkt'			=> 'application/vnd.crick.clicker.template',
		'clkw'			=> 'application/vnd.crick.clicker.wordbank',
		'clkx'			=> 'application/vnd.crick.clicker',
		'clp'			=> 'application/x-msclip',
		'cmc'			=> 'application/vnd.cosmocaller',
		'cmdf'			=> 'chemical/x-cmdf',
		'cml'			=> 'chemical/x-cml',
		'cmp'			=> 'application/vnd.yellowriver-custom-menu',
		'cmx'			=> 'image/x-cmx',
		'cod'			=> 'application/vnd.rim.cod',
		'com'			=> 'application/x-msdownload',
		'conf'			=> 'text/plain',
		'cpio'			=> 'application/x-cpio',
		'cpp'			=> 'text/x-c',
		'cpt'			=> 'application/mac-compactpro',
		'crd'			=> 'application/x-mscardfile',
		'crl'			=> 'application/pkix-crl',
		'crt'			=> 'application/x-x509-ca-cert',
		'cryptonote'	=> 'application/vnd.rig.cryptonote',
		'csh'			=> 'application/x-csh',
		'csml'			=> 'chemical/x-csml',
		'csp'			=> 'application/vnd.commonspace',
		'css'			=> 'text/css',
		'cst'			=> 'application/x-director',
		'csv'			=> 'text/csv',
		'cu'			=> 'application/cu-seeme',
		'curl'			=> 'text/vnd.curl',
		'cww'			=> 'application/prs.cww',
		'cxt'			=> 'application/x-director',
		'cxx'			=> 'text/x-c',
		'dae'			=> 'model/vnd.collada+xml',
		'daf'			=> 'application/vnd.mobius.daf',
		'dart'			=> 'application/vnd.dart',
		'dataless'		=> 'application/vnd.fdsn.seed',
		'davmount'		=> 'application/davmount+xml',
		'dbk'			=> 'application/docbook+xml',
		'dcr'			=> 'application/x-director',
		'dcurl'			=> 'text/vnd.curl.dcurl',
		'dd2'			=> 'application/vnd.oma.dd2+xml',
		'ddd'			=> 'application/vnd.fujixerox.ddd',
		'deb'			=> 'application/x-debian-package',
		'def'			=> 'text/plain',
		'deploy'		=> 'application/octet-stream',
		'der'			=> 'application/x-x509-ca-cert',
		'dfac'			=> 'application/vnd.dreamfactory',
		'dgc'			=> 'application/x-dgc-compressed',
		'dic'			=> 'text/x-c',
		'dir'			=> 'application/x-director',
		'dis'			=> 'application/vnd.mobius.dis',
		'dist'			=> 'application/octet-stream',
		'distz'			=> 'application/octet-stream',
		'djv'			=> 'image/vnd.djvu',
		'djvu'			=> 'image/vnd.djvu',
		'dll'			=> 'application/x-msdownload',
		'dmg'			=> 'application/x-apple-diskimage',
		'dmp'			=> 'application/vnd.tcpdump.pcap',
		'dms'			=> 'application/octet-stream',
		'dna'			=> 'application/vnd.dna',
		'doc'			=> 'application/msword',
		'docm'			=> 'application/vnd.ms-word.document.macroenabled.12',
		'docx'			=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'dot'			=> 'application/msword',
		'dotm'			=> 'application/vnd.ms-word.template.macroenabled.12',
		'dotx'			=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
		'dp'			=> 'application/vnd.osgi.dp',
		'dpg'			=> 'application/vnd.dpgraph',
		'dra'			=> 'audio/vnd.dra',
		'dsc'			=> 'text/prs.lines.tag',
		'dssc'			=> 'application/dssc+der',
		'dtb'			=> 'application/x-dtbook+xml',
		'dtd'			=> 'application/xml-dtd',
		'dts'			=> 'audio/vnd.dts',
		'dtshd'			=> 'audio/vnd.dts.hd',
		'dump'			=> 'application/octet-stream',
		'dvb'			=> 'video/vnd.dvb.file',
		'dvi'			=> 'application/x-dvi',
		'dwf'			=> 'model/vnd.dwf',
		'dwg'			=> 'image/vnd.dwg',
		'dxf'			=> 'image/vnd.dxf',
		'dxp'			=> 'application/vnd.spotfire.dxp',
		'dxr'			=> 'application/x-director',
		'ecelp4800'		=> 'audio/vnd.nuera.ecelp4800',
		'ecelp7470'		=> 'audio/vnd.nuera.ecelp7470',
		'ecelp9600'		=> 'audio/vnd.nuera.ecelp9600',
		'ecma'			=> 'application/ecmascript',
		'edm'			=> 'application/vnd.novadigm.edm',
		'edx'			=> 'application/vnd.novadigm.edx',
		'efif'			=> 'application/vnd.picsel',
		'ei6'			=> 'application/vnd.pg.osasli',
		'elc'			=> 'application/octet-stream',
		'emf'			=> 'application/x-msmetafile',
		'eml'			=> 'message/rfc822',
		'emma'			=> 'application/emma+xml',
		'emz'			=> 'application/x-msmetafile',
		'eol'			=> 'audio/vnd.digital-winds',
		'eot'			=> 'application/vnd.ms-fontobject',
		'eps'			=> 'application/postscript',
		'epub'			=> 'application/epub+zip',
		'es3'			=> 'application/vnd.eszigno3+xml',
		'esa'			=> 'application/vnd.osgi.subsystem',
		'esf'			=> 'application/vnd.epson.esf',
		'et3'			=> 'application/vnd.eszigno3+xml',
		'etx'			=> 'text/x-setext',
		'eva'			=> 'application/x-eva',
		'evy'			=> 'application/x-envoy',
		'exe'			=> 'application/x-msdownload',
		'exi'			=> 'application/exi',
		'ext'			=> 'application/vnd.novadigm.ext',
		'ez'			=> 'application/andrew-inset',
		'ez2'			=> 'application/vnd.ezpix-album',
		'ez3'			=> 'application/vnd.ezpix-package',
		'f'				=> 'text/x-fortran',
		'f4v'			=> 'video/x-f4v',
		'f77'			=> 'text/x-fortran',
		'f90'			=> 'text/x-fortran',
		'fbs'			=> 'image/vnd.fastbidsheet',
		'fcdt'			=> 'application/vnd.adobe.formscentral.fcdt',
		'fcs'			=> 'application/vnd.isac.fcs',
		'fdf'			=> 'application/vnd.fdf',
		'fe_launch'		=> 'application/vnd.denovo.fcselayout-link',
		'fg5'			=> 'application/vnd.fujitsu.oasysgp',
		'fgd'			=> 'application/x-director',
		'fh'			=> 'image/x-freehand',
		'fh4'			=> 'image/x-freehand',
		'fh5'			=> 'image/x-freehand',
		'fh7'			=> 'image/x-freehand',
		'fhc'			=> 'image/x-freehand',
		'fig'			=> 'application/x-xfig',
		'flac'			=> 'audio/x-flac',
		'fli'			=> 'video/x-fli',
		'flo'			=> 'application/vnd.micrografx.flo',
		'flv'			=> 'video/x-flv',
		'flw'			=> 'application/vnd.kde.kivio',
		'flx'			=> 'text/vnd.fmi.flexstor',
		'fly'			=> 'text/vnd.fly',
		'fm'			=> 'application/vnd.framemaker',
		'fnc'			=> 'application/vnd.frogans.fnc',
		'for'			=> 'text/x-fortran',
		'fpx'			=> 'image/vnd.fpx',
		'frame'			=> 'application/vnd.framemaker',
		'fsc'			=> 'application/vnd.fsc.weblaunch',
		'fst'			=> 'image/vnd.fst',
		'ftc'			=> 'application/vnd.fluxtime.clip',
		'fti'			=> 'application/vnd.anser-web-funds-transfer-initiation',
		'fvt'			=> 'video/vnd.fvt',
		'fxp'			=> 'application/vnd.adobe.fxp',
		'fxpl'			=> 'application/vnd.adobe.fxp',
		'fzs'			=> 'application/vnd.fuzzysheet',
		'g2w'			=> 'application/vnd.geoplan',
		'g3'			=> 'image/g3fax',
		'g3w'			=> 'application/vnd.geospace',
		'gac'			=> 'application/vnd.groove-account',
		'gam'			=> 'application/x-tads',
		'gbr'			=> 'application/rpki-ghostbusters',
		'gca'			=> 'application/x-gca-compressed',
		'gdl'			=> 'model/vnd.gdl',
		'geo'			=> 'application/vnd.dynageo',
		'gex'			=> 'application/vnd.geometry-explorer',
		'ggb'			=> 'application/vnd.geogebra.file',
		'ggt'			=> 'application/vnd.geogebra.tool',
		'ghf'			=> 'application/vnd.groove-help',
		'gif'			=> 'image/gif',
		'gim'			=> 'application/vnd.groove-identity-message',
		'gml'			=> 'application/gml+xml',
		'gmx'			=> 'application/vnd.gmx',
		'gnumeric'		=> 'application/x-gnumeric',
		'gph'			=> 'application/vnd.flographit',
		'gpx'			=> 'application/gpx+xml',
		'gqf'			=> 'application/vnd.grafeq',
		'gqs'			=> 'application/vnd.grafeq',
		'gram'			=> 'application/srgs',
		'gramps'		=> 'application/x-gramps-xml',
		'gre'			=> 'application/vnd.geometry-explorer',
		'grv'			=> 'application/vnd.groove-injector',
		'grxml'			=> 'application/srgs+xml',
		'gsf'			=> 'application/x-font-ghostscript',
		'gtar'			=> 'application/x-gtar',
		'gtm'			=> 'application/vnd.groove-tool-message',
		'gtw'			=> 'model/vnd.gtw',
		'gv'			=> 'text/vnd.graphviz',
		'gxf'			=> 'application/gxf',
		'gxt'			=> 'application/vnd.geonext',
		'h'				=> 'text/x-c',
		'h261'			=> 'video/h261',
		'h263'			=> 'video/h263',
		'h264'			=> 'video/h264',
		'hal'			=> 'application/vnd.hal+xml',
		'hbci'			=> 'application/vnd.hbci',
		'hdf'			=> 'application/x-hdf',
		'hh'			=> 'text/x-c',
		'hlp'			=> 'application/winhlp',
		'hpgl'			=> 'application/vnd.hp-hpgl',
		'hpid'			=> 'application/vnd.hp-hpid',
		'hps'			=> 'application/vnd.hp-hps',
		'hqx'			=> 'application/mac-binhex40',
		'htke'			=> 'application/vnd.kenameaapp',
		'htm'			=> 'text/html',
		'html'			=> 'text/html',
		'hvd'			=> 'application/vnd.yamaha.hv-dic',
		'hvp'			=> 'application/vnd.yamaha.hv-voice',
		'hvs'			=> 'application/vnd.yamaha.hv-script',
		'i2g'			=> 'application/vnd.intergeo',
		'icc'			=> 'application/vnd.iccprofile',
		'ice'			=> 'x-conference/x-cooltalk',
		'icm'			=> 'application/vnd.iccprofile',
		'ico'			=> 'image/x-icon',
		'ics'			=> 'text/calendar',
		'ief'			=> 'image/ief',
		'ifb'			=> 'text/calendar',
		'ifm'			=> 'application/vnd.shana.informed.formdata',
		'iges'			=> 'model/iges',
		'igl'			=> 'application/vnd.igloader',
		'igm'			=> 'application/vnd.insors.igm',
		'igs'			=> 'model/iges',
		'igx'			=> 'application/vnd.micrografx.igx',
		'iif'			=> 'application/vnd.shana.informed.interchange',
		'imp'			=> 'application/vnd.accpac.simply.imp',
		'ims'			=> 'application/vnd.ms-ims',
		'in'			=> 'text/plain',
		'ink'			=> 'application/inkml+xml',
		'inkml'			=> 'application/inkml+xml',
		'install'		=> 'application/x-install-instructions',
		'iota'			=> 'application/vnd.astraea-software.iota',
		'ipfix'			=> 'application/ipfix',
		'ipk'			=> 'application/vnd.shana.informed.package',
		'irm'			=> 'application/vnd.ibm.rights-management',
		'irp'			=> 'application/vnd.irepository.package+xml',
		'iso'			=> 'application/x-iso9660-image',
		'itp'			=> 'application/vnd.shana.informed.formtemplate',
		'ivp'			=> 'application/vnd.immervision-ivp',
		'ivu'			=> 'application/vnd.immervision-ivu',
		'jad'			=> 'text/vnd.sun.j2me.app-descriptor',
		'jam'			=> 'application/vnd.jam',
		'jar'			=> 'application/java-archive',
		'java'			=> 'text/x-java-source',
		'jisp'			=> 'application/vnd.jisp',
		'jlt'			=> 'application/vnd.hp-jlyt',
		'jnlp'			=> 'application/x-java-jnlp-file',
		'joda'			=> 'application/vnd.joost.joda-archive',
		'jpe'			=> 'image/jpeg',
		'jpeg'			=> 'image/jpeg',
		'jpg'			=> 'image/jpeg',
		'jpgm'			=> 'video/jpm',
		'jpgv'			=> 'video/jpeg',
		'jpm'			=> 'video/jpm',
		'js'			=> 'application/javascript',
		'json'			=> 'application/json',
		'jsonml'		=> 'application/jsonml+json',
		'kar'			=> 'audio/midi',
		'karbon'		=> 'application/vnd.kde.karbon',
		'kfo'			=> 'application/vnd.kde.kformula',
		'kia'			=> 'application/vnd.kidspiration',
		'kml'			=> 'application/vnd.google-earth.kml+xml',
		'kmz'			=> 'application/vnd.google-earth.kmz',
		'kne'			=> 'application/vnd.kinar',
		'knp'			=> 'application/vnd.kinar',
		'kon'			=> 'application/vnd.kde.kontour',
		'kpr'			=> 'application/vnd.kde.kpresenter',
		'kpt'			=> 'application/vnd.kde.kpresenter',
		'kpxx'			=> 'application/vnd.ds-keypoint',
		'ksp'			=> 'application/vnd.kde.kspread',
		'ktr'			=> 'application/vnd.kahootz',
		'ktx'			=> 'image/ktx',
		'ktz'			=> 'application/vnd.kahootz',
		'kwd'			=> 'application/vnd.kde.kword',
		'kwt'			=> 'application/vnd.kde.kword',
		'lasxml'		=> 'application/vnd.las.las+xml',
		'latex'			=> 'application/x-latex',
		'lbd'			=> 'application/vnd.llamagraphics.life-balance.desktop',
		'lbe'			=> 'application/vnd.llamagraphics.life-balance.exchange+xml',
		'les'			=> 'application/vnd.hhe.lesson-player',
		'lha'			=> 'application/x-lzh-compressed',
		'link66'		=> 'application/vnd.route66.link66+xml',
		'list'			=> 'text/plain',
		'list3820'		=> 'application/vnd.ibm.modcap',
		'listafp'		=> 'application/vnd.ibm.modcap',
		'lnk'			=> 'application/x-ms-shortcut',
		'log'			=> 'text/plain',
		'lostxml'		=> 'application/lost+xml',
		'lrf'			=> 'application/octet-stream',
		'lrm'			=> 'application/vnd.ms-lrm',
		'ltf'			=> 'application/vnd.frogans.ltf',
		'lvp'			=> 'audio/vnd.lucent.voice',
		'lwp'			=> 'application/vnd.lotus-wordpro',
		'lzh'			=> 'application/x-lzh-compressed',
		'm13'			=> 'application/x-msmediaview',
		'm14'			=> 'application/x-msmediaview',
		'm1v'			=> 'video/mpeg',
		'm21'			=> 'application/mp21',
		'm2a'			=> 'audio/mpeg',
		'm2v'			=> 'video/mpeg',
		'm3a'			=> 'audio/mpeg',
		'm3u'			=> 'audio/x-mpegurl',
		'm3u8'			=> 'application/vnd.apple.mpegurl',
		'm4a'			=> 'audio/mp4',
		'm4u'			=> 'video/vnd.mpegurl',
		'm4v'			=> 'video/x-m4v',
		'ma'			=> 'application/mathematica',
		'mads'			=> 'application/mads+xml',
		'mag'			=> 'application/vnd.ecowin.chart',
		'maker'			=> 'application/vnd.framemaker',
		'man'			=> 'text/troff',
		'mar'			=> 'application/octet-stream',
		'mathml'		=> 'application/mathml+xml',
		'mb'			=> 'application/mathematica',
		'mbk'			=> 'application/vnd.mobius.mbk',
		'mbox'			=> 'application/mbox',
		'mc1'			=> 'application/vnd.medcalcdata',
		'mcd'			=> 'application/vnd.mcd',
		'mcurl'			=> 'text/vnd.curl.mcurl',
		'mdb'			=> 'application/x-msaccess',
		'mdi'			=> 'image/vnd.ms-modi',
		'me'			=> 'text/troff',
		'mesh'			=> 'model/mesh',
		'meta4'			=> 'application/metalink4+xml',
		'metalink'		=> 'application/metalink+xml',
		'mets'			=> 'application/mets+xml',
		'mfm'			=> 'application/vnd.mfmp',
		'mft'			=> 'application/rpki-manifest',
		'mgp'			=> 'application/vnd.osgeo.mapguide.package',
		'mgz'			=> 'application/vnd.proteus.magazine',
		'mid'			=> 'audio/midi',
		'midi'			=> 'audio/midi',
		'mie'			=> 'application/x-mie',
		'mif'			=> 'application/vnd.mif',
		'mime'			=> 'message/rfc822',
		'mj2'			=> 'video/mj2',
		'mjp2'			=> 'video/mj2',
		'mk3d'			=> 'video/x-matroska',
		'mka'			=> 'audio/x-matroska',
		'mks'			=> 'video/x-matroska',
		'mkv'			=> 'video/x-matroska',
		'mlp'			=> 'application/vnd.dolby.mlp',
		'mmd'			=> 'application/vnd.chipnuts.karaoke-mmd',
		'mmf'			=> 'application/vnd.smaf',
		'mmr'			=> 'image/vnd.fujixerox.edmics-mmr',
		'mng'			=> 'video/x-mng',
		'mny'			=> 'application/x-msmoney',
		'mobi'			=> 'application/x-mobipocket-ebook',
		'mods'			=> 'application/mods+xml',
		'mov'			=> 'video/quicktime',
		'movie'			=> 'video/x-sgi-movie',
		'mp2'			=> 'audio/mpeg',
		'mp21'			=> 'application/mp21',
		'mp2a'			=> 'audio/mpeg',
		'mp3'			=> 'audio/mpeg',
		'mp4'			=> 'video/mp4',
		'mp4a'			=> 'audio/mp4',
		'mp4s'			=> 'application/mp4',
		'mp4v'			=> 'video/mp4',
		'mpc'			=> 'application/vnd.mophun.certificate',
		'mpe'			=> 'video/mpeg',
		'mpeg'			=> 'video/mpeg',
		'mpg'			=> 'video/mpeg',
		'mpg4'			=> 'video/mp4',
		'mpga'			=> 'audio/mpeg',
		'mpkg'			=> 'application/vnd.apple.installer+xml',
		'mpm'			=> 'application/vnd.blueice.multipass',
		'mpn'			=> 'application/vnd.mophun.application',
		'mpp'			=> 'application/vnd.ms-project',
		'mpt'			=> 'application/vnd.ms-project',
		'mpy'			=> 'application/vnd.ibm.minipay',
		'mqy'			=> 'application/vnd.mobius.mqy',
		'mrc'			=> 'application/marc',
		'mrcx'			=> 'application/marcxml+xml',
		'ms'			=> 'text/troff',
		'mscml'			=> 'application/mediaservercontrol+xml',
		'mseed'			=> 'application/vnd.fdsn.mseed',
		'mseq'			=> 'application/vnd.mseq',
		'msf'			=> 'application/vnd.epson.msf',
		'msh'			=> 'model/mesh',
		'msi'			=> 'application/x-msdownload',
		'msl'			=> 'application/vnd.mobius.msl',
		'msty'			=> 'application/vnd.muvee.style',
		'mts'			=> 'model/vnd.mts',
		'mus'			=> 'application/vnd.musician',
		'musicxml'		=> 'application/vnd.recordare.musicxml+xml',
		'mvb'			=> 'application/x-msmediaview',
		'mwf'			=> 'application/vnd.mfer',
		'mxf'			=> 'application/mxf',
		'mxl'			=> 'application/vnd.recordare.musicxml',
		'mxml'			=> 'application/xv+xml',
		'mxs'			=> 'application/vnd.triscape.mxs',
		'mxu'			=> 'video/vnd.mpegurl',
		'n-gage'		=> 'application/vnd.nokia.n-gage.symbian.install',
		'n3'			=> 'text/n3',
		'nb'			=> 'application/mathematica',
		'nbp'			=> 'application/vnd.wolfram.player',
		'nc'			=> 'application/x-netcdf',
		'ncx'			=> 'application/x-dtbncx+xml',
		'nfo'			=> 'text/x-nfo',
		'ngdat'			=> 'application/vnd.nokia.n-gage.data',
		'nitf'			=> 'application/vnd.nitf',
		'nlu'			=> 'application/vnd.neurolanguage.nlu',
		'nml'			=> 'application/vnd.enliven',
		'nnd'			=> 'application/vnd.noblenet-directory',
		'nns'			=> 'application/vnd.noblenet-sealer',
		'nnw'			=> 'application/vnd.noblenet-web',
		'npx'			=> 'image/vnd.net-fpx',
		'nsc'			=> 'application/x-conference',
		'nsf'			=> 'application/vnd.lotus-notes',
		'ntf'			=> 'application/vnd.nitf',
		'nzb'			=> 'application/x-nzb',
		'oa2'			=> 'application/vnd.fujitsu.oasys2',
		'oa3'			=> 'application/vnd.fujitsu.oasys3',
		'oas'			=> 'application/vnd.fujitsu.oasys',
		'obd'			=> 'application/x-msbinder',
		'obj'			=> 'application/x-tgif',
		'oda'			=> 'application/oda',
		'odb'			=> 'application/vnd.oasis.opendocument.database',
		'odc'			=> 'application/vnd.oasis.opendocument.chart',
		'odf'			=> 'application/vnd.oasis.opendocument.formula',
		'odft'			=> 'application/vnd.oasis.opendocument.formula-template',
		'odg'			=> 'application/vnd.oasis.opendocument.graphics',
		'odi'			=> 'application/vnd.oasis.opendocument.image',
		'odm'			=> 'application/vnd.oasis.opendocument.text-master',
		'odp'			=> 'application/vnd.oasis.opendocument.presentation',
		'ods'			=> 'application/vnd.oasis.opendocument.spreadsheet',
		'odt'			=> 'application/vnd.oasis.opendocument.text',
		'oga'			=> 'audio/ogg',
		'ogg'			=> 'audio/ogg',
		'ogv'			=> 'video/ogg',
		'ogx'			=> 'application/ogg',
		'omdoc'			=> 'application/omdoc+xml',
		'onepkg'		=> 'application/onenote',
		'onetmp'		=> 'application/onenote',
		'onetoc'		=> 'application/onenote',
		'onetoc2'		=> 'application/onenote',
		'opf'			=> 'application/oebps-package+xml',
		'opml'			=> 'text/x-opml',
		'oprc'			=> 'application/vnd.palm',
		'org'			=> 'application/vnd.lotus-organizer',
		'osf'			=> 'application/vnd.yamaha.openscoreformat',
		'osfpvg'		=> 'application/vnd.yamaha.openscoreformat.osfpvg+xml',
		'otc'			=> 'application/vnd.oasis.opendocument.chart-template',
		'otf'			=> 'font/otf',
		'otg'			=> 'application/vnd.oasis.opendocument.graphics-template',
		'oth'			=> 'application/vnd.oasis.opendocument.text-web',
		'oti'			=> 'application/vnd.oasis.opendocument.image-template',
		'otp'			=> 'application/vnd.oasis.opendocument.presentation-template',
		'ots'			=> 'application/vnd.oasis.opendocument.spreadsheet-template',
		'ott'			=> 'application/vnd.oasis.opendocument.text-template',
		'oxps'			=> 'application/oxps',
		'oxt'			=> 'application/vnd.openofficeorg.extension',
		'p'				=> 'text/x-pascal',
		'p10'			=> 'application/pkcs10',
		'p12'			=> 'application/x-pkcs12',
		'p7b'			=> 'application/x-pkcs7-certificates',
		'p7c'			=> 'application/pkcs7-mime',
		'p7m'			=> 'application/pkcs7-mime',
		'p7r'			=> 'application/x-pkcs7-certreqresp',
		'p7s'			=> 'application/pkcs7-signature',
		'p8'			=> 'application/pkcs8',
		'pas'			=> 'text/x-pascal',
		'paw'			=> 'application/vnd.pawaafile',
		'pbd'			=> 'application/vnd.powerbuilder6',
		'pbm'			=> 'image/x-portable-bitmap',
		'pcap'			=> 'application/vnd.tcpdump.pcap',
		'pcf'			=> 'application/x-font-pcf',
		'pcl'			=> 'application/vnd.hp-pcl',
		'pclxl'			=> 'application/vnd.hp-pclxl',
		'pct'			=> 'image/x-pict',
		'pcurl'			=> 'application/vnd.curl.pcurl',
		'pcx'			=> 'image/x-pcx',
		'pdb'			=> 'application/vnd.palm',
		'pdf'			=> 'application/pdf',
		'pfa'			=> 'application/x-font-type1',
		'pfb'			=> 'application/x-font-type1',
		'pfm'			=> 'application/x-font-type1',
		'pfr'			=> 'application/font-tdpfr',
		'pfx'			=> 'application/x-pkcs12',
		'pgm'			=> 'image/x-portable-graymap',
		'pgn'			=> 'application/x-chess-pgn',
		'pgp'			=> 'application/pgp-encrypted',
		'pic'			=> 'image/x-pict',
		'pkg'			=> 'application/octet-stream',
		'pki'			=> 'application/pkixcmp',
		'pkipath'		=> 'application/pkix-pkipath',
		'plb'			=> 'application/vnd.3gpp.pic-bw-large',
		'plc'			=> 'application/vnd.mobius.plc',
		'plf'			=> 'application/vnd.pocketlearn',
		'pls'			=> 'application/pls+xml',
		'pml'			=> 'application/vnd.ctc-posml',
		'png'			=> 'image/png',
		'pnm'			=> 'image/x-portable-anymap',
		'portpkg'		=> 'application/vnd.macports.portpkg',
		'pot'			=> 'application/vnd.ms-powerpoint',
		'potm'			=> 'application/vnd.ms-powerpoint.template.macroenabled.12',
		'potx'			=> 'application/vnd.openxmlformats-officedocument.presentationml.template',
		'ppam'			=> 'application/vnd.ms-powerpoint.addin.macroenabled.12',
		'ppd'			=> 'application/vnd.cups-ppd',
		'ppm'			=> 'image/x-portable-pixmap',
		'pps'			=> 'application/vnd.ms-powerpoint',
		'ppsm'			=> 'application/vnd.ms-powerpoint.slideshow.macroenabled.12',
		'ppsx'			=> 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
		'ppt'			=> 'application/vnd.ms-powerpoint',
		'pptm'			=> 'application/vnd.ms-powerpoint.presentation.macroenabled.12',
		'pptx'			=> 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'pqa'			=> 'application/vnd.palm',
		'prc'			=> 'application/x-mobipocket-ebook',
		'pre'			=> 'application/vnd.lotus-freelance',
		'prf'			=> 'application/pics-rules',
		'ps'			=> 'application/postscript',
		'psb'			=> 'application/vnd.3gpp.pic-bw-small',
		'psd'			=> 'image/vnd.adobe.photoshop',
		'psf'			=> 'application/x-font-linux-psf',
		'pskcxml'		=> 'application/pskc+xml',
		'ptid'			=> 'application/vnd.pvi.ptid1',
		'pub'			=> 'application/x-mspublisher',
		'pvb'			=> 'application/vnd.3gpp.pic-bw-var',
		'pwn'			=> 'application/vnd.3m.post-it-notes',
		'pya'			=> 'audio/vnd.ms-playready.media.pya',
		'pyv'			=> 'video/vnd.ms-playready.media.pyv',
		'qam'			=> 'application/vnd.epson.quickanime',
		'qbo'			=> 'application/vnd.intu.qbo',
		'qfx'			=> 'application/vnd.intu.qfx',
		'qps'			=> 'application/vnd.publishare-delta-tree',
		'qt'			=> 'video/quicktime',
		'qwd'			=> 'application/vnd.quark.quarkxpress',
		'qwt'			=> 'application/vnd.quark.quarkxpress',
		'qxb'			=> 'application/vnd.quark.quarkxpress',
		'qxd'			=> 'application/vnd.quark.quarkxpress',
		'qxl'			=> 'application/vnd.quark.quarkxpress',
		'qxt'			=> 'application/vnd.quark.quarkxpress',
		'ra'			=> 'audio/x-pn-realaudio',
		'ram'			=> 'audio/x-pn-realaudio',
		'rar'			=> 'application/x-rar-compressed',
		'ras'			=> 'image/x-cmu-raster',
		'rcprofile'		=> 'application/vnd.ipunplugged.rcprofile',
		'rdf'			=> 'application/rdf+xml',
		'rdz'			=> 'application/vnd.data-vision.rdz',
		'rep'			=> 'application/vnd.businessobjects',
		'res'			=> 'application/x-dtbresource+xml',
		'rgb'			=> 'image/x-rgb',
		'rif'			=> 'application/reginfo+xml',
		'rip'			=> 'audio/vnd.rip',
		'ris'			=> 'application/x-research-info-systems',
		'rl'			=> 'application/resource-lists+xml',
		'rlc'			=> 'image/vnd.fujixerox.edmics-rlc',
		'rld'			=> 'application/resource-lists-diff+xml',
		'rm'			=> 'application/vnd.rn-realmedia',
		'rmi'			=> 'audio/midi',
		'rmp'			=> 'audio/x-pn-realaudio-plugin',
		'rms'			=> 'application/vnd.jcp.javame.midlet-rms',
		'rmvb'			=> 'application/vnd.rn-realmedia-vbr',
		'rnc'			=> 'application/relax-ng-compact-syntax',
		'roa'			=> 'application/rpki-roa',
		'roff'			=> 'text/troff',
		'rp9'			=> 'application/vnd.cloanto.rp9',
		'rpss'			=> 'application/vnd.nokia.radio-presets',
		'rpst'			=> 'application/vnd.nokia.radio-preset',
		'rq'			=> 'application/sparql-query',
		'rs'			=> 'application/rls-services+xml',
		'rsd'			=> 'application/rsd+xml',
		'rss'			=> 'application/rss+xml',
		'rtf'			=> 'application/rtf',
		'rtx'			=> 'text/richtext',
		's'				=> 'text/x-asm',
		's3m'			=> 'audio/s3m',
		'saf'			=> 'application/vnd.yamaha.smaf-audio',
		'sbml'			=> 'application/sbml+xml',
		'sc'			=> 'application/vnd.ibm.secure-container',
		'scd'			=> 'application/x-msschedule',
		'scm'			=> 'application/vnd.lotus-screencam',
		'scq'			=> 'application/scvp-cv-request',
		'scs'			=> 'application/scvp-cv-response',
		'scurl'			=> 'text/vnd.curl.scurl',
		'sda'			=> 'application/vnd.stardivision.draw',
		'sdc'			=> 'application/vnd.stardivision.calc',
		'sdd'			=> 'application/vnd.stardivision.impress',
		'sdkd'			=> 'application/vnd.solent.sdkm+xml',
		'sdkm'			=> 'application/vnd.solent.sdkm+xml',
		'sdp'			=> 'application/sdp',
		'sdw'			=> 'application/vnd.stardivision.writer',
		'see'			=> 'application/vnd.seemail',
		'seed'			=> 'application/vnd.fdsn.seed',
		'sema'			=> 'application/vnd.sema',
		'semd'			=> 'application/vnd.semd',
		'semf'			=> 'application/vnd.semf',
		'ser'			=> 'application/java-serialized-object',
		'setpay'		=> 'application/set-payment-initiation',
		'setreg'		=> 'application/set-registration-initiation',
		'sfd-hdstx'		=> 'application/vnd.hydrostatix.sof-data',
		'sfs'			=> 'application/vnd.spotfire.sfs',
		'sfv'			=> 'text/x-sfv',
		'sgi'			=> 'image/sgi',
		'sgl'			=> 'application/vnd.stardivision.writer-global',
		'sgm'			=> 'text/sgml',
		'sgml'			=> 'text/sgml',
		'sh'			=> 'application/x-sh',
		'shar'			=> 'application/x-shar',
		'shf'			=> 'application/shf+xml',
		'sid'			=> 'image/x-mrsid-image',
		'sig'			=> 'application/pgp-signature',
		'sil'			=> 'audio/silk',
		'silo'			=> 'model/mesh',
		'sis'			=> 'application/vnd.symbian.install',
		'sisx'			=> 'application/vnd.symbian.install',
		'sit'			=> 'application/x-stuffit',
		'sitx'			=> 'application/x-stuffitx',
		'skd'			=> 'application/vnd.koan',
		'skm'			=> 'application/vnd.koan',
		'skp'			=> 'application/vnd.koan',
		'skt'			=> 'application/vnd.koan',
		'sldm'			=> 'application/vnd.ms-powerpoint.slide.macroenabled.12',
		'sldx'			=> 'application/vnd.openxmlformats-officedocument.presentationml.slide',
		'slt'			=> 'application/vnd.epson.salt',
		'sm'			=> 'application/vnd.stepmania.stepchart',
		'smf'			=> 'application/vnd.stardivision.math',
		'smi'			=> 'application/smil+xml',
		'smil'			=> 'application/smil+xml',
		'smv'			=> 'video/x-smv',
		'smzip'			=> 'application/vnd.stepmania.package',
		'snd'			=> 'audio/basic',
		'snf'			=> 'application/x-font-snf',
		'so'			=> 'application/octet-stream',
		'spc'			=> 'application/x-pkcs7-certificates',
		'spf'			=> 'application/vnd.yamaha.smaf-phrase',
		'spl'			=> 'application/x-futuresplash',
		'spot'			=> 'text/vnd.in3d.spot',
		'spp'			=> 'application/scvp-vp-response',
		'spq'			=> 'application/scvp-vp-request',
		'spx'			=> 'audio/ogg',
		'sql'			=> 'application/x-sql',
		'src'			=> 'application/x-wais-source',
		'srt'			=> 'application/x-subrip',
		'sru'			=> 'application/sru+xml',
		'srx'			=> 'application/sparql-results+xml',
		'ssdl'			=> 'application/ssdl+xml',
		'sse'			=> 'application/vnd.kodak-descriptor',
		'ssf'			=> 'application/vnd.epson.ssf',
		'ssml'			=> 'application/ssml+xml',
		'st'			=> 'application/vnd.sailingtracker.track',
		'stc'			=> 'application/vnd.sun.xml.calc.template',
		'std'			=> 'application/vnd.sun.xml.draw.template',
		'stf'			=> 'application/vnd.wt.stf',
		'sti'			=> 'application/vnd.sun.xml.impress.template',
		'stk'			=> 'application/hyperstudio',
		'stl'			=> 'application/vnd.ms-pki.stl',
		'str'			=> 'application/vnd.pg.format',
		'stw'			=> 'application/vnd.sun.xml.writer.template',
		'sub'			=> 'text/vnd.dvb.subtitle',
		'sus'			=> 'application/vnd.sus-calendar',
		'susp'			=> 'application/vnd.sus-calendar',
		'sv4cpio'		=> 'application/x-sv4cpio',
		'sv4crc'		=> 'application/x-sv4crc',
		'svc'			=> 'application/vnd.dvb.service',
		'svd'			=> 'application/vnd.svd',
		'svg'			=> 'image/svg+xml',
		'svgz'			=> 'image/svg+xml',
		'swa'			=> 'application/x-director',
		'swf'			=> 'application/x-shockwave-flash',
		'swi'			=> 'application/vnd.aristanetworks.swi',
		'sxc'			=> 'application/vnd.sun.xml.calc',
		'sxd'			=> 'application/vnd.sun.xml.draw',
		'sxg'			=> 'application/vnd.sun.xml.writer.global',
		'sxi'			=> 'application/vnd.sun.xml.impress',
		'sxm'			=> 'application/vnd.sun.xml.math',
		'sxw'			=> 'application/vnd.sun.xml.writer',
		't'				=> 'text/troff',
		't3'			=> 'application/x-t3vm-image',
		'taglet'		=> 'application/vnd.mynfc',
		'tao'			=> 'application/vnd.tao.intent-module-archive',
		'tar'			=> 'application/x-tar',
		'tcap'			=> 'application/vnd.3gpp2.tcap',
		'tcl'			=> 'application/x-tcl',
		'teacher'		=> 'application/vnd.smart.teacher',
		'tei'			=> 'application/tei+xml',
		'teicorpus'		=> 'application/tei+xml',
		'tex'			=> 'application/x-tex',
		'texi'			=> 'application/x-texinfo',
		'texinfo'		=> 'application/x-texinfo',
		'text'			=> 'text/plain',
		'tfi'			=> 'application/thraud+xml',
		'tfm'			=> 'application/x-tex-tfm',
		'tga'			=> 'image/x-tga',
		'thmx'			=> 'application/vnd.ms-officetheme',
		'tif'			=> 'image/tiff',
		'tiff'			=> 'image/tiff',
		'tmo'			=> 'application/vnd.tmobile-livetv',
		'torrent'		=> 'application/x-bittorrent',
		'tpl'			=> 'application/vnd.groove-tool-template',
		'tpt'			=> 'application/vnd.trid.tpt',
		'tr'			=> 'text/troff',
		'tra'			=> 'application/vnd.trueapp',
		'trm'			=> 'application/x-msterminal',
		'tsd'			=> 'application/timestamped-data',
		'tsv'			=> 'text/tab-separated-values',
		'ttc'			=> 'font/collection',
		'ttf'			=> 'font/ttf',
		'ttl'			=> 'text/turtle',
		'twd'			=> 'application/vnd.simtech-mindmapper',
		'twds'			=> 'application/vnd.simtech-mindmapper',
		'txd'			=> 'application/vnd.genomatix.tuxedo',
		'txf'			=> 'application/vnd.mobius.txf',
		'txt'			=> 'text/plain',
		'u32'			=> 'application/x-authorware-bin',
		'udeb'			=> 'application/x-debian-package',
		'ufd'			=> 'application/vnd.ufdl',
		'ufdl'			=> 'application/vnd.ufdl',
		'ulx'			=> 'application/x-glulx',
		'umj'			=> 'application/vnd.umajin',
		'unityweb'		=> 'application/vnd.unity',
		'uoml'			=> 'application/vnd.uoml+xml',
		'uri'			=> 'text/uri-list',
		'uris'			=> 'text/uri-list',
		'urls'			=> 'text/uri-list',
		'ustar'			=> 'application/x-ustar',
		'utz'			=> 'application/vnd.uiq.theme',
		'uu'			=> 'text/x-uuencode',
		'uva'			=> 'audio/vnd.dece.audio',
		'uvd'			=> 'application/vnd.dece.data',
		'uvf'			=> 'application/vnd.dece.data',
		'uvg'			=> 'image/vnd.dece.graphic',
		'uvh'			=> 'video/vnd.dece.hd',
		'uvi'			=> 'image/vnd.dece.graphic',
		'uvm'			=> 'video/vnd.dece.mobile',
		'uvp'			=> 'video/vnd.dece.pd',
		'uvs'			=> 'video/vnd.dece.sd',
		'uvt'			=> 'application/vnd.dece.ttml+xml',
		'uvu'			=> 'video/vnd.uvvu.mp4',
		'uvv'			=> 'video/vnd.dece.video',
		'uvva'			=> 'audio/vnd.dece.audio',
		'uvvd'			=> 'application/vnd.dece.data',
		'uvvf'			=> 'application/vnd.dece.data',
		'uvvg'			=> 'image/vnd.dece.graphic',
		'uvvh'			=> 'video/vnd.dece.hd',
		'uvvi'			=> 'image/vnd.dece.graphic',
		'uvvm'			=> 'video/vnd.dece.mobile',
		'uvvp'			=> 'video/vnd.dece.pd',
		'uvvs'			=> 'video/vnd.dece.sd',
		'uvvt'			=> 'application/vnd.dece.ttml+xml',
		'uvvu'			=> 'video/vnd.uvvu.mp4',
		'uvvv'			=> 'video/vnd.dece.video',
		'uvvx'			=> 'application/vnd.dece.unspecified',
		'uvvz'			=> 'application/vnd.dece.zip',
		'uvx'			=> 'application/vnd.dece.unspecified',
		'uvz'			=> 'application/vnd.dece.zip',
		'vcard'			=> 'text/vcard',
		'vcd'			=> 'application/x-cdlink',
		'vcf'			=> 'text/x-vcard',
		'vcg'			=> 'application/vnd.groove-vcard',
		'vcs'			=> 'text/x-vcalendar',
		'vcx'			=> 'application/vnd.vcx',
		'vis'			=> 'application/vnd.visionary',
		'viv'			=> 'video/vnd.vivo',
		'vob'			=> 'video/x-ms-vob',
		'vor'			=> 'application/vnd.stardivision.writer',
		'vox'			=> 'application/x-authorware-bin',
		'vrml'			=> 'model/vrml',
		'vsd'			=> 'application/vnd.visio',
		'vsf'			=> 'application/vnd.vsf',
		'vss'			=> 'application/vnd.visio',
		'vst'			=> 'application/vnd.visio',
		'vsw'			=> 'application/vnd.visio',
		'vtu'			=> 'model/vnd.vtu',
		'vxml'			=> 'application/voicexml+xml',
		'w3d'			=> 'application/x-director',
		'wad'			=> 'application/x-doom',
		'wav'			=> 'audio/x-wav',
		'wax'			=> 'audio/x-ms-wax',
		'wbmp'			=> 'image/vnd.wap.wbmp',
		'wbs'			=> 'application/vnd.criticaltools.wbs+xml',
		'wbxml'			=> 'application/vnd.wap.wbxml',
		'wcm'			=> 'application/vnd.ms-works',
		'wdb'			=> 'application/vnd.ms-works',
		'wdp'			=> 'image/vnd.ms-photo',
		'weba'			=> 'audio/webm',
		'webm'			=> 'video/webm',
		'webp'			=> 'image/webp',
		'wg'			=> 'application/vnd.pmi.widget',
		'wgt'			=> 'application/widget',
		'wks'			=> 'application/vnd.ms-works',
		'wm'			=> 'video/x-ms-wm',
		'wma'			=> 'audio/x-ms-wma',
		'wmd'			=> 'application/x-ms-wmd',
		'wmf'			=> 'application/x-msmetafile',
		'wml'			=> 'text/vnd.wap.wml',
		'wmlc'			=> 'application/vnd.wap.wmlc',
		'wmls'			=> 'text/vnd.wap.wmlscript',
		'wmlsc'			=> 'application/vnd.wap.wmlscriptc',
		'wmv'			=> 'video/x-ms-wmv',
		'wmx'			=> 'video/x-ms-wmx',
		'wmz'			=> 'application/x-msmetafile',
		'woff'			=> 'font/woff',
		'woff2'			=> 'font/woff2',
		'wpd'			=> 'application/vnd.wordperfect',
		'wpl'			=> 'application/vnd.ms-wpl',
		'wps'			=> 'application/vnd.ms-works',
		'wqd'			=> 'application/vnd.wqd',
		'wri'			=> 'application/x-mswrite',
		'wrl'			=> 'model/vrml',
		'wsdl'			=> 'application/wsdl+xml',
		'wspolicy'		=> 'application/wspolicy+xml',
		'wtb'			=> 'application/vnd.webturbo',
		'wvx'			=> 'video/x-ms-wvx',
		'x32'			=> 'application/x-authorware-bin',
		'x3d'			=> 'model/x3d+xml',
		'x3db'			=> 'model/x3d+binary',
		'x3dbz'			=> 'model/x3d+binary',
		'x3dv'			=> 'model/x3d+vrml',
		'x3dvz'			=> 'model/x3d+vrml',
		'x3dz'			=> 'model/x3d+xml',
		'xaml'			=> 'application/xaml+xml',
		'xap'			=> 'application/x-silverlight-app',
		'xar'			=> 'application/vnd.xara',
		'xbap'			=> 'application/x-ms-xbap',
		'xbd'			=> 'application/vnd.fujixerox.docuworks.binder',
		'xbm'			=> 'image/x-xbitmap',
		'xdf'			=> 'application/xcap-diff+xml',
		'xdm'			=> 'application/vnd.syncml.dm+xml',
		'xdp'			=> 'application/vnd.adobe.xdp+xml',
		'xdssc'			=> 'application/dssc+xml',
		'xdw'			=> 'application/vnd.fujixerox.docuworks',
		'xenc'			=> 'application/xenc+xml',
		'xer'			=> 'application/patch-ops-error+xml',
		'xfdf'			=> 'application/vnd.adobe.xfdf',
		'xfdl'			=> 'application/vnd.xfdl',
		'xht'			=> 'application/xhtml+xml',
		'xhtml'			=> 'application/xhtml+xml',
		'xhvml'			=> 'application/xv+xml',
		'xif'			=> 'image/vnd.xiff',
		'xla'			=> 'application/vnd.ms-excel',
		'xlam'			=> 'application/vnd.ms-excel.addin.macroenabled.12',
		'xlc'			=> 'application/vnd.ms-excel',
		'xlf'			=> 'application/x-xliff+xml',
		'xlm'			=> 'application/vnd.ms-excel',
		'xls'			=> 'application/vnd.ms-excel',
		'xlsb'			=> 'application/vnd.ms-excel.sheet.binary.macroenabled.12',
		'xlsm'			=> 'application/vnd.ms-excel.sheet.macroenabled.12',
		'xlsx'			=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'xlt'			=> 'application/vnd.ms-excel',
		'xltm'			=> 'application/vnd.ms-excel.template.macroenabled.12',
		'xltx'			=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
		'xlw'			=> 'application/vnd.ms-excel',
		'xm'			=> 'audio/xm',
		'xml'			=> 'application/xml',
		'xo'			=> 'application/vnd.olpc-sugar',
		'xop'			=> 'application/xop+xml',
		'xpi'			=> 'application/x-xpinstall',
		'xpl'			=> 'application/xproc+xml',
		'xpm'			=> 'image/x-xpixmap',
		'xpr'			=> 'application/vnd.is-xpr',
		'xps'			=> 'application/vnd.ms-xpsdocument',
		'xpw'			=> 'application/vnd.intercon.formnet',
		'xpx'			=> 'application/vnd.intercon.formnet',
		'xsl'			=> 'application/xml',
		'xslt'			=> 'application/xslt+xml',
		'xsm'			=> 'application/vnd.syncml+xml',
		'xspf'			=> 'application/xspf+xml',
		'xul'			=> 'application/vnd.mozilla.xul+xml',
		'xvm'			=> 'application/xv+xml',
		'xvml'			=> 'application/xv+xml',
		'xwd'			=> 'image/x-xwindowdump',
		'xyz'			=> 'chemical/x-xyz',
		'xz'			=> 'application/x-xz',
		'yang'			=> 'application/yang',
		'yin'			=> 'application/yin+xml',
		'z1'			=> 'application/x-zmachine',
		'z2'			=> 'application/x-zmachine',
		'z3'			=> 'application/x-zmachine',
		'z4'			=> 'application/x-zmachine',
		'z5'			=> 'application/x-zmachine',
		'z6'			=> 'application/x-zmachine',
		'z7'			=> 'application/x-zmachine',
		'z8'			=> 'application/x-zmachine',
		'zaz'			=> 'application/vnd.zzazz.deck+xml',
		'zip'			=> 'application/zip',
		'zir'			=> 'application/vnd.zul',
		'zirz'			=> 'application/vnd.zul',
		'zmm'			=> 'application/vnd.handheld-entertainment+xml'
	);

	/**
	 * Get the mime type based on file extension
	 * 
	 * @param   string   $file The file name or path
	 *
	 * @return  string the mime type on success
	 * 
	 */
	public static function mimeType($file)
	{
		/**
		 *                  **DISCLAIMER**
		 * This will just match the file extension to the following
		 * array. It does not guarantee that the file is TRULY that
		 * of the extension that this function returns.
		 * https://gist.github.com/Llewellynvdm/74be373357e131b8775a7582c3de508b
		 */		

		// get the extension form file
		$extension = \strtolower(\pathinfo($file, \PATHINFO_EXTENSION));
		// check if we have the extension listed
		if (isset(self::$fileExtensionToMimeType[$extension]))
		{
			return self::$fileExtensionToMimeType[$extension];
		}
		elseif (function_exists('mime_content_type'))
		{
			return mime_content_type($file);
		}
		elseif (function_exists('finfo_open'))
		{
			$finfo	= finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $file);
			finfo_close($finfo);
			return $mimetype;
		}
		return 'application/octet-stream';
	}

	/**
	 * Get the file extensions
	 * 
	 * @param   string    $target   The targeted/filter option
	 * @param   boolean   $sorted   The multidimensional grouping sort (only if targeted filter is used)
	 *
	 * @return  array     All the extensions (targeted & sorted)
	 * 
	 */
	public static function getFileExtensions($target = null, $sorted = false)
	{
		// we have some in-house grouping/filters :)
		$filters = array(
			'image' => array('image', 'font', 'model'),
			'document' => array('application', 'text', 'chemical', 'message'),
			'media' => array('video', 'audio'),
			'file' => array('image', 'application', 'text', 'video', 'audio'),
			'all' => array('application', 'text', 'chemical', 'message', 'image', 'font', 'model', 'video', 'audio', 'x-conference')
		);
		// sould we filter
		if ($target)
		{
			// the bucket to get extensions
			$fileextensions = array();
			// check if filter exist (if not return empty array)
			if (isset($filters[$target]))
			{
				foreach (self::$fileExtensionToMimeType as $extension => $mimetype)
				{
					// get the key mime type
					$mimearr = explode("/", $mimetype, 2);
					// check if this file extension should be added
					if (in_array($mimearr[0], $filters[$target]))
					{
						if ($sorted)
						{
							if (!isset($fileextensions[$mimearr[0]]))
							{
								$fileextensions[$mimearr[0]] = array();
							}
							$fileextensions[$mimearr[0]][$extension] = $extension;
						}
						else
						{
							$fileextensions[$extension] = $extension;
						}
					}
				}
			}
			return $fileextensions;
		}
		// we just return all file extensions
		return array_keys(self::$fileExtensionToMimeType);
	}

	/**
	* Write a file to the server
	*
	* @param  string   $path    The path and file name where to safe the data
	* @param  string   $data    The data to safe
	*
	* @return  bool true   On success
	*
	*/
	public static function writeFile($path, $data)
	{
		$klaar = false;
		if (self::checkString($data))
		{
			// open the file
			$fh = fopen($path, "w");
			if (!is_resource($fh))
			{
				return $klaar;
			}
			// write to the file
			if (fwrite($fh, $data))
			{
				// has been done
				$klaar = true;
			}
			// close file.
			fclose($fh);
		}
		return $klaar;
	}


	/**
	* get between
	* 
	* @param  string          $content    The content to search
	* @param  string          $start        The starting value
	* @param  string          $end         The ending value
	* @param  string          $default     The default value if none found
	*
	* @return  string          On success / empty string on failure 
	* 
	*/
	public static function getBetween($content, $start, $end, $default = '')
	{
		$r = explode($start, $content);
		if (isset($r[1]))
		{
			$r = explode($end, $r[1]);
			return $r[0];
		}
		return $default;
	}

	/**
	* get all between
	* 
	* @param  string          $content    The content to search
	* @param  string          $start        The starting value
	* @param  string          $end         The ending value
	*
	* @return  array          On success
	* 
	*/
	public static function getAllBetween($content, $start, $end)
	{
		// reset bucket
		$bucket = array();
		for ($i = 0; ; $i++)
		{
			// search for string
			$found = self::getBetween($content,$start,$end);
			if (self::checkString($found))
			{
				// add to bucket
				$bucket[] = $found;
				// build removal string
				$remove = $start.$found.$end;
				// remove from content
				$content = str_replace($remove,'',$content);
			}
			else
			{
				break;
			}
			// safety catch
			if ($i == 500)
			{
				break;
			}
		}
		// only return unique array of values
		return  array_unique($bucket);
	}


	/**
	* Returns a GUIDv4 string
	* 
	* Thanks to Dave Pearson (and other)
	* https://www.php.net/manual/en/function.com-create-guid.php#119168 
	*
	* Uses the best cryptographically secure method
	* for all supported platforms with fallback to an older,
	* less secure version.
	*
	* @param bool $trim
	* @return string
	*/
	public static function GUID ($trim = true)
	{
		// Windows
		if (function_exists('com_create_guid') === true)
		{
			if ($trim === true)
			{
				return trim(com_create_guid(), '{}');
			}
			return com_create_guid();
		}

		// set the braces if needed
		$lbrace = $trim ? "" : chr(123);    // "{"
		$rbrace = $trim ? "" : chr(125);    // "}"

		// OSX/Linux
		if (function_exists('openssl_random_pseudo_bytes') === true)
		{
			$data = openssl_random_pseudo_bytes(16);
			$data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
			$data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
			return $lbrace . vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)) . $lbrace;
		}

		// Fallback (PHP 4.2+)
		mt_srand((double)microtime() * 10000);
		$charid = strtolower(md5(uniqid(rand(), true)));
		$hyphen = chr(45);                  // "-"
		$guidv4 = $lbrace.
			substr($charid,  0,  8).$hyphen.
			substr($charid,  8,  4).$hyphen.
			substr($charid, 12,  4).$hyphen.
			substr($charid, 16,  4).$hyphen.
			substr($charid, 20, 12).
			$rbrace;
		return $guidv4;
	}

	/**
	* Validate the Globally Unique Identifier
	*
	* Thanks to Lewie
	* https://stackoverflow.com/a/1515456/1429677
	*
	* @param string $guid
	* @return bool
	*/
	public static function validGUID ($guid)
	{
		// check if we have a string
		if (self::checkString($guid))
		{
			return preg_match("/^(\{)?[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}(?(1)\})$/i", $guid);
		}
		return false;
	}


	/**
	* the Crypt objects
	**/
	protected static $CRYPT = array();

	/**
	* the Cipher MODE switcher (list of ciphers)
	**/
	protected static $setCipherMode = array(
		'AES' => true,
		'Rijndael' => true,
		'Twofish' => false, // can but not good idea
		'Blowfish' => false,  // can but not good idea
		'RC4' => false, // nope
		'RC2' => false,  // can but not good idea
		'TripleDES' => false,  // can but not good idea
		'DES' => true
	);

	/**
	* get the Crypt object
	*
	* @return  object on success with Crypt power
	**/
	public static function crypt($type, $mode = null)
	{
		// set key based on mode
		if ($mode)
		{
			$key = $type . $mode;
		}
		else
		{
			$key = $type;
		}
		// check if it was already set
		if (isset(self::$CRYPT[$key]) && self::checkObject(self::$CRYPT[$key]))
		{
			return self::$CRYPT[$key];
		}
		// make sure we have the composer classes loaded
		self::composerAutoload('phpseclib');
		// build class name
		$CLASS = '\phpseclib\Crypt\\' . $type;
		// make sure we have the phpseclib classes
		if (!class_exists($CLASS))
		{
			// class not in place so send out error
			JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_SENTINEL_THE_BSB_LIBRARYCLASS_IS_NOT_AVAILABLE_THIS_LIBRARYCLASS_SHOULD_HAVE_BEEN_ADDED_TO_YOUR_BLIBRARIESPHPSECLIBVENDORB_FOLDER_PLEASE_CONTACT_YOUR_SYSTEM_ADMINISTRATOR_FOR_MORE_INFO', $CLASS), 'Error');
			return false;
		}
		// does this crypt class use mode
		if ($mode && isset(self::$setCipherMode[$type]) && self::$setCipherMode[$type])
		{
			switch ($mode)
			{
				case 'CTR':
					self::$CRYPT[$key] = new $CLASS($CLASS::MODE_CTR);
				break;
				case 'ECB':
					self::$CRYPT[$key] = new $CLASS($CLASS::MODE_ECB);
				break;
				case 'CBC':
					self::$CRYPT[$key] = new $CLASS($CLASS::MODE_CBC);
				break;
				case 'CBC3':
					self::$CRYPT[$key] = new $CLASS($CLASS::MODE_CBC3);
				break;
				case 'CFB':
					self::$CRYPT[$key] = new $CLASS($CLASS::MODE_CFB);
				break;
				case 'CFB8':
					self::$CRYPT[$key] = new $CLASS($CLASS::MODE_CFB8);
				break;
				case 'OFB':
					self::$CRYPT[$key] = new $CLASS($CLASS::MODE_OFB);
				break;
				case 'GCM':
					self::$CRYPT[$key] = new $CLASS($CLASS::MODE_GCM);
				break;
				case 'STREAM':
					self::$CRYPT[$key] = new $CLASS($CLASS::MODE_STREAM);
				break;
				default:
					// No valid mode has been specified
					JFactory::getApplication()->enqueueMessage(JText::_('COM_SENTINEL_NO_VALID_MODE_HAS_BEEN_SPECIFIED'), 'Error');
					return false;
				break;
			}
		}
		else
		{
			// set the 
			self::$CRYPT[$key] = new $CLASS();
		}
		// return the object
		return self::$CRYPT[$key];
	}

	/**
	* the IV objects
	**/
	protected static $cipherIV = array();

	/**
	* the Cipher IV switcher (list of ciphers)
	**/
	protected static $canCipherIV = array(
		'AES' => true,
		'Rijndael' => true,
		'Twofish' => false,
		'Blowfish' => false,
		'RC4' => false,
		'RC2' => false,
		'TripleDES' => true,
		'DES' => true
	);

	/**
	* do the encryption
	*
	* @return  string the encrypted string
	**/
	public static function encrypt($string, $base64 = true, $cipher = 'AES', $password = null, $mode = 'CBC', $keyLength = 256, $derivation = 'pbkdf2')
	{
		if (($encryption = self::getCrypt($cipher, $password, $mode, $keyLength, $derivation)) !== false)
		{
			// get the IV
			$_iv_ = self::getCipherIV();
			// build the padding for IV
			if ($_iv_){ $_iv_ = bin2hex($_iv_) . '-'; } else { $_iv_ = ''; }
			// encrypted string
			$string = $_iv_ . bin2hex($encryption->encrypt($string));
			// return base64 encoded
			if ($base64)
			{
				return base64_encode($string);
			}
			return $string;
		}
		return $string;
	}

	/**
	* do the decryption
	*
	* @return  string the decrypted string
	**/
	public static function decrypt($string, $base64 = true, $cipher = 'AES', $password = null, $mode = 'CBC', $keyLength = 256, $derivation = 'pbkdf2')
	{
		if (($decryption = self::getCrypt($cipher, $password, $mode, $keyLength, $derivation)) !== false)
		{
			// base64 decode field
			if ($base64 && !is_numeric($string) && $string === base64_encode(base64_decode($string, true)))
			{
				$string =  base64_decode($string);
			}
			// check if we have an IV
			if (strpos($string, '-') !== false)
			{
				$string = explode('-', $string, 2);
				// set the IV
				self::$cipherIV[$cipher . $mode] = pack("H*" ,$string[0]);
				// set the IV
				$decryption->setIV(self::$cipherIV[$cipher . $mode]);
				// decrypt field
				return $decryption->decrypt(pack("H*" , $string[1]));
			}
			// we can't open this value so remove it
			return '';
		}
		return $string;
	}

	/**
	* get the Crypt class
	*
	* @return  object on success with crypt ability
	**/
	public static function getCrypt($cipher = 'AES', $password = null, $mode = 'CBC', $keyLength = 256, $derivation = 'pbkdf2')
	{
		// make sure we have strings
		if (!self::checkString($cipher) && isset(self::$canCipherIV[$cipher]))
		{
			JFactory::getApplication()->enqueueMessage(JText::_('COM_SENTINEL_THE_ENCRYPTION_OBJECT_COULD_NOT_BE_INSTANTIATED_PLEASE_CHECK_YOUR_BCIPHERB_SELECTION_VALUE'), 'Error');
			return false;
		}
		elseif (!self::checkString($mode))
		{
			JFactory::getApplication()->enqueueMessage(JText::_('COM_SENTINEL_THE_ENCRYPTION_OBJECT_COULD_NOT_BE_INSTANTIATED_PLEASE_CHECK_YOUR_BMODEB_SELECTION_VALUE'), 'Error');
			return false;
		}
		// make sure all is uppercase
		$cipher = strtoupper($cipher);
		$mode = strtoupper($mode);
		// make sure all is lowercase
		if ($derivation)
		{
			$derivation = strtolower($derivation);
			// set the encryption name
			$type = $cipher . '_' . $mode . '_' . $keyLength . '_' . $derivation . '_encryption';
		}
		else
		{
			// set the encryption name
			$type = $cipher . '_' . $mode . '_' . $keyLength . '_encryption';
		}
		// check if it was already set
		if (isset(self::$CRYPT[$type]))
		{
			return self::$CRYPT[$type];
		}
		// get the encryption class
		elseif (self::checkObject(self::crypt($cipher, $mode)))
		{
			// set the password
			if (!$password)
			{
				// get crypt password
				if (method_exists(__CLASS__, 'getCryptPass'))
				{
					$password = self::getCryptPass($keyLength);
				}
				// get Medium Crypt Key
				elseif (method_exists(__CLASS__, 'getMediumCryptKey'))
				{
					$password = self::getCryptKey('medium');
				}
				// if still no key was set
				if (!$password)
				{
					JFactory::getApplication()->enqueueMessage(JText::_('COM_SENTINEL_THE_ENCRYPTION_OBJECT_COULD_NOT_BE_INSTANTIATED_SINCE_BPASSWORDB_COULD_NOT_BE_FOUND'), 'Error');
					return false;
				}
			}
			// set pass based on derivation
			if ($derivation)
			{
				// get local salt
				$salt = 'vdm/phpseclib';
				if (method_exists(__CLASS__, 'getSalt'))
				{
					$salt = self::getSalt($cipher, $mode);
				}
				// get local hash type
				$hash = 'sha1';
				if (method_exists(__CLASS__, 'getHashType'))
				{
					$hash = self::getHashType($cipher, $mode);
				}
				// set the password to the cipher
				self::crypt($cipher, $mode)->setPassword($password, $derivation, $hash, $salt, 1000, (int) $keyLength / 8);
			}
			else
			{
				self::crypt($cipher, $mode)->setKeyLength($keyLength);
				self::crypt($cipher, $mode)->setKey($password);
			}
			// set the IV
			if (self::setCipherIV($cipher, $mode))
			{
				self::crypt($cipher, $mode)->setIV(self::getCipherIV($cipher, $mode));
			}
			// store class/object for reuse
			self::$CRYPT[$type] = self::crypt($cipher, $mode);
			// return the encryption class
			return self::$CRYPT[$type];
		}
		return false;
	}

	/**
	* get the cipher IV
	*
	* @return  string on success with Cipher IV
	**/
	public static function getCipherIV($cipher = 'AES', $mode = 'CBC')
	{
		// check if the IV was set
		if (self::setCipherIV($cipher, $mode))
		{
			// return set IV
			return self::$cipherIV[$cipher . $mode];
		}
		return false;
	}

	/**
	* set the cipher IV
	*
	* @return  string on success with Cipher IV
	**/
	protected static function setCipherIV($cipher = 'AES', $mode = 'CBC')
	{
		// if mode is ECB then no IV
		if ('ECB' !== $mode && self::$canCipherIV[$cipher])
		{
			// check if already set
			if (isset(self::$cipherIV[$cipher . $mode]))
			{
				return true;
			}
			// get random class
			if (($Random = self::crypt('Random')) === false)
			{
				JFactory::getApplication()->enqueueMessage(JText::_('COM_SENTINEL_THE_CIPHER_IV_COULD_NOT_BE_INSTANTIATED_SINCE_BRANDOM_CLASSB_COULD_NOT_LOAD'), 'Error');
				return false;
			}
			// set the IV
			self::$cipherIV[$cipher . $mode] = $Random::string(self::crypt($cipher, $mode)->getBlockLength() >> 3);

			return  true;
		}
		return false;
	}


	/**
	 * The secure encryption password
	 *
	 * @var  string/bool
	 **/
	protected static $cryptPass = false;

	/**
	 * Get the encryption password
	 *
	 * @param  string        $length     The key length
	 *
	 * @return  string   On success
	 *
	 **/
	public static function getCryptPass($length)
	{
		// Get the global params
		$params = JComponentHelper::getParams('com_sentinel', true);
		// check if key is already loaded.
		if (self::checkString(self::$cryptPass))
		{
			return pack("H*" ,self::$cryptPass);
		}
		// get the path to the encryption key.
		$encryption_key_path = $params->get('encryption_key_path', null);
		if (self::checkString($encryption_key_path))
		{
			// load the password from the file.
			if (self::getCryptPassFile($encryption_key_path, $length))
			{
				return pack("H*" ,self::$cryptPass);
			}
		}
		return false;
	}

	/**
	 * Get the encryption password from file
	 *
	 * @param   string    $path  The path to the  encryption crypt key folder
	 *
	 * @return  string    On success
	 *
	 **/
	protected static function getCryptPassFile($path, $length)
	{
		// Prep the path a little
		$path = '/'. trim(str_replace('//', '/', $path), '/');
		jimport('joomla.filesystem.folder');
		/// Check if folder exist
		if (!JFolder::exists($path))
		{
			// Set the error message.
			JFactory::getApplication()->enqueueMessage(JText::_('COM_SENTINEL_ENCRYPTION_PASSWORD_PATH_DOES_NOT_EXIST'), 'Error');
			return false;
		}
		// Create FileName and set file path
		$filePath = $path . '/.' . md5('encryption_crypt_key_file' . JURI::root() . JPATH_ROOT);
		// Check if we already have the file set
		if ((self::$cryptPass = @file_get_contents($filePath)) !== FALSE)
		{
			return true;
		}
		// Set the key for the first time
		if (($Random = self::crypt('Random')) !== false)
		{
			self::$cryptPass = bin2hex($Random::string($length));
		}
		else
		{
			self::$cryptPass = self::randomkey($length);
		}
		// Open the key file
		$fh = @fopen($filePath, 'w');
		if (!is_resource($fh))
		{
			// Lock key.
			self::$cryptPass = false;
			// Set the error message.
			JFactory::getApplication()->enqueueMessage(JText::_('COM_SENTINEL_COULD_NOT_OPEN_TO_THE_ENCRYPTION_PASSWORD_FILE'), 'Error');
			return false;
		}
		// Write to the key file
		if (!fwrite($fh, self::$cryptPass))
		{
			// Close key file.
			fclose($fh);
			// Lock key.
			self::$cryptPass = false;
			// Set the error message.
			JFactory::getApplication()->enqueueMessage(JText::_('COM_SENTINEL_COULD_NOT_WRITE_TO_THE_ENCRYPTION_PASSWORD_PATH'), 'Error');
			return false;
		}
		// Close key file.
		fclose($fh);
		// Key is set.
		return true;
	}


	/**
	* Composer Switch
	**/
	protected static $composer = array(); 

	/**
	* Load the Composer Vendors
	**/
	public static function composerAutoload($target)
	{
		// insure we load the composer vendor only once
		if (!isset(self::$composer[$target]))
		{
			// get the function name
			$functionName = self::safeString('compose' . $target);
			// check if method exist
			if (method_exists(__CLASS__, $functionName))
			{
				return self::{$functionName}();
			}
			return false;
		}
		return self::$composer[$target];
	}


	/**
	* Load the Composer Vendor phpseclib
	**/
	protected static function composephpseclib()
	{
		// load the autoloader for phpseclib
		require_once JPATH_SITE . '/libraries/phpseclib/vendor/autoload.php';
		// do not load again
		self::$composer['phpseclib'] = true;

		return  true;
	}


	/**
	 * Get/load the component helper class if not already loaded
	 *
	 * @param   string   $_component    The component element name
	 *
	 * @return string   The helper class name
	 *
	 */
	public static function getHelperClass($_component)
	{
		// make sure we have com_
		if (strpos($_component, 'com_') !== false)
		{
			// get component name
			$component = str_replace('com_', '', $_component);
		}
		else
		{
			// get the component name
			$component = $_component;
			// set the element name
			$_component = 'com_' . $component;
		}
		// build component helper name
		$componentHelper = self::safeString($component, 'F') . 'Helper';
		// check if it is already set
		if (!class_exists($componentHelper))
		{
			// set the correct path focus
			$focus = JPATH_ADMINISTRATOR;
			// check if we are in the site area
			if (JFactory::getApplication()->isClient('site'))
			{
				// set admin path
				$adminPath = $focus . '/components/' . $_component . '/helpers/' . $component . '.php';
				// change the focus
				$focus = JPATH_ROOT;
			}
			// set path based on focus
			$path = $focus . '/components/' . $_component . '/helpers/' . $component . '.php';
			// check if file exist, if not try admin again.
			if (file_exists($path))
			{
				// make sure to load the helper
				JLoader::register($componentHelper, $path);
			}
			// fallback option
			elseif (isset($adminPath) && file_exists($adminPath))
			{
				// make sure to load the helper
				JLoader::register($componentHelper, $adminPath);
			}
			else
			{
				// could not find this
				return false;
			}
		}
		// success
		return $componentHelper;
	}


	/**
	 * Can a member access another member's data
	 *
	 * @param   mix      $member    The the member being accessed
	 *                                 To do a dynamic get of member ID use the following array
	 *                                 array( table, where, whereString, what)
	 * @param   array    $types     The type of member being accessed
	 * @param   mix      $user      The active user
	 * @param   object   $db        The database object
	 *
	 * @return  bool true of can access
	 *
	 */
	public static function canAccessMember($member = null, $types = null, $user = null, $db = null)
	{
		// if member is array then we get member id dynamically
		if (self::checkArray($member) && isset($member['table']) && isset($member['where'])
			&& isset($member['whereString']) && isset($member['what']))
		{
			// if this is a new item being created (creation is not handle here, we just validate access to existing member data)
			if (!is_numeric($member['where']) || $member['where'] == 0)
			{
				return true;
			}
			elseif (($member = self::getVar($member['table'], $member['where'], $member['whereString'], $member['what'])) === false)
			{
				// if the member can not be found, then no access. (secure option)
				return false;
			}
		}
		// get the membersmanager helper class
		if (($helperClass = self::getHelperClass('membersmanager')) !== false && method_exists($helperClass, 'canAccessMember'))
		{
			return $helperClass::canAccessMember($member, $types, $user, $db);
		}
		return false;
	}


	// <<<=== Privacy integration with Joomla Privacy suite ===>>>

	/**
	 * Performs validation to determine if the data associated with a remove information request can be processed
	 *
	 * @param   PrivacyPlugin  $plugin  The plugin being processed
	 * @param   PrivacyRemovalStatus  $status  The status being set
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  PrivacyRemovalStatus
	 */
	public static function onPrivacyCanRemoveData(&$plugin, &$status, &$request, &$user)
	{
		// Bucket to get all reasons why removal not allowed
		$reasons = array();
		// Check if user has permission to delete Forms
		if (!$user->authorise('form.delete', 'com_sentinel') && !$user->authorise('form.privacy.delete', 'com_sentinel'))
		{
			$reasons[] = JText::_('COM_SENTINEL_PRIVACY_CANT_REMOVE_FORMS');
		}
		// Check if any reasons were found not to allow removal
		if (self::checkArray($reasons))
		{
			$status->canRemove = false;
			$status->reason = implode(' ' . PHP_EOL, $reasons) . ' ' . PHP_EOL . JText::_('COM_SENTINEL_PRIVACY_CANT_REMOVE_CONTACT_SUPPORT');
		}
		return $status;
	}

	/**
	 * Processes an export request for Joomla core user data
	 *
	 * @param   PrivacyPlugin  $plugin  The plugin being processed
	 * @param   DomainArray  $domains  The array of domains
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  PrivacyExportDomain[]
	 */
	public static function onPrivacyExportRequest(&$plugin, &$domains, &$request, &$user)
	{
		// Check if user has permission to access Forms
		if ($user->authorise('form.access', 'com_sentinel') || $user->authorise('form.privacy.access', 'com_sentinel'))
		{
			// Get Form domain
			$domains[] = self::createFormsDomain($plugin, $user);
		}
		return $domains;
	}

	/**
	 * Create the domain for the Form
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 */
	protected static function createFormsDomain(&$plugin, &$user)
	{
		// create Forms domain
		$domain = self::createDomain('form', 'sentinel_form_data');
		// get database object
		$db = JFactory::getDbo();
		// get all item ids of Forms that belong to this user
		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__sentinel_form'));
		if (($member_id = self::getVar('member', $user->id, 'user', 'id', '=', 'membersmanager')) !== false && is_numeric($member_id) && $member_id > 0)
		{
			$query->where($db->quoteName('member') . ' = ' . (int) $member_id);
		}
		else
		{
			$query->where($db->quoteName('member') . ' = -2'); // return none
		}
		// get all items for the Forms domain
		$pks = $db->setQuery($query)->loadColumn();
		// get the Forms model
		$model = self::getModel('forms', JPATH_ADMINISTRATOR . '/components/com_sentinel');
		// Get all item details of Forms that belong to this user
		$items = $model->getPrivacyExport($pks, $user);
		// check if we have items since permissions could block the request
		if (self::checkArray($items))
		{
			// Remove Form default columns
			foreach (array('params', 'asset_id', 'checked_out', 'checked_out_time', 'created', 'created_by', 'modified', 'modified_by', 'published', 'ordering', 'access', 'version', 'hits') as $column)
			{
				$items = ArrayHelper::dropColumn($items, $column);
			}
			// load the items into the domain object
			foreach ($items as $item)
			{
				$domain->addItem(self::createItemFromArray($item, $item['id']));
			}
		}
		return $domain;
	}

	/**
	 * Create a new domain object
	 *
	 * @param   string  $name         The domain's name
	 * @param   string  $description  The domain's description
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since   3.9.0
	 */
	protected static function createDomain($name, $description = '')
	{
		$domain              = new PrivacyExportDomain;
		$domain->name        = $name;
		$domain->description = $description;

		return $domain;
	}

	/**
	 * Create an item object for an array
	 *
	 * @param   array         $data    The array data to convert
	 * @param   integer|null  $itemId  The ID of this item
	 *
	 * @return  PrivacyExportItem
	 *
	 * @since   3.9.0
	 */
	protected static function createItemFromArray(array $data, $itemId = null)
	{
		$item = new PrivacyExportItem;
		$item->id = $itemId;

		foreach ($data as $key => $value)
		{
			if (is_object($value))
			{
				$value = (array) $value;
			}

			if (is_array($value))
			{
				$value = print_r($value, true);
			}

			$field        = new PrivacyExportField;
			$field->name  = $key;
			$field->value = $value;

			$item->addField($field);
		}

		return $item;
	}

	/**
	 * Removes the data associated with a remove information request
	 *
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  void
	 */
	public static function onPrivacyRemoveData(&$plugin, &$request, &$user)
	{
		// Check if user has permission to delet Forms
		if ($user->authorise('form.delete', 'com_sentinel') || $user->authorise('form.privacy.delete', 'com_sentinel'))
		{
			// Anonymize Form data
			self::anonymizeFormsData($plugin, $user);
		}
	}

	/**
	 * Anonymize the Form data
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  void
	 */
	protected static function anonymizeFormsData(&$plugin, &$user)
	{
		// get database object
		$db = JFactory::getDbo();
		// get all item ids of Forms that belong to this user
		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__sentinel_form'));
		if (($member_id = self::getVar('member', $user->id, 'user', 'id', '=', 'membersmanager')) !== false && is_numeric($member_id) && $member_id > 0)
		{
			$query->where($db->quoteName('member') . ' = ' . (int) $member_id);
		}
		else
		{
			$query->where($db->quoteName('member') . ' = -2'); // return none
		}
		// get all items for the Forms table that belong to this user
		$pks = $db->setQuery($query)->loadColumn();

		if (self::checkArray($pks))
		{
			// get the form model
			$model = self::getModel('form', JPATH_ADMINISTRATOR . '/components/com_sentinel');
			// this is the pseudoanonymised data array for Forms
			$pseudoanonymisedData = array(
				'member' => '0'
			);

			// Get global permissional control activation. (default is inactive)
			$strict_permission_per_field = JComponentHelper::getParams('com_sentinel')->get('strict_permission_per_field', 0);
			if($strict_permission_per_field)
			{
				// remove all fields that is not permitted to be changed
				if (!$user->authorise('form.edit.member', 'com_sentinel') || !$user->authorise('form.access.member', 'com_sentinel') || !$user->authorise('form.view.member', 'com_sentinel'))
				{
					unset($pseudoanonymisedData['member']);
				}
			}
			// get the Forms table
			$table = $model->getTable();
			// check that we still have pseudoanonymised data for Forms set
			if (!self::checkArray($pseudoanonymisedData))
			{
				// still archive all items
				$table->publish($pks, 2);
				return false;
			}
			// Iterate the items to anonimize each one.
			foreach ($pks as $i => $pk)
			{
				$table->reset();
				$pseudoanonymisedData['id'] = $pk;

				if ($table->bind($pseudoanonymisedData))
				{
					$table->store();
				}
			}
			// archive all items
			$table->publish($pks, 2);
		}
	}


	public static function jsonToString($value, $sperator = ", ", $table = null, $id = 'id', $name = 'name')
	{
		// do some table foot work
		$external = false;
		if (strpos($table, '#__') !== false)
		{
			$external = true;
			$table = str_replace('#__', '', $table);
		}
		// check if string is JSON
		$result = json_decode($value, true);
		if (json_last_error() === JSON_ERROR_NONE)
		{
			// is JSON
			if (self::checkArray($result))
			{
				if (self::checkString($table))
				{
					$names = array();
					foreach ($result as $val)
					{
						if ($external)
						{
							if ($_name = self::getVar(null, $val, $id, $name, '=', $table))
							{
								$names[] = $_name;
							}
						}
						else
						{
							if ($_name = self::getVar($table, $val, $id, $name))
							{
								$names[] = $_name;
							}
						}
					}
					if (self::checkArray($names))
					{
						return (string) implode($sperator,$names);
					}	
				}
				return (string) implode($sperator,$result);
			}
			return (string) json_decode($value);
		}
		return $value;
	}

	/**
	* Load the Component xml manifest.
	**/
	public static function manifest()
	{
		$manifestUrl = JPATH_ADMINISTRATOR."/components/com_sentinel/sentinel.xml";
		return simplexml_load_file($manifestUrl);
	}

	/**
	* Joomla version object
	**/	
	protected static $JVersion;

	/**
	* set/get Joomla version
	**/
	public static function jVersion()
	{
		// check if set
		if (!self::checkObject(self::$JVersion))
		{
			self::$JVersion = new JVersion();
		}
		return self::$JVersion;
	}

	/**
	* Load the Contributors details.
	**/
	public static function getContributors()
	{
		// get params
		$params	= JComponentHelper::getParams('com_sentinel');
		// start contributors array
		$contributors = array();
		// get all Contributors (max 20)
		$searchArray = range('0','20');
		foreach($searchArray as $nr)
		{
			if ((NULL !== $params->get("showContributor".$nr)) && ($params->get("showContributor".$nr) == 2 || $params->get("showContributor".$nr) == 3))
			{
				// set link based of selected option
				if($params->get("useContributor".$nr) == 1)
                                {
					$link_front = '<a href="mailto:'.$params->get("emailContributor".$nr).'" target="_blank">';
					$link_back = '</a>';
				}
                                elseif($params->get("useContributor".$nr) == 2)
                                {
					$link_front = '<a href="'.$params->get("linkContributor".$nr).'" target="_blank">';
					$link_back = '</a>';
				}
                                else
                                {
					$link_front = '';
					$link_back = '';
				}
				$contributors[$nr]['title']	= self::htmlEscape($params->get("titleContributor".$nr));
				$contributors[$nr]['name']	= $link_front.self::htmlEscape($params->get("nameContributor".$nr)).$link_back;
			}
		}
		return $contributors;
	}

	/**
	 *	Can be used to build help urls.
	 **/
	public static function getHelpUrl($view)
	{
		return false;
	}

	/**
	* Get any component's model
	**/
	public static function getModel($name, $path = JPATH_COMPONENT_SITE, $Component = 'Sentinel', $config = array())
	{
		// fix the name
		$name = self::safeString($name);
		// full path to models
		$fullPathModels = $path . '/models';
		// load the model file
		JModelLegacy::addIncludePath($fullPathModels, $Component . 'Model');
		// make sure the table path is loaded
		if (!isset($config['table_path']) || !self::checkString($config['table_path']))
		{
			// This is the JCB default path to tables in Joomla 3.x
			$config['table_path'] = JPATH_ADMINISTRATOR . '/components/com_' . strtolower($Component) . '/tables';
		}
		// get instance
		$model = JModelLegacy::getInstance($name, $Component . 'Model', $config);
		// if model not found (strange)
		if ($model == false)
		{
			jimport('joomla.filesystem.file');
			// get file path
			$filePath = $path . '/' . $name . '.php';
			$fullPathModel = $fullPathModels . '/' . $name . '.php';
			// check if it exists
			if (JFile::exists($filePath))
			{
				// get the file
				require_once $filePath;
			}
			elseif (JFile::exists($fullPathModel))
			{
				// get the file
				require_once $fullPathModel;
			}
			// build class names
			$modelClass = $Component . 'Model' . $name;
			if (class_exists($modelClass))
			{
				// initialize the model
				return new $modelClass($config);
			}
		}
		return $model;
	}

	/**
	* Add to asset Table
	*/
	public static function setAsset($id, $table, $inherit = true)
	{
		$parent = JTable::getInstance('Asset');
		$parent->loadByName('com_sentinel');
		
		$parentId = $parent->id;
		$name     = 'com_sentinel.'.$table.'.'.$id;
		$title    = '';

		$asset = JTable::getInstance('Asset');
		$asset->loadByName($name);

		// Check for an error.
		$error = $asset->getError();

		if ($error)
		{
			return false;
		}
		else
		{
			// Specify how a new or moved node asset is inserted into the tree.
			if ($asset->parent_id != $parentId)
			{
				$asset->setLocation($parentId, 'last-child');
			}

			// Prepare the asset to be stored.
			$asset->parent_id = $parentId;
			$asset->name      = $name;
			$asset->title     = $title;
			// get the default asset rules
			$rules = self::getDefaultAssetRules('com_sentinel', $table, $inherit);
			if ($rules instanceof JAccessRules)
			{
				$asset->rules = (string) $rules;
			}

			if (!$asset->check() || !$asset->store())
			{
				JFactory::getApplication()->enqueueMessage($asset->getError(), 'warning');
				return false;
			}
			else
			{
				// Create an asset_id or heal one that is corrupted.
				$object = new stdClass();

				// Must be a valid primary key value.
				$object->id = $id;
				$object->asset_id = (int) $asset->id;

				// Update their asset_id to link to the asset table.
				return JFactory::getDbo()->updateObject('#__sentinel_'.$table, $object, 'id');
			}
		}
		return false;
	}

	/**
	 * Gets the default asset Rules for a component/view.
	 */
	protected static function getDefaultAssetRules($component, $view, $inherit = true)
	{
		// if new or inherited
		$assetId = 0;
		// Only get the actual item rules if not inheriting
		if (!$inherit)
		{
			// Need to find the asset id by the name of the component.
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__assets'))
				->where($db->quoteName('name') . ' = ' . $db->quote($component));
			$db->setQuery($query);
			$db->execute();
			// check that there is a value
			if ($db->getNumRows())
			{
				// asset already set so use saved rules
				$assetId = (int) $db->loadResult();
			}
		}
		// get asset rules
		$result =  JAccess::getAssetRules($assetId);
		if ($result instanceof JAccessRules)
		{
			$_result = (string) $result;
			$_result = json_decode($_result);
			foreach ($_result as $name => &$rule)
			{
				$v = explode('.', $name);
				if ($view !== $v[0])
				{
					// remove since it is not part of this view
					unset($_result->$name);
				}
				elseif ($inherit)
				{
					// clear the value since we inherit
					$rule = array();
				}
			}
			// check if there are any view values remaining
			if (count((array) $_result))
			{
				$_result = json_encode($_result);
				$_result = array($_result);
				// Instantiate and return the JAccessRules object for the asset rules.
				$rules = new JAccessRules($_result);
				// return filtered rules
				return $rules;
			}
		}
		return $result;
	}

	/**
	 * xmlAppend
	 *
	 * @param   SimpleXMLElement   $xml      The XML element reference in which to inject a comment
	 * @param   mixed              $node     A SimpleXMLElement node to append to the XML element reference, or a stdClass object containing a comment attribute to be injected before the XML node and a fieldXML attribute containing a SimpleXMLElement
	 *
	 * @return  null
	 *
	 */
	public static function xmlAppend(&$xml, $node)
	{
		if (!$node)
		{
			// element was not returned
			return;
		}
		switch (get_class($node))
		{
			case 'stdClass':
				if (property_exists($node, 'comment'))
				{
					self::xmlComment($xml, $node->comment);
				}
				if (property_exists($node, 'fieldXML'))
				{
					self::xmlAppend($xml, $node->fieldXML);
				}
				break;
			case 'SimpleXMLElement':
				$domXML = dom_import_simplexml($xml);
				$domNode = dom_import_simplexml($node);
				$domXML->appendChild($domXML->ownerDocument->importNode($domNode, true));
				$xml = simplexml_import_dom($domXML);
				break;
		}
	}

	/**
	 * xmlComment
	 *
	 * @param   SimpleXMLElement   $xml        The XML element reference in which to inject a comment
	 * @param   string             $comment    The comment to inject
	 *
	 * @return  null
	 *
	 */
	public static function xmlComment(&$xml, $comment)
	{
		$domXML = dom_import_simplexml($xml);
		$domComment = new DOMComment($comment);
		$nodeTarget = $domXML->ownerDocument->importNode($domComment, true);
		$domXML->appendChild($nodeTarget);
		$xml = simplexml_import_dom($domXML);
	}

	/**
	 * xmlAddAttributes
	 *
	 * @param   SimpleXMLElement   $xml          The XML element reference in which to inject a comment
	 * @param   array              $attributes   The attributes to apply to the XML element
	 *
	 * @return  null
	 *
	 */
	public static function xmlAddAttributes(&$xml, $attributes = array())
	{
		foreach ($attributes as $key => $value)
		{
			$xml->addAttribute($key, $value);
		}
	}

	/**
	 * xmlAddOptions
	 *
	 * @param   SimpleXMLElement   $xml          The XML element reference in which to inject a comment
	 * @param   array              $options      The options to apply to the XML element
	 *
	 * @return  void
	 *
	 */
	public static function xmlAddOptions(&$xml, $options = array())
	{
		foreach ($options as $key => $value)
		{
			$addOption = $xml->addChild('option');
			$addOption->addAttribute('value', $key);
			$addOption[] = $value;
		}
	}

	/**
	 * get the field object
	 *
	 * @param   array      $attributes   The array of attributes
	 * @param   string     $default      The default of the field
	 * @param   array      $options      The options to apply to the XML element
	 *
	 * @return  object
	 *
	 */
	public static function getFieldObject(&$attributes, $default = '', $options = null)
	{
		// make sure we have attributes and a type value
		if (self::checkArray($attributes) && isset($attributes['type']))
		{
			// make sure the form helper class is loaded
			if (!method_exists('JFormHelper', 'loadFieldType'))
			{
				jimport('joomla.form.form');
			}
			// get field type
			$field = JFormHelper::loadFieldType($attributes['type'], true);
			// get field xml
			$XML = self::getFieldXML($attributes, $options);
			// setup the field
			$field->setup($XML, $default);
			// return the field object
			return $field;
		}
		return false;
	}

	/**
	 * get the field xml
	 *
	 * @param   array      $attributes   The array of attributes
	 * @param   array      $options      The options to apply to the XML element
	 *
	 * @return  object
	 *
	 */
	public static function getFieldXML(&$attributes, $options = null)
	{
		// make sure we have attributes and a type value
		if (self::checkArray($attributes))
		{
			// start field xml
			$XML = new SimpleXMLElement('<field/>');
			// load the attributes
			self::xmlAddAttributes($XML, $attributes);
			// check if we have options
			if (self::checkArray($options))
			{
				// load the options
				self::xmlAddOptions($XML, $options);
			}
			// return the field xml
			return $XML;
		}
		return false;
	}

	/**
	 * Render Bool Button
	 *
	 * @param   array   $args   All the args for the button
	 *                             0) name
	 *                             1) additional (options class) // not used at this time
	 *                             2) default
	 *                             3) yes (name)
	 *                             4) no (name)
	 *
	 * @return  string    The input html of the button
	 *
	 */
	public static function renderBoolButton()
	{
		$args = func_get_args();
		// check if there is additional button class
		$additional = isset($args[1]) ? (string) $args[1] : ''; // not used at this time
		// button attributes
		$buttonAttributes = array(
			'type' => 'radio',
			'name' => isset($args[0]) ? self::htmlEscape($args[0]) : 'bool_button',
			'label' => isset($args[0]) ? self::safeString(self::htmlEscape($args[0]), 'Ww') : 'Bool Button', // not seen anyway
			'class' => 'btn-group',
			'filter' => 'INT',
			'default' => isset($args[2]) ? (int) $args[2] : 0);
		// set the button options
		$buttonOptions = array(
			'1' => isset($args[3]) ? self::htmlEscape($args[3]) : 'JYES',
			'0' => isset($args[4]) ? self::htmlEscape($args[4]) : 'JNO');
		// return the input
		return self::getFieldObject($buttonAttributes, $buttonAttributes['default'], $buttonOptions)->input;
	}

	/**
	 *  UIKIT Component Classes
	 **/
	public static $uk_components = array(
			'data-uk-grid' => array(
				'grid' ),
			'uk-accordion' => array(
				'accordion' ),
			'uk-autocomplete' => array(
				'autocomplete' ),
			'data-uk-datepicker' => array(
				'datepicker' ),
			'uk-form-password' => array(
				'form-password' ),
			'uk-form-select' => array(
				'form-select' ),
			'data-uk-htmleditor' => array(
				'htmleditor' ),
			'data-uk-lightbox' => array(
				'lightbox' ),
			'uk-nestable' => array(
				'nestable' ),
			'UIkit.notify' => array(
				'notify' ),
			'data-uk-parallax' => array(
				'parallax' ),
			'uk-search' => array(
				'search' ),
			'uk-slider' => array(
				'slider' ),
			'uk-slideset' => array(
				'slideset' ),
			'uk-slideshow' => array(
				'slideshow',
				'slideshow-fx' ),
			'uk-sortable' => array(
				'sortable' ),
			'data-uk-sticky' => array(
				'sticky' ),
			'data-uk-timepicker' => array(
				'timepicker' ),
			'data-uk-tooltip' => array(
				'tooltip' ),
			'uk-placeholder' => array(
				'placeholder' ),
			'uk-dotnav' => array(
				'dotnav' ),
			'uk-slidenav' => array(
				'slidenav' ),
			'uk-form' => array(
				'form-advanced' ),
			'uk-progress' => array(
				'progress' ),
			'upload-drop' => array(
				'upload', 'form-file' )
			);

	/**
	 *  Add UIKIT Components
	 **/
	public static $uikit = false;

	/**
	 *  Get UIKIT Components
	 **/
	public static function getUikitComp($content,$classes = array())
	{
		if (strpos($content,'class="uk-') !== false)
		{
			// reset
			$temp = array();
			foreach (self::$uk_components as $looking => $add)
			{
				if (strpos($content,$looking) !== false)
				{
					$temp[] = $looking;
				}
			}
			// make sure uikit is loaded to config
			if (strpos($content,'class="uk-') !== false)
			{
				self::$uikit = true;
			}
			// sorter
			if (self::checkArray($temp))
			{
				// merger
				if (self::checkArray($classes))
				{
					$newTemp = array_merge($temp,$classes);
					$temp = array_unique($newTemp);
				}
				return $temp;
			}
		}
		if (self::checkArray($classes))
		{
			return $classes;
		}
		return false;
	}

	/**
	 * Get a variable 
	 *
	 * @param   string   $table        The table from which to get the variable
	 * @param   string   $where        The value where
	 * @param   string   $whereString  The target/field string where/name
	 * @param   string   $what         The return field
	 * @param   string   $operator     The operator between $whereString/field and $where/value
	 * @param   string   $main         The component in which the table is found
	 *
	 * @return  mix string/int/float
	 *
	 */
	public static function getVar($table, $where = null, $whereString = 'user', $what = 'id', $operator = '=', $main = 'sentinel')
	{
		if(!$where)
		{
			$where = JFactory::getUser()->id;
		}
		// Get a db connection.
		$db = JFactory::getDbo();
		// Create a new query object.
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array($what)));		
		if (empty($table))
		{
			$query->from($db->quoteName('#__'.$main));
		}
		else
		{
			$query->from($db->quoteName('#__'.$main.'_'.$table));
		}
		if (is_numeric($where))
		{
			$query->where($db->quoteName($whereString) . ' '.$operator.' '.(int) $where);
		}
		elseif (is_string($where))
		{
			$query->where($db->quoteName($whereString) . ' '.$operator.' '. $db->quote((string)$where));
		}
		else
		{
			return false;
		}
		$db->setQuery($query);
		$db->execute();
		if ($db->getNumRows())
		{
			return $db->loadResult();
		}
		return false;
	}

	/**
	 * Get array of variables
	 *
	 * @param   string   $table        The table from which to get the variables
	 * @param   string   $where        The value where
	 * @param   string   $whereString  The target/field string where/name
	 * @param   string   $what         The return field
	 * @param   string   $operator     The operator between $whereString/field and $where/value
	 * @param   string   $main         The component in which the table is found
	 * @param   bool     $unique       The switch to return a unique array
	 *
	 * @return  array
	 *
	 */
	public static function getVars($table, $where = null, $whereString = 'user', $what = 'id', $operator = 'IN', $main = 'sentinel', $unique = true)
	{
		if(!$where)
		{
			$where = JFactory::getUser()->id;
		}

		if (!self::checkArray($where) && $where > 0)
		{
			$where = array($where);
		}

		if (self::checkArray($where))
		{
			// prep main <-- why? well if $main='' is empty then $table can be categories or users
			if (self::checkString($main))
			{
				$main = '_'.ltrim($main, '_');
			}
			// Get a db connection.
			$db = JFactory::getDbo();
			// Create a new query object.
			$query = $db->getQuery(true);

			$query->select($db->quoteName(array($what)));
			if (empty($table))
			{
				$query->from($db->quoteName('#__'.$main));
			}
			else
			{
				$query->from($db->quoteName('#_'.$main.'_'.$table));
			}
			// add strings to array search
			if ('IN_STRINGS' === $operator || 'NOT IN_STRINGS' === $operator)
			{
				$query->where($db->quoteName($whereString) . ' ' . str_replace('_STRINGS', '', $operator) . ' ("' . implode('","',$where) . '")');
			}
			else
			{
				$query->where($db->quoteName($whereString) . ' ' . $operator . ' (' . implode(',',$where) . ')');
			}
			$db->setQuery($query);
			$db->execute();
			if ($db->getNumRows())
			{
				if ($unique)
				{
					return array_unique($db->loadColumn());
				}
				return $db->loadColumn();
			}
		}
		return false;
	} 

	public static function isPublished($id,$type)
	{
		if ($type == 'raw')
		{
			$type = 'item';
		}
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select(array('a.published'));
		$query->from('#__sentinel_'.$type.' AS a');
		$query->where('a.id = '. (int) $id);
		$query->where('a.published = 1');
		$db->setQuery($query);
		$db->execute();
		$found = $db->getNumRows();
		if($found)
		{
			return true;
		}
		return false;
	}

	public static function getGroupName($id)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select(array('a.title'));
		$query->from('#__usergroups AS a');
		$query->where('a.id = '. (int) $id);
		$db->setQuery($query);
		$db->execute();
		$found = $db->getNumRows();
		if($found)
		{
			return $db->loadResult();
		}
		return $id;
	}

	/**
	* Get the action permissions
	*
	* @param  string   $view        The related view name
	* @param  int      $record      The item to act upon
	* @param  string   $views       The related list view name
	* @param  mixed    $target      Only get this permission (like edit, create, delete)
	* @param  string   $component   The target component
	* @param  object   $user        The user whose permissions we are loading
	*
	* @return  object   The JObject of permission/authorised actions
	* 
	**/
	public static function getActions($view, &$record = null, $views = null, $target = null, $component = 'sentinel', $user = 'null')
	{
		// load the user if not given
		if (!self::checkObject($user))
		{
			// get the user object
			$user = JFactory::getUser();
		}
		// load the JObject
		$result = new JObject;
		// make view name safe (just incase)
		$view = self::safeString($view);
		if (self::checkString($views))
		{
			$views = self::safeString($views);
 		}
		// get all actions from component
		$actions = JAccess::getActionsFromFile(
			JPATH_ADMINISTRATOR . '/components/com_' . $component . '/access.xml',
			"/access/section[@name='component']/"
		);
		// if non found then return empty JObject
		if (empty($actions))
		{
			return $result;
		}
		// get created by if not found
		if (self::checkObject($record) && !isset($record->created_by) && isset($record->id))
		{
			$record->created_by = self::getVar($view, $record->id, 'id', 'created_by', '=', $component);
		}
		// set actions only set in component settings
		$componentActions = array('core.admin', 'core.manage', 'core.options', 'core.export');
		// check if we have a target
		$checkTarget = false;
		if ($target)
		{
			// convert to an array
			if (self::checkString($target))
			{
				$target = array($target);
			}
			// check if we are good to go
			if (self::checkArray($target))
			{
				$checkTarget = true;
			}
		}
		// loop the actions and set the permissions
		foreach ($actions as $action)
		{
			// check target action filter
			if ($checkTarget && self::filterActions($view, $action->name, $target))
			{
				continue;
			}
			// set to use component default
			$fallback = true;
			// reset permission per/action
			$permission = false;
			$catpermission = false;
			// set area
			$area = 'comp';
			// check if the record has an ID and the action is item related (not a component action)
			if (self::checkObject($record) && isset($record->id) && $record->id > 0 && !in_array($action->name, $componentActions) &&
				(strpos($action->name, 'core.') !== false || strpos($action->name, $view . '.') !== false))
			{
				// we are in item
				$area = 'item';
				// The record has been set. Check the record permissions.
				$permission = $user->authorise($action->name, 'com_' . $component . '.' . $view . '.' . (int) $record->id);
				// if no permission found, check edit own
				if (!$permission)
				{
					// With edit, if the created_by matches current user then dig deeper.
					if (($action->name === 'core.edit' || $action->name === $view . '.edit') && $record->created_by > 0 && ($record->created_by == $user->id))
					{
						// the correct target
						$coreCheck = (array) explode('.', $action->name);
						// check that we have both local and global access
						if ($user->authorise($coreCheck[0] . '.edit.own', 'com_' . $component . '.' . $view . '.' . (int) $record->id) &&
							$user->authorise($coreCheck[0]  . '.edit.own', 'com_' . $component))
						{
							// allow edit
							$result->set($action->name, true);
							// set not to use global default
							// because we already validated it
							$fallback = false;
						}
						else
						{
							// do not allow edit
							$result->set($action->name, false);
							$fallback = false;
						}
					}
				}
				elseif (self::checkString($views) && isset($record->catid) && $record->catid > 0)
				{
					// we are in item
					$area = 'category';
					// set the core check
					$coreCheck = explode('.', $action->name);
					$core = $coreCheck[0];
					// make sure we use the core. action check for the categories
					if (strpos($action->name, $view) !== false && strpos($action->name, 'core.') === false )
					{
						$coreCheck[0] = 'core';
						$categoryCheck = implode('.', $coreCheck);
					}
					else
					{
						$categoryCheck = $action->name;
					}
					// The record has a category. Check the category permissions.
					$catpermission = $user->authorise($categoryCheck, 'com_' . $component . '.' . $views . '.category.' . (int) $record->catid);
					if (!$catpermission && !is_null($catpermission))
					{
						// With edit, if the created_by matches current user then dig deeper.
						if (($action->name === 'core.edit' || $action->name === $view . '.edit') && $record->created_by > 0 && ($record->created_by == $user->id))
						{
							// check that we have both local and global access
							if ($user->authorise('core.edit.own', 'com_' . $component . '.' . $views . '.category.' . (int) $record->catid) &&
								$user->authorise($core . '.edit.own', 'com_' . $component))
							{
								// allow edit
								$result->set($action->name, true);
								// set not to use global default
								// because we already validated it
								$fallback = false;
							}
							else
							{
								// do not allow edit
								$result->set($action->name, false);
								$fallback = false;
							}
						}
					}
				}
			}
			// if allowed then fallback on component global settings
			if ($fallback)
			{
				// if item/category blocks access then don't fall back on global
				if ((($area === 'item') && !$permission) || (($area === 'category') && !$catpermission))
				{
					// do not allow
					$result->set($action->name, false);
				}
				// Finally remember the global settings have the final say. (even if item allow)
				// The local item permissions can block, but it can't open and override of global permissions.
				// Since items are created by users and global permissions is set by system admin.
				else
				{
					$result->set($action->name, $user->authorise($action->name, 'com_' . $component));
				}
			}
		}
		return $result;
	}

	/**
	* Filter the action permissions
	*
	* @param  string   $action   The action to check
	* @param  array    $targets  The array of target actions
	*
	* @return  boolean   true if action should be filtered out
	* 
	**/
	protected static function filterActions(&$view, &$action, &$targets)
	{
		foreach ($targets as $target)
		{
			if (strpos($action, $view . '.' . $target) !== false ||
				strpos($action, 'core.' . $target) !== false)
			{
				return false;
				break;
			}
		}
		return true;
	}

	/**
	* Check if have an json string
	*
	* @input	string   The json string to check
	*
	* @returns bool true on success
	**/
	public static function checkJson($string)
	{
		if (self::checkString($string))
		{
			json_decode($string);
			return (json_last_error() === JSON_ERROR_NONE);
		}
		return false;
	}

	/**
	* Check if have an object with a length
	*
	* @input	object   The object to check
	*
	* @returns bool true on success
	**/
	public static function checkObject($object)
	{
		if (isset($object) && is_object($object))
		{
			return count((array)$object) > 0;
		}
		return false;
	}

	/**
	* Check if have an array with a length
	*
	* @input	array   The array to check
	*
	* @returns bool/int  number of items in array on success
	**/
	public static function checkArray($array, $removeEmptyString = false)
	{
		if (isset($array) && is_array($array) && ($nr = count((array)$array)) > 0)
		{
			// also make sure the empty strings are removed
			if ($removeEmptyString)
			{
				foreach ($array as $key => $string)
				{
					if (empty($string))
					{
						unset($array[$key]);
					}
				}
				return self::checkArray($array, false);
			}
			return $nr;
		}
		return false;
	}

	/**
	* Check if have a string with a length
	*
	* @input	string   The string to check
	*
	* @returns bool true on success
	**/
	public static function checkString($string)
	{
		if (isset($string) && is_string($string) && strlen($string) > 0)
		{
			return true;
		}
		return false;
	}

	/**
	* Check if we are connected
	* Thanks https://stackoverflow.com/a/4860432/1429677
	*
	* @returns bool true on success
	**/
	public static function isConnected()
	{
		// If example.com is down, then probably the whole internet is down, since IANA maintains the domain. Right?
		$connected = @fsockopen("www.example.com", 80); 
			// website, port  (try 80 or 443)
		if ($connected)
		{
			//action when connected
			$is_conn = true;
			fclose($connected);
		}
		else
		{
			//action in connection failure
			$is_conn = false;
		}
		return $is_conn;
	}

	/**
	* Merge an array of array's
	*
	* @input	array   The arrays you would like to merge
	*
	* @returns array on success
	**/
	public static function mergeArrays($arrays)
	{
		if(self::checkArray($arrays))
		{
			$arrayBuket = array();
			foreach ($arrays as $array)
			{
				if (self::checkArray($array))
				{
					$arrayBuket = array_merge($arrayBuket, $array);
				}
			}
			return $arrayBuket;
		}
		return false;
	}

	// typo sorry!
	public static function sorten($string, $length = 40, $addTip = true)
	{
		return self::shorten($string, $length, $addTip);
	}

	/**
	* Shorten a string
	*
	* @input	string   The you would like to shorten
	*
	* @returns string on success
	**/
	public static function shorten($string, $length = 40, $addTip = true)
	{
		if (self::checkString($string))
		{
			$initial = strlen($string);
			$words = preg_split('/([\s\n\r]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE);
			$words_count = count((array)$words);

			$word_length = 0;
			$last_word = 0;
			for (; $last_word < $words_count; ++$last_word)
			{
				$word_length += strlen($words[$last_word]);
				if ($word_length > $length)
				{
					break;
				}
			}

			$newString	= implode(array_slice($words, 0, $last_word));
			$final	= strlen($newString);
			if ($initial != $final && $addTip)
			{
				$title = self::shorten($string, 400 , false);
				return '<span class="hasTip" title="'.$title.'" style="cursor:help">'.trim($newString).'...</span>';
			}
			elseif ($initial != $final && !$addTip)
			{
				return trim($newString).'...';
			}
		}
		return $string;
	}

	/**
	* Making strings safe (various ways)
	*
	* @input	string   The you would like to make safe
	*
	* @returns string on success
	**/
	public static function safeString($string, $type = 'L', $spacer = '_', $replaceNumbers = true, $keepOnlyCharacters = true)
	{
		if ($replaceNumbers === true)
		{
			// remove all numbers and replace with english text version (works well only up to millions)
			$string = self::replaceNumbers($string);
		}
		// 0nly continue if we have a string
		if (self::checkString($string))
		{
			// create file name without the extention that is safe
			if ($type === 'filename')
			{
				// make sure VDM is not in the string
				$string = str_replace('VDM', 'vDm', $string);
				// Remove anything which isn't a word, whitespace, number
				// or any of the following caracters -_()
				// If you don't need to handle multi-byte characters
				// you can use preg_replace rather than mb_ereg_replace
				// Thanks @Łukasz Rysiak!
				// $string = mb_ereg_replace("([^\w\s\d\-_\(\)])", '', $string);
				$string = preg_replace("([^\w\s\d\-_\(\)])", '', $string);
				// http://stackoverflow.com/a/2021729/1429677
				return preg_replace('/\s+/', ' ', $string);
			}
			// remove all other characters
			$string = trim($string);
			$string = preg_replace('/'.$spacer.'+/', ' ', $string);
			$string = preg_replace('/\s+/', ' ', $string);
			// Transliterate string
			$string = self::transliterate($string);
			// remove all and keep only characters
			if ($keepOnlyCharacters)
			{
				$string = preg_replace("/[^A-Za-z ]/", '', $string);
			}
			// keep both numbers and characters
			else
			{
				$string = preg_replace("/[^A-Za-z0-9 ]/", '', $string);
			}
			// select final adaptations
			if ($type === 'L' || $type === 'strtolower')
			{
				// replace white space with underscore
				$string = preg_replace('/\s+/', $spacer, $string);
				// default is to return lower
				return strtolower($string);
			}
			elseif ($type === 'W')
			{
				// return a string with all first letter of each word uppercase(no undersocre)
				return ucwords(strtolower($string));
			}
			elseif ($type === 'w' || $type === 'word')
			{
				// return a string with all lowercase(no undersocre)
				return strtolower($string);
			}
			elseif ($type === 'Ww' || $type === 'Word')
			{
				// return a string with first letter of the first word uppercase and all the rest lowercase(no undersocre)
				return ucfirst(strtolower($string));
			}
			elseif ($type === 'WW' || $type === 'WORD')
			{
				// return a string with all the uppercase(no undersocre)
				return strtoupper($string);
			}
			elseif ($type === 'U' || $type === 'strtoupper')
			{
				// replace white space with underscore
				$string = preg_replace('/\s+/', $spacer, $string);
				// return all upper
				return strtoupper($string);
			}
			elseif ($type === 'F' || $type === 'ucfirst')
			{
				// replace white space with underscore
				$string = preg_replace('/\s+/', $spacer, $string);
				// return with first caracter to upper
				return ucfirst(strtolower($string));
			}
			elseif ($type === 'cA' || $type === 'cAmel' || $type === 'camelcase')
			{
				// convert all words to first letter uppercase
				$string = ucwords(strtolower($string));
				// remove white space
				$string = preg_replace('/\s+/', '', $string);
				// now return first letter lowercase
				return lcfirst($string);
			}
			// return string
			return $string;
		}
		// not a string
		return '';
	}

	public static function transliterate($string)
	{
		// set tag only once
		if (!self::checkString(self::$langTag))
		{
			// get global value
			self::$langTag = JComponentHelper::getParams('com_sentinel')->get('language', 'en-GB');
		}
		// Transliterate on the language requested
		$lang = Language::getInstance(self::$langTag);
		return $lang->transliterate($string);
	}

	public static function htmlEscape($var, $charset = 'UTF-8', $shorten = false, $length = 40)
	{
		if (self::checkString($var))
		{
			$filter = new JFilterInput();
			$string = $filter->clean(html_entity_decode(htmlentities($var, ENT_COMPAT, $charset)), 'HTML');
			if ($shorten)
			{
           		return self::shorten($string,$length);
			}
			return $string;
		}
		else
		{
			return '';
		}
	}

	public static function replaceNumbers($string)
	{
		// set numbers array
		$numbers = array();
		// first get all numbers
		preg_match_all('!\d+!', $string, $numbers);
		// check if we have any numbers
		if (isset($numbers[0]) && self::checkArray($numbers[0]))
		{
			foreach ($numbers[0] as $number)
			{
				$searchReplace[$number] = self::numberToString((int)$number);
			}
			// now replace numbers in string
			$string = str_replace(array_keys($searchReplace), array_values($searchReplace),$string);
			// check if we missed any, strange if we did.
			return self::replaceNumbers($string);
		}
		// return the string with no numbers remaining.
		return $string;
	}

	/**
	* Convert an integer into an English word string
	* Thanks to Tom Nicholson <http://php.net/manual/en/function.strval.php#41988>
	*
	* @input	an int
	* @returns a string
	**/
	public static function numberToString($x)
	{
		$nwords = array( "zero", "one", "two", "three", "four", "five", "six", "seven",
			"eight", "nine", "ten", "eleven", "twelve", "thirteen",
			"fourteen", "fifteen", "sixteen", "seventeen", "eighteen",
			"nineteen", "twenty", 30 => "thirty", 40 => "forty",
			50 => "fifty", 60 => "sixty", 70 => "seventy", 80 => "eighty",
			90 => "ninety" );

		if(!is_numeric($x))
		{
			$w = $x;
		}
		elseif(fmod($x, 1) != 0)
		{
			$w = $x;
		}
		else
		{
			if($x < 0)
			{
				$w = 'minus ';
				$x = -$x;
			}
			else
			{
				$w = '';
				// ... now $x is a non-negative integer.
			}

			if($x < 21)   // 0 to 20
			{
				$w .= $nwords[$x];
			}
			elseif($x < 100)  // 21 to 99
			{ 
				$w .= $nwords[10 * floor($x/10)];
				$r = fmod($x, 10);
				if($r > 0)
				{
					$w .= ' '. $nwords[$r];
				}
			}
			elseif($x < 1000)  // 100 to 999
			{
				$w .= $nwords[floor($x/100)] .' hundred';
				$r = fmod($x, 100);
				if($r > 0)
				{
					$w .= ' and '. self::numberToString($r);
				}
			}
			elseif($x < 1000000)  // 1000 to 999999
			{
				$w .= self::numberToString(floor($x/1000)) .' thousand';
				$r = fmod($x, 1000);
				if($r > 0)
				{
					$w .= ' ';
					if($r < 100)
					{
						$w .= 'and ';
					}
					$w .= self::numberToString($r);
				}
			} 
			else //  millions
			{    
				$w .= self::numberToString(floor($x/1000000)) .' million';
				$r = fmod($x, 1000000);
				if($r > 0)
				{
					$w .= ' ';
					if($r < 100)
					{
						$w .= 'and ';
					}
					$w .= self::numberToString($r);
				}
			}
		}
		return $w;
	}

	/**
	* Random Key
	*
	* @returns a string
	**/
	public static function randomkey($size)
	{
		$bag = "abcefghijknopqrstuwxyzABCDDEFGHIJKLLMMNOPQRSTUVVWXYZabcddefghijkllmmnopqrstuvvwxyzABCEFGHIJKNOPQRSTUWXYZ";
		$key = array();
		$bagsize = strlen($bag) - 1;
		for ($i = 0; $i < $size; $i++)
		{
			$get = rand(0, $bagsize);
			$key[] = $bag[$get];
		}
		return implode($key);
	}
}
