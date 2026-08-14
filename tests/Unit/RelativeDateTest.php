<?php

use Sambat\NepaliCalendar\Exceptions\InvalidNepaliDateException;
use Sambat\NepaliCalendar\NepaliDate;
use Sambat\NepaliCalendar\Support\Parser;

it('parses Nepali relative words', function () {
    $today = NepaliDate::now();

    expect(Parser::parse('आज')->toDateString())->toBe($today->toDateString())
        ->and(Parser::parse('हिजो')->toDateString())->toBe($today->subDay()->toDateString())
        ->and(Parser::parse('अस्ति')->toDateString())->toBe($today->subDays(2)->toDateString())
        ->and(Parser::parse('भोलि')->toDateString())->toBe($today->addDay()->toDateString())
        ->and(Parser::parse('पर्सि')->toDateString())->toBe($today->addDays(2)->toDateString())
        ->and(Parser::parse('परसि')->toDateString())->toBe($today->addDays(3)->toDateString());
});

it('parses English relative words', function () {
    $today = NepaliDate::now();

    expect(Parser::parse('today')->toDateString())->toBe($today->toDateString())
        ->and(Parser::parse('tomorrow')->toDateString())->toBe($today->addDay()->toDateString())
        ->and(Parser::parse('yesterday')->toDateString())->toBe($today->subDay()->toDateString())
        ->and(Parser::parse('NEXT WEEK')->toDateString())->toBe($today->addWeeks(1)->toDateString())
        ->and(Parser::parse('last week')->toDateString())->toBe($today->subWeeks(1)->toDateString())
        ->and(Parser::parse('next month')->toDateString())->toBe($today->addMonths(1)->toDateString())
        ->and(Parser::parse('last month')->toDateString())->toBe($today->subMonths(1)->toDateString())
        ->and(Parser::parse('next year')->toDateString())->toBe($today->addYears(1)->toDateString())
        ->and(Parser::parse('last year')->toDateString())->toBe($today->subYears(1)->toDateString());
});

it('rejects unknown relative words', function () {
    Parser::parse('next fortnite');
})->throws(InvalidNepaliDateException::class);
