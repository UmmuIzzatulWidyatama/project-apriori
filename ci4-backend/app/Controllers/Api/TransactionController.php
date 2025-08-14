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

    public function getList()
    {
        $transactions = $this->transactionModel
            ->orderBy('sale_date', 'DESC')
            ->findAll();

        $result = [];

        foreach ($transactions as $trans) {
            // Ambil detail produk dari transaksi
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
            'data' => $result
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

}
