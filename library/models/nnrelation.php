<?php
/**
 * @package   FOF NNRelation
 * @author    Federico Liva <mail@federicoliva.info>
 * @copyright Copyright (C) 2014 Federico Liva
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 */

defined('F0F_INCLUDED') or die;

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
		$input = new F0FInput;
		$db    = $model->getDbo();

		// Retrieve the relations configuration for this table
		$table     = $model->getTable();
		$key       = $table->getConfigProviderKey() . '.relations';
		$relations = $table->getConfigProvider()->get($key, array());

		// For each multiple type relation add the filter query
		foreach ($relations as $relation)
		{
			if ($relation['type'] == 'multiple')
			{
				// Get complete relation fields
				$relation = array_merge(array(
					'itemName' => $relation['itemName']
				), $table->getRelations()->getRelation($relation['itemName'], $relation['type']));

				// Model only save $table->getKnownFields as state, so we look into the input
				$filter_name        = $relation['itemName'];
				$model_filter_value = $input->getInt($filter_name);

				// Build the conditions based on relation configuration
				if (!empty($model_filter_value))
				{
					$query->innerJoin(sprintf('%1$s ON %1$s.%2$s = %3$s.%4$s',
						$db->qn($relation['pivotTable']),
						$db->qn($relation['ourPivotKey']),
						$db->qn($table->getTableName()),
						$db->qn($relation['localKey'])));

					$query->where(sprintf('%s.%s = %s',
						$db->qn($relation['pivotTable']),
						$db->qn($relation['theirPivotKey']),
						$model_filter_value));
				}
			}
		}
	}

}
