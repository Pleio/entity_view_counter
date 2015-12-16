<?php

function entity_view_counter_entity_menu_hook($hook, $type, $returnvalue, $params) {
	$result = $returnvalue;

	if (empty($params) | !is_array($params)) {
		return $result;
	}

	$entity = elgg_extract("entity", $params);
	if (empty($entity)) {
		return $result;
	}

	if (!entity_view_counter_is_configured_entity_type($entity->getType(), $entity->getSubtype())) {
		return $result;
	}

	$count = entity_view_counter_count_views($entity);

	$text = "<span title='" . htmlspecialchars(elgg_echo("entity_view_counter:entity:menu:views", array($count)), ENT_QUOTES, "UTF-8", false) . "'>";
	$text .= elgg_view_icon("eye") . $count;
	$text .= "</span>";

	$result[] = ElggMenuItem::factory(array(
		"name" => "view_counter",
		"text" => $text,
		"href" => false,
		"priority" => 300
	));

	return $result;
}

function entity_view_counter_plugin_setting_hook($hook, $type, $returnvalue, $params) {
	$result = $returnvalue;

	if (!empty($params) && is_array($params)) {
		$plugin = elgg_extract("plugin", $params);
		$setting = elgg_extract("name", $params);

		if (($plugin->getID() == "entity_view_counter") && ($setting = "entity_types")) {
			$result = json_encode(elgg_extract("value", $params));
		}
	}

	return $result;
}