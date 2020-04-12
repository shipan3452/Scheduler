<?php
namespace Scheduler\Mutex;
use Scheduler\Event;
use Scheduler\CacheStore\CacheStore;

class CacheEventMutex implements EventMutex
{
    /**
     * The cache store implementation.
     *
     * @var CacheStore
     */
    public $cache;

    /**
     * Create a new overlapping strategy.
     *
     * @param  CacheStore  $cacheStore
     * @return void
     */
    public function __construct(CacheStore $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to obtain a mutex for the given event.
     *
     * @param  Event  $event
     * @return bool
     */
    public function create(Event $event)
    {
        return $this->cache->add(
            $event->mutexName(), true, $event->expiresAt
        );
    }

    /**
     * Determine if a mutex exists for the given event.
     *
     * @param  Event  $event
     * @return bool
     */
    public function exists(Event $event)
    {
        return $this->cache->has($event->mutexName());
    }

    /**
     * Clear the mutex for the given event.
     *
     * @param  Event  $event
     * @return void
     */
    public function forget(Event $event)
    {
        $this->cache->forget($event->mutexName());
    }
}