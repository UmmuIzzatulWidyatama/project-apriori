<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\TransactionModel;
use App\Models\AnalisisDataModel;
use App\Models\AprioriItemsetModel;

class HomeController extends BaseController
{
    use ResponseTrait;

    public function homeView()
    {
        return view('halaman_utama');
    }

    public function summary()
    {
        $mTx  = new TransactionModel();
        $mAna = new AnalisisDataModel();

        // hitung total baris pada masing-masing tabel
        $jumlahDataTransaksi = $mTx->countAllResults();
        $jumlahDataAnalisis  = $mAna->countAllResults();

        return $this->respond([
            'status' => 'success',
            'data'   => [
                'jumlahDataTransaksi' => (int)$jumlahDataTransaksi,
                'jumlahDataAnalisis'  => (int)$jumlahDataAnalisis,
            ],
        ]);
    }
    public function topProducts()
    {
        $limit = (int) ($this->request->getGet('limit') ?? 3);
        if ($limit <= 0) $limit = 3;

        $mItem = new AprioriItemsetModel();

        // Ambil semua itemset + frequency, lalu tally di PHP
        $rows = $mItem->select('itemsets, frequency')->findAll();

        $counts = [];
        foreach ($rows as $r) {
            $items = json_decode($r['itemsets'] ?? '[]', true) ?: [];
            $freq  = (int)($r['frequency'] ?? 0);
            if ($freq <= 0) $freq = 1;          // fallback aman

            foreach ($items as $it) {
                $name = (string)$it;
                $counts[$name] = ($counts[$name] ?? 0) + $freq;
            }
        }

        // Urutkan descending & ambil top-N
        arsort($counts);
        $top = array_slice($counts, 0, $limit, true);

        // Format data untuk chart
        $data = [];
        foreach ($top as $name => $val) {
            $data[] = ['product' => $name, 'value' => (int)$val];
        }

        return $this->respond([
            'status' => 'success',
            'data'   => $data,
        ]);
    }
}
