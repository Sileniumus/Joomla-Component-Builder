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

	@version		@update number 39 of this MVC
	@build			14th October, 2017
	@created		30th April, 2015
	@package		Component Builder
	@subpackage		view.html.php
	@author			Llewellyn van der Merwe <http://vdm.bz/component-builder>	
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html 
	
	Builds Complex Joomla Components 
                                                             
/-----------------------------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * Componentbuilder View class for the Fields
 */
class ComponentbuilderViewFields extends JViewLegacy
{
	/**
	 * Fields view display method
	 * @return void
	 */
	function display($tpl = null)
	{
		if ($this->getLayout() !== 'modal')
		{
			// Include helper submenu
			ComponentbuilderHelper::addSubmenu('fields');
		}

		// Assign data to the view
		$this->items 		= $this->get('Items');
		$this->pagination 	= $this->get('Pagination');
		$this->state		= $this->get('State');
		$this->user 		= JFactory::getUser();
		$this->listOrder	= $this->escape($this->state->get('list.ordering'));
		$this->listDirn		= $this->escape($this->state->get('list.direction'));
		$this->saveOrder	= $this->listOrder == 'ordering';
                // get global action permissions
		$this->canDo		= ComponentbuilderHelper::getActions('field');
		$this->canEdit		= $this->canDo->get('field.edit');
		$this->canState		= $this->canDo->get('field.edit.state');
		$this->canCreate	= $this->canDo->get('field.create');
		$this->canDelete	= $this->canDo->get('field.delete');
		$this->canBatch	= $this->canDo->get('core.batch');

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
		JToolBarHelper::title(JText::_('COM_COMPONENTBUILDER_FIELDS'), 'lamp');
		JHtmlSidebar::setAction('index.php?option=com_componentbuilder&view=fields');
                JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

		if ($this->canCreate)
                {
			JToolBarHelper::addNew('field.add');
		}

                // Only load if there are items
                if (ComponentbuilderHelper::checkArray($this->items))
		{
                        if ($this->canEdit)
                        {
                            JToolBarHelper::editList('field.edit');
                        }

                        if ($this->canState)
                        {
                            JToolBarHelper::publishList('fields.publish');
                            JToolBarHelper::unpublishList('fields.unpublish');
                            JToolBarHelper::archiveList('fields.archive');

                            if ($this->canDo->get('core.admin'))
                            {
                                JToolBarHelper::checkin('fields.checkin');
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
                            JToolbarHelper::deleteList('', 'fields.delete', 'JTOOLBAR_EMPTY_TRASH');
                        }
                        elseif ($this->canState && $this->canDelete)
                        {
                                JToolbarHelper::trash('fields.trash');
                        }

			if ($this->canDo->get('core.export') && $this->canDo->get('field.export'))
			{
				JToolBarHelper::custom('fields.exportData', 'download', '', 'COM_COMPONENTBUILDER_EXPORT_DATA', true);
			}
                } 

		if ($this->canDo->get('core.import') && $this->canDo->get('field.import'))
		{
			JToolBarHelper::custom('fields.importData', 'upload', '', 'COM_COMPONENTBUILDER_IMPORT_DATA', false);
		}

                // set help url for this view if found
                $help_url = ComponentbuilderHelper::getHelpUrl('fields');
                if (ComponentbuilderHelper::checkString($help_url))
                {
                        JToolbarHelper::help('COM_COMPONENTBUILDER_HELP_MANAGER', false, $help_url);
                }

                // add the options comp button
                if ($this->canDo->get('core.admin') || $this->canDo->get('core.options'))
                {
                        JToolBarHelper::preferences('com_componentbuilder');
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
                                JText::_('COM_COMPONENTBUILDER_KEEP_ORIGINAL_STATE'),
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
                                JText::_('COM_COMPONENTBUILDER_KEEP_ORIGINAL_ACCESS'),
                                'batch[access]',
                                JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text')
			);
                } 

		// Category Filter.
		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_CATEGORY'),
			'filter_category_id',
			JHtml::_('select.options', JHtml::_('category.options', 'com_componentbuilder.fields'), 'value', 'text', $this->state->get('filter.category_id'))
		);

