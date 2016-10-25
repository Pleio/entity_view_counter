<?php
/**
 * Perform migration of old correct question answer labels to new ones
 *
 * @package Questions
 */

set_time_limit(0);

if (php_sapi_name() !== 'cli') {
  throw new Exception('This script must be run from the CLI.');
}

// Configure with "main site". Needed so subsite_manager can identify our instance.

// Production
/*
$_SERVER["HTTP_HOST"] = "ffd.pleio.nl";
$_SERVER["HTTPS"] = true;
*/

// Test
$_SERVER["HTTP_HOST"] = "ffd.pleio.dev";
$_SERVER["HTTPS"] = false;

require_once(dirname(dirname(dirname(__FILE__))) . "/../engine/start.php");
$ia = elgg_set_ignore_access(true);

$views = get_data("SELECT * FROM elgg_entity_views");

foreach ($views as $view) {
    $entity = get_entity($view->guid);
    if (!$entity) {
        continue;
    }

    update_data("UPDATE elgg_entity_views SET site_guid = {$entity->site_guid} WHERE guid = {$entity->guid}");
}
