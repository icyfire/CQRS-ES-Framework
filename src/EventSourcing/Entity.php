<?php namespace SmoothPhp\EventSourcing;

use SmoothPhp\Contracts\EventSourcing\Event;
use SmoothPhp\Contracts\EventSourcing\AggregateRoot as AggregateRootContract;

/**
 * Abstract class Entity
 *
 * @author jrdn hannah <jrdn@jrdnhannah.co.uk>
 */
abstract class Entity implements \SmoothPhp\Contracts\EventSourcing\Entity
{
    /** @var AggregateRootContract */
    private $aggregateRoot;

    /**
     * @param Event $event
     * @throws Exception\AggregateRootAlreadyRegistered
     */
    public function handleRecursively(Event $event)
    {
        $this->handle($event);

        foreach ($this->getChildren() as $entity) {
            $entity->registerAggregateRoot($this->aggregateRoot);
            $entity->handleRecursively($event);
        }
    }

    /**
     * @param AggregateRootContract $aggregateRoot
     * @throws Exception\AggregateRootAlreadyRegistered
     */
    public function registerAggregateRoot(AggregateRootContract $aggregateRoot)
    {
        if (null !== $this->aggregateRoot && $this->aggregateRoot !== $aggregateRoot) {
            throw new Exception\AggregateRootAlreadyRegistered;
        }

        $this->aggregateRoot = $aggregateRoot;
    }

    /**
     * @return \SmoothPhp\Contracts\EventSourcing\Entity[] $entity
     */
    public function getChildren()
    {
        return [];
    }

    /**
     * @param Event $event
     * @throws Exception\NoAggregateRootRegistered
     */
    protected function apply(Event $event)
    {
        if (!$this->aggregateRoot) {
            throw new Exception\NoAggregateRootRegistered;
        }
        $this->aggregateRoot->apply($event);
    }

    /**
     * Handles event if capable.
     *
     * @param $event
     */
    protected function handle(Event $event)
    {
        $method = $this->getApplyMethod($event);
        if (!method_exists($this, $method)) {
            return;
        }
        $this->$method($event);
    }

    /**
     * @param Event $event
     * @return string
     */
    private function getApplyMethod(Event $event)
    {
        $classParts = explode('\\', get_class($event));

        return 'apply' . end($classParts);
    }
}