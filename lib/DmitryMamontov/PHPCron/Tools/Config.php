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

namespace DmitryMamontov\PHPCron\Tools;

/**
 * Config - Helper class configurations.
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2015 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: 2.0.0
 * @link      https://github.com/dmamontov/symfony-phpcron/
 * @since     Class available since Release 2.0.0
 */
class Config
{
    /*
     * Regular expression to retrieve the parameters of the tasks.
     */
    const CRONREGX = '/^(\*(\/\d+)?|([0-5]?\d)(-([0-5]?\d)(\/\d+)?)?(,([0-5]?\d)(-([0-5]?\d)(\/\d+)?)?)*)\s(\*(\/\d+)?|([01]?\d|2[0-3])(-([01]?\d|2[0-3])(\/\d+)?)?(,([01]?\d|2[0-3])(-([01]?\d|2[0-3])(\/\d+)?)?)*)\s(\*(\/\d+)?|(0?[1-9]|[12]\d|3[01])(-(0?[1-9]|[12]\d|3[01])(\/\d+)?)?(,(0?[1-9]|[12]\d|3[01])(-(0?[1-9]|[12]\d|3[01])(\/\d+)?)?)*)\s(\*(\/\d+)?|([1-9]|1[012])(-([1-9]|1[012])(\/\d+)?)?(,([1-9]|1[012])(-([1-9]|1[012])(\/\d+)?)?)*|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\s(\*(\/\d+)?|([0-7])(-([0-7])(\/\d+)?)?(,([0-7])(-([0-7])(\/\d+)?)?)*|mon|tue|wed|thu|fri|sat|sun)\s(.*)$/';

    /*
     * Version.
     */
    const VERSION = '2.0.0';

    /*
     * Name.
     */
    const NAME = 'PHPCron';

    /*
     * A memory unit for storing status.
     */
    const STATUS = 11511697116117115;

    /*
     * A memory unit for storing the identifier of the process.
     */
    const PID = 112105100;

    /*
     * Path to the auxiliary folder.
     */
    public static $path = '';

    /*
     * The name of the folder with the task.
     */
    public static $cronPath = 'tasks';

    /*
     * The name of the folder with logs.
     */
    public static $logsPath = 'logs';

    /*
     * The identifier of the storage unit.
     */
    public static $sharedId;

    /*
     * Integer equivalents names of the months and weeks.
     */
    public static $txtToInt = array(
        'month' => array(
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
            'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8,
            'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12
        ),
        'dow' => array(
            'sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6
        ),
    );

    /*
     * Maximum limits.
     */
    public static $maximum = array(
        'min' => 59, 'hour' => 23, 'day' => 31, 'month' => 12, 'dow' => 6
    );

    /**
     * Formatting date.
     * @param DateTime $time
     * @return mixed
     */
    public static function formatTime($time)
    {
        return array(
            "min"   => (int) $time->format('i'),
            "hour"  => (int) $time->format('H'),
            "day"   => (int) $time->format('j'),
            "month" => (int) $time->format('n'),
            "dow"   => (int) $time->format('w'),
            "year"  => (int) $time->format('Y')
        );
    }

    /**
     * Setting sleep.
     * @param array $entrie
     * @param DateTime $time
     * @return integer
     */
    public static function setSleep($entrie, $time)
    {
        $time = (int) $time->getTimestamp();
        foreach ($entrie as $type => $value) {
            if ($value != '*') {
                switch ($type) {
                    case 'min':
                        return ($time + 60 - ($time % 3600) % (60)) - $time;
                        break;
                    case 'hour':
                        return ($time + 3600 - ($time % 3600) % (60)) - $time;
                        break;
                    case 'day':
                        return (strtotime('+1 day', $time) - ($time % 3600) % (60)) - $time;
                        break;
                    case 'dow':
                        return (strtotime('+1 week', $time) - ($time % 3600) % (60)) - $time;
                        break;
                    case 'month':
                        return (strtotime('+1 month', $time) - ($time % 3600) % (60)) - $time;
                        break;
                }
            }
        }
        return 0;
    }
}
