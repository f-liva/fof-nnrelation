<?php
/**
 * @package   FOF NNRelations
 * @author    Federico Liva <mail@federicoliva.info>
 * @copyright Copyright (C) 2014 Federico Liva
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 */

namespace FOFNnRelations\F0FTableBehaviorNnrelation;

defined('_JEXEC') or die;

class F0FTableBehaviorNnrelation extends F0FTableBehavior
{
	/**
	 * Save fields for many-to-many relations in their pivot tables.
	 *
	 * @param F0FTable $table Current item table.
	 *
	 * @return bool True if the object can be saved successfully, false elsewhere.
	 * @throws Exception The error message get trying to save fields into the pivot tables.
	 */
	public function onAfterStore(&$table)
	{
		// Retrieve the relations configured for this table
		$input     = new F0FInput();
		$key       = $table->getConfigProviderKey() . '.relations';
		$relations = $table->getConfigProvider()->get($key, array());

		// For each relation check relative field
		foreach ($relations as $relation)
		{
			// Only if it is a multiple relation, sure!
			if ($relation['type'] == 'multiple')
			{
				// Be sure all parameters are ready, of follow conventions to build they
				self::normaliseParameters($relation, $table);

				// Deduce the name of the field used in the form
				$field_name = F0FInflector::pluralize($relation['itemName']);
				// If field exists we catch its values!
				$field_values = $input->get($field_name, array(), 'array');

				// If the field exists, build the correct pivot couple objects
				$new_couples = array();

				foreach ($field_values as $value)
				{
					$new_couples[] = array(
						$relation['ourPivotKey']   => $table->getId(),
						$relation['theirPivotKey'] => $value
					);
				}

				// Find existent relations in the pivot table
				$query = $table->getDbo()
					->getQuery(true)
					->select($relation['ourPivotKey'] . ', ' . $relation['theirPivotKey'])
					->from($relation['pivotTable'])
					->where($relation['ourPivotKey'] . ' = ' . $table->getId());

				$existent_couples = $table->getDbo()
					->setQuery($query)
					->loadAssocList();

				// Find new couples and create its
//				$create_couples = array();

				foreach ($new_couples as $couple)
				{
					if (!in_array($couple, $existent_couples))
					{
//						$create_couples[] = $couple;

						$query = $table->getDbo()
							->getQuery(true)
							->insert($relation['pivotTable'])
							->columns($relation['ourPivotKey'] . ', ' . $relation['theirPivotKey'])
							->values($couple[$relation['ourPivotKey']] . ', ' . $couple[$relation['theirPivotKey']]);

//						die($query->__toString());

						// Use database to create the new record
						if (!$table->getDbo()->setQuery($query)->execute())
						{
							throw new Exception('ERROREEEEEE 1'); // todo sistemare
						}
					}
				}

				// Now find the couples no more present, that will be deleted
//				$delete_couples = array();

				foreach ($existent_couples as $couple)
				{
					if (!in_array($couple, $new_couples))
					{
//						$delete_couples[] = $couple;

						$query = $table->getDbo()
							->getQuery(true)
							->delete($relation['pivotTable'])
							->where($relation['ourPivotKey'] . ' = ' . $couple[$relation['ourPivotKey']])
							->where($relation['theirPivotKey'] . ' = ' . $couple[$relation['theirPivotKey']]);

						// Use database to create the new record
						if (!$table->getDbo()->setQuery($query)->execute())
						{
							throw new Exception('ERROREEEEEE 2'); // todo sistemare
						}
					}
				}

//				Kint::dump($new_couples);
//				Kint::dump($existent_couples);
//				Kint::dump($create_couples);
//				Kint::dump($delete_couples);
			}
		}

		return true;
	}

	/**
	 * Normalise the parameters of a relation, to be sure all fields are present.
	 * If not yet present, create all missing fields following F0F conventions.
	 *
	 * @param object   $relation The relation onto check parameters.
	 * @param F0Ftable $table    The current table.
	 */
	public static function normaliseParameters(&$relation, F0Ftable &$table)
	{
		// Pivot table name
		if (empty($relation['pivotTable']))
		{
			$relation['pivotTable'] = $table->getTableName() . '_' . F0FInflector::pluralize($relation['itemName']);
		}

		// Our pivot key and local key
		if (empty($relation['ourPivotKey']) || empty($relation['localKey']))
		{
			$relation['ourPivotKey'] = $relation['localKey'] = $table->getKeyName();
		}
		elseif (empty($relation['ourPivotKey']))
		{
			$relation['ourPivotKey'] = $relation['localKey'];
		}
		elseif (empty($relation['localKey']))
		{
			$relation['localKey'] = $relation['ourPivotKey'];
		}

		// Their pivot key and remote key
		if (empty($relation['theirPivotKey']) || empty($relation['remoteKey']))
		{
			$table_parts    = F0FInflector::explode($table->getTableName());
			$pivot_key_name = $table_parts[2] . '_' . F0FInflector::singularize($relation['itemName']) . '_id';

			$relation['theirPivotKey'] = $relation['remoteKey'] = $pivot_key_name;
		}
		elseif (empty($relation['ourPivotKey']))
		{
			$relation['theirPivotKey'] = $relation['remoteKey'];
		}
		elseif (empty($relation['localKey']))
		{
			$relation['remoteKey'] = $relation['theirPivotKey'];
		}
	}
}