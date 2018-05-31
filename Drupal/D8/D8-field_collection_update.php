<?php
  use Drupal\node\Entity\Node;
  class Blah extends XYZ {
    // Use anywhere you want to update existing field collection
    function callback_function() {
      //Host node is not required as it is already set (use field_collection_save if you want to create new collection)
      
      //Loading field collection using collection id
      $field_collection_existing_object = \Drupal\field_collection\Entity\FieldCollectionItem::load($field_collection_id);
      //Set the field_single_video value.
      $field_collection_existing_object -> set('field_single_video', $video_id); // Field in field collection (@var video_id)
      //Set the field_single_video_progress value
      $field_collection_existing_object -> set('field_single_video_progress', $video_time); // Field in field collection (@var video_id)
      //Save the field_collection item. This will save the host node too.
      $field_collection_existing_object -> save();
    }
  }