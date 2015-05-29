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

namespace DmitryMamontov\PHPCron;

use Symfony\Component\Finder\Finder;
use DmitryMamontov\PHPCron\Tools\Config;

/**
 * Entries - Checking records and parsing of cron.
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2015 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: 2.0.0
 * @link      https://github.com/dmamontov/symfony-phpcron/
 * @since     Class available since Release 2.0.0
 */
class Entries
{
    /**
     * Check the record.
     * @param array $entrie
     * @param DateTime $time
     * @return boolean
     */
    public static function check($entrie, $time)
    {
        $time = Config::formatTime($time);
        foreach ($time as $type => $value) {
            if ($type == 'year') {
                continue;
            }

            if (
                ($type == 'month' || $type == 'dow') &&
                is_string($entrie[$type]) &&
                array_key_exists($entrie[$type], Config::$txtToInt[$type])
            ) {
                $entrie[$type] = Config::$txtToInt[$type][$entrie[$type]];
            }

            // example: *
            if ($entrie[$type] == '*') {
                continue;
            }

            // example: 23
            if (
                is_numeric($entrie[$type]) &&
                (int) $entrie[$type] <= Config::$maximum[$type] &&
                $value == (int) $entrie[$type]
            ) {
                continue;
            }

            // example: */15
            if (preg_match("/^\*\/(\d+)$/", $entrie[$type], $out)) {
                if (
                    is_numeric($out[1]) &&
                    (int) $out[1] <= Config::$maximum[$type] &&
                    ($value % (int) $out[1]) == 0
                ) {
                    continue;
                }
            }

            // example: 5-15
            if (preg_match("/^(\d+)\-(\d+)$/", $entrie[$type], $out)) {
                if (
                    is_numeric($out[1]) &&
                    is_numeric($out[2]) &&
                    (int) $out[1] <= Config::$maximum[$type] &&
                    $out[2] <= Config::$maximum[ $type ] &&
                    $out[2] > $out[1] &&
                    $value >= $out[1] &&
                    $value <= $out[2]
                ) {
                    continue;
                }
            }

            // example: 5-15/2
            if (preg_match("/^(\d+)\-(\d+)\/(\d+)$/", $entrie[$type], $out)) {
                if (
                    is_numeric($out[1]) &&
                    is_numeric($out[2]) &&
                    is_numeric($out[3]) &&
                    (int) $out[1] <= Config::$maximum[$type] &&
                    $out[2] <= Config::$maximum[$type] &&
                    $out[3] <= Config::$maximum[$type] &&
                    $out[2] > $out[1] &&
                    $value >= $out[1] &&
                    $value <= $out[2] &&
                    ($value % (int) $out[3]) == 0
                ) {
                    continue;
                }
            }

            // example: 5,7,12
            $out = explode(',', $entrie[$type]);
            if (count($out) > 1 && in_array($value, $out)) {
                $key = array_search($value, $out);
                if (is_numeric($out[$key]) && (int) $out[$key] <= Config::$maximum[$type]) {
                    continue;
                }
            }

            return false;
        }

        return true;
    }

    /**
     * Obtaining records.
     * @return array
     */
    public static function getCrontab()
    {
        $lines = array();
        $finder = new Finder();
        $finder->files()->in(Config::$path . Config::$cronPath);
        foreach ($finder as $file) {
            if (stripos($file->getRelativePathname(), '.cron', 1) !== false) {
                $lines = array_merge($lines, file($file->getRealpath(), FILE_SKIP_EMPTY_LINES));
            }
        }

        $entries = array();
        if (count($lines) > 0) {
            foreach ($lines as $line) {
                if (preg_match(Config::CRONREGX, trim($line), $properties)) {
                    $formatingEntries = array(
                        'min'   => $properties[1],
                        'hour'  => $properties[12],
                        'day'   => $properties[23],
                        'month' => $properties[34],
                        'dow'   => $properties[45],
                        'cmd'   => $properties[56]
                    );
                    $skipEmpty = array_count_values($formatingEntries);
                    if (
                        isset($skipEmpty['*']) === false ||
                        ($skipEmpty['*'] < 5 && $formatingEntries['cmd'] != '*')
                    ) {
                        array_push($entries, $formatingEntries);
                    }
                }
            }
        }

        return $entries;
    }
}
