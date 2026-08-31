<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminVoucherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Voucher::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('loyalty')) {
            if ($request->boolean('loyalty')) {
                $query->whereNotNull('conditions');
            } else {
                $query->whereNull('conditions');
            }
        }

        $vouchers = $query->withCount('usages')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($vouchers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateVoucher($request);

        $data = $this->toModelData($validated);
        $voucher = Voucher::create($data);

        return response()->json([
            'message' => 'Voucher berhasil dibuat.',
            'data' => $voucher->loadCount('usages'),
        ], 201);
    }

    public function show(Voucher $voucher): JsonResponse
    {
        return response()->json([
            'data' => $voucher->loadCount('usages'),
        ]);
    }

    public function update(Request $request, Voucher $voucher): JsonResponse
    {
        $validated = $this->validateVoucher($request, $voucher);

        $data = $this->toModelData($validated);
        $voucher->update($data);

        return response()->json([
            'message' => 'Voucher berhasil diperbarui.',
            'data' => $voucher->fresh()->loadCount('usages'),
        ]);
    }

    public function destroy(Voucher $voucher): JsonResponse
    {
        $voucher->delete();

        return response()->json(['message' => 'Voucher berhasil dihapus.']);
    }

    private function validateVoucher(Request $request, ?Voucher $ignore = null): array
    {
        $uniqueCode = 'unique:vouchers,code';
        if ($ignore) {
            $uniqueCode .= ',' . $ignore->id;
        }

        return $request->validate([
            'code' => ['required', 'string', 'max:64', $uniqueCode],
            'description' => 'nullable|string|max:1000',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => [
                'required',
                'numeric',
                'min:0.01',
                $request->input('discount_type') === 'percentage' ? 'max:100' : 'max:1000000000',
            ],
            'max_discount' => 'nullable|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'total_quota' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'is_active' => 'sometimes|boolean',
            'loyalty_redeemable' => 'sometimes|boolean',
            'cost_coins' => 'nullable|numeric|min:0',
            'min_tier' => 'sometimes|in:bronze,silver,gold,platinum',
            'is_free' => 'sometimes|boolean',
        ]);
    }

    private function toModelData(array $validated): array
    {
        $data = [
            'code' => strtoupper($validated['code']),
            'description' => $validated['description'] ?? null,
            'discount_type' => $validated['discount_type'],
            'discount_value' => (float) $validated['discount_value'],
            'max_discount' => $validated['max_discount'] !== null && $validated['max_discount'] !== ''
                ? (float) $validated['max_discount']
                : null,
            'min_purchase' => $validated['min_purchase'] !== null && $validated['min_purchase'] !== ''
                ? (float) $validated['min_purchase']
                : 0,
            'total_quota' => $validated['total_quota'] !== null && $validated['total_quota'] !== ''
                ? (int) $validated['total_quota']
                : null,
            'per_user_limit' => $validated['per_user_limit'] !== null && $validated['per_user_limit'] !== ''
                ? (int) $validated['per_user_limit']
                : 1,
            'valid_from' => $validated['valid_from'],
            'valid_until' => $validated['valid_until'],
            'is_active' => $validated['is_active'] ?? true,
            'is_free' => $validated['is_free'] ?? false,
        ];

        $redeemable = (bool) ($validated['loyalty_redeemable'] ?? false);
        if ($redeemable) {
            $conditions = [];
            $minTier = $validated['min_tier'] ?? 'bronze';
            $costCoins = (float) ($validated['cost_coins'] ?? 0);
            if ($costCoins > 0) {
                $conditions['cost_coins'] = $costCoins;
            }
            $conditions['min_tier'] = $minTier;
            $data['conditions'] = $conditions;
        } else {
            $data['conditions'] = null;
        }

        return $data;
    }
}