<?php

  $raw_node = node_load($nid);
  
  //Create the collection entity and set it's "host".
  $collection = entity_create('field_collection_item', array('field_name' => 'field_cost_usage_data')); // field_cost_usage_data => field collection machine name
  
  // Attaching collection to the requried node
  $collection -> setHostEntity('node', $raw_node);
  
  $cwrapper = entity_metadata_wrapper('field_collection_item', $collection); // Load entity metadata wrapper for created collection
  
  $cwrapper -> field_cost_usage_state -> set(114); // Set field with machine name field_cost_usage_state
  $cwrapper -> 	field_cost_load -> set(6325); // Set field with machine name field_cost_load
  $cwrapper -> field_usage_load -> set(465); // Set field with machine name field_usage_load
  
  //To save the node after modifying
  $cwrapper -> save();