<?php
/**
 * @package   FOF NNRelations
 * @author    Federico Liva <mail@federicoliva.info>
 * @copyright Copyright (C) 2014 Federico Liva
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 */

namespace FOFNnRelations\F0FModelBehaviorNnrelation;

defined('_JEXEC') or die;

class F0FModelBehaviorNnrelation extends F0FModelBehavior
{
	/**
	 * Modify the query to filter list objects by n:n relation.
	 *
	 * @param F0FModel       $model The model on which operate.
	 * @param JDatabaseQuery $query The query to alter.
	 */
	public function onAfterBuildQuery(&$model, &$query)
	{
		// Import relative table behavior to use normalise parameters
		$input = new F0FInput;
		JLoader::import('F0FTableBehaviorNnrelation', JPATH_ADMINISTRATOR . '/components/' . $input->getString('option') . '/tables/behaviors');
		JLoader::import('F0FTableBehaviorNnrelation', JPATH_SITE . '/components/' . $input->getString('option') . '/tables/behaviors');

		// Retrieve the relations configuration for this table
		$table     = $model->getTable();
		$key       = $table->getConfigProviderKey() . '.relations';
		$relations = $table->getConfigProvider()->get($key, array());

		// For each multiple type relation add the filter query
		foreach ($relations as $relation)
		{
			if ($relation['type'] == 'multiple')
			{
				// Normalise parameters like on behaviors
				F0FTableBehaviorNnrelation::normaliseParameters($relation, $table);

				// Retrieve the filter for this relation (singular name of relation's one)
				$filter_name        = F0FInflector::singularize($relation['itemName']);
				$model_filter_value = $model->getState($filter_name);

				// Build the conditions based on relation configuration
				if (!empty($model_filter_value))
				{
					// todo what if these fields aren't declared? follow conventions
					$join_condition  = sprintf('%1$s ON %1$s.%2$s = %3$s.%4$s',
						$model->getDbo()->qn($relation['pivotTable']),
						$model->getDbo()->qn($relation['ourPivotKey']),
						$model->getDbo()->qn($table->getTableName()),
						$model->getDbo()->qn($relation['localKey'])
					);
					$where_condition = sprintf('%s.%s = %d',
						$model->getDbo()->qn($relation['pivotTable']),
						$model->getDbo()->qn($relation['theirPivotKey']),
						$model_filter_value
					);

					$query->innerJoin($join_condition)->where($where_condition);
				}
			}
		}
	}

}