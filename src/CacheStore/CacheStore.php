<?php
namespace Scheduler\CacheStore;

interface CacheStore{
    /**
     * Remove an item from the cache.
     *
     * @param  string $key
     * @return bool
     */
    public function forget($key);

     /**
     * Determine if an item exists in the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key);

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null);


    /**
     * Store an item in the cache if the key does not exist.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  int    $minutes
     * @return bool
     */
    public function add($key, $value, $minutes);
}