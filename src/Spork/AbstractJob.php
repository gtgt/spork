<?php

namespace Spork;

use Spork\Exception\UnexpectedTypeException;

abstract class AbstractJob
{
    protected $manager;
    protected $data;
    protected $name;
    protected $callback;

    public function __construct(ProcessManager $manager, $data = null)
    {
        $this->manager = $manager;
        $this->data = $data;
        $this->name = '<anonymous>';
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function setCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new UnexpectedTypeException($callback, 'callable');
        }

        $this->callback = $callback;

        return $this;
    }

    public function execute($callback = null)
    {
        if (null !== $callback) {
            $this->setCallback($callback);
        }

        return $this->manager->fork($this)->setName($this->name);
    }

    /**
     * Runs in a child process.
     *
     * @see execute()
     */
    abstract public function __invoke();

}
