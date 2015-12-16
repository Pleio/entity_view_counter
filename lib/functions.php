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
	if (!entity_view_counter_is_counted($entity)) {
		return null;
	}

	$current_session = elgg_get_entities_from_annotations(array(
		"guid" => $entity->guid,
		"annotation_name" => ENTITY_VIEW_COUNTER_ANNOTATION_NAME,
		"annotation_value" => session_id(),
		"count" => true
	));

	if ($current_session) {
		return null;
	}

	$new_annotation_owner_guid = elgg_get_logged_in_user_guid();
	if (empty($new_annotation_owner_guid)) {
		$new_annotation_owner_guid = $entity->getGUID();
	}

	$entity->annotate(ENTITY_VIEW_COUNTER_ANNOTATION_NAME, session_id(), ACCESS_PUBLIC, $new_annotation_owner_guid);

	$count = $entity->getPrivateSetting(ENTITY_VIEW_COUNTER_ANNOTATION_NAME);
	if (is_int($count)) {
		return $entity->setPrivateSetting(ENTITY_VIEW_COUNTER_ANNOTATION_NAME, $count + 1);
	}
}

function entity_view_counter_is_counted(ElggEntity $entity) {
	if (!entity_view_counter_is_configured_entity_type($entity->getType(), $entity->getSubtype())) {
		return false;
	}

	if (isset($_SERVER["HTTP_USER_AGENT"]) && preg_match('/bot|crawl|slurp|spider/i', $_SERVER["HTTP_USER_AGENT"])) {
		return false;
	}

	$user = elgg_get_logged_in_user_entity();
	if ($user && $user->getGUID() == $entity->getOwnerGUID()) {
		return false;
	}

	return true;

}

function entity_view_counter_count_views(ElggEntity $entity) {
	$count = $entity->getPrivateSetting(ENTITY_VIEW_COUNTER_ANNOTATION_NAME);
	if (is_int($count)) {
		return $count;
	}

	$count = $entity->countAnnotations(ENTITY_VIEW_COUNTER_ANNOTATION_NAME);
	$entity->setPrivateSetting(ENTITY_VIEW_COUNTER_ANNOTATION_NAME, $count);

	return $count;
}