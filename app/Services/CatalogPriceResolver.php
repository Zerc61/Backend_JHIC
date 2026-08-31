<?php

namespace App\Services;

use App\Models\Destination;
use App\Models\Hotel;
use App\Models\HotelRoom;
use App\Models\Product;
use App\Models\TransportTicket;
use App\Models\TravelPackage;

/**
 * Resolver harga/identitas katalog lintas entitas, dipakai bersama oleh
 * itinerary builder, price-tracking wishlist, dan search.
 */
class CatalogPriceResolver
{
    public const TYPES = ['hotel', 'destination', 'umkm', 'transport', 'package'];

    private const CLASS_TYPES = [
        Hotel::class => 'hotel',
        Destination::class => 'destination',
        Product::class => 'umkm',
        TransportTicket::class => 'transport',
        TravelPackage::class => 'package',
    ];

    public static function classFromType(string $type): ?string
    {
        return [
            'hotel' => Hotel::class,
            'destination' => Destination::class,
            'umkm' => Product::class,
            'transport' => TransportTicket::class,
            'package' => TravelPackage::class,
        ][$type] ?? null;
    }

    public static function typeFromClass(string $class): ?string
    {
        return self::CLASS_TYPES[$class] ?? null;
    }

    /**
     * @return array{price: float, name: string, image: ?string, lat: ?float, lng: ?float}|null
     */
    public function resolve(string $type, int $referenceId): ?array
    {
        return match ($type) {
            'hotel' => $this->hotel($referenceId),
            'destination' => $this->destination($referenceId),
            'umkm' => $this->product($referenceId),
            'transport' => $this->transport($referenceId),
            'package' => $this->package($referenceId),
            default => null,
        };
    }

    private function hotel(int $id): ?array
    {
        $hotel = Hotel::with('rooms')->find($id);

        if (! $hotel) {
            return null;
        }

        $minNight = $hotel->rooms->min('price_per_night');

        return [
            'price' => (float) ($minNight ?? 0),
            'name' => $hotel->name,
            'image' => $hotel->thumbnail,
            'lat' => $hotel->latitude ? (float) $hotel->latitude : null,
            'lng' => $hotel->longitude ? (float) $hotel->longitude : null,
        ];
    }

    private function destination(int $id): ?array
    {
        $dest = Destination::with('galleries')->find($id);

        if (! $dest) {
            return null;
        }

        return [
            'price' => (float) ($dest->ticket_price ?? $dest->estimated_cost ?? 0),
            'name' => $dest->name,
            'image' => $dest->main_image,
            'lat' => $dest->latitude ? (float) $dest->latitude : null,
            'lng' => $dest->longitude ? (float) $dest->longitude : null,
        ];
    }

    private function product(int $id): ?array
    {
        $product = Product::with('umkm')->find($id);

        if (! $product) {
            return null;
        }

        return [
            'price' => (float) ($product->price ?? 0),
            'name' => $product->name ?: ($product->umkm?->name ?? 'Produk UMKM'),
            'image' => $product->image,
            'lat' => $product->umkm?->latitude ? (float) $product->umkm->latitude : null,
            'lng' => $product->umkm?->longitude ? (float) $product->umkm->longitude : null,
        ];
    }

    private function transport(int $id): ?array
    {
        $ticket = TransportTicket::find($id);

        if (! $ticket) {
            return null;
        }

        return [
            'price' => (float) $ticket->price_per_ticket,
            'name' => $ticket->provider . ' — ' . $ticket->getRouteLabel(),
            'image' => null,
            'lat' => null,
            'lng' => null,
        ];
    }

    private function package(int $id): ?array
    {
        $package = TravelPackage::with('destination')->find($id);

        if (! $package) {
            return null;
        }

        return [
            'price' => (float) $package->price_per_person,
            'name' => $package->name,
            'image' => $package->thumbnail,
            'lat' => $package->destination?->latitude ? (float) $package->destination->latitude : null,
            'lng' => $package->destination?->longitude ? (float) $package->destination->longitude : null,
        ];
    }
}