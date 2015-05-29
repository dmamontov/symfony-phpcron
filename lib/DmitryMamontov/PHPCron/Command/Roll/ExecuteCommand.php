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
namespace DmitryMamontov\PHPCron\Command\Roll;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use DmitryMamontov\PHPCron\Tools\Config;
use DmitryMamontov\PHPCron\Entries;
use DmitryMamontov\PHPCron\PHPCron;

/**
 * ExecuteCommand - Starting cron
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2015 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: 2.0.0
 * @link      https://github.com/dmamontov/symfony-phpcron/
 * @since     Class available since Release 2.0.0
 */
class ExecuteCommand extends BaseCommand
{
    /**
     * Configuring command.
     */
    protected function configure()
    {
        $this
            ->setName('execute')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force execute')
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Debug none')
            ->setDescription('Execute ' . Config::NAME);
    }

    /**
     * Execution commands.
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (is_null(Config::$sharedId) == false && @shm_has_var(Config::$sharedId, Config::PID)) {
            $output->writeln('<info>' . Config::NAME . ' ' . shm_get_var(Config::$sharedId, Config::PID) . ' is running</info>');
            exit();
        }

        if ($input->getOption('debug') === false) {
            if (function_exists('ini_set')) {
                ini_set('error_log', Config::$path . Config::$logsPath . '/error.log');
            }
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);
            $STDIN = fopen('/dev/null', 'r');
            $STDOUT = fopen(Config::$path . Config::$logsPath . '/appilication.log', 'ab');
            $STDERR = fopen(Config::$path . Config::$logsPath . '/error.log', 'ab');
        }

        $phpcron = new PHPCron(Entries::getCrontab());
        $phpcron->execute();
    }
}
