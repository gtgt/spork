<?php
/**
 * Created by PhpStorm.
 * User: fon60
 * Date: 23.04.16
 * Time: 12:24
 */

namespace Spork;


use Spork\Exception\UnexpectedTypeException;

class Job
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
    public function __invoke()
    {
        $forks = array();
//        foreach ($this->strategy->createBatches($this->data) as $index => $batch) {
//            $forks[] = $this->manager
//                ->fork($this->strategy->createRunner($batch, $this->callback))
//                ->setName(sprintf('%s batch #%d', $this->name, $index))
//            ;
//        }

        // block until all forks have exited
        $this->manager->wait();

        $results = array();
        foreach ($forks as $fork) {
            $results = array_merge($results, $fork->getResult());
        }

        return $results;
    }
}