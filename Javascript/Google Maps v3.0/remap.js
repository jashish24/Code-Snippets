$ = jQuery.noConflict();
var neonetworkProjectMap = neonetworkProjectMap || {};
var neonetworkProjectMap1 = neonetworkProjectMap1 || {};
var markers = [];
var markers1 = [];
var infoWindow;
var mapCenter_toggle;
var center_lat = parseFloat(Drupal.settings.center_lat);
var center_long = parseFloat(Drupal.settings.center_long);

function gmapInitialize() {
  
  markers = [];
  markers1 = [];
  
  // creates map - Offsite
  var mapPosition;
  var marker;
  var currentlyOpenedInfowindow;
  var bounds = new google.maps.LatLngBounds();
  
  if (!neonetworkProjectMap.map) {
    var mapCenter = new google.maps.LatLng(center_lat, center_long);
    var mapOptions = {};
      mapOptions = {
        center: mapCenter,
        zoom: 1,
        mapTypeId: google.maps.MapTypeId.ROADMAP
      }
    neonetworkProjectMap.map = new google.maps.Map(document.getElementById("project-remap"), mapOptions);
  }
  
  // Create markers.
  features.forEach(function(feature) {
    var lposition = new google.maps.LatLng(feature.field_geolocation_lat, feature.field_geolocation_lon);
    bounds.extend(lposition);
    var marker = new google.maps.Marker({
      position: lposition,
      icon: '/sites/default/files/' + feature.uri.replace('public://', ''),
      title: feature.cmpny_title + '\n' + feature.title + '\nSize: ' + feature.field_size_value + 'MW\nTechnology: ' + feature.name,
      map: neonetworkProjectMap.map
    });
    
    marker.addListener('click', function() {
      window.open(feature.path, '_blank');
    });
    
    markers.push(marker);
  });
  
  mapCenter_toggle = new google.maps.LatLng(center_lat, center_long);
  //neonetworkProjectMap.map.fitBounds(bounds);
  neonetworkProjectMap.map.setCenter(mapCenter_toggle);
  neonetworkProjectMap.map.setZoom(4);
  
  // creates map - Onsite
  var mapPosition1;
  var marker1;
  var currentlyOpenedInfowindow1;
  var bounds = new google.maps.LatLngBounds();
  
  if (!neonetworkProjectMap1.map) {
    var mapCenter = new google.maps.LatLng(center_lat, center_long);
    var mapOptions = {};
      mapOptions = {
        center: mapCenter,
        zoom: 4,
        mapTypeId: google.maps.MapTypeId.ROADMAP
      }
    neonetworkProjectMap1.map = new google.maps.Map(document.getElementById("project-remap-cs"), mapOptions);
  }
  
  // Create markers.
  features_cs.forEach(function(feature) {
    var lposition = new google.maps.LatLng(feature.field_geolocation_lat, feature.field_geolocation_lon);
    bounds.extend(lposition);
    var f_uri = (feature.uri == null) ? '' : feature.uri;
    var f_td1_name = (feature.td1_name == null) ? 'None' : feature.td1_name;
    var marker = new google.maps.Marker({
      position: lposition,
      visible: false,
      icon: '/sites/default/files/' + f_uri.replace('public://', ''),
      title: feature.cmpny_title + '\n' + feature.title + '\nTechnology: ' + f_td1_name,
      map: neonetworkProjectMap1.map
    });
    
    marker.addListener('click', function() {
      $('#edit-technology-1').val(feature.field_industry_tid);
      $('#edit-geography-1').val(feature.field_geo_state_tid);
      $('#casestudy-filter-form .apply-filters').trigger('click');
    });
    
    markers1.push(marker);
    if (typeof(features_states[feature.namekey]) != 'undefined') {
      var poly1 = new google.maps.Polygon({
        paths: features_states[feature.namekey],
        strokeColor: '#FF0000',
        strokeOpacity: 0.8,
        strokeWeight: 1,
        fillColor: '#27974F',
        fillOpacity: 0.5
      });
      
      poly1.setMap(neonetworkProjectMap1.map);
      
      poly1.addListener('click', function(event) {
        $('#edit-geography-1').val(feature.field_geo_state_tid);
        $('#casestudy-filter-form .apply-filters').trigger('click');
      });
    }
  });
  
  /* Change markers on zoom */
  google.maps.event.addListener(neonetworkProjectMap1.map, 'zoom_changed', function() {
    var zoom = neonetworkProjectMap1.map.getZoom();
    // iterate over markers and call setVisible
    for (i = 0; i < markers1.length; i++) {
      markers1[i].setVisible(zoom >= 6);
    }
  });
  
  infoWindow = new google.maps.InfoWindow;
  mapCenter_toggle = new google.maps.LatLng(center_lat, center_long);
  neonetworkProjectMap1.map.setCenter(mapCenter_toggle);
  //neonetworkProjectMap1.map.fitBounds(bounds);
  $('.casestudies-wrapper').hide();
}

