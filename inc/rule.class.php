<?php

/*
 * Copyright (C) 2017 Javier Samaniego García <jsamaniegog@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Rule class store all informations about a GLPI rule :
 *   - description
 *   - criterias
 *   - actions
 *
 * */
class PluginRulesRule extends Rule {
    /**
     * If do history.
     * @var bool 
     */
    public $dohistory = true;

    /**
     * Name for profile rights.
     * @var type 
     */
    static $rightname = 'config';

    /**
     * model attributes 
     * @var array
     */
    public $attributes = array();

    /**
     * Visualization options.
     * @var array 
     */
    public $view_options = array();

    /**
     *
     * @var type 
     */
    public $can_sort = true;

    /**
     * Event on add
     */
    const ONADD = 1;

    /**
     * Event on update
     */
    const ONUPDATE = 2;

    /**
     * Event on delete
     */
    const ONDELETE = 4;

    /**
     * Event on purge
     */
    //const ONPURGE = 8;

    /**
     * Return the asset of actual class. Based on name.
     * @return String
     */
    static function getItem() {
        return str_replace("PluginRulesRule", "", get_called_class());
    }

    /**
     * @since version 0.85
     * */
    static function getConditionsArray() {
        return array(
            static::ONADD => __('Add'),
            static::ONUPDATE => __('Update'),
            static::ONADD | static::ONUPDATE => sprintf(__('%1$s / %2$s'), __('Add'), __('Update')),
            static::ONDELETE => __('Delete'),
            //static::ONPURGE => __('Purge'),
            //static::ONDELETE | static::ONPURGE => sprintf(__('%1$s / %2$s'), __('Delete'), __('Purge'))
        );
    }

    /**
     * Get name of this type
     *
     * @return text name of this type by language of the user connected
     *
     */
    static function getTypeName($nb = 1) {
        $asset = self::getItem();
        return _n('Rule for ', 'Rules for ', $nb, 'rules') . $asset::getTypeName();
    }

    function getTitle() {
        return self::getTypeName(2);
    }

    function maybeRecursive() {
        return true;
    }

    function isEntityAssign() {
        return true;
    }

    function canUnrecurs() {
        return true;
    }

    /* function maxCriteriasCount() {
      return 2;
      } */

    function maxActionsCount() {
        return count($this->getActions());
    }

    function addSpecificParamsForPreview($params) {
        if (!isset($params["entities_id"])) {
            $params["entities_id"] = $_SESSION["glpiactive_entity"];
        }

        return $params;
    }

    /**
     * Function used to display type specific criterias during rule's preview
     *
     * @param $fields fields values
     * */
    function showSpecificCriteriasForPreview($fields) {
        $entity_as_criteria = false;

        foreach ($this->criterias as $criteria) {
            if ($criteria->fields['criteria'] == 'entities_id') {
                $entity_as_criteria = true;
                break;
            }
        }

        if (!$entity_as_criteria) {
            echo "<input type='hidden' name='entities_id' value='" . $_SESSION["glpiactive_entity"] . "'>";
        }
    }

    public function getCriteriasFromObject(CommonDBTM $object) {
        $criterias = $object->getSearchOptions();

        foreach ($criterias as $key => $criteria) {
            if (!is_numeric($key)) {
                unset($criterias[$key]);
                // on actions can't view groups
                //$criterias_final[$key] = $object->getType() . ' - ' . $key;
                continue;
            }

            if ($criterias[$key]['table'] != $object->getTable()) {
                // if it's foreingkey but don't dropdown
                if ($criterias[$key]['datatype'] != 'dropdown') {
                    unset($criterias[$key]);
                    continue;
                }

                $field = $criterias[$key]['linkfield'] = str_replace("glpi_", "", $criterias[$key]['table']) . "_id";
            } else {
                $field = $criterias[$key]['field'];
            }

            // the index must be named like the field not a number
            $criterias_final[$field] = $criterias[$key];
            
            // type == datatype
            $criterias_final[$field]['type'] = $criterias[$key]['datatype'];
            
            // unset the number index
            //unset($criterias[$key]);
        }

        return $criterias_final;
    }

    function getCriterias() {
        $criterias = array();

        $asset = self::getItem();
        $asset = new $asset();
        $criterias = $this->getCriteriasFromObject($asset);
        
        return $criterias;
    }

    function getActions() {
        $criterias = array();

        $asset = self::getItem();
        $asset = new $asset();
        $criterias = $this->getCriteriasFromObject($asset);

        return $criterias;
    }

    /**
     * Process rules and assign the new values to the input.
     * @param type $condition See constants.
     */
    function processRules(CommonDBTM $item, $condition = 0) {
        $criterias = $this->getCriteriasFromObject($item);

        $ruleCollection = 'PluginRulesRule'.$this->getItem().'Collection';
        $ruleCollection = new $ruleCollection();

        $ruleCollection->setEntity($item->input['entities_id']);
        
        $fields_affected_by_rules = $ruleCollection->processAllRules(
            $item->input, array(), array(), array(
            'condition' => $condition
            )
        );

        // unnecessary variable, number of rules processed
        unset($fields_affected_by_rules['_rule_process']);

        foreach ($fields_affected_by_rules as $key => $value) {
            $item->input[$key] = $value;
        }
    }
}
