<?php

namespace MiscBundle\Subscriber\Paginate;

use Symfony\Component\Finder\Finder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;

class DirectorySubscriber implements EventSubscriberInterface
{
  public function items(ItemsEvent $event)
  {
    if (is_string($event->target) && is_dir($event->target)) {
      $finder = new Finder;
      $finder
        ->files()
        ->depth('< 4') // 3 levels
        ->in($event->target)
      ;
      $iter = $finder->getIterator();
      $files = iterator_to_array($iter);
      $event->count = count($files);
      $event->items = array_slice(
        $files,
        $event->getOffset(),
        $event->getLimit()
      );
      $event->stopPropagation();
    }
  }

  public static function getSubscribedEvents()
  {
    return array(
      'knp_pager.items' => array('items', 1/*increased priority to override any internal*/)
    );
  }
}
