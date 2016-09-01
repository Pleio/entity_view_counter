<?php

function entity_view_counter_get_configured_entity_types() {
	static $result;
	
	if (!isset($result)) {
		$result = false;
		
		// get registered entity types and plugin setting
		if (($registered_types = elgg_get_config("registered_entities")) && ($setting = elgg_get_plugin_setting("entity_types", "entity_view_counter"))) {
			$setting = json_decode($setting, true);
			$temp_result = array();
			
			foreach ($registered_types as $type => $subtypes) {
				if (elgg_extract($type, $setting)) {
					$temp_result[$type] = array();
					
					if (!empty($subtypes) && is_array($subtypes)) {
						foreach ($subtypes as $subtype) {
							if (elgg_extract($subtype, $setting[$type])) {
								$temp_result[$type][] = $subtype;
							}
						}
					}
				}
			}
			
			if(!empty($temp_result)) {
				$result = $temp_result;
			}
		}
	}
	
	return $result;
}

function entity_view_counter_is_configured_entity_type($type, $subtype = "") {
	$result = false;
	
	if ($entity_types = entity_view_counter_get_configured_entity_types()) {
		
		foreach($entity_types as $entity_type => $entity_subtypes) {
			// do the types match
			if ($entity_type == $type) {
				// do we need to check the subtype
				if (!empty($subtype) && !empty($entity_subtypes) && is_array($entity_subtypes)) {
					foreach ($entity_subtypes as $entity_subtype) {
						// do the subtypes match
						if ($entity_subtype == $subtype) {
							$result = true;
							break(2);
						}
					}
				} elseif (empty($subtype) && empty($entity_subtypes)) {
					// no subtype supplied and none in this type
					$result = true;
					break;
				}
			}
		}
	}
	
	return $result;
}

function entity_view_counter_extend_views() {
	if ($entity_types = entity_view_counter_get_configured_entity_types()) {
		// let's extend the base views of these entities
		foreach ($entity_types as $type => $subtypes) {
			if (!empty($subtypes) && is_array($subtypes)) {
				foreach ($subtypes as $subtype) {
					elgg_extend_view($type . "/" . $subtype, "entity_view_counter/extends/counter", 450);
				}
			} else {
				// user and group don't have a subtype
				elgg_extend_view($type . "/default", "entity_view_counter/extends/counter", 450);
			}
		}
	}
}

function entity_view_counter_add_view(ElggEntity $entity) {
	if (entity_view_counter_is_counted($entity)) {
		return;
	}

	if (is_memcache_available()) {
		$cache = new ElggMemcache('entity_view_counter');
		$key = "view_" . session_id() . "_" . $entity->guid;
		$cache->save($key, 1);
    }

    $guid = (int) $entity->guid;
    $type = sanitise_string($entity->type);
    $subtype = (int) $entity->subtype;

    insert_data("
    	INSERT INTO elgg_entity_views (guid, type, subtype, views)
    	VALUES ({$guid}, '{$type}', {$subtype}, 1)
    	ON DUPLICATE KEY UPDATE views = views + 1;
    ");
}

function entity_view_counter_is_counted(ElggEntity $entity) {
	if (!entity_view_counter_is_configured_entity_type($entity->getType(), $entity->getSubtype())) {
		return true;
	}

	if (isset($_SERVER["HTTP_USER_AGENT"]) && preg_match('/bot|crawl|slurp|spider/i', $_SERVER["HTTP_USER_AGENT"])) {
		return true;
	}

	$user = elgg_get_logged_in_user_entity();
	if ($user && $user->getGUID() == $entity->getOwnerGUID()) {
		return true;
	}

	if (is_memcache_available()) {
		$cache = new ElggMemcache('entity_view_counter');
		$key = "view_" . session_id() . "_" . $entity->guid;
        if ($cache->load($key)) {
                return true;
        }
    }

    if (entity_view_counter_ignore_ip()) {
    	return true;
    }

	return false;
}

function entity_view_counter_ignore_ip() {
	elgg_load_library("pgregg.ipcheck");

	$client_ip = $_SERVER["REMOTE_ADDR"];
	$client_ip = elgg_trigger_plugin_hook("remote_address", "system", array(
		"remote_address" => $client_ip
	), $client_ip);

	$ranges = explode(',', elgg_get_plugin_setting("ignore_ips", "entity_view_counter"));
	foreach ($ranges as $range) {
		if (ip_in_range($client_ip, $range)) {
			return true;
		}
	}

	return false;
}

function entity_view_counter_count_views(ElggEntity $entity) {
	$guid = (int) $entity->guid;
	$count = get_data_row("SELECT views FROM elgg_entity_views WHERE guid = {$guid}");

	if ($count->views) {
		return $count->views;
	}

	return 0;
}