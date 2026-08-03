<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Sambat\NepaliCalendar\Casts\NepaliDateCast;
use Sambat\NepaliCalendar\Casts\NepaliDateTimeCast;
use Sambat\NepaliCalendar\NepaliDate;

beforeEach(function () {
    Schema::create('events', function ($table) {
        $table->increments('id');
        $table->date('event_date')->nullable();
        $table->dateTime('event_at')->nullable();
    });
});

afterEach(function () {
    Schema::dropIfExists('events');
});

it('casts a stored AD date to a BS NepaliDate', function () {
    $event = new class extends Model
    {
        protected $table = 'events';

        public $timestamps = false;

        protected $guarded = [];

        protected $casts = ['event_date' => NepaliDateCast::class];
    };

    $event->event_date = '2081-11-05';
    $event->save();

    // Canonical Gregorian value is what lands in the database.
    expect($event->getRawOriginal('event_date'))->toBe('2025-02-17');

    $fresh = $event->fresh();
    expect($fresh->event_date)->toBeInstanceOf(NepaliDate::class);
    expect($fresh->event_date->toDateString())->toBe('2081-11-05');
    expect($fresh->event_date->format('Y-m-d'))->toBe('२०८१-११-०५');
});

it('accepts NepaliDate instances and null when setting', function () {
    $event = new class extends Model
    {
        protected $table = 'events';

        public $timestamps = false;

        protected $guarded = [];

        protected $casts = ['event_date' => NepaliDateCast::class];
    };

    $event->event_date = NepaliDate::parse('2081-11-05');
    $event->save();

    expect($event->fresh()->event_date->toDateString())->toBe('2081-11-05');

    $event->update(['event_date' => null]);
    expect($event->fresh()->event_date)->toBeNull();
});

it('preserves the time of day with the datetime cast', function () {
    $event = new class extends Model
    {
        protected $table = 'events';

        public $timestamps = false;

        protected $guarded = [];

        protected $casts = ['event_at' => NepaliDateTimeCast::class];
    };

    $event->event_at = '2081-11-05 14:30:00';
    $event->save();

    expect($event->getRawOriginal('event_at'))->toBe('2025-02-17 14:30:00');

    $fresh = $event->fresh();
    expect($fresh->event_at->toDateString())->toBe('2081-11-05');
    expect($fresh->event_at->ad()->format('H:i'))->toBe('14:30');
});
