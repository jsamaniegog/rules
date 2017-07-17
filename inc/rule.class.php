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
     * For criteria screen
     */
    const CRITERIA = 1;

    /**
     * For action screen
     */
    const ACTION = 2;

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

    /**
     * 
     * @param CommonDBTM $object
     * @param type $criteria_or_action See constants.
     * @return string
     */
    public function getCriteriasFromObject(CommonDBTM $object, $criteria_or_action) {
        $criterias = $object->getSearchOptions();
        
        // for infocom table
        $criterias = array_merge($criterias, $this::getInfocomCriterias($object, $criteria_or_action));

        foreach ($criterias as $key => $criteria) {
            if (!is_numeric($key)) {
                unset($criterias[$key]);

                // on actions can't view groups
                if ($criteria_or_action == $this::CRITERIA) {
                    $criterias_final[$key] = $object->getTypeName() . ' - ' . $key;
                }

                continue;
            }

            // foreing key table
            if ($criteria['table'] != $object->getTable()) {
                $sufix = "_id";

                // if it's foreingkey but don't dropdown
                if ($criteria['datatype'] != 'dropdown') {
                    if ($criteria_or_action == $this::ACTION) {
                        $sufix = "_" . $criteria['field'];
                        $item = str_replace("glpi_", "", $criteria['table']);
                        $item = ucfirst(substr($item, 0, strlen($item) - 1));
                        if (class_exists($item)) {
                            $criterias[$key]['name'] = $item::getTypeName(1) . " - " . $criterias[$key]['name'];
                        } else {
                            unset($criterias[$key]);
                            continue;
                        }
                    } else {
                        unset($criterias[$key]);
                        continue;
                    }
                }

                $field = $criterias[$key]['linkfield'] = str_replace("glpi_", "", $criterias[$key]['table']) . $sufix;
                
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

    /**
     * Special item infocom for actions.
     * @param type $criteria_or_action See constants.
     */
    static function getInfocomCriterias(CommonDBTM $object, $criteria_or_action) {
        if ($criteria_or_action == self::ACTION) {
            $infocom = new Infocom();
            return $infocom->getSearchOptionsToAdd(get_class($object));
        }
        
        return array();
    }

    function getCriterias() {
        $criterias = array();

        $asset = self::getItem();
        $asset = new $asset();
        $criterias = $this->getCriteriasFromObject($asset, $this::CRITERIA);

        return $criterias;
    }

    function getActions() {
        $criterias = array();

        $asset = self::getItem();
        $asset = new $asset();
        $criterias = $this->getCriteriasFromObject($asset, $this::ACTION);

        return $criterias;
    }

    /**
     * Process rules and assign the new values to the input.
     * @param type $condition See constants.
     */
    function processRules(CommonDBTM $item, $condition = 0) {
        $criterias = $this->getCriteriasFromObject($item);

        $ruleCollection = 'PluginRulesRule' . $this->getItem() . 'Collection';
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
            // hack for dates
            if ($value == "{today}") {
                $value = date("Y-m-d");
            }

            if (isset($item->input[$key])) {
                $item->input[$key] = $value;
            } else {
                // else is a foreing field
                // parsing to extract item and field
                list($subitem, $field) = explode("_", $key, 2);
                // linkfield with the table
                $linkfield = $subitem . "_id";
                // extract the last s
                $subitem = ucfirst(substr($subitem, 0, strlen($subitem) - 1));
                // check for caution
                if (class_exists($subitem)) {
                    $subitem = new $subitem();
                    $input = array();

                    // hack for infocom
                    if (get_class($subitem) == 'Infocom') {
                        if (!$subitem->getFromDBByQuery("WHERE items_id = " . $item->input['id'] . " AND itemtype = '" . get_class($item) . "'")) {
                            // new infocom
                            $subitem->add(array(
                                'items_id'=>$item->input['id'],
                                'itemtype'=>get_class($item)
                            ));
                        }
                        $input["id"] = $subitem->fields['id'];
                        
                        // hack for dates, the dates don't must change
                        if (strstr($field, "date") and $subitem->fields[$field] != "") {
                            continue;
                        }
                        
                    } else {
                        $input["id"] = $item->input[$linkfield];
                    }
                    
                    $input[$field] = $value;
                    $subitem->update($input);
                }
            }
        }
    }

}
