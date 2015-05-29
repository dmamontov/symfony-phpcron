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

namespace DmitryMamontov\PHPCron\Command;

use Symfony\Component\Console\Application as AbstractApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use DmitryMamontov\PHPCron\Tools\Config;
use DmitryMamontov\PHPCron\Tools\Dependences;

/**
 * Application - Initialization and Initial Configuration Console
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2015 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: 2.0.0
 * @link      https://github.com/dmamontov/symfony-phpcron/
 * @since     Class available since Release 2.0.0
 */
class Application extends AbstractApplication
{
    /**
     * Setting up the system, setting parameters.
     */
    public function __construct()
    {
        if (function_exists('ini_set') && extension_loaded('xdebug')) {
            ini_set('xdebug.show_exception_trace', false);
            ini_set('xdebug.scream', false);
        }

        if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
            date_default_timezone_set(@date_default_timezone_get());
        }

        if (stripos(\Phar::running(), 'phar:') !== false) {
            Config::$path = '/var/phpcron/';
        }

        $this->createPatch();

        Config::$sharedId = shm_attach(ftok(__FILE__, 'A'));

        parent::__construct(strtolower(Config::NAME), Config::VERSION);
    }

    /**
     * Check conditions before you start.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if ($error = Dependences::get()) {
            $output->writeln("<error>$error \nupgrading is strongly recommended.</error>");
            exit();
        }

        $result = parent::doRun($input, $output);
        return $result;
    }

    /**
     * Creating auxiliary directory.
     */
    private function createPatch()
    {
        $fs = new Filesystem();
        if ($fs->exists(Config::$path . Config::$cronPath) == false) {
            try {
                $fs->mkdir(Config::$path . Config::$cronPath);
            } catch (IOExceptionInterface $e) {
                $output->writeln("<error>An error occurred while creating your directory at {$e->getPath()}</error>");
            }
        }
        if ($fs->exists(Config::$path . Config::$logsPath) == false) {
            try {
                $fs->mkdir(Config::$path . Config::$logsPath);
            } catch (IOExceptionInterface $e) {
                $output->writeln("<error>An error occurred while creating your directory at {$e->getPath()}</error>");
            }
        }
    }

    /**
     * Set the default command.
     * @return array
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new Roll\ImportCommand();
        $commands[] = new Roll\StatusCommand();
        $commands[] = new Roll\CancelCommand();
        $commands[] = new Roll\ExecuteCommand();

        return $commands;
    }

    /**
     * Supplement commands.
     * @return InputDefinition
     */
    protected function getDefaultInputDefinition()
    {
        $definition = new InputDefinition(array(
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),
            new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
            new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this application version'),
        ));

        return $definition;
    }
}
