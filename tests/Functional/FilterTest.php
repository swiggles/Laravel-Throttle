<?php

/**
 * This file is part of Laravel Throttle by Graham Campbell.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace GrahamCampbell\Tests\Throttle\Functional;

use GrahamCampbell\Tests\Throttle\AbstractTestCase;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * This is the filter test class.
 *
 * @package    Laravel-Throttle
 * @author     Graham Campbell
 * @copyright  Copyright 2013-2014 Graham Campbell
 * @license    https://github.com/GrahamCampbell/Laravel-Throttle/blob/master/LICENSE.md
 * @link       https://github.com/GrahamCampbell/Laravel-Throttle
 */
class FilterTest extends AbstractTestCase
{
    /**
     * Specify if routing filters are enabled.
     *
     * @return bool
     */
    protected function enableFilters()
    {
        return true;
    }

    /**
     * Additional application environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function additionalSetup($app)
    {
        $app['config']->set('graham-campbell/throttle::driver', 'array');
    }

    /**
     * Run extra tear down code.
     *
     * @return void
     */
    protected function finish()
    {
        $this->app['cache']->driver('array')->flush();
    }

    public function testBasicFilter()
    {
        $this->app['router']->get('throttle-test-route', array('before' => 'throttle', function () {
            return 'Why herro there!';
        }));

        $this->hit();
    }

    public function testCustomLimit()
    {
        $this->app['router']->get('throttle-test-route', array('before' => 'throttle:5', function () {
            return 'Why herro there!';
        }));

        $this->hit(5);
    }

    public function testCustomTime()
    {
        $this->app['router']->get('throttle-test-route', array('before' => 'throttle:3,5', function () {
            return 'Why herro there!';
        }));

        $this->hit(3, 300);
    }

    public function testLimitAndClear()
    {
        $this->app['router']->get('throttle-test-route', array('before' => 'throttle:4', function () {
            return 'Why herro there!';
        }));

        $this->hit(4);

        $this->app['cache']->driver('array')->flush();

        $this->call('GET', 'throttle-test-route');
        $this->assertResponseOk();
    }

    protected function hit($times = 10, $time = 3600) {
        for($i = 0; $i < $times; $i++) {
            $this->call('GET', 'throttle-test-route');
            $this->assertResponseOk();
        }

        try {
            $this->call('GET', 'throttle-test-route');
        } catch (TooManyRequestsHttpException $e) {
            $this->assertEquals('Rate limit exceed.', $e->getMessage());
            $this->assertEquals($time, $e->getHeaders()['Retry-After']);
        }
    }
}
