<?php

namespace WPXCronManagerLight\WPBones\Routing;

class Route {

  static $menu = [];


  public static function get( $path )
  {
    self::$menu[] = $path;
  }

}