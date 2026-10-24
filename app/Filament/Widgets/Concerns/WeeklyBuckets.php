<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

// Shared helpers for the weekly dashboard charts (weeks run Monday–Sunday, Pakistan time)
trait WeeklyBuckets
{
    protected function getFilters(): ?array
    {
        return [
            '8'  => 'Last 8 weeks',
            '12' => 'Last 12 weeks',
            '26' => 'Last 6 months',
            '52' => 'Last 12 months',
        ];
    }

    protected function weeks(): int
    {
        return in_array((int) $this->filter, [8, 12, 26, 52], true) ? (int) $this->filter : 12;
    }

    protected function firstWeekStart(): CarbonImmutable
    {
        return CarbonImmutable::now()->startOfWeek()->subWeeks($this->weeks() - 1);
    }

    /** @return array<string, CarbonImmutable> week key (Y-m-d of Monday) => Monday */
    protected function weekStarts(): array
    {
        $weeks = [];
        $start = $this->firstWeekStart();

        for ($i = 0; $i < $this->weeks(); $i++) {
            $monday = $start->addWeeks($i);
            $weeks[$monday->format('Y-m-d')] = $monday;
        }

        return $weeks;
    }

    protected function labels(): array
    {
        return array_values(array_map(fn (CarbonImmutable $monday) => $monday->format('d M'), $this->weekStarts()));
    }

    /**
     * Sum (or count) values per week.
     *
     * @param  Collection<int, mixed>  $rows
     */
    protected function perWeek(Collection $rows, string $dateField, ?callable $value = null): array
    {
        $totals = array_fill_keys(array_keys($this->weekStarts()), 0);

        foreach ($rows as $row) {
            $date = $row->{$dateField};
            if (! $date) {
                continue;
            }

            $key = CarbonImmutable::parse($date)->startOfWeek()->format('Y-m-d');
            if (array_key_exists($key, $totals)) {
                $totals[$key] += $value ? $value($row) : 1;
            }
        }

        return array_values($totals);
    }
}
