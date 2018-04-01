<?php

namespace WPXCronManagerLight\WPBones\Html;

class Html
{

  protected static $htmlTags = [
    'a'        => '\WPXCronManagerLight\WPBones\Html\HtmlTagA',
    'button'   => '\WPXCronManagerLight\WPBones\Html\HtmlTagButton',
    'checkbox' => '\WPXCronManagerLight\WPBones\Html\HtmlTagCheckbox',
    'datetime' => '\WPXCronManagerLight\WPBones\Html\HtmlTagDatetime',
    'fieldset' => '\WPXCronManagerLight\WPBones\Html\HtmlTagFieldSet',
    'form'     => '\WPXCronManagerLight\WPBones\Html\HtmlTagForm',
    'input'    => '\WPXCronManagerLight\WPBones\Html\HtmlTagInput',
    'label'    => '\WPXCronManagerLight\WPBones\Html\HtmlTagLabel',
    'optgroup' => '\WPXCronManagerLight\WPBones\Html\HtmlTagOptGroup',
    'option'   => '\WPXCronManagerLight\WPBones\Html\HtmlTagOption',
    'select'   => '\WPXCronManagerLight\WPBones\Html\HtmlTagSelect',
    'textarea' => '\WPXCronManagerLight\WPBones\Html\HtmlTagTextArea',
  ];

  public static function __callStatic( $name, $arguments )
  {
    if ( in_array( $name, array_keys( self::$htmlTags ) ) ) {
      $args = ( isset( $arguments[ 0 ] ) && ! is_null( $arguments[ 0 ] ) ) ? $arguments[ 0 ] : [];

      return new self::$htmlTags[ $name ]( $args );
    }
  }
}