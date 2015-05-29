<?php
/**
 * PHPCron
 *
 * Copyright (c) 2015, Dmitry Mamontov <d.slonyara@gmail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Dmitry Mamontov nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package   symfony-phpcron
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2015 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @since     File available since Release 2.0.0
 */

declare (ticks=1);

namespace DmitryMamontov\PHPCron;

use Symfony\Component\Process\Process;
use DmitryMamontov\PHPCron\Entries;
use DmitryMamontov\PHPCron\Tools\Config;

/**
 * PHPCron - The main class.
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2015 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: 2.0.0
 * @link      https://github.com/dmamontov/symfony-phpcron/
 * @since     Class available since Release 2.0.0
 */
class PHPCron
{
    /*
     * Records.
     */
    private $entries = array();

    /*
     * Running processes..
     */
    private $running = array();

    /**
     * Setting up and installing the data handler.
     * @param array $entries
     */
    public function __construct($entries)
    {
        $this->entries = $entries;

        pcntl_signal_dispatch();

        pcntl_signal(SIGTERM, array($this, 'signalHandler'));
        pcntl_signal(SIGCHLD, array($this, 'signalHandler'));
    }

    /**
     * Execution records.
     */
    public function execute()
    {
        $pid = pcntl_fork();
        if ($pid) {
            exit();
        } elseif (count($this->entries) > 0) {
            shm_put_var(Config::$sharedId, Config::PID, getmypid());
            shm_put_var(Config::$sharedId, Config::STATUS, 'RUNNING');
            while (is_null(Config::$sharedId) == false && @shm_has_var(Config::$sharedId, Config::PID)) {
                foreach ($this->entries as $id => $entrie) {
                    while (count($this->running) >= count($this->entries)) {
                        sleep(1);
                    }

                    if (in_array($id, $this->running)) {
                        continue;
                    } else {
                        $this->execEntrie($id, $entrie);
                    }
                }
            }
        }
        posix_setsid();
    }

    /**
     * Execution records individually.
     * @param integer $id
     * @param array $entrie
     * @return boolean
     */
    protected function execEntrie($id, $entrie)
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            return false;
        } elseif ($pid) {
            $this->running[$pid] = $id;
        } else {
            while (is_null(Config::$sharedId) == false && @shm_has_var(Config::$sharedId, Config::PID)) {
                $currentTime = new \DateTime();

                if (Entries::check($entrie, $currentTime)) {
                    $process = new Process($entrie['cmd']);
                    $process->run();

                    if ($process->isSuccessful()) {
                        sleep(Config::setSleep($entrie, $currentTime));
                        exit();
                    }
                } else {
                    sleep(Config::setSleep($entrie, $currentTime));
                }
            }
            exit();
        }
    }

    /**
     * Obtaining the status of processes and their completion.
     * @param string $signo
     * @param string $pid
     * @param string $status
     */
    public function signalHandler($signo, $pid = null, $status = null)
    {
        switch ($signo) {
            case SIGTERM:
                if (is_null(Config::$sharedId) == false) {
                    shm_remove(Config::$sharedId);
                }
                break;
            case SIGCHLD:
                if (is_null($pid)) {
                    $pid = pcntl_waitpid(-1, $status, WNOHANG);
                }
                while ($pid > 0) {
                    if ($pid && isset($this->running[$pid])) {
                        unset($this->running[$pid]);
                    }
                    $pid = pcntl_waitpid(-1, $status, WNOHANG);
                }
                break;
        }
    }
}
