<?php
/**
 * This file is part of GameQ.
 *
 * GameQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * GameQ is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace GameQ\Tests\Protocols;

abstract class Base extends \PHPUnit_Framework_TestCase
{

    /**
     * Shared provider to give protocols the data to test with
     *
     * @return array
     */
    public function loadData()
    {

        // Explode the class that called to avoid strict error
        $class = explode('\\', get_called_class());

        // Determine the folder to grab the provider files and results from
        $providersLookup = sprintf('%s/Providers/%s/', __DIR__, array_pop($class));

        // Init the return array
        $providers = [ ];

        // Do a glob lookup just for the responses
        $files = new \GlobIterator($providersLookup . '*_response.txt');

        // Iterate over the list of response files that exists
        foreach ($files as $fileinfo) {
            if (!$fileinfo->isReadable() || !$fileinfo->isFile()) {
                continue;
            }

            list($index, $type) = explode('_', $fileinfo->getFilename());

            // Append this data to the providers return
            $providers[] = [
                explode(PHP_EOL . '||' . PHP_EOL, file_get_contents($fileinfo->getRealPath())),
                json_decode(file_get_contents(sprintf('%s%d_result.json', $providersLookup, $index)), true)
            ];
        }

        // Clear some memory
        unset($files, $fileinfo, $providersLookup);

        return $providers;
    }

    /**
     * Generic query test function to simulate testing of protocol classes
     *
     * @param      $host
     * @param      $protocol
     * @param      $responses
     * @param bool $debug
     *
     * @return mixed
     */
    protected function queryTest($host, $protocol, $responses, $debug = false)
    {

        // Create a mock server
        $server = $this->getMock('\GameQ\Server', null, [
            [
                \GameQ\Server::SERVER_HOST => $host,
                \GameQ\Server::SERVER_TYPE => $protocol,
            ]
        ]);

        // Set the packet response as if we have really queried it
        $server->protocol()->packetResponse($responses);

        // Create a mock GameQ
        $gq_mock = $this->getMock('\GameQ\GameQ', null, [ ]);
        $gq_mock->setOption('debug', $debug);

        // Reflect on GameQ class so we can parse
        $gameq = new \ReflectionClass($gq_mock);

        // Get the parse method so we can call it
        $method = $gameq->getMethod('doParseAndFilter');

        // Set the method to accessible
        $method->setAccessible(true);

        $testResult = $method->invoke($gq_mock, $server);

        unset($server, $gq_mock, $gameq, $method);

        return $testResult;
    }
}
