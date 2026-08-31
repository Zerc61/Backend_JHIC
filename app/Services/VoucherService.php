<?php

namespace App\Services;

use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherClaim;
use Illuminate\Validation\ValidationException;

class VoucherService
{
    /**
     * Pratinjau diskon untuk user yang sudah mengklaim voucher.
     * Tidak mengubah status apa pun.
     *
     * @return array{valid: bool, voucher?: Voucher, discount?: float, final_amount?: float, message?: string}
     */
    public function preview(User $user, Voucher $voucher, float $amount): array
    {
        $claim = $this->findUnusedClaim($user, $voucher);

        if (! $claim) {
            return [
                'valid' => false,
                'message' => 'Kode voucher belum kamu klaim atau sudah terpakai.',
            ];
        }

        if (! $voucher->isValid()) {
            return [
                'valid' => false,
                'message' => 'Voucher tidak aktif atau sudah kedaluwarsa.',
            ];
        }

        $calc = $voucher->calculateDiscount($amount);

        if (($calc['valid'] ?? false) !== true || (float) $calc['discount'] <= 0) {
            return [
                'valid' => false,
                'message' => $calc['message'] ?? 'Minimum pembelian belum terpenuhi.',
            ];
        }

        return [
            'valid' => true,
            'voucher' => $voucher,
            'discount' => (float) $calc['discount'],
            'final_amount' => (float) $calc['final_amount'],
        ];
    }

    /**
     * Terapkan voucher ke nilai transaksi: validasi claim milik user,
     * tandai claim terpakai, dan naikkan used_count voucher.
     *
     * @return array{voucher_id: int, code: string, discount: float, final_amount: float}
     */
    public function apply(User $user, string $code, float $amount): array
    {
        $voucher = Voucher::byCode($code)->first();

        if (! $voucher) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Kode voucher tidak ditemukan.',
            ]);
        }

        $preview = $this->preview($user, $voucher, $amount);

        if (! $preview['valid']) {
            throw ValidationException::withMessages([
                'voucher_code' => $preview['message'],
            ]);
        }

        $claim = VoucherClaim::where('user_id', $user->id)
            ->where('voucher_id', $voucher->id)
            ->where('status', 'unused')
            ->lockForUpdate()
            ->first();

        if (! $claim) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Kode voucher belum kamu klaim atau sudah terpakai.',
            ]);
        }

        $claim->update([
            'status' => 'used',
            'used_at' => now(),
        ]);

        $voucher->increment('used_count');

        return [
            'voucher_id' => $voucher->id,
            'code' => $voucher->code,
            'discount' => $preview['discount'],
            'final_amount' => $preview['final_amount'],
        ];
    }

    private function findUnusedClaim(User $user, Voucher $voucher): ?VoucherClaim
    {
        return VoucherClaim::where('user_id', $user->id)
            ->where('voucher_id', $voucher->id)
            ->where('status', 'unused')
            ->first();
    }
}