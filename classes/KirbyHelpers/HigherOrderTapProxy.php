<?php

namespace KirbyHelpers;

class HigherOrderTapProxy
{
    /**
     * The target being tapped
     */
    public $target;

    /**
     * Create a new tap proxy instance
     *
     * @param mixed $target
     */
    public function __construct($target)
    {
        $this->target = $target;
    }

    /**
     * Dynamically pass method calls to the target
     *
     * @param string $method
     * @param array $parameters
     */
    public function __call(string $method, array $parameters)
    {
        $this->target->{$method}(...$parameters);

        return $this->target;
    }
}
