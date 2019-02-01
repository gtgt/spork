<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork;

use Spork\Exception\ProcessControlException;

/**
 * Sends messages between processes.
 */
class SharedMemory
{
    private $pid;
    private $ppid;
    private $signal;

    /**
     * Constructor.
     *
     * @param integer $pid    The child process id or null if this is the child
     * @param integer $signal The signal to send after writing to shared memory
     */
    public function __construct($pid = null, $signal = null)
    {
        if (null === $pid) {
            // child
            $pid   = posix_getpid();
            $ppid  = posix_getppid();
        } else {
            // parent
            $ppid  = null;
        }

        $this->pid  = $pid;
        $this->ppid = $ppid;
        $this->signal = $signal;
    }

    /**
     * Reads all messages from shared memory.
     *
     * @return array An array of messages
     */
    public function receive()
    {
        set_error_handler(function () { return true; });
        $shmId = @shmop_open($this->pid, 'a', 0, 0);
        restore_error_handler();
        if ($shmId > 0) {
            $serializedMessages = shmop_read($shmId, 0, shmop_size($shmId));
            shmop_delete($shmId);
            shmop_close($shmId);

            return unserialize(gzuncompress($serializedMessages));
        }

        return array();
    }

    /**
     * Writes a message to the shared memory.
     *
     * @param mixed   $message The message to send
     * @param integer $signal  The signal to send afterward
     * @param integer $pause   The number of microseconds to pause after signalling
     */
    public function send($message, $signal = null, $pause = 500)
    {
        $messageArray = $this->receive();

        // Add the current message to the end of the array, and serialize it
        $messageArray[] = $message;
        $serializedMessage = gzcompress(serialize($messageArray), 9);

        // Write new serialized message to shared memory
        $shmId = shmop_open($this->pid, 'c', 0644, strlen($serializedMessage));
        if (!$shmId) {
            throw new ProcessControlException(sprintf('Not able to create shared memory segment for PID: %s', $this->pid));
        } else if (shmop_write($shmId, $serializedMessage, 0) !== strlen($serializedMessage)) {
            throw new ProcessControlException(
                sprintf('Not able to write message to shared memory segment for segment ID: %s', $shmId)
            );
        }

        if (false === $signal) {
            return;
        }

        $this->signal($signal ?: $this->signal);
        usleep($pause);
    }

    /**
     * Sends a signal to the other process.
     */
    public function signal($signal)
    {
        $pid = null === $this->ppid ? $this->pid : $this->ppid;

        return posix_kill($pid, $signal);
    }
}
