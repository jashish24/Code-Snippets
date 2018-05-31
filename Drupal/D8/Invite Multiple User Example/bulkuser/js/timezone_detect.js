/**
 * @file
 * Determine and set user's timezone on page load.
 * 
 * @todo: Use Drupal.behaviors?
 */
jQuery(document).ready(function () {

  // Determine timezone from browser client using jsTimezoneDetect library.
  var tz = jstz.determine();
 
    // Set any timezone select on this page to the detected timezone.
    jQuery('select[name="timezone"] > option[value="' + tz.name() + '"]')
      .closest('select')
      .val(tz.name());  
});
