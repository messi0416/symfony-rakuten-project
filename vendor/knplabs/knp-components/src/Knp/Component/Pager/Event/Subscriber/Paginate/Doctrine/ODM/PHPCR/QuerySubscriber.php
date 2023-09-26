<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ODM\PHPCR;

use Doctrine\ODM\PHPCR\Query\Query;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;

/**
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class QuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if (!$event->target instanceof Query) {
            return;
        }

        $queryCount = clone $event->target;
        $event->count = $queryCount->execute(null, Query::HYDRATE_PHPCR)->getRows()->count();

        $query = $event->target;
        $query->setMaxResults($event->getLimit());
        $query->setFirstResult($event->getOffset());

        $event->items = $query->execute();
        $event->stopPropagation();
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 0)
        );
    }
}
