<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Sambat\NepaliCalendar\Commands\NepaliDateConvertCommand;
use Sambat\NepaliCalendar\Commands\NepaliDateInfoCommand;
use Sambat\NepaliCalendar\Commands\NepaliDateSeedCommand;
use Sambat\NepaliCalendar\Facades\NepaliDate;
use Sambat\NepaliCalendar\Providers\ArrayCalendarDataProvider;
use Sambat\NepaliCalendar\Providers\DatabaseCalendarDataProvider;
use Sambat\NepaliCalendar\Query\NepaliDateQueryBuilder;

class NepaliCalendarServiceProvider extends ServiceProvider
{
    public const VERSION = '1.7.0';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nepali-calendar.php', 'nepali-calendar');

        $this->app->singleton('nepali-date', fn () => new NepaliDateFactory);
        $this->app->alias('nepali-date', NepaliDateFactory::class);

        $this->app->singleton(ArrayCalendarDataProvider::class);
        $this->app->singleton(DatabaseCalendarDataProvider::class);

        // The active calendar data source. Bindings registered under this
        // key are used verbatim, so apps can supply a fully custom provider.
        $this->app->bind('nepali-calendar.provider', function () {
            $driver = strtolower((string) config('nepali-calendar.driver', 'algorithm'));

            return match ($driver) {
                'database', 'db' => $this->app->make(DatabaseCalendarDataProvider::class),
                default => $this->app->make(ArrayCalendarDataProvider::class),
            };
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                NepaliDateConvertCommand::class,
                NepaliDateInfoCommand::class,
                NepaliDateSeedCommand::class,
            ]);

            AboutCommand::add('Nepali Calendar', fn () => [
                'Version' => self::VERSION,
                'Driver' => ucfirst((string) config('nepali-calendar.driver', 'algorithm')),
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/nepali-calendar.php' => config_path('nepali-calendar.php'),
        ], 'nepali-calendar-config');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'nepali-calendar-migrations');

        $this->registerBladeDirectives();
        $this->registerValidationRules();
        $this->registerCarbonMacros();
        $this->registerQueryMacros();
    }

    private function registerBladeDirectives(): void
    {
        if (! class_exists(Blade::class)) {
            return;
        }

        Blade::directive('nepaliDate', function (string $expression) {
            return "<?php echo \\Sambat\\NepaliCalendar\\Support\\Blade::render({$expression}); ?>";
        });

        Blade::directive('nepaliDateHuman', function (string $expression) {
            return "<?php echo \\Sambat\\NepaliCalendar\\Support\\Blade::human({$expression}); ?>";
        });
    }

    private function registerValidationRules(): void
    {
        if (! class_exists(Validator::class)) {
            return;
        }

        Validator::extend('nepali_date', function (string $attribute, mixed $value): bool {
            try {
                NepaliDate::parse($value);

                return true;
            } catch (\Throwable) {
                return false;
            }
        });

        Validator::extend('nepali_date_format', function (string $attribute, mixed $value, array $parameters): bool {
            $format = $parameters[0] ?? (string) config('nepali-calendar.default_format', 'Y-m-d');

            try {
                // Compare using western numerals so digit script does not affect the check.
                return NepaliDate::parse($value)->format($format, null, false) === $value;
            } catch (\Throwable) {
                return false;
            }
        });

        Validator::extend('nepali_date_before', function (string $attribute, mixed $value, array $parameters): bool {
            if (! isset($parameters[0])) {
                return true;
            }

            try {
                return NepaliDate::parse($value)->isBefore($parameters[0]);
            } catch (\Throwable) {
                return false;
            }
        });

        Validator::extend('nepali_date_after', function (string $attribute, mixed $value, array $parameters): bool {
            if (! isset($parameters[0])) {
                return true;
            }

            try {
                return NepaliDate::parse($value)->isAfter($parameters[0]);
            } catch (\Throwable) {
                return false;
            }
        });

        Validator::extend('nepali_date_between', function (string $attribute, mixed $value, array $parameters): bool {
            if (count($parameters) < 2) {
                return true;
            }

            try {
                return NepaliDate::parse($value)->isBetween(
                    NepaliDate::parse($parameters[0]),
                    NepaliDate::parse($parameters[1])
                );
            } catch (\Throwable) {
                return false;
            }
        });

        // Custom rule messages: Laravel does not substitute :attribute inside
        // replacer output, so the displayable attribute is used directly.
        Validator::replacer('nepali_date', fn ($message, $attribute) => "The {$attribute} must be a valid Nepali (Bikram Sambat) date.");
        Validator::replacer(
            'nepali_date_format',
            fn ($message, $attribute, $rule, $parameters) => "The {$attribute} must be a valid Nepali date"
                .(isset($parameters[0]) ? " in the format {$parameters[0]}" : '').'.'
        );
        Validator::replacer('nepali_date_before', fn ($message, $attribute, $rule, $parameters) => "The {$attribute} must be a date before {$parameters[0]}.");
        Validator::replacer('nepali_date_after', fn ($message, $attribute, $rule, $parameters) => "The {$attribute} must be a date after {$parameters[0]}.");
        Validator::replacer('nepali_date_between', fn ($message, $attribute, $rule, $parameters) => "The {$attribute} must be a date between {$parameters[0]} and {$parameters[1]}.");
    }

    private function registerQueryMacros(): void
    {
        if (! class_exists(Builder::class)) {
            return;
        }

        Builder::macro('whereNepaliDate', function (string $column, mixed $date) {
            return NepaliDateQueryBuilder::whereNepaliDate($this, $column, $date);
        });

        Builder::macro('whereNepaliYear', function (string $column, int $year) {
            return NepaliDateQueryBuilder::whereNepaliYear($this, $column, $year);
        });

        Builder::macro('whereNepaliMonth', function (string $column, int $year, int $month) {
            return NepaliDateQueryBuilder::whereNepaliMonth($this, $column, $year, $month);
        });

        Builder::macro('whereNepaliDay', function (string $column, int $year, int $month, int $day) {
            return NepaliDateQueryBuilder::whereNepaliDay($this, $column, $year, $month, $day);
        });

        Builder::macro('whereNepaliBetween', function (string $column, mixed $start, mixed $end) {
            return NepaliDateQueryBuilder::whereNepaliBetween($this, $column, $start, $end);
        });

        Builder::macro('orderByNepaliDate', function (string $column, string $direction = 'asc') {
            return NepaliDateQueryBuilder::orderByNepaliDate($this, $column, $direction);
        });
    }

    private function registerCarbonMacros(): void
    {
        if (! class_exists(Carbon::class)) {
            return;
        }

        if (! Carbon::hasMacro('toNepaliDate')) {
            Carbon::macro('toNepaliDate', fn () => NepaliDate::fromCarbon($this));
        }

        if (! Carbon::hasMacro('formatNepali')) {
            Carbon::macro('formatNepali', fn (string $format, ?string $language = null) => NepaliDate::fromCarbon($this)->format($format, $language));
        }
    }
}
