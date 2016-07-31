<?php

$english = array(
	'entity_view_counter:entity:menu:views' => "%d views",
	'entity_view_counter:settings:description' => "In order to track the views of an entity type, please check it below. If you enable a new plugin you may have to check back here, because by default the entity type will NOT be tracked.",
	'entity_view_counter:settings:entity_type' => "Entity type to be tracked",
    'entity_view_counter:settings:ignore_ips' => "Ignore these IPs when counting views",
    'entity_view_counter:settings:ignore_ips:description' => "Do not count views comming from these IP ranges (comma seperated). Example: 127.0.0.1/32, 127.1.0.0/24",

);

add_translation("en", $english);