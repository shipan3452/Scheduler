<?php

namespace Scheduler\Mutex;

use Scheduler\Event;
use Scheduler\Contract\CacheStore as Cache;

class CacheSchedulingMutex
{
    /**
     * The cache repository implementation.
     *
     * @var Repository
     */
    public $cache;

    /**
     * Create a new overlapping strategy.
     *
     * @param  Repository  $cache
     * @return void
     */
    public function __construct(Cache $cache)
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
            $event->mutexName(),
            true,
            $event->expiresAt
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
}
