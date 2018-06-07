<?php

namespace Drupal\consume_webservice\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\file\Entity\File;

/**
 * Webservice list Controller
 */

class KexamplesListings extends ControllerBase {
  public function cexamples() {
    $consumeks_config = \Drupal::config('consume_webservice.settings');
    $base_service = $consumeks_config -> get('base_service');
    $cexample_list_service = $consumeks_config -> get('get_cexamples_service');

    $cexample_per_page = $consumeks_config -> get('get_cexamples_per_page');
    $offset = isset($_GET['page']) ? ($cexample_per_page * strip_tags($_GET['page'])) : 0;

    $data = [
      'limit' => $cexample_per_page,
      'offset' => $offset,
    ];

    $parameters = http_build_query($data);
    $request_path = $base_service . $cexample_list_service . '?' . $parameters;

    $data = perform_webservice_get($request_path);

    $header = [
      'serial' => $this -> t('S. No.'),
      'cexample' => $this -> t('cexample'),
      'code' => $this -> t('Code'),
      'level' => $this -> t('Level'),
      'type' => $this -> t('Type'),
      'last_modified' => $this -> t('Updated'),
      'operation' => $this -> t('Operation')
    ];

    if ($data) {
      $table_rows = [];
      $row_num = 0;
      $serial = $offset;
      // Now that we have the total number of results, initialize the pager.
      pager_default_initialize($data -> total, $cexample_per_page);

      foreach ($data -> data as $key => $cexample_data) {
        $updated = date('Y-m-d H:i:s', strtotime($cexample_data -> last_modified));

        $link_unlink = Link::createFromRoute($this -> t('Link / Unlink'),
          'consume_webservice.updatecexamples',
          ['cid' => $cexample_data -> id],
          ['attributes' => [
              'class' => ['update-cexample'],
              'target' => '_blank',
            ],
          ]
        );

        $edit_link = Link::createFromRoute($this -> t('Edit'),
          'consume_webservice.editcexample',
          ['cid' => $cexample_data -> id],
          ['attributes' => [
              'class' => ['edit-cexample'],
              'target' => '_blank',
            ],
          ]
        );

        $link_unlink = $link_unlink -> toRenderable();
        $edit_link = $edit_link -> toRenderable();

        $table_rows[$row_num]['serial'] = $serial + 1;
        $table_rows[$row_num]['cexample'] = $cexample_data -> cexample;
        $table_rows[$row_num]['code'] = $cexample_data -> code;
        $table_rows[$row_num]['level'] = $cexample_data -> level;
        $table_rows[$row_num]['type'] = $cexample_data -> type;
        $table_rows[$row_num]['last_modified'] = $updated;
        $table_rows[$row_num]['operation'] = t('@edit | @lunlink', ['@edit' => render($edit_link), '@lunlink' => render($link_unlink)]);
        $row_num++;
        $serial++;
      }

      $renderable_output['pager_top'] = [
        '#type' => 'pager',
        '#weight' => 1,
      ];

      $renderable_output['cexamples'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $table_rows,
        '#empty' => t('No cexamples found.'),
        '#weight' => 2,
      ];

      $renderable_output['pager_bottom'] = [
        '#type' => 'pager',
        '#weight' => 3,
      ];

      return $renderable_output;
    }

  	return [
  	  '#type' => 'markup',
  	  '#markup' => '<h2>' . $this -> t('If you are seeing this message even after proper settings. You should try logging out and log in again to renew access token.') . '</h2>',
  	];
  }

  // Function to list kexamples with details
  public function kexamples() {
    $consumeks_config = \Drupal::config('consume_webservice.settings');
    $base_service = $consumeks_config -> get('base_service');
    $kexamples_list_service = $consumeks_config -> get('get_kexamples_service');

    $kexamples_per_page = $consumeks_config -> get('get_kexamples_per_page');
    $offset = isset($_GET['page']) ? ($kexamples_per_page * strip_tags($_GET['page'])) : 0;

    $data = [
      'limit' => $kexamples_per_page,
      'offset' => $offset,
    ];

    $parameters = http_build_query($data);
    $request_path = $base_service . $kexamples_list_service . '?' . $parameters;

    $data = perform_webservice_get($request_path);

    $header = [
      'serial' => $this -> t('S. No.'),
      'kexample' => $this -> t('kexample'),
      'status' => $this -> t('Status'),
      'created' => $this -> t('Created'),
      'last_modified' => $this -> t('Updated'),
      'operation' => $this -> t('Operation')
    ];

    if ($data) {
      $table_rows = [];
      $row_num = 0;
      $serial = $offset;
      // Now that we have the total number of results, initialize the pager.
      pager_default_initialize($data -> total, $kexamples_per_page);

      foreach ($data -> data as $key => $kexample_data) {
        $updated = date('Y-m-d H:i:s', strtotime($kexample_data -> last_modified));
        $created = date('Y-m-d H:i:s', strtotime($kexample_data -> created));

        $link_unlink = Link::createFromRoute($this -> t('Link / Unlink'),
          'consume_webservice.updatekexamples',
          ['kid' => $kexample_data -> id],
          ['attributes' => [
              'class' => ['update-kexample'],
              'target' => '_blank',
            ],
          ]
        );

        $edit_link = Link::createFromRoute($this -> t('Edit'),
          'consume_webservice.editkexample',
          ['kid' => $kexample_data -> id],
          ['attributes' => [
              'class' => ['edit-kexample'],
              'target' => '_blank',
            ],
          ]
        );

        $delete_link = Link::createFromRoute($this -> t('Delete'),
          'consume_webservice.deletekexample',
          ['kid' => $kexample_data -> id],
          ['attributes' => [
              'class' => ['delete-kexample'],
              'target' => '_blank',
            ],
          ]
        );

        $edit_link = $edit_link -> toRenderable();
        $delete_link = $delete_link -> toRenderable();
        $link_unlink = $link_unlink -> toRenderable();

        $operations = t('@edit | @lunlink | @delete', ['@edit' => render($edit_link), '@lunlink' => render($link_unlink), '@delete' => render($delete_link)]);

        $table_rows[$row_num]['serial'] = $serial + 1;
        $table_rows[$row_num]['kexamples'] = $kexample_data -> kexample;
        $table_rows[$row_num]['status'] = $kexample_data -> status;
        $table_rows[$row_num]['created'] = $created;
        $table_rows[$row_num]['last_modified'] = $updated;
        $table_rows[$row_num]['operation'] = $operations;
        $row_num++;
        $serial++;
      }

      $renderable_output['pager_top'] = [
        '#type' => 'pager',
        '#weight' => 1,
      ];

      $renderable_output['cexamples'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $table_rows,
        '#empty' => t('No cexamples found.'),
        '#weight' => 2,
      ];

      $renderable_output['pager_bottom'] = [
        '#type' => 'pager',
        '#weight' => 3,
      ];

      return $renderable_output;
    }

  	return [
  	  '#type' => 'markup',
  	  '#markup' => '<h2>' . $this -> t('If you are seeing this message even after proper settings. You should try logging out and log in again to renew access token.') . '</h2>',
  	];
  }
}
