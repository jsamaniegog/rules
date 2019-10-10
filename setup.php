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
 * Init the hooks of the plugins -Needed
 * @global array $PLUGIN_HOOKS
 * @glpbal array $CFG_GLPI
 */
function plugin_init_rules() {
    global $PLUGIN_HOOKS, $CFG_GLPI;

    Plugin::registerClass('PluginRulesRule');

    require_once 'inc/rule.class.php';
    $assets = PluginRulesRule::$items;
    
    foreach ($assets as $asset) {
        if (file_exists(GLPI_ROOT . "/plugins/rules/inc/rule" . str_replace(" ", "", strtolower($asset)) . "collection.class.php")) {
            Plugin::registerClass(
                'PluginRulesRule' . $asset . 'Collection', array('rulecollections_types' => true)
            );
            
            // hooks
            $PLUGIN_HOOKS['pre_item_add']['rules'][$asset] = 'plugin_pre_item_add_rules';
            $PLUGIN_HOOKS['pre_item_update']['rules'][$asset] = 'plugin_pre_item_update_rules';
            $PLUGIN_HOOKS['pre_item_delete']['rules'][$asset] = 'plugin_pre_item_delete_rules';
            $PLUGIN_HOOKS['pre_item_purge']['rules'][$asset] = 'plugin_pre_item_purge_rules';
        }
    }

    //$PLUGIN_HOOKS['rule_matched']['rules'] = 'plugin_rule_matched_rules';
    
    $PLUGIN_HOOKS['csrf_compliant']['rules'] = true;
}

/**
 * Fonction de définition de la version du plugin
 * @return type
 */
function plugin_version_rules() {
    return array('name' => __('Rules', 'rules'),
        'version' => '0.2.0',
        'author' => 'Javier Samaniego',
        'license' => 'AGPLv3+',
        'homepage' => 'https://github.com/jsamaniegog/rules',
        'minGlpiVersion' => '9.4');
}

/**
 * Fonction de vérification des prérequis
 * @return boolean
 */
function plugin_rules_check_prerequisites() {
    if (version_compare(GLPI_VERSION, '9.4', 'lt')) {
        _e('This plugin requires GLPI >= 9.4', 'rules');
        return false;
    }

    return true;
}

/**
 * Fonction de vérification de la configuration initiale
 * Uninstall process for plugin : need to return true if succeeded
 * may display messages or add to message after redirect.
 * @param type $verbose
 * @return boolean
 */
function plugin_rules_check_config($verbose = false) {
    // check here
    if (true) {
        return true;
    }

    if ($verbose) {
        _e('Installed / not configured', 'rules');
    }

    return false;
}

?>
