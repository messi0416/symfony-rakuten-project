<?php

namespace MiscBundle\Subscriber\Paginate;

use forestlib\Doctrine\ORM\LimitableNativeQuery;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Doctrine\ORM\Query;

/**
 * @deprecated see UsesPaginator
 **/
class LimitableNativeQuerySubscriber implements EventSubscriberInterface
{
  /**
   * Used if user set the count manually
   */
  const HINT_COUNT = 'knp_paginator.count';

  public function items(ItemsEvent $event)
  {
    if ($event->target instanceof LimitableNativeQuery) {

      $event->count = $event->target->count();

      // sort
      // 現状、いらない実装
      $sortFieldName = $event->options['sortFieldParameterName'];
      $sortDirectionFieldName = $event->options['sortDirectionParameterName'];

      if (isset($event->options[$sortFieldName]) && strlen($sortFieldName)) {
        $direction = isset($event->options[$sortDirectionFieldName]) ? $event->options[$sortDirectionFieldName] : 'asc';
        $event->target->setOrders([ $event->options[$sortFieldName] => $direction ]);
      }

      // process items
      $result = null;
      if ($event->count) {
        $event->target
          ->setFirstResult($event->getOffset())
          ->setMaxResults($event->getLimit())
        ;
        $result = $event->target->execute();

      } else {
        $result = array(); // count is 0
      }
      $event->items = $result;
      $event->stopPropagation();
    }
  }

  public static function getSubscribedEvents()
  {
    return array(
      'knp_pager.items' => array('items', 1)
    );
  }
}
