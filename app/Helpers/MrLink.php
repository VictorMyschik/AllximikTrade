<?php


namespace App\Helpers;


use App\Http\Controllers\Controller;

class MrLink extends Controller
{
  public static function open(string $route_name, array $arguments, ?string $text, ?string $class, $title = '', array $attr = array())
  {
    $out = array();

    $out['url'] = $route_name;
    $out['arguments'] = $arguments;
    $out['text'] = $text ?? '';
    $out['class'] = $class ?? '';
    $out['title'] = $title;
    $out['attributes'] = $attr;
    return View('layouts.Elements.link')->with($out)->toHtml();
  }
}