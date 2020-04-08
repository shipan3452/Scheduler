<?php

namespace Scheduler;

use DateTimeInterface;
use Scheduler\Utility\ProcessUtils;
use Scheduler\Mutex\EventMutex;


class Schedule
{
    /**
     * All of the events on the schedule.
     *
     * @var Event[]
     */
    protected $events = [];

    /**
     * The event mutex implementation.
     *
     * @var EventMutex
     */
    protected $eventMutex;

    /**
     * The scheduling mutex implementation.
     *
     * @var SchedulingMutex
     */
    protected $schedulingMutex;

    /**
     * Create a new schedule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //创建锁
    }


    public function run()
    {
        foreach ($this->dueEvents() as $event) {
            // if (! $event->filtersPass($this->laravel)) {
            //     continue;
            // }
            $event->run();
            $event->callAfterCallbacks();
        }
    }


    /**
     * Add a new command event to the schedule.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return Event
     */
    public function exec($command, array $parameters = [])
    {
        if (count($parameters)) {
            $command .= ' ' . $this->compileParameters($parameters);
        }
        $this->events[] = $event = new Event($command, $this->eventMutex);
        return $event;
    }

    /**
     * Compile parameters for a command.
     *
     * @param  array  $parameters
     * @return string
     */
    protected function compileParameters(array $parameters)
    {
        array_walk($parameters, function (&$val, $key) {
            if (is_array($val)) {
                $val = array_map(function ($val2) {
                    return ProcessUtils::escapeArgument($val2);
                }, $val);
                $val = implode(" ", $val);
            } elseif (!is_numeric($val) && !preg_match('/^(-.$|--.*)/i', $val)) {
                $val = ProcessUtils::escapeArgument($val);
            }
            return $val = is_numeric($key) ? $val : "{$key}={$val}";
        });
        return  implode(" ", $parameters);
    }

    /**
     * Determine if the server is allowed to run this event.
     *
     * @param  Scheduler\Event  $event
     * @param  \DateTimeInterface  $time
     * @return bool
     */
    // public function serverShouldRun(Event $event, DateTimeInterface $time)
    // {
    //     return $this->schedulingMutex->create($event, $time);
    // }

    /**
     * Get all of the events on the schedule that are due.
     */
    public function dueEvents()
    {
        return array_filter($this->events, function ($event) {
            return $event->isDue();
        });
    }

    /**
     * Get all of the events on the schedule.
     *
     * @return \Illuminate\Console\Scheduling\Event[]
     */
    public function events()
    {
        return $this->events;
    }

    /**
     * Specify the cache store that should be used to store mutexes.
     *
     * @param  string  $store
     * @return $this
     */
    public function useCache($store)
    {
        // if ($this->eventMutex instanceof CacheEventMutex) {
        //     $this->eventMutex->useStore($store);
        // }

        // if ($this->schedulingMutex instanceof CacheSchedulingMutex) {
        //     $this->schedulingMutex->useStore($store);
        // }
        // return $this;
    }
}
