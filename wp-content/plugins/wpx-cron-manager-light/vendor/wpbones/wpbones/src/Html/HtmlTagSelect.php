<?php

namespace WPXCronManagerLight\WPBones\Html;

use WPXCronManagerLight\WPBones\Html\HtmlTagOption;

class HtmlTagSelect extends HtmlTag
{
  /**
   * Attributes.
   *
   * See http://www.w3schools.com/tags/default.asp for definitions
   *
   * @var array
   */
  protected $attributes = [
    'autofocus' => null,
    'disabled'  => null,
    'form'      => null,
    'multiple'  => null,
    'name'      => null,
    'size'      => null
  ];

  /**
   * Html Tag markup, open and close.
   *
   * @var array
   */
  protected $markup = [ '<select', '</select>' ];

  public function options( $items )
  {
    $stack = [ ];
    foreach ( $items as $key => $value ) {
      $option = new HtmlTagOption( $value );
      if ( is_string( $key ) ) {
        $option->value = $key;
      }
      $stack[] = $option->html();
    }

    $this->content = implode( '', $stack );

    return $this;
  }

}