<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('reports', function ($table) {
        $table->increments('id');
        $table->date('report_date');
        $table->string('title');
    });

    // Canonical AD storage; BS equivalents: 2081-01-01, 2081-11-05, 2081-11-28, 2082-01-01.
    reportModel()::create(['report_date' => '2024-04-13', 'title' => 'New Year']);
    reportModel()::create(['report_date' => '2025-02-17', 'title' => 'Mid Falgun']);
    reportModel()::create(['report_date' => '2025-03-12', 'title' => 'End Falgun']);
    reportModel()::create(['report_date' => '2025-04-14', 'title' => 'Next Year']);
});

afterEach(function () {
    Schema::dropIfExists('reports');
});

it('queries by an exact BS date', function () {
    $titles = reportModel()::whereNepaliDate('report_date', '2081-11-05')->pluck('title')->all();

    expect($titles)->toBe(['Mid Falgun']);
});

it('queries by BS year', function () {
    $titles = reportModel()::whereNepaliYear('report_date', 2081)->pluck('title')->all();

    expect($titles)->toBe(['New Year', 'Mid Falgun', 'End Falgun']);
});

it('queries by BS month and day', function () {
    $titles = reportModel()::whereNepaliMonth('report_date', 2081, 11)->pluck('title')->all();

    expect($titles)->toBe(['Mid Falgun', 'End Falgun']);

    $titles = reportModel()::whereNepaliDay('report_date', 2081, 11, 28)->pluck('title')->all();

    expect($titles)->toBe(['End Falgun']);
});

it('queries by BS range and orders by date', function () {
    $titles = reportModel()::whereNepaliBetween('report_date', '2081-11-05', '2081-11-28')->pluck('title')->all();

    expect($titles)->toBe(['Mid Falgun', 'End Falgun']);

    $titles = reportModel()::orderByNepaliDate('report_date', 'desc')->pluck('title')->all();

    expect($titles)->toBe(['Next Year', 'End Falgun', 'Mid Falgun', 'New Year']);
});

it('chains with other query constraints', function () {
    $count = reportModel()::whereNepaliYear('report_date', 2081)
        ->where('title', 'not like', '%Falgun')
        ->count();

    expect($count)->toBe(1);
});

/** @return class-string<Model> */
function reportModel(): string
{
    return get_class(new class extends Model
    {
        protected $table = 'reports';

        public $timestamps = false;

        protected $guarded = [];
    });
}
