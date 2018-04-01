<?php

namespace WPXCronManagerLight\WPBones\Routing;

use WPXCronManagerLight\WPBones\View\View;
use WPXCronManagerLight\WPBones\Foundation\Http\Request;

abstract class Controller
{

  private $_request = null;

  public function load() {}

  public function __get( $name )
  {
    $method = 'get' . ucfirst( $name ) . 'Attribute';
    if ( method_exists( $this, $method ) ) {
      return $this->$method();
    }
  }

  public function redirect( $location = '' )
  {
    $args = array_filter( array_keys( $_REQUEST ), function ( $e ) {
      return ( $e !== 'page' );
    } );

    if ( empty( $location ) ) {
      $location = remove_query_arg( $args );
    }

    if ( headers_sent() ) {

      echo '<script type="text/javascript">';
      echo 'window.location.href="' . $location . '";';
      echo '</script>';
      echo '<noscript>';
      echo '<meta http-equiv="refresh" content="0;url=' . $location . '" />';
      echo '</noscript>';
      exit();

    }

    wp_redirect( $location );
    exit();
  }

  public function render( $method )
  {
    $view = $this->{$method}();

    if ( $view instanceof View ) {
      return $view->render();
    }
  }

  public function getRequestAttribute()
  {
    if( is_null( $this->_request ) ) {
      $this->_request = new Request();
    }
    return $this->_request;
  }

}