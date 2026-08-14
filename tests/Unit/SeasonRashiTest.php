<?php

use Sambat\NepaliCalendar\Enums\Rashi;
use Sambat\NepaliCalendar\Enums\Season;
use Sambat\NepaliCalendar\Exceptions\InvalidNepaliMonthException;
use Sambat\NepaliCalendar\NepaliDate;

it('maps every BS month to the correct season', function () {
    expect(Season::fromMonth(1))->toBe(Season::Grishma)   // Baisakh–Jestha
        ->and(Season::fromMonth(2))->toBe(Season::Grishma)
        ->and(Season::fromMonth(3))->toBe(Season::Barsha)  // Ashadh–Shrawan
        ->and(Season::fromMonth(4))->toBe(Season::Barsha)
        ->and(Season::fromMonth(5))->toBe(Season::Sharad)  // Bhadra–Ashwin
        ->and(Season::fromMonth(6))->toBe(Season::Sharad)
        ->and(Season::fromMonth(7))->toBe(Season::Hemanta) // Kartik–Mangsir
        ->and(Season::fromMonth(8))->toBe(Season::Hemanta)
        ->and(Season::fromMonth(9))->toBe(Season::Shishir) // Poush–Magh
        ->and(Season::fromMonth(10))->toBe(Season::Shishir)
        ->and(Season::fromMonth(11))->toBe(Season::Vasanta) // Falgun–Chaitra
        ->and(Season::fromMonth(12))->toBe(Season::Vasanta);
});

it('renders season names in all three languages', function () {
    expect(Season::Barsha->devanagari())->toBe('वर्षा')
        ->and(Season::Barsha->roman())->toBe('Barsha')
        ->and(Season::Barsha->english())->toBe('Monsoon')
        ->and(Season::Barsha->name('nepali'))->toBe('वर्षा')
        ->and(Season::Barsha->name('roman'))->toBe('Barsha')
        ->and(Season::Barsha->name('english'))->toBe('Monsoon');
});

it('rejects invalid months for seasons', function () {
    Season::fromMonth(0);
})->throws(InvalidNepaliMonthException::class);

it('maps every BS month to the correct rashi', function () {
    expect(Rashi::fromMonth(1))->toBe(Rashi::Mesh)        // Baisakh
        ->and(Rashi::fromMonth(2))->toBe(Rashi::Vrish)     // Jestha
        ->and(Rashi::fromMonth(3))->toBe(Rashi::Mithun)    // Ashadh
        ->and(Rashi::fromMonth(4))->toBe(Rashi::Karkat)    // Shrawan
        ->and(Rashi::fromMonth(5))->toBe(Rashi::Simha)     // Bhadra
        ->and(Rashi::fromMonth(6))->toBe(Rashi::Kanya)     // Ashwin
        ->and(Rashi::fromMonth(7))->toBe(Rashi::Tula)      // Kartik
        ->and(Rashi::fromMonth(8))->toBe(Rashi::Vrishchik) // Mangsir
        ->and(Rashi::fromMonth(9))->toBe(Rashi::Dhanu)     // Poush
        ->and(Rashi::fromMonth(10))->toBe(Rashi::Makar)    // Magh
        ->and(Rashi::fromMonth(11))->toBe(Rashi::Kumbha)   // Falgun
        ->and(Rashi::fromMonth(12))->toBe(Rashi::Meena);   // Chaitra
});

it('renders rashi names in all three languages', function () {
    expect(Rashi::Vrishchik->devanagari())->toBe('वृश्चिक')
        ->and(Rashi::Vrishchik->roman())->toBe('Vrishchik')
        ->and(Rashi::Vrishchik->english())->toBe('Scorpio')
        ->and(Rashi::Vrishchik->name())->toBe('वृश्चिक')
        ->and(Rashi::Vrishchik->name('english'))->toBe('Scorpio');
});

it('rejects invalid months for rashis', function () {
    Rashi::fromMonth(13);
})->throws(InvalidNepaliMonthException::class);

it('derives season and rashi from a date', function () {
    $date = NepaliDate::parse('2083-04-15'); // Shrawan

    expect($date->season())->toBe(Season::Barsha)
        ->and($date->rashi())->toBe(Rashi::Karkat)
        ->and($date->season()->name('nepali'))->toBe('वर्षा')
        ->and($date->rashi()->name('english'))->toBe('Cancer');
});

it('computes age at a specific date', function () {
    $born = NepaliDate::parse('2075-04-15');

    expect($born->ageAt('2080-04-14'))->toBe(4)
        ->and($born->ageAt('2080-04-15'))->toBe(5)
        ->and($born->ageAt('2080-04-16'))->toBe(5);
});

it('returns zero age before birth', function () {
    expect(NepaliDate::parse('2080-04-15')->ageAt('2079-01-01'))->toBe(0);
});

it('detects birthdays', function () {
    $born = NepaliDate::parse('2075-11-05');

    expect($born->isBirthday(NepaliDate::parse('2083-11-05')))->toBeTrue()
        ->and($born->isBirthday(NepaliDate::parse('2083-11-06')))->toBeFalse()
        ->and($born->isBirthday(NepaliDate::parse('2083-10-05')))->toBeFalse();
});

it('celebrates month-end birthdays on the last existing day', function () {
    // Born on a 32-day Ashadh; in years where Ashadh has only 31 days the
    // birthday is celebrated on the 31st.
    $born = NepaliDate::parse('2084-03-32');

    expect($born->isBirthday(NepaliDate::parse('2085-03-31')))->toBeTrue();
});

it('finds the next birthday', function () {
    $born = NepaliDate::parse('2075-11-05');

    expect($born->nextBirthday(NepaliDate::parse('2083-11-04'))->toDateString())->toBe('2083-11-05')
        ->and($born->nextBirthday(NepaliDate::parse('2083-11-05'))->toDateString())->toBe('2084-11-05')
        ->and($born->nextBirthday(NepaliDate::parse('2083-11-06'))->toDateString())->toBe('2084-11-05');
});

it('decomposes a difference into years, months and days', function () {
    [$y, $m, $d] = NepaliDate::parse('2080-11-05')->diffInYearsMonthsDays(NepaliDate::parse('2083-04-12'));

    expect([$y, $m, $d])->toBe([2, 5, 7]);
});

it('returns signed components when absolute is disabled', function () {
    [$y, $m, $d] = NepaliDate::parse('2083-04-12')->diffInYearsMonthsDays(NepaliDate::parse('2080-11-05'), absolute: false);

    expect([$y, $m, $d])->toBe([-2, -5, -7]);
});

it('computes age broken down into years, months and days', function () {
    [$y, $m, $d] = NepaliDate::parse('2075-11-05')->ageInYearsMonthsDays(NepaliDate::parse('2083-04-12'));

    expect([$y, $m, $d])->toBe([7, 5, 7]);
});

it('provides the bs_season and bs_rashi helpers', function () {
    expect(bs_season('2083-04-15'))->toBe(Season::Barsha)
        ->and(bs_rashi('2083-04-15'))->toBe(Rashi::Karkat)
        ->and(bs_season())->toBeInstanceOf(Season::class)
        ->and(bs_rashi())->toBeInstanceOf(Rashi::class);
});