function get_projects_list(obj) {
  $('.maps-loader-wrapper').show();
  var s_geography = $.trim($('#edit-geography').val());
  var s_technology = $.trim($('#edit-technology').val());
  var s_provider = $.trim($('#edit-provider').val());
  var page = $.trim($(obj).attr('rel'));
  $.ajax({
    type: 'POST',
    url: 'get-projects',
    data: 'geography=' + s_geography + '&technology=' + s_technology + '&provider=' + s_provider + '&page=' + page,
    dataType: 'json',
    success: function(results) {
      map_update();
      recreate_map(results.features);
      $('#filtered-projects').html(results.data);
      $('.maps-loader-wrapper').hide();
      
      var href_project_d = 'get-downloads?type=project' + '&geography=' + s_geography + '&technology=' + s_technology + '&provider=' + s_provider;
      $('.projects-list-wrapper .feed-icon a').attr('href', href_project_d);
      
      flag_reset(1);
    },
    error: function() {
      $('.maps-loader-wrapper').hide();
    }
  });
}

function get_casestudies_list(obj, sort) {
  $('.maps-loader-wrapper').show();
  var s_geography = $.trim($('#edit-geography-1').val());
  var s_technology = $.trim($('#edit-technology-1').val());
  var s_provider = $.trim($('#edit-provider-1').val());
  var page = $.trim($(obj).attr('rel'));
  sort = (sort == 3) ? $.trim($('#sord-orders a.active').attr('rel')) : sort;
  
  $.ajax({
    type: 'POST',
    url: 'get-casestudies',
    data: 'geography=' + s_geography + '&technology=' + s_technology + '&provider=' + s_provider + '&page=' + page + '&sort=' + sort,
    dataType: 'json',
    success: function(results) {
      map_update_cs();
      recreate_map_cs(results.features);
      $('#filtered-casestudies').html(results.data);
      $('.maps-loader-wrapper').hide();
      
      var href_casestudies_d = 'get-downloads?type=casestudies' + '&geography=' + s_geography + '&technology=' + s_technology + '&provider=' + s_provider;
      $('.casestudies-wrapper .feed-icon-cs a').attr('href', href_casestudies_d);
      
      flag_reset(2);
    },
    error: function() {
      $('.maps-loader-wrapper').hide();
    }
  });
}

function map_update() {
  for (j in markers) {
    markers[j].setMap(null);
    delete markers[j];
  }
}

function map_update_cs() {
  for (j in markers1) {
    markers1[j].setMap(null);
    delete markers1[j];
  }
}

