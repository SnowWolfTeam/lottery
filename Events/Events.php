<?php
namespace LuckyDraw\Events;
class Events
{
    private $eventPool = [];

    /**
     * Store Event into event pool.
     */
    public function setEvents($key, $value, $params = [])
    {
        if (!empty($key) && is_string($key) && !empty($value) &&
            ($value instanceof \Closure || is_string($value) || is_array($value))
        ) {
            if (!is_array($params)) $params = [$params];
            $event = compact('value', 'params');
            $this->eventPool[$key] = $event;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check the event by $key whether existed in the event pool.
     */
    public function exist($key)
    {
        return isset($this->eventPool[$key]);
    }

    /**
     * Get event data from the event pool.
     */
    public function getEvents($key)
    {
        if (isset($this->eventPool[$key]))
            return $this->eventPool[$key];
    }

    /**
     * Run event by $key from the event pool.
     */
    public function run($key, $newParams = [])
    {
        if (isset($key)) {
            if (!is_array($newParams)) $newParams = [$newParams];
            $data = $this->eventPool[$key];
            $oldParams = $data['params'];
            foreach ($oldParams as $subParams) {
                array_unshift($newParams, $subParams);
            }
            //var_dump($newParams);
            return call_user_func_array($data['value'], $newParams);
        } else {
            return false;
        }
    }

    /**
     * Set event to this event pool.
     */
    public function __set($name, $value)
    {
        $this->setEvents($name, $value);
    }

    /**
     * Run events with the invalid property.
     */
    public function __get($name)
    {
        return $this->run($name);
    }
}