<?php

// app/Services/TransportTicket/TransportTicketServiceInterface.php

namespace App\Services\TransportTicket;

use Illuminate\Database\Eloquent\Collection;

interface TransportTicketServiceInterface
{
    /**
     * Cari tiket tersedia berdasarkan rute & tanggal
     * 
     * @return Collection<int, \App\Models\TransportTicket>
     */
    public function search(
        string $origin,
        string $destination,
        string $date,
        int    $passengers,
        ?string $mode = null
    ): Collection;

    /**
     * Proses booking tiket (simulasi issue)
     * 
     * @param  array<array<string, string>>  $passengers  [[name, id_type, id_number], ...]
     * @return array{provider_booking_code: string, ticket_numbers: array, seat_numbers: array, status: string, issued_at: string, raw_response: array}
     */
    public function book(int $ticketId, array $passengers): array;

    /**
     * Batalkan booking tiket
     */
    public function cancel(string $providerBookingCode): bool;
}