function recreate_map(features_new) {
  // creates map
  var mapPosition;
  var marker;
  var currentlyOpenedInfowindow;
  var bounds = new google.maps.LatLngBounds();
  
  if (!neonetworkProjectMap.map) {
    var mapCenter = new google.maps.LatLng(0, 0);
    var mapOptions = {};
      mapOptions = {
        center: mapCenter,
        zoom: 1,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        maxZoom: 15
      }
    neonetworkProjectMap.map = new google.maps.Map(document.getElementById("project-remap"), mapOptions);
  }
  
  // Create markers.
  markers = [];
  features_new.forEach(function(feature) {
    if (feature.field_geolocation_lat != null && feature.field_geolocation_lon != null) {
      var lposition = new google.maps.LatLng(feature.field_geolocation_lat, feature.field_geolocation_lon);
      var x_adjust = 0.00001;
      
      // check if this position has already had a marker
      for(var x = 0; x < markers.length; x++) {
        if (typeof(markers[x].getPosition()) != 'undefined') {
          while (markers[x].getPosition().equals(lposition)) {
            lposition = new google.maps.LatLng(parseFloat(feature.field_geolocation_lat) + x_adjust, parseFloat(feature.field_geolocation_lon) + x_adjust);
            x_adjust += 0.00001;
          }
        }
      }
      
      bounds.extend(lposition);
      var marker = new google.maps.Marker({
        position: lposition,
        icon: '/sites/default/files/' + feature.uri.replace('public://', ''),
        title: feature.cmpny_title + '\n' + feature.title + '\nTechnology: ' + feature.name,
        map: neonetworkProjectMap.map
      });
      
      marker.addListener('click', function() {
        window.open(feature.path, '_blank');
      });
      
      markers.push(marker);
    }
  });
  
  neonetworkProjectMap.map.fitBounds(bounds);
  if (markers.length == 0) {
    // US Center
    var usCenter = {lat: center_lat, lng: center_long};
    neonetworkProjectMap.map.setZoom(4);
    neonetworkProjectMap.map.setCenter(usCenter);
  }
  else if (markers.length == 1) {
    neonetworkProjectMap.map.setZoom(4);
  }
  else {
    neonetworkProjectMap.map.fitBounds(bounds);
    neonetworkProjectMap.map.setZoom(3);
  }
}


function recreate_map_cs(features_new) {
  // creates map
  var mapPosition;
  var marker;
  var currentlyOpenedInfowindow;
  var bounds = new google.maps.LatLngBounds();
  
  var mapCenter = new google.maps.LatLng(0, 0);
  var mapOptions = {};
    mapOptions = {
      center: mapCenter,
      zoom: 1,
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      //maxZoom: 8
    }
  neonetworkProjectMap1.map = new google.maps.Map(document.getElementById("project-remap-cs"), mapOptions);
  
  // Create markers.
  markers1 = [];
  features_new.forEach(function(feature) {
    if (feature.field_geolocation_lat != null && feature.field_geolocation_lon != null) {
      var lposition = new google.maps.LatLng(feature.field_geolocation_lat, feature.field_geolocation_lon);
      var cmpny_title = (feature.cmpny_title == null) ? 'Neo' : feature.cmpny_title;
      var x_adjust = 0.00001;
      
      // check if this position has already had a marker
      for(var x = 0; x < markers1.length; x++) {
        while (markers1[x].getPosition().equals(lposition)) {
          lposition = new google.maps.LatLng(parseFloat(feature.field_geolocation_lat) + x_adjust, parseFloat(feature.field_geolocation_lon) + x_adjust);
          x_adjust += 0.00001;
        }
      }
      
      bounds.extend(lposition);
      var marker = new google.maps.Marker({
        position: lposition,
        icon: '/sites/default/files/' + feature.fmgd1_uri.replace('public://', ''),
        title: cmpny_title + '\n' + feature.title + '\nTechnology: ' + feature.td1_name,
        map: neonetworkProjectMap1.map
      });
      
      marker.addListener('click', function() {
        $('#edit-technology-1').val(feature.field_industry_tid);
        $('#edit-geography-1').val(feature.field_geo_state_tid);
        $('#casestudy-filter-form .apply-filters').trigger('click');
      });
      
      markers1.push(marker);
      
    if (typeof(features_states[feature.namekey]) != 'undefined') {
      var poly1 = new google.maps.Polygon({
        paths: features_states[feature.namekey],
        strokeColor: '#FF0000',
        strokeOpacity: 0.8,
        strokeWeight: 1,
        fillColor: '#27974F',
        fillOpacity: 0.5
      });
      
      poly1.setMap(neonetworkProjectMap1.map);
      
      poly1.addListener('click', function(event) {
        $('#edit-geography-1').val(feature.field_geo_state_tid);
        $('#casestudy-filter-form .apply-filters').trigger('click');
      });
    }
      
    }
  });
  
  /* Change markers on zoom */
  google.maps.event.addListener(neonetworkProjectMap1.map, 'zoom_changed', function() {
    var zoom = neonetworkProjectMap1.map.getZoom();
    // iterate over markers and call setVisible
    for (i = 0; i < markers1.length; i++) {
      markers1[i].setVisible(zoom >= 1);
    }
  });
  
  neonetworkProjectMap1.map.fitBounds(bounds);
  if (markers1.length == 0) {
    // US Center
    var usCenter = {lat: center_lat, lng: center_long};
    neonetworkProjectMap1.map.setZoom(4);
    neonetworkProjectMap1.map.setCenter(usCenter);
  }
  else if (markers1.length == 1) {
    neonetworkProjectMap1.map.setZoom(4);
  }
  else {
    neonetworkProjectMap1.map.fitBounds(bounds);
    neonetworkProjectMap1.map.setZoom(3);
  }
}

