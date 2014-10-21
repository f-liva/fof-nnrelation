<?php
/**
 * @package   FOF NNRelations
 * @author    Federico Liva <mail@federicoliva.info>
 * @copyright Copyright (C) 2014 Federico Liva
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 */

defined('_JEXEC') or die;

class F0FFormFieldNnrelation extends F0FFormFieldList
{
	protected function getOptions()
	{
		$options     = array();
		$this->value = array(); // The selected values

		// Deduce table name from conventional names
		$input            = new F0FInput;
		$component_prefix = ucfirst(str_replace('com_', '', $input->getString('option')));
		$view_prefix      = ucfirst($input->getString('view'));

		// Deduce name of the relation
		$relation_name = @F0FInflector::pluralize($this->element['name']); // todo remove silence operator

		// Create a relation's model instance
		$relation_model = F0FModel::getTmpInstance(ucfirst($relation_name), $component_prefix . 'Model');

		// Get the name of key and title field
		$table 		= $relation_model->getTable();
		$key_field 	= $table->getKeyName();
		$value_field 	= $table->getColumnAlias('title');

		// List all items from the referred table
		foreach ($relation_model->getItemList(true) as $value)
		{
			$options[] = JHtmlSelect::option($value->$key_field, $value->$value_field);
		}

		// Don't load selected values if item is new
		if ($id = $input->getInt('id'))
		{
			// Create an instance of the correct table and load this item
			$table = F0FTable::getInstance($view_prefix, $component_prefix . 'Table');

			// Load the instance of this item, based on ID query parameter
			$table->load($id);

			// Get the relation
			$relation = $table->getRelations()->getMultiple($relation_name);

			// Add existent relation as default selected values on list
			foreach ($relation as $item)
			{
				$this->value[] = $item->getId();
			}
		}

		return $options;
	}

	public function getRepeatable()
	{
		$html = '';

		// Find which is the relation to use
		if ($this->item instanceof F0FTable)
		{
			// Pluralize the name to match that of the relation
			$relation_name = F0FInflector::pluralize($this->name);

			// Get the relation
			$iterator = $this->item->getRelations()->getMultiple($relation_name);
			$results  = array();

			// Decide which class name use in tag markup
			$class = !empty($this->element['class']) ? $this->element['class'] : F0FInflector::singularize($this->name);

			foreach ($iterator as $item)
			{
				$markup = '<span class="%s">%s</span>';

				// Add link if show_link parameter is set
				if (!empty($this->element['url']) && !empty($this->element['show_link']) && $this->element['show_link'])
				{
					// Parse URL
					$url = $this->getReplacedPlaceholders($this->element['url'], $item);

					// Set new link markup
					$markup = '<a class="%s" href="' . JRoute::_($url) . '">%s</a>';
				}

				array_push($results, sprintf($markup, $class, $item->get($item->getColumnAlias('title'))));
			}

			// Join all html segments
			$html .= implode(', ', $results);
		}

		// Parse field parameters
		if (empty($html) && !empty($this->element['empty_replacement']))
		{
			$html = JText::_($this->element['empty_replacement']);
		}

		return $html;
	}

	/**
	 * Replace all ITEM:* placeholders contained in a URL (or a string).
	 *
	 * @param string   $url   The URL wherein will be replaced the placeholders.
	 * @param F0FTable $table A table object instance from which get known fields to replace.
	 *
	 * @return string The replaced URL.
	 */
	private function getReplacedPlaceholders($url, F0FTable &$table)
	{
		// Ask to table which are the fields to replace
		$fields = $table->getKnownFields();

		// Replace all placeholders in the URL
		foreach ($fields as $field)
		{
			$url = str_replace('[ITEM:' . strtoupper($field) . ']', $table->get($field), $url);
		}

		return $url;
	}

}