		if ($this->canBatch && $this->canCreate && $this->canEdit)
		{
			// Category Batch selection.
			JHtmlBatch_::addListSelection(
				JText::_('COM_COMPONENTBUILDER_KEEP_ORIGINAL_CATEGORY'),
				'batch[category]',
				JHtml::_('select.options', JHtml::_('category.options', 'com_componentbuilder.fields'), 'value', 'text')
			);
		} 

		// Set Fieldtype Name Selection
		$this->fieldtypeNameOptions = JFormHelper::loadFieldType('Fieldtypes')->getOptions();
		if ($this->fieldtypeNameOptions)
		{
			// Fieldtype Name Filter
			JHtmlSidebar::addFilter(
				'- Select '.JText::_('COM_COMPONENTBUILDER_FIELD_FIELDTYPE_LABEL').' -',
				'filter_fieldtype',
				JHtml::_('select.options', $this->fieldtypeNameOptions, 'value', 'text', $this->state->get('filter.fieldtype'))
			);

			if ($this->canBatch && $this->canCreate && $this->canEdit)
			{
				// Fieldtype Name Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_COMPONENTBUILDER_FIELD_FIELDTYPE_LABEL').' -',
					'batch[fieldtype]',
					JHtml::_('select.options', $this->fieldtypeNameOptions, 'value', 'text')
				);
			}
		}

		// Set Datatype Selection
		$this->datatypeOptions = $this->getTheDatatypeSelections();
		if ($this->datatypeOptions)
		{
			// Datatype Filter
			JHtmlSidebar::addFilter(
				'- Select '.JText::_('COM_COMPONENTBUILDER_FIELD_DATATYPE_LABEL').' -',
				'filter_datatype',
				JHtml::_('select.options', $this->datatypeOptions, 'value', 'text', $this->state->get('filter.datatype'))
			);

			if ($this->canBatch && $this->canCreate && $this->canEdit)
			{
				// Datatype Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_COMPONENTBUILDER_FIELD_DATATYPE_LABEL').' -',
					'batch[datatype]',
					JHtml::_('select.options', $this->datatypeOptions, 'value', 'text')
				);
			}
		}

		// Set Indexes Selection
		$this->indexesOptions = $this->getTheIndexesSelections();
		if ($this->indexesOptions)
		{
			// Indexes Filter
			JHtmlSidebar::addFilter(
				'- Select '.JText::_('COM_COMPONENTBUILDER_FIELD_INDEXES_LABEL').' -',
				'filter_indexes',
				JHtml::_('select.options', $this->indexesOptions, 'value', 'text', $this->state->get('filter.indexes'))
			);

			if ($this->canBatch && $this->canCreate && $this->canEdit)
			{
				// Indexes Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_COMPONENTBUILDER_FIELD_INDEXES_LABEL').' -',
					'batch[indexes]',
					JHtml::_('select.options', $this->indexesOptions, 'value', 'text')
				);
			}
		}

		// Set Null Switch Selection
		$this->null_switchOptions = $this->getTheNull_switchSelections();
		if ($this->null_switchOptions)
		{
			// Null Switch Filter
			JHtmlSidebar::addFilter(
				'- Select '.JText::_('COM_COMPONENTBUILDER_FIELD_NULL_SWITCH_LABEL').' -',
				'filter_null_switch',
				JHtml::_('select.options', $this->null_switchOptions, 'value', 'text', $this->state->get('filter.null_switch'))
			);

			if ($this->canBatch && $this->canCreate && $this->canEdit)
			{
				// Null Switch Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_COMPONENTBUILDER_FIELD_NULL_SWITCH_LABEL').' -',
					'batch[null_switch]',
					JHtml::_('select.options', $this->null_switchOptions, 'value', 'text')
				);
			}
		}

		// Set Store Selection
		$this->storeOptions = $this->getTheStoreSelections();
		if ($this->storeOptions)
		{
			// Store Filter
			JHtmlSidebar::addFilter(
				'- Select '.JText::_('COM_COMPONENTBUILDER_FIELD_STORE_LABEL').' -',
				'filter_store',
				JHtml::_('select.options', $this->storeOptions, 'value', 'text', $this->state->get('filter.store'))
			);

			if ($this->canBatch && $this->canCreate && $this->canEdit)
			{
				// Store Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_COMPONENTBUILDER_FIELD_STORE_LABEL').' -',
					'batch[store]',
					JHtml::_('select.options', $this->storeOptions, 'value', 'text')
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
		$document = JFactory::getDocument();
		$document->setTitle(JText::_('COM_COMPONENTBUILDER_FIELDS'));
		$document->addStyleSheet(JURI::root() . "administrator/components/com_componentbuilder/assets/css/fields.css");
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
			return ComponentbuilderHelper::htmlEscape($var, $this->_charset, true);
		}
                // use the helper htmlEscape method instead.
		return ComponentbuilderHelper::htmlEscape($var, $this->_charset);
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
			'a.name' => JText::_('COM_COMPONENTBUILDER_FIELD_NAME_LABEL'),
			'g.name' => JText::_('COM_COMPONENTBUILDER_FIELD_FIELDTYPE_LABEL'),
			'a.datatype' => JText::_('COM_COMPONENTBUILDER_FIELD_DATATYPE_LABEL'),
			'a.indexes' => JText::_('COM_COMPONENTBUILDER_FIELD_INDEXES_LABEL'),
			'a.null_switch' => JText::_('COM_COMPONENTBUILDER_FIELD_NULL_SWITCH_LABEL'),
			'c.category_title' => JText::_('COM_COMPONENTBUILDER_FIELD_FIELD_CATEGORY'),
			'a.store' => JText::_('COM_COMPONENTBUILDER_FIELD_STORE_LABEL'),
			'a.id' => JText::_('JGRID_HEADING_ID')
		);
	} 

	protected function getTheDatatypeSelections()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName('datatype'));
		$query->from($db->quoteName('#__componentbuilder_field'));
		$query->order($db->quoteName('datatype') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadColumn();

		if ($results)
		{
			// get model
			$model = $this->getModel();
			$results = array_unique($results);
			$_filter = array();
			foreach ($results as $datatype)
			{
				// Translate the datatype selection
				$text = $model->selectionTranslation($datatype,'datatype');
				// Now add the datatype and its text to the options array
				$_filter[] = JHtml::_('select.option', $datatype, JText::_($text));
			}
			return $_filter;
		}
		return false;
	}

	protected function getTheIndexesSelections()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName('indexes'));
		$query->from($db->quoteName('#__componentbuilder_field'));
		$query->order($db->quoteName('indexes') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadColumn();

		if ($results)
		{
			// get model
			$model = $this->getModel();
			$results = array_unique($results);
			$_filter = array();
			foreach ($results as $indexes)
			{
				// Translate the indexes selection
				$text = $model->selectionTranslation($indexes,'indexes');
				// Now add the indexes and its text to the options array
				$_filter[] = JHtml::_('select.option', $indexes, JText::_($text));
			}
			return $_filter;
		}
		return false;
	}

	protected function getTheNull_switchSelections()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName('null_switch'));
		$query->from($db->quoteName('#__componentbuilder_field'));
		$query->order($db->quoteName('null_switch') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadColumn();

		if ($results)
		{
			// get model
			$model = $this->getModel();
			$results = array_unique($results);
			$_filter = array();
			foreach ($results as $null_switch)
			{
				// Translate the null_switch selection
				$text = $model->selectionTranslation($null_switch,'null_switch');
				// Now add the null_switch and its text to the options array
				$_filter[] = JHtml::_('select.option', $null_switch, JText::_($text));
			}
			return $_filter;
		}
		return false;
	}

	protected function getTheStoreSelections()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName('store'));
		$query->from($db->quoteName('#__componentbuilder_field'));
		$query->order($db->quoteName('store') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadColumn();

		if ($results)
		{
			// get model
			$model = $this->getModel();
			$results = array_unique($results);
			$_filter = array();
			foreach ($results as $store)
			{
				// Translate the store selection
				$text = $model->selectionTranslation($store,'store');
				// Now add the store and its text to the options array
				$_filter[] = JHtml::_('select.option', $store, JText::_($text));
			}
			return $_filter;
		}
		return false;
	}
}
