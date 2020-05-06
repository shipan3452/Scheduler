<?php
namespace Scheduler\CacheStore;

class RedisStore implements CacheStore
{
    public function __construct($redis)
    {
        $this->redis = $redis;
    }
    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $value = $this->redis->get($key);
        return !is_null($value) ? $this->unserialize($value) : $default;
    }


    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        return (bool) $this->redis->del($key);
    }

    /**
     *  cache has key
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key){
        return !is_null($this->get($key));
    }

    /**
     * Store an item in the cache if the key doesn't exist.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  float|int  $second
     * @return bool
     */
    public function add($key, $value, $second)
    {
        $lua = "return redis.call('exists',KEYS[1])<1 and redis.call('setex',KEYS[1],ARGV[2],ARGV[1])";

        return (bool) $this->redis->eval(
            $lua,
            1,
            $key,
            $this->serialize($value),
            (int) max(1, $second)
        );
    }

    /**
     * Serialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function serialize($value)
    {
        return is_numeric($value) ? $value : serialize($value);
    }

    /**
     * Unserialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        return is_numeric($value) ? $value : unserialize($value);
    }
}
