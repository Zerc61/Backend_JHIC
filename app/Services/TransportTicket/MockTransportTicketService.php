<?php


// app/Services/TransportTicket/MockTransportTicketService.php

namespace App\Services\TransportTicket;

use App\Models\TransportTicket;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class MockTransportTicketService implements TransportTicketServiceInterface
{
    /**
     * Fase 1: Query langsung ke database lokal (data seed)
     * Fase 2: Ganti dengan HTTP call ke API aggregator
     */
    public function search(
        string $origin,
        string $destination,
        string $date,
        int    $passengers,
        ?string $mode = null
    ): Collection {
        return TransportTicket::query()
            ->available()
            ->forRoute($origin, $destination)
            ->forDate($date)
            ->byMode($mode)
            ->where('available_seats', '>=', $passengers)
            ->orderBy('departure_time')
            ->get();
    }

    /**
     * Fase 1: Return data simulasi seolah dari API
     * Fase 2: Ganti dengan HTTP call ke API aggregator untuk issue tiket
     */
    public function book(int $ticketId, array $passengers): array
    {
        $ticket = TransportTicket::findOrFail($ticketId);

        // Simulasi: generate data seolah dari provider
        $bookingCode = 'MOCK-' . strtoupper(Str::random(6));

        $ticketNumbers = [];
        $seatNumbers = [];

        // Generate seat berurutan (simulasi)
        $rows = range('A', 'F');
        $startRow = rand(1, 20);

        foreach ($passengers as $index => $p) {
            $ticketNumbers[] = 'MOCK-TKT-' . strtoupper(Str::random(4)) . ($index + 1);
            $seatNumbers[] = ($startRow + $index) . $rows[$index % count($rows)];
        }

        return [
            'provider_booking_code' => $bookingCode,
            'ticket_numbers'        => $ticketNumbers,
            'seat_numbers'          => $seatNumbers,
            'status'                => 'issued',
            'issued_at'             => now()->toIso8601String(),
            'raw_response'          => [
                'mock'            => true,
                'message'         => 'Simulated booking — no real API called',
                'provider'        => $ticket->provider,
                'flight_number'   => $ticket->flight_number,
                'booking_code'    => $bookingCode,
                'note'            => 'Ganti implementasi ini dengan API call saat Fase 2',
            ],
        ];
    }

    /**
     * Fase 1: Hapus data lokal + return true
     * Fase 2: HTTP call ke API untuk cancel
     */
    public function cancel(string $providerBookingCode): bool
    {
        // Di Fase 1, kita tidak perlu call API apapun
        // Cukup update status lokal (sudah dihandle oleh controller)
        return true;
    }
}
