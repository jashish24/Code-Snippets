<?php

namespace Drupal\consume_webservice\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Tags;

/**
 * Webservice list Controller
 */

class SearchKexample extends ControllerBase {
  public function getCexamples(Request $request) {
    $results = [];

    //Get typed string from the URL, if it exists.
    if ($input = $request -> query -> get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));

      $consumeks_config = \Drupal::config('consume_webservice.settings');
      $base_service = $consumeks_config -> get('base_service');
      $cexample_list_service = $consumeks_config -> get('get_cexamples_service');

      $cexample_per_page = 10;

      $data = [
        'limit' => $cexample_per_page,
        'offset' => 0,
        'name' => $typed_string,
      ];

      $parameters = http_build_query($data);
      $request_path = $base_service . $cexample_list_service . '?' . $parameters;

      $data = perform_webservice_get($request_path);

      if ($data) {
        foreach ($data -> data as $key => $cexample) {
          $results[$cexample -> id] = $cexample -> cexample . ' (type: ' . $cexample -> type . ')';
        }
      }
    }

    return new JsonResponse($results);
  }

  public function getKexamples(Request $request) {
    $results = [];

    //Get typed string from the URL, if it exists.
    if ($input = $request -> query -> get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));

      $consumeks_config = \Drupal::config('consume_webservice.settings');
      $base_service = $consumeks_config -> get('base_service');
      $kexample_list_service = $consumeks_config -> get('get_kexamples_service');

      $kexample_per_page = 10;

      $data = [
        'limit' => $kexample_per_page,
        'offset' => 0,
        'name' => $typed_string,
      ];

      $parameters = http_build_query($data);
      $request_path = $base_service . $kexample_list_service . '?' . $parameters;

      $data = perform_webservice_get($request_path);

      if ($data) {
        foreach ($data -> data as $key => $kexample) {
          $results[$kexample -> id] = $kexample -> kexample . ' (id: ' . $kexample -> id . ')';
        }
      }
    }

    return new JsonResponse($results);
  }
}