window.onload = function(e) {
  gmapInitialize();
}

$(document).ready(function() {
  var top_v = $('#maps-view-tabs').offset().top - $('#region-content').offset().top;
  $('#region-sidebar-first').css('margin-top', top_v + 'px');
  
  $('form#project-filter-form .apply-filters').on('tap click', function() {
    get_projects_list(this);
  });
  
  $('form#casestudy-filter-form .apply-filters').on('tap click', function() {
    get_casestudies_list(this, 3);
  });
  
  $('#project-filter-form .reset-filters').on('tap click', function() {
    $('#project-filter-form')[0].reset();
    reset_map();
  });
  
  $('#casestudy-filter-form .reset-filters').on('tap click', function() {
    $('#casestudy-filter-form')[0].reset();
    reset_map_cs();
  });
  
  // Sort Order - Case Studies
  $('#sord-orders a').on('tap click', function() {
    var data = $.trim($('#filtered-casestudies').text());
    
    $('#sord-orders a').removeClass('active');
    $(this).addClass('active');
    if (data != '') {
      var sort = $.trim($(this).attr('rel'));
      get_casestudies_list('', sort);
    }
  });
  
  // Maps Toggle
  $('#maps-view-tabs a.offsite').on('click tap', function(e) {
    $('#maps-view-tabs a.onsite').removeClass('active');
    $(this).addClass('active');
    $('.casestudies-wrapper, #block-neonetwork-remap-casestudies-filters, #block-views-technology-list-block-1').hide();
    $('.projects-list-wrapper, #block-neonetwork-remap-project-maps-filters, #block-views-technology-list-block').show();
    google.maps.event.trigger(neonetworkProjectMap.map, 'resize');
    neonetworkProjectMap.map.setZoom(4);
    neonetworkProjectMap.map.setCenter(mapCenter_toggle);
    e.preventDefault();
  });
  
  $('#maps-view-tabs a.onsite').on('click tap', function(e) {
    $('#maps-view-tabs a.offsite').removeClass('active');
    $(this).addClass('active');
    $('.casestudies-wrapper, #block-neonetwork-remap-casestudies-filters, #block-views-technology-list-block-1').show();
    $('.projects-list-wrapper, #block-neonetwork-remap-project-maps-filters, #block-views-technology-list-block').hide();
    google.maps.event.trigger(neonetworkProjectMap1.map, 'resize');
    neonetworkProjectMap1.map.setZoom(4);
    neonetworkProjectMap1.map.setCenter(mapCenter_toggle);
    e.preventDefault();
  });
});

/**
 * Function to rebuild events on flag button
 */
function flag_reset(type_list) {
  $('.flag-wrapper a').unbind();
  $('.flag-wrapper a').on('click', function() {
    $('.maps-loader-wrapper').show();
    var flag_href = $.trim($(this).attr('href'));
    var unflag = $(this).hasClass('unflag-action');
    var title = '';
    if (type_list == 1) {
      title = $.trim($(this).parents('.project-list').find('.project-title a').text());
    }
    else {
      title = $.trim($(this).parents('.casestudy-list').find('.casestudy-title a').text());
    }
    $.ajax({
      type: 'GET',
      url: flag_href,
      data: '',
      success: function(results) {
        if (unflag) {
          alert(title + ' has been removed from your favorites');
        }
        else {
          alert(title + ' has been added to your favorites');
        }
        $('.maps-loader-wrapper').hide();
        if (type_list == 1) {
          get_projects_list();
        }
        else {
          var sort = $.trim($('#sord-orders a.active').attr('rel'));
          get_casestudies_list('', sort);
        }
      },
      error: function() {
        alert('Error processing. Please try again later.');
      }
    });
    
    return false;
  });
}
/**
 * Function to raise remove project request
 */
