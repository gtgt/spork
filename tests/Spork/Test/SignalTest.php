<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Test;

require_once ('vendor/autoload.php');
use PHPUnit\Framework\TestCase;
use Spork\ProcessManager;

class SignalTest extends TestCase
{
    /**
     * @var ProcessManager
     */
    private $manager;

    protected function setUp()
    {
        $this->manager = new ProcessManager();
    }

    protected function tearDown()
    {
        $this->manager = null;
    }

    public function testSignalParent()
    {
        $signaled = false;
        $this->manager->addListener(SIGUSR1, function() use(& $signaled) {
            $signaled = true;
        });

        $this->manager->fork(function($sharedMem) {
            $sharedMem->signal(SIGUSR1);
        });

        $this->manager->wait();

        $this->assertTrue($signaled);
    }
}
