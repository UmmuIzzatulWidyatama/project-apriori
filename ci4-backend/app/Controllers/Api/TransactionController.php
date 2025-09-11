<?php

namespace App\Controllers\Api;
use App\Controllers\BaseController; 
use CodeIgniter\API\ResponseTrait;

use App\Models\TransactionModel;
use App\Models\TransactionDetailModel;

class TransactionController extends BaseController
{
    use ResponseTrait;
    protected $transactionModel;
    protected $transactionDetailModel;

    public function __construct()
    {
        $this->transactionModel = new TransactionModel();
        $this->transactionDetailModel = new TransactionDetailModel();
    }

    public function transactionView()
    {
        return view('transaksi_list');
    }

    public function detailView()
    {
        return view('transaksi_detail');
    }

    public function list()
    {
        $page = $this->request->getGet('page') ?? 1;
        $perPage = $this->request->getGet('limit') ?? 10;

        $transactions = $this->transactionModel
            ->orderBy('sale_date', 'DESC')
            ->paginate($perPage, 'default', $page);

        $total = $this->transactionModel->countAll();

        $result = [];

        foreach ($transactions as $trans) {
            $items = $this->transactionDetailModel
                ->select('product_name')
                ->where('transaction_id', $trans['id'])
                ->findAll();

            $result[] = [
                'id' => $trans['id'],
                'sale_date' => $trans['sale_date'],
                'transaction_number' => $trans['transaction_number'],
                'items' => implode(', ', array_column($items, 'product_name'))
            ];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $result,
            'meta' => [
                'page' => (int)$page,
                'limit' => (int)$perPage,
                'total' => $total
            ]
        ]);
    }

    public function detail($id)
    {
        $transaction = $this->transactionModel->find($id);

        if (!$transaction) {
            return $this->failNotFound("Transaksi dengan ID $id tidak ditemukan");
        }

        $products = $this->transactionDetailModel
            ->select('product_name')
            ->where('transaction_id', $id)
            ->findAll();

        return $this->respond([
            'status' => 'success',
            'data' => [
                [
                    'id' => (string)$transaction['id'],
                    'sale_date' => $transaction['sale_date'],
                    'transaction_number' => $transaction['transaction_number'],
                    'products' => $products
                ]
            ]
        ]);
    }

    public function create()
    {
        $json = $this->request->getJSON();

        if (!$json || !is_array($json)) {
            return $this->fail('Format JSON tidak valid');
        }

        $trxModel = new TransactionModel();
        $detailModel = new TransactionDetailModel();
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            foreach ($json as $trx) {
                $trxModel->insert([
                    'sale_date' => $trx->sale_date,
                    'transaction_number' => $trx->transaction_number ?? null
                ]);
                $transactionId = $trxModel->insertID();

                foreach ($trx->products as $product) {
                    $detailModel->insert([
                        'transaction_id' => $transactionId,
                        'product_name' => $product
                    ]);
                }
            }

            $db->transComplete();
            return $this->respond([
                'status' => 200,
                'message' => 'Transaksi berhasil disimpan'
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->failServerError('Gagal menyimpan transaksi: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        $transaction = $this->transactionModel->find($id);

        if (!$transaction) {
            return $this->failNotFound("Transaksi dengan ID $id tidak ditemukan");
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Hapus dulu detailnya
            $this->transactionDetailModel
                ->where('transaction_id', $id)
                ->delete();

            // Hapus transaksi utamanya
            $this->transactionModel->delete($id);

            $db->transComplete();

            return $this->respondDeleted([
                'status' => 'success',
                'message' => "Transaksi ID $id berhasil dihapus"
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->failServerError('Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }


}
