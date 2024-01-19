<?php

namespace Sambat\NepaliCalendar\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Sambat\NepaliCalendar\Facades\NepaliDate;
use Sambat\NepaliCalendar\NepaliCalendarServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [NepaliCalendarServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'NepaliDate' => NepaliDate::class,
        ];
    }
}
