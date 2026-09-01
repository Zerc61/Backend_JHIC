<?php

namespace App\Console\Commands;

use App\Models\Destination;
use Illuminate\Console\Command;

class ExportDestinationsForAI extends Command
{
    protected $signature = 'destinations:export-ai {--path=app/ai/destinations-ai.json}';

    protected $description = 'Export destinasi (beserta fasilitas & review) ke JSON untuk di-ingest ke EJT AI Core';

    public function handle(): int
    {
        $destinations = Destination::query()
            ->with(['facilities', 'reviews', 'category'])
            ->orderBy('id')
            ->get();

        $records = $destinations->map(function (Destination $destination) {
            $reviewLines = $destination->reviews
                ->map(fn ($review) => sprintf(
                    '%s (rating %d/5): %s',
                    $review->user?->name ?? 'Pengunjung',
                    (int) $review->rating,
                    $review->comment ?? '-'
                ))
                ->take(5)
                ->all();

            return [
                'id'           => $destination->id,
                'source_type'  => 'destination',
                'name'         => $destination->name,
                'slug'         => $destination->slug,
                'category'     => $destination->category?->name,
                'location'     => $destination->address,
                'description'  => $destination->description,
                'estimated_cost' => $destination->estimated_cost !== null
                    ? 'Rp ' . number_format((float) $destination->estimated_cost, 0, ',', '.')
                    : null,
                'price'        => $destination->ticket_price > 0
                    ? 'Rp ' . number_format((float) $destination->ticket_price, 0, ',', '.')
                    : 'Gratis',
                'opening_hours' => $destination->open_hour && $destination->close_hour
                    ? $destination->open_hour . ' - ' . $destination->close_hour
                    : null,
                'facilities'   => $destination->facilities->pluck('name')->implode(', '),
                'average_rating' => $destination->average_rating,
                'reviews'      => implode(' | ', $reviewLines),
            ];
        })->values();

        $payload = [
            'source_type' => 'destination',
            'records'     => $records->all(),
        ];

        $target = $this->option('path');
        if (!str_starts_with($target, '/')) {
            $target = storage_path($target);
        }

        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }

        file_put_contents($target, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info(sprintf(
            '✅ Destination exported: %d records -> %s',
            $records->count(),
            $target
        ));

        return self::SUCCESS;
    }
}
