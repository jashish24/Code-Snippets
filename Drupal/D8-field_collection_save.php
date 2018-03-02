<?php
  use Drupal\node\Entity\Node;
  class Blah extends XYZ {
    // Use anywhere you want to create new field collection
    function callback_function() {
      // Loading node for which field collection has to be updated
      $host_node = Node::load($host_node_id);
      
      // Create field collection with field name "field_videos_watched" (machine name of field collection)
      $field_collection_new_object = entity_create('field_collection_item', array('field_name' => 'field_videos_watched'));
      //Set the relationship to the host node.
      $field_collection_new_object -> setHostEntity($host_node_id);
      //Set the field_single_video value.
      $field_collection_new_object -> set('field_single_video', $video_id); // Field in field collection (@var video_id)
      //Set the field_single_video_progress value
      $field_collection_new_object -> set('field_single_video_progress', $video_time); // Field in field collection (@var video_id)
      //Save the field_collection item. This will save the host node too.
      $field_collection_new_object -> save();
    }
  }