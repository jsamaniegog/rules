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

/**
 * Hook called on profile change
 * Good place to evaluate the user right on this plugin
 * And to save it in the session
 */
function plugin_change_profile_rules() {
    
}

/**
 * Fonction d'installation du plugin
 * @return boolean
 */
function plugin_rules_install() {
    global $DB;

    $time_in_seconds = 604800;

    $res = CronTask::Register(
        "PluginRulesRule", 
        "ExecuteAllRules", 
        $time_in_seconds, 
        array(
            'comment' => __('Executes all rules over all inventory.', 'rules'),
            'mode' => CronTask::MODE_EXTERNAL,
            'state' => CronTask::STATE_DISABLE
        )
    );

    return true;
}

/**
 * Fonction de désinstallation du plugin
 * @return boolean
 */
function plugin_rules_uninstall() {
    global $DB;

    return true;
}

function plugin_pre_item_add_rules(CommonDBTM $item) {
    $ruleitem = 'PluginRulesRule' . get_class($item);
    $ruleitem = new $ruleitem();
    $ruleitem->processRules($item, PluginRulesRule::ONADD);
}

function plugin_pre_item_update_rules(CommonDBTM $item) {
    $ruleitem = 'PluginRulesRule' . get_class($item);
    $ruleitem = new $ruleitem();
    $ruleitem->processRules($item, PluginRulesRule::ONUPDATE);
}

function plugin_pre_item_delete_rules(CommonDBTM $item) {
    $ruleitem = 'PluginRulesRule' . get_class($item);
    $ruleitem = new $ruleitem();
    $ruleitem->processRules($item, PluginRulesRule::ONDELETE);
}

/* function plugin_pre_item_purge_rules(CommonDBTM $item) {
  $ruleitem = 'PluginRulesRule' . get_class($item);
  $ruleitem = new $ruleitem();
  $ruleitem->processRules($item, PluginRulesRule::ONPURGE);
  } */

/* function plugin_rule_matched_rules($params) {
  $params['subtype'];
  $params['ruleid'];
  $params['subtype'];
  $params['input'];
  $params['output'];
  } */