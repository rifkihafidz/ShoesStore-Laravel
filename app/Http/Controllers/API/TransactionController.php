<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request) {
        $id = $request->input('id');
        $limit = $request->input('limit');
        $status = $request->input('status');

        if ($id) {
            $transaction = Transaction::with(['items.product'])->find($id);

            if ($transaction) {
                return ResponseFormatter::success(
                    $transaction,
                    'Data transaksi berhasil diambil',
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Data transaksi tidak ada',
                    404,
                );
            }
        }

        // Data transaksi user yang terautentikasi.
        $transaction = Transaction::with(['items.product'])->where('users_id', Auth::user()->id);


        if ($status) {
            $transaction->where('status', $status);
        }

        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Data transaksi berhasil diambil',
        );
    }

    public function checkout(Request $request) {
        $request->validate([
            'items' => 'required|array',
            // Validasi apakah id barang ada di table product
            'items.*.id' => 'exists:products,id',
            'total_price' => 'required',
            'shipping_price' => 'required',
            // in: Hanya menerima seperti yang di deklarasikan
            'status' => 'required|in:PENDING,SUCCESS,CANCELED,FAILED,SHIPPING,SHIPPED',
        ]);

        $transaction = Transaction::create([
            'users_id' => Auth::user()->id,
            'address' => $request->address,
            'total_price' => $request->total_price,
            'shipping_price' => $request->shipping_price,
            'status' => $request->status,
        ]);

        // Insert data produk dari 1 transaksi ke tabel transactionitems
        foreach($request->items as $product) {
            TransactionItem::create([
                'users_id' => Auth::user()->id,
                'products_id' => $product['id'],
                'transactions_id' => $transaction->id,
                'quantity' => $product['quantity'],
            ]);
        }

        return ResponseFormatter::success($transaction->load('items.product'), 'Transaction successfully created.');
    }
}
