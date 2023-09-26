<?php
namespace MiscBundle\Extend\Twig;

use /** @noinspection PhpUndefinedClassInspection */ Twig_Extension;
use Twig_SimpleFunction;

class Plusnao_Twig_Extension extends Twig_Extension
{
  public function getFunctions()
  {
    $sortableCss = new Twig_SimpleFunction('sortable_css_class', '\MiscBundle\Extend\Twig\plusnao_twig_get_sortable_table_header_css');
    return array(
      $sortableCss
    );
  }

  public function getName()
  {
    return 'plusnao';
  }
}

/**
 * 一覧表示テーブル ヘッダソートCSSクラス名取得
 * @param \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $pagination
 * @param string $target
 * @return string
 */
function plusnao_twig_get_sortable_table_header_css(\Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $pagination, $target)
{
  $css = 'sortFree';
  if ($pagination->isSorted($target)) {
    if ($pagination->getDirection() == 'asc') {
      $css = 'sortAsc';
    } else {
      $css = 'sortDesc';
    }
  }

  return $css;
}

