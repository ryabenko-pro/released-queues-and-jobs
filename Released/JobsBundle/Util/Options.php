<?php

namespace Released\JobsBundle\Util;


class Options
{

    protected $params;

    function __construct($params = [])
    {
        $this->params = (array)$params;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->params;
    }

    /**
     * @param array $params
     * @return self
     */
    public function setAll($params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if (array_key_exists($name, $this->params)) {
            return $this->params[$name];
        }

        return $default;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return Options
     */
    public function set($name, $value)
    {
        $this->params[$name] = $value;

        return $this;
    }

}
