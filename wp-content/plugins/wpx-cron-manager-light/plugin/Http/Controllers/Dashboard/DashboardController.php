<?php

namespace WPXCronManagerLight\Http\Controllers\Dashboard;

use WPXCronManagerLight\Http\Controllers\Controller;
use WPXCronManagerLight\Http\Controllers\Dashboard\CronListTable;

class DashboardController extends Controller
{

  public function index()
  {

    $table = new CronListTable();

    return WPXCronManagerLight()
      ->view( 'dashboard.index' )
      ->withAdminScripts( 'wpxcm-main' )
      ->withAdminStyles( 'wpxcm' )
      ->with( 'table', $table );
  }
}