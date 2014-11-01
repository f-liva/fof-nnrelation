<?php

// Protect from unauthorized access
    defined('F0F_INCLUDED') or die;

    class F0FFormHeaderNnrelation extends F0FFormHeaderField
    {
        /**
         * Create objects for the options
         *
         * @return  array  The array of option objects
         */
        protected function getOptions()
        {
            // Fieldoptions: title translate and show how much related items
            $countAndShowRelated        = ((string)$this->element['countAndShowRelated'] == 'true');
            $translateTitle             = ((string)$this->element['translateTitel'] == 'true');

            $table = $this->form->getModel()->getTable();

            // Get relationdefinitions from fof.xml
            $key       = $table->getConfigProviderKey() . '.relations';
            $relations = $table->getConfigProvider()->get($key, array());

            // Get the relation type:
            $relationType = '';
            foreach($relations as $relation)
            {
               if($relation['itemName'] == $this->name)
               {
                   $relationType = $relation['type'];
                   break;
               }
            }


            // Get full relation definitions from F0FTableRelation object:
            $relation = $table->getRelations()->getRelation($this->name, $relationType);

            $dbOptions = array();

            // First implementation: multiple relations. Not using others yet
            if($relation['type'] == 'multiple')
            {
                // Guessing the related table name from the given tableclassname followed the naming-conventions:
                list($option, $tableWord, $optItemSingular) = explode('_',F0FInflector::underscore($relation['tableClass']));
                $optionsTableName = '#__'.$option.'_'.F0FInflector::pluralize($optItemSingular);

                // Get the options table object
                $optionsTableObject = F0FTable::getAnInstance($relation['tableClass'], null);

                // Get the Items
                $db = JFactory::getDbo();
                $q  = $db->getQuery(true);
                $q->select('o.*')->from($db->qn($optionsTableName) . ' as o');

                // Should we count and show related items?
                if($countAndShowRelated)
                {
                    $q->select('count('.$db->qn('p.'.$relation['ourPivotKey']).') as total');
                    $q->leftJoin($db->qn($relation['pivotTable']) . ' as p on ' . $db->qn('o.' . $relation['remoteKey']) . ' = ' . $db->qn('p.'.$relation['theirPivotKey']));
                    $q->group($db->qn('o.'.$relation['remoteKey']));
                }

                // 2Do: Language-filtering?

                $dbOptions = $db->setQuery($q)->loadObjectList($relation['remoteKey']);

            }

            $options = array();

            // Get the field $options on top
            foreach ($this->element->children() as $option)
            {
                // Only add <option /> elements.
                if ($option->getName() != 'option')
                {
                    continue;
                }

                // Create a new option object based on the <option /> element.
                $options[] = JHtml::_(
                    'select.option',
                    (string) $option['value'],
                    JText::alt(
                        trim((string) $option),
                        preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)
                    ),
                    'value', 'text', ((string) $option['disabled'] == 'true')
                );
            }


            // Loop through the data and prime the $options array
            // Get title column alias:
            $titleField = $optionsTableObject->getColumnAlias('title');
            $keyField   = $relation['remoteKey'];
            $enabledField = $optionsTableObject->getColumnAlias('enabled');
            foreach ($dbOptions as $dbOption)
            {
                $key    = $dbOption->$keyField;
                $value  = $dbOption->$titleField;
                $disabled = (!$dbOption->$enabledField) ? true : false;

                if ($translateTitle)
                {
                    $value = JText::_($value);
                }

                if($countAndShowRelated)
                {
                    $value = $value.' ('.$dbOption->total.')';
                }

                $options[] = JHtml::_('select.option', $key, $value, 'value', 'text', $disabled);
            }

            return $options;
        } // function
    } // class
