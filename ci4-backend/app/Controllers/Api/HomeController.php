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

        // Ambil hanya kolom yang dibutuhkan + filter freq > 0
        $rows = $mItem->select('itemsets, frequency')
                    ->where('frequency >', 0)
                    ->findAll();

        $counts = [];
        foreach ($rows as $r) {
            $items = json_decode($r['itemsets'] ?? '[]', true) ?: [];

            if (!is_array($items) || count($items) !== 1) {
                continue;
            }

            $freq = (int)($r['frequency'] ?? 0);

            $name = (string)$items[0]; 
            $counts[$name] = ($counts[$name] ?? 0) + $freq;
        }

        // Urutkan dan ambil top-N
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
