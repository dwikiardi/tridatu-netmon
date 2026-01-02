<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\CustomerLog;
use Illuminate\Support\Facades\Auth;

class CustomerObserver
{
    /**
     * Handle the Customer "created" event.
     */
    public function created(Customer $customer): void
    {
        $user = Auth::user();

        CustomerLog::create([
            'customer_cid' => $customer->cid,
            'action' => 'created',
            'field_changed' => null,
            'old_value' => null,
            'new_value' => json_encode($customer->toArray()),
            'changed_by' => $user ? $user->name : 'System',
            'user_id' => $user ? $user->id : null,
        ]);
    }

    /**
     * Handle the Customer "updated" event.
     */
    public function updated(Customer $customer): void
    {
        $user = Auth::user();
        $changes = $customer->getChanges();
        $original = $customer->getOriginal();

        // Field labels untuk display yang lebih user-friendly
        $fieldLabels = [
            'nama' => 'Nama Customer',
            'email' => 'Email',
            'sales' => 'Sales',
            'packet' => 'Paket',
            'alamat' => 'Alamat',
            'pic_it' => 'PIC IT',
            'no_it' => 'No IT',
            'pic_finance' => 'PIC Finance',
            'no_finance' => 'No Finance',
            'coordinate_maps' => 'Koordinat Maps',
            'pembayaran_perbulan' => 'Pembayaran Per Bulan',
            'status' => 'Status',
            'note' => 'Note',
            'tgl_customer_aktif' => 'Tanggal Customer Aktif',
            'billing_aktif' => 'Billing Aktif',
        ];

        foreach ($changes as $field => $newValue) {
            // Skip updated_at and created_at fields
            if (in_array($field, ['updated_at', 'created_at'])) {
                continue;
            }

            $oldValue = $original[$field] ?? null;

            // Skip jika nilai tidak benar-benar berubah (untuk menghindari false positive)
            if ($oldValue == $newValue) {
                continue;
            }

            // Format nilai untuk pembayaran_perbulan
            if ($field === 'pembayaran_perbulan') {
                $oldValue = $oldValue ? 'Rp. ' . number_format($oldValue, 0, ',', '.') : '-';
                $newValue = $newValue ? 'Rp. ' . number_format($newValue, 0, ',', '.') : '-';
            }

            CustomerLog::create([
                'customer_cid' => $customer->cid,
                'action' => 'updated',
                'field_changed' => $fieldLabels[$field] ?? $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'changed_by' => $user ? $user->name : 'System',
                'user_id' => $user ? $user->id : null,
            ]);
        }
    }

    /**
     * Handle the Customer "deleting" event.
     */
    public function deleting(Customer $customer): void
    {
        $user = Auth::user();

        CustomerLog::create([
            'customer_cid' => $customer->cid,
            'action' => 'deleted',
            'field_changed' => null,
            'old_value' => json_encode($customer->toArray()),
            'new_value' => null,
            'changed_by' => $user ? $user->name : 'System',
            'user_id' => $user ? $user->id : null,
        ]);
    }

    /**
     * Handle the Customer "restored" event.
     */
    public function restored(Customer $customer): void
    {
        //
    }

    /**
     * Handle the Customer "force deleted" event.
     */
    public function forceDeleted(Customer $customer): void
    {
        //
    }
}
