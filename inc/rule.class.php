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
     * Assets managed by rules plugin.
     * @var type 
     */
    static public $items = array(
        'Computer',
        'Monitor',
        'Printer',
        'CartridgeItem',
        'ConsumableItem',
        'Phone',
        'Peripheral',
        'NetworkEquipment',
        'Software',
        'Infocom',
        'NetworkPort'
    );

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
        return ($asset == '') 
            ? 'PluginRulesRule'
            : _n('Rule for ', 'Rules for ', $nb, 'rules') . $asset::getTypeName() ;
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
        $criterias = $object->rawSearchOptions();

        // for infocom table
        $criterias = array_merge($criterias, $this::getInfocomCriterias($object, $criteria_or_action));

        // for networkport table
        $criterias = array_merge($criterias, $this::getNetworkPortCriterias($object, $criteria_or_action));

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

                //$criteria['table'] = str_replace(" ", "", $criteria['table']);
                // if it's foreingkey but don't dropdown
                if ($criteria['datatype'] != 'dropdown') {
                    if ($criteria_or_action == $this::ACTION) {
                        $sufix = "_" . $criteria['field'];
                        $item = str_replace("glpi_", "", $criteria['table']);
                        $item = ucfirst(substr($item, 0, strlen($item) - 1));

                        // hack for plural "es"
                        if (!class_exists($item)) {
                            $item = ucfirst(substr($item, 0, strlen($item) - 1));
                        }

                        $item = str_replace(" ", "", $item);

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
    private static function getInfocomCriterias(CommonDBTM $object, $criteria_or_action) {
        if ($criteria_or_action == self::ACTION) {
            $infocom = new Infocom();
            return $infocom->getSearchOptionsToAdd(get_class($object));
        }

        return array();
    }

    /**
     * Special item networkport for actions.
     * @param type $criteria_or_action See constants.
     */
    private static function getNetworkPortCriterias(CommonDBTM $object, $criteria_or_action) {
        if ($criteria_or_action == self::ACTION) {
            $networkport = new NetworkPort();
            return $networkport->getSearchOptionsToAdd(get_class($object));
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
     * @return bool false if no rule matches
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

        if ($fields_affected_by_rules['_no_rule_matches'] == 1) {
            return false;
        }
        
        foreach ($fields_affected_by_rules as $key => $value) {
            // hack for dates
            if ($value == "{today}") {
                $value = date("Y-m-d");
            }

            if (isset($item->fields[$key])) {
                $item->input[$key] = $value;
            } else {
                // else is a foreing field
                // parsing to extract item and field
                list($subitem, $field) = explode("_", $key, 2);
                // linkfield with the table
                $linkfield = $subitem . "_id";
                // extract the last s
                $subitem = ucfirst(substr($subitem, 0, strlen($subitem) - 1));

                // hack for plural "es"
                if (!class_exists($subitem)) {
                    $subitem = ucfirst(substr($subitem, 0, strlen($subitem) - 1));
                }

                // check for caution
                if (class_exists($subitem)) {
                    $subitem = new $subitem();
                    $input = array();

                    // hack for infocom
                    if (get_class($subitem) == 'Infocom') {
                        if (!$subitem->getFromDBByQuery("WHERE items_id = " . $item->input['id'] . " AND itemtype = '" . get_class($item) . "'")) {
                            // new infocom
                            $subitem->add(array(
                                'items_id' => $item->input['id'],
                                'itemtype' => get_class($item)
                            ));
                        }
                        $input["id"] = $subitem->fields['id'];

                        // hack for dates, the dates don't must change
                        if (strstr($field, "date") and $subitem->fields[$field] != "") {
                            continue;
                        }
                    } elseif (get_class($subitem) == 'NetworkPort'
                        or get_class($subitem) == 'Netpoint'
                        or get_class($subitem) == 'IPAddress'
                        or get_class($subitem) == 'NetworkName'
                        or get_class($subitem) == 'NetworkAlias'
                        or get_class($subitem) == 'Vlan'
                    ) {
                        // search for interfaces
                        $networkport = new NetworkPort();
                        $networkports = $networkport->find("items_id = " . $item->input['id'] . " and itemtype = '" . get_class($item) . "'");
                        foreach ($networkports as $networkport) {

                            // instantiation of networkport to update networkname or ipaddresses
                            $np = new NetworkPort();

                            // update networkport
                            if (get_class($subitem) == 'NetworkPort') {
                                $np->update(array(
                                    'id' => $networkport['id'],
                                    $field => $value
                                ));
                            }

                            // update netpoint
                            if (get_class($subitem) == 'Netpoint') {
                                $npe = new NetworkPortEthernet();
                                $npe->update(array(
                                    "networkports_id" => $networkport['id'],
                                    "netpoints_id" => $value
                                ));
                            }

                            // update vlans
                            if (get_class($subitem) == 'Vlan') {
                                $npv = new NetworkPort_Vlan();
                                if ($value != "" and $value != "0") {
                                    $npv->assignVlan($networkport['id'], $value, '0');
                                } else {
                                    foreach ($npv->getVlansForNetworkPort($networkport['id']) as $vlanid) {
                                        $npv->unassignVlan($networkport['id'], $vlanid);
                                    }
                                }
                            }

                            // search for networkname
                            $networkname = new NetworkName();
                            if ($networkname->getFromDBByQuery("WHERE items_id = " . $networkport['id'] . " and itemtype = 'NetworkPort'")) {

                                // update networkname
                                if (get_class($subitem) == 'NetworkName') {
                                    $networkname->fields[$field] = $value;
                                    $np->update(array(
                                        "_create_children" => 1,
                                        "id" => $networkport['id'],
                                        "NetworkName_id" => $networkname->fields['id'],
                                        "NetworkName_name" => $networkname->fields['name'],
                                        "NetworkName_fqdns_id" => $networkname->fields['fqdns_id']
                                    ));
                                }

                                // update networkalias
                                $na = new NetworkAlias();
                                if (get_class($subitem) == 'NetworkAlias'
                                    and $aliases = $na->find("networknames_id = " . $networkname->fields['id'])
                                ) {
                                    foreach ($aliases as $alias) {
                                        $na->update(array(
                                            "id" => $alias['id'],
                                            $field => $value
                                        ));
                                    }
                                }

                                // search for ip addresses
                                $ipaddress = new IPAddress();
                                if (get_class($subitem) == 'IPAddress'
                                    and $ips = $ipaddress->find("items_id = " . $networkname->fields['id'] . " and itemtype = 'NetworkName'")
                                ) {
                                    foreach ($ips as $ip) {
                                        // if you don't specify networkname values this will be deleted
                                        $np->update(array(
                                            "_create_children" => 1,
                                            "id" => $networkport['id'],
                                            "NetworkName_id" => $networkname->fields['id'],
                                            "NetworkName_name" => $networkname->fields['name'],
                                            "NetworkName_fqdns_id" => $networkname->fields['fqdns_id'],
                                            "NetworkName__ipaddresses" => array($ip['id'] => $value)
                                        ));
                                    }
                                }
                            }
                        }

                        continue;
                    } elseif (get_class($subitem) == 'PluginFusioninventoryAgent') {
                        // only one option: remove the agent
                        $subitem->getFromDBByQuery("WHERE computers_id = " . $item->fields['id']);
                        if (!empty($subitem->fields)) {
                            $subitem->delete(
                                array(
                                'id' => $subitem->fields['id']
                                ), 1
                            );
                        }
                    } else {
                        $input["id"] = $item->input[$linkfield];
                    }

                    $input[$field] = $value;
                    $subitem->update($input);
                }
            }
        }
        
        return true;
    }
    
    /**
     * Executed by cron. This task search items that matches the rules and
     * execute them. Only for update event, add and delete event not.
     */
    static function cronExecuteAllRules(CronTask $task) {
        $items = self::$items;
        
        foreach ($items as $item) {
            $item = new $item();
            
            $records = $item->find("is_deleted = 0");
            self::processRecords($item, $records);
        }
    }
    
    /**
     * 
     * @param object $item Item of GLPI.
     * @param array $records Array for records.
     * @param type $event Event: see constants of PluginRulesRule.
     */
    static private function processRecords($item, $records, $event = PluginRulesRule::ONUPDATE) {
        foreach ($records as $record) {
            $item->fields = $record;
            $item->input = $record;
            
            $ruleitem = 'PluginRulesRule' . get_class($item);
            $ruleitem = new $ruleitem();
            if ($ruleitem->processRules($item, $event) != false) {
                $item->update($item->input);
            }
        }
    }
}