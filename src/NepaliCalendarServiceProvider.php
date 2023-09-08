<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar;

use Carbon\Carbon;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Sambat\NepaliCalendar\Commands\NepaliDateConvertCommand;
use Sambat\NepaliCalendar\Commands\NepaliDateInfoCommand;
use Sambat\NepaliCalendar\Facades\NepaliDate;

class NepaliCalendarServiceProvider extends ServiceProvider
{
    public const VERSION = '1.0.0';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nepali-calendar.php', 'nepali-calendar');

        $this->app->singleton('nepali-date', fn () => new NepaliDateFactory);
        $this->app->alias('nepali-date', NepaliDateFactory::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                NepaliDateConvertCommand::class,
                NepaliDateInfoCommand::class,
            ]);

            AboutCommand::add('Nepali Calendar', fn () => ['Version' => self::VERSION]);
        }

        $this->publishes([
            __DIR__.'/../config/nepali-calendar.php' => config_path('nepali-calendar.php'),
        ], 'nepali-calendar-config');

        $this->registerBladeDirectives();
        $this->registerValidationRules();
        $this->registerCarbonMacros();
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

        Validator::replacer('nepali_date', fn () => 'The :attribute must be a valid Nepali (Bikram Sambat) date.');
        Validator::replacer(
            'nepali_date_format',
            fn ($message, $attribute, $rule, $parameters) => 'The :attribute must be a valid Nepali date'
                .(isset($parameters[0]) ? " in the format {$parameters[0]}" : '').'.'
        );
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