function remove_project(obj) {
  var rmprjct = $.trim($(obj).attr('rel'));
  $('.maps-loader-wrapper').show();
  
  $.ajax({
    type: 'POST',
    url: 'remove-project',
    data: 'nid=' + rmprjct,
    success: function(results) {
      alert('Request successfully submitted!\nPlease allow up to 48 hours for the\nrequest to be completed.');
      $('.maps-loader-wrapper').hide();
    },
    error: function() {
      alert('Error processing. Please try again later.');
      $('.maps-loader-wrapper').hide();
    }
  });
  
}

/**
 * Function to reset maps - Project
 */

function reset_map() {
  markers = [];
  
  // creates map - Offsite
  var mapPosition;
  var marker;
  var currentlyOpenedInfowindow;
  var bounds = new google.maps.LatLngBounds();
  var mapCenter = new google.maps.LatLng(center_lat, center_long);
  var mapOptions = {};
    mapOptions = {
      center: mapCenter,
      zoom: 4,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    }
  neonetworkProjectMap.map = new google.maps.Map(document.getElementById("project-remap"), mapOptions);
  
  // Create markers.
  features.forEach(function(feature) {
    var lposition = new google.maps.LatLng(feature.field_geolocation_lat, feature.field_geolocation_lon);
    bounds.extend(lposition);
    var marker = new google.maps.Marker({
      position: lposition,
      icon: '/sites/default/files/' + feature.uri.replace('public://', ''),
      title: feature.cmpny_title + '\n' + feature.title + '\nSize: ' + feature.field_size_value + 'MW\nTechnology: ' + feature.name,
      map: neonetworkProjectMap.map
    });
    
    marker.addListener('click', function() {
      window.open(feature.path, '_blank');
    });
    
    markers.push(marker);
  })
  
  //neonetworkProjectMap.map.fitBounds(bounds);
  $('#filtered-projects').html('&nbsp;');
}

/**
 * Function to reset maps - Case Studies
 */

function reset_map_cs() {
  markers1 = [];
  
  // creates map - Onsite
  var mapPosition1;
  var marker1;
  var currentlyOpenedInfowindow1;
  var bounds = new google.maps.LatLngBounds();
  var mapCenter = new google.maps.LatLng(center_lat, center_long);
  var mapOptions = {};
    mapOptions = {
      center: mapCenter,
      zoom: 4,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    }
  neonetworkProjectMap1.map = new google.maps.Map(document.getElementById("project-remap-cs"), mapOptions);
  
  // Create markers.
  features_cs.forEach(function(feature) {
    var lposition = new google.maps.LatLng(feature.field_geolocation_lat, feature.field_geolocation_lon);
    bounds.extend(lposition);
    var f_uri = (feature.uri == null) ? '' : feature.uri;
    var f_td1_name = (feature.td1_name == null) ? 'None' : feature.td1_name;
    var marker = new google.maps.Marker({
      position: lposition,
      visible: false,
      icon: '/sites/default/files/' + f_uri.replace('public://', ''),
      title: feature.cmpny_title + '\n' + feature.title + '\nTechnology: ' + f_td1_name,
      map: neonetworkProjectMap1.map
    });
    
    marker.addListener('click', function() {
      $('#edit-technology-1').val(feature.field_industry_tid);
      $('#edit-geography-1').val(feature.field_geo_state_tid);
      $('#casestudy-filter-form .apply-filters').trigger('click');
    });
    
    markers1.push(marker);
    if (typeof(features_states[feature.namekey]) != 'undefined') {
      var poly1 = new google.maps.Polygon({
        paths: features_states[feature.namekey],
        strokeColor: '#FF0000',
        strokeOpacity: 0.8,
        strokeWeight: 1,
        fillColor: '#27974F',
        fillOpacity: 0.5
      });
      
      poly1.setMap(neonetworkProjectMap1.map);
      
      poly1.addListener('click', function(event) {
        $('#edit-geography-1').val(feature.field_geo_state_tid);
        $('#casestudy-filter-form .apply-filters').trigger('click');
      });
    }
  });
  
  /* Change markers on zoom */
  google.maps.event.addListener(neonetworkProjectMap1.map, 'zoom_changed', function() {
    var zoom = neonetworkProjectMap1.map.getZoom();
    // iterate over markers and call setVisible
    for (i = 0; i < markers1.length; i++) {
      markers1[i].setVisible(zoom >= 6);
    }
  });
  
  $('#filtered-casestudies').html('&nbsp;');
}
