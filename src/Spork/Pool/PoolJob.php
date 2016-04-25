<?php

namespace Spork\Pool;

use Spork\AbstractJob;
use Spork\Batch\BatchRunner;
use Spork\ProcessManager;

class PoolJob extends AbstractJob
{
    protected $poolSize;

    public function __construct(ProcessManager $manager, $data, $pollSize = 3)
    {
        parent::__construct($manager, $data);

        $this->poolSize = $pollSize;
    }

    /**
     * Runs in a child process.
     *
     * @see execute()
     */
    public function __invoke()
    {
        $forks = array();
        $results = array();
        $batches = $this->data;
        $index = 0;
        while (count($batches) > 0) {
            while (count($forks) < $this->poolSize) {
                $batch = array_splice($batches, 0, 1);
                $fork = $this->manager->fork(new BatchRunner($batch, $this->callback))
                    ->setName(sprintf('%s part #%d', $this->name, $index));
                $forks[$fork->getPid()] = $fork;
                $index++;
            }
            do {
                $endedFork = $this->manager->waitForNext();
            } while (!isset($endedFork));

            $results = array_merge($results, $endedFork->getResult());
            unset($forks[$endedFork->getPid()]);

            $endedFork = null;
        }
        // block until all forks have exited
        $this->manager->wait();


        foreach ($forks as $fork) {
            $results = array_merge($results, $fork->getResult());
        }

        return $results;
    }
}
