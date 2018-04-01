<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Use minified styles and scripts
  |--------------------------------------------------------------------------
  |
  | If you would like gulp to compile and compress your styles and scripts
  | the filenames in `public/css` will have `.min` as postfix. If this
  | setting is TRUE then will be used the minified version.
  |
  */

  'minified' => true,

  /*
  |--------------------------------------------------------------------------
  | Screen options
  |--------------------------------------------------------------------------
  |
  | Here is where you can register the screen options for List Table.
  |
  */

  'screen_options' => [
    'crons_per_page' => 'WPXCronManagerLight\Http\Controllers\Dashboard\CronListTable'
  ],

  /*
  |--------------------------------------------------------------------------
  | Custom Post Types
  |--------------------------------------------------------------------------
  |
  | Here is where you can register the Custom Post Types.
  |
  */

  'custom_post_types' => [ ],

  /*
  |--------------------------------------------------------------------------
  | Custom Taxonomies
  |--------------------------------------------------------------------------
  |
  | Here is where you can register the Custom Taxonomy Types.
  |
  */

  'custom_taxonomy_types' => [ ],


  /*
  |--------------------------------------------------------------------------
  | Shortcodes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register the Shortcodes.
  |
  */

  'shortcodes' => [ ],


  /*
  |--------------------------------------------------------------------------
  | Ajax
  |--------------------------------------------------------------------------
  |
  | Here is where you can register your own Ajax actions.
  |
  */

  'ajax' => [ 'WPXCronManagerLight\Ajax\WPXCronManagerLightAjax' ],

];