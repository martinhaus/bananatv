<?php

/*
|--------------------------------------------------------------------------
| Plugin Menus routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the menu routes for a plugin.
| In this context the route are the menu link.
|
*/

return [
  'wpx_cron_manager_light_slug_menu' => [
    "menu_title" => "Cron Manager",
    'capability' => 'manage_options',
    'icon'       => 'dashicons-clock',
    'items'      => [
      [
        "menu_title" => "Cron List",
        'route'      => [
          'get' => 'Dashboard\DashboardController@index'
        ],
      ],
    ]
  ]
];
