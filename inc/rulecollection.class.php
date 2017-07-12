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

class PluginRulesRuleCollection extends RuleCollection {

    /**
     * Process collection stop on first matched rule
     * @var type 
     */
    public $stop_on_first_match = false;
    public $use_output_rule_process_as_next_input = false;
    
    public $menu_option='plugin_rules';
    
    /**
     * Rights.
     * @var type 
     */
    static $rightname = 'config';

    function __construct($entity = 0) {
        parent::__construct();
        $this->entity = $entity;
        $this->menu_option .= strtolower(self::getItem());
    }
    
    /**
     * Return the asset of actual class. Based on name.
     * @return String
     */
    static function getItem() {
        return str_replace("PluginRulesRule", "", str_replace("Collection", "", get_called_class()));
    }
    
    function getTitle() {
        $asset = self::getItem();
        return __('Rules for ', 'rules') . $asset::getTypeName();
    }

    function showInheritedTab() {
        return Session::haveRight("config", UPDATE) && ($this->entity);
    }

    function showChildrensTab() {
        return Session::haveRight("config", UPDATE) && (count($_SESSION['glpiactiveentities']) > 1);
    }

}
