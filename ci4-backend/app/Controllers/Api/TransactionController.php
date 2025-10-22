<?php

namespace App\Controllers\Api;
use App\Controllers\BaseController; 
use CodeIgniter\API\ResponseTrait;

use App\Models\TransactionModel;
use App\Models\TransactionDetailModel;

use PhpOffice\PhpSpreadsheet\IOFactory; 

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

    public function uploadView()
    {
        return view('transaksi_upload');
    }

    public function detailView()
    {
        return view('transaksi_detail');
    }

    public function list()
    {
        $page    = (int) ($this->request->getGet('page')  ?? 1);
        $perPage = (int) ($this->request->getGet('limit') ?? 10);

        // 1) Ambil transaksi per halaman (masih pakai paginate)
        $transactions = $this->transactionModel
            ->orderBy('sale_date', 'DESC')
            ->paginate($perPage, 'default', $page);

        // 2) Ambil SEMUA detail untuk transaksi di halaman ini dalam SATU query
        $ids = array_column($transactions, 'id');
        $detailMap = [];

        if (!empty($ids)) {
            $rows = $this->transactionDetailModel
                ->select('transaction_id, product_name')
                ->whereIn('transaction_id', $ids)
                ->orderBy('transaction_id', 'ASC')
                ->findAll();

            foreach ($rows as $r) {
                $detailMap[$r['transaction_id']][] = $r['product_name'];
            }
        }

        // 3) Susun hasil akhir
        $result = [];
        foreach ($transactions as $t) {
            $result[] = [
                'id' => (int) $t['id'],
                'sale_date' => $t['sale_date'],
                'transaction_number' => $t['transaction_number'],
                'items' => isset($detailMap[$t['id']]) ? implode(', ', $detailMap[$t['id']]) : ''
            ];
        }

        // 4) Total yang benar (mengikuti query paginate terakhir)
        $total = $this->transactionModel->pager->getTotal();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $result,
            'meta'   => [
                'page'  => $page,
                'limit' => $perPage,
                'total' => $total,
            ],
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

    public function upload()
    {
        $file = $this->request->getFile('file');
        if (!$file) {
            return $this->failValidationErrors('Field "file" tidak ditemukan');
        }
        if (!$file->isValid()) {
            return $this->failValidationErrors(
                'Upload error: '.$file->getErrorString().' ('.$file->getError().')'
            );
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['xlsx', 'xls'])) {
            return $this->failValidationErrors('Format file harus .xlsx atau .xls');
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $sheet       = $spreadsheet->getActiveSheet();
            // index kolom: 'A','B','C','D',...
            $rows        = $sheet->toArray(null, true, true, true);

            // deteksi header di baris pertama
            $startRow = 1;
            if (!empty($rows[1])) {
                $a = strtolower(trim((string)($rows[1]['A'] ?? '')));
                $b = strtolower(trim((string)($rows[1]['B'] ?? '')));
                $c = strtolower(trim((string)($rows[1]['C'] ?? '')));
                $d = strtolower(trim((string)($rows[1]['D'] ?? '')));
                $maybeHeader = (
                    (strpos($a, 'nomor') !== false && strpos($a, 'transaksi') !== false) ||
                    (strpos($b, 'tanggal') !== false) ||
                    (strpos($c, 'kode')   !== false && strpos($c, 'item') !== false) ||
                    (strpos($d, 'produk') !== false || strpos($d, 'nama') !== false)
                );
                if ($maybeHeader) $startRow = 2;
            }

            $result = [];

            for ($i = $startRow; $i <= count($rows); $i++) {
                $row = $rows[$i] ?? null;
                if ($row === null) continue;

                // A: Nomor Transaksi 
                // B: Tanggal Transaksi
                // C: Kode Item
                // D: Nama Produk 
                $noTransaksi = trim((string)($row['A'] ?? ''));
                $tglTransaksi     = trim((string)($row['B'] ?? ''));
                $kodeItem    = trim((string)($row['C'] ?? ''));
                $namaProduk  = trim((string)($row['D'] ?? ''));

                // Jika ada data kosong semua (A..D kosong semua)
                if ($noTransaksi === '' && $tglTransaksi === '' && $kodeItem === '' && $namaProduk === '') {
                    return $this->failValidationErrors('Format tidak sesuai');
                }

                // Validasi field jika kosong
                if ($noTransaksi === '' or $tglTransaksi === '' or $namaProduk === '') {
                    $result[] = [
                        'row_number'   => (string)$i,
                        'no_transaksi' => $noTransaksi,
                        'tgl_transaksi' => $tglTransaksi,
                        'product_name' => $namaProduk,  
                        'status'       => 'Data tidak boleh kosong',
                    ];
                    continue;
                }

                $result[] = [
                    'row_number'   => (string)$i,
                    'no_transaksi' => $noTransaksi,
                    'tgl_transaksi' => $tglTransaksi,
                    'product_name' => $namaProduk,     
                    'status'       => 'Dapat disimpan'
                ];
            }

            return $this->respond([
                'status' => 'success',
                'data'   => $result,
            ]);

        } catch (\Throwable $e) {
            return $this->failServerError('Gagal memproses file: '.$e->getMessage());
        }
    }

    public function save()
    {
        $items = $this->request->getJSON(true);
        if (!is_array($items) || empty($items)) {
            return $this->failValidationErrors('Payload harus berupa array dan tidak boleh kosong');
        }

        $mTrx = new TransactionModel();
        $mDet = new TransactionDetailModel();

        // Normalisasi & validasi awal + grupkan per (no_transaksi, sale_date)
        $groups = [];     // key: "no|date" => ['no'=>..., 'date'=>..., 'rows'=>[[rowNum, productNameRaw]]]
        $rowResults = []; // feedback per baris
        $errors = 0; $validRows = 0;

        foreach ($items as $idx => $row) {
            $rowNum      = $idx + 1;
            $no          = trim((string)($row['no_transaksi']   ?? ''));
            $tglRaw      = trim((string)($row['tgl_transaksi']  ?? ''));
            $productName = trim((string)($row['product_name']   ?? ''));

            if ($no === '' || $tglRaw === '' || $productName === '') {
                $rowResults[] = [
                    'row_number'    => (string)$rowNum,
                    'no_transaksi'  => $no,
                    'tgl_transaksi' => $tglRaw,
                    'product_name'  => $productName,
                    'status'        => 'Baris tidak lengkap (no_transaksi/tgl_transaksi/product_name wajib)',
                ];
                $errors++;
                continue;
            }

            $ts = strtotime($tglRaw);
            if ($ts === false) {
                $rowResults[] = [
                    'row_number'    => (string)$rowNum,
                    'no_transaksi'  => $no,
                    'tgl_transaksi' => $tglRaw,
                    'product_name'  => $productName,
                    'status'        => 'Format tgl_transaksi tidak valid',
                ];
                $errors++;
                continue;
            }
            $saleDate = date('Y-m-d', $ts);

            $key = $no.'|'.$saleDate;
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'no'    => $no,
                    'date'  => $saleDate,
                    'rows'  => [], // simpan [rowNum, productNameRaw]
                ];
            }
            $groups[$key]['rows'][] = [$rowNum, $productName];

            $rowResults[] = [
                'row_number'    => (string)$rowNum,
                'no_transaksi'  => $no,
                'tgl_transaksi' => $saleDate,
                'product_name'  => $productName,
                'status'        => 'Siap disimpan',
            ];
            $validRows++;
        }

        if ($validRows === 0) {
            return $this->failValidationErrors('Tidak ada baris valid untuk disimpan');
        }

        $db = $mTrx->db;
        $insertedHeaders = 0;
        $reusedHeaders   = 0;
        $insertedDetails = 0;
        $skippedPayloadDup = 0;
        $skippedDbDup      = 0;

        try {
            $db->transException(true)->transStart();

            foreach ($groups as $g) {
                $no   = $g['no'];
                $date = $g['date'];

                // cek header
                $existing = $mTrx->where('transaction_number', $no)
                                ->where('sale_date', $date)
                                ->first();

                if ($existing) {
                    $transactionId = (int)$existing['id'];
                    $reusedHeaders++;
                } else {
                    $mTrx->insert([
                        'sale_date'          => $date,
                        'transaction_number' => $no,
                    ]);
                    $transactionId = (int)$mTrx->getInsertID();
                    $insertedHeaders++;
                }

                // Ambil produk yang sudah ada di DB untuk transaksi tersebut
                $existingDetails = $mDet->select('product_name')
                                        ->where('transaction_id', $transactionId)
                                        ->findAll();
                $existingSet = [];
                foreach ($existingDetails as $ed) {
                    $existingSet[mb_strtolower(trim($ed['product_name']))] = true;
                }

                // Set untuk deteksi duplikasi di payload yang sama (per transaksi)
                $payloadSet = [];

                // Insert detail, skip duplikat
                foreach ($g['rows'] as [$rowNum, $productRaw]) {
                    $nameNorm = mb_strtolower(trim($productRaw));

                    // cek duplikasi di payload yg sama
                    if (isset($payloadSet[$nameNorm])) {
                        $skippedPayloadDup++;
                        foreach ($rowResults as &$rr) {
                            if ((string)$rowNum === (string)$rr['row_number'] && $rr['status'] === 'Siap disimpan') {
                                $rr['status'] = 'Duplikat pada payload - diabaikan';
                                break;
                            }
                        }
                        continue;
                    }

                    // cek duplikasi di DB
                    if (isset($existingSet[$nameNorm])) {
                        $skippedDbDup++;
                        foreach ($rowResults as &$rr) {
                            if ((string)$rowNum === (string)$rr['row_number'] && $rr['status'] === 'Siap disimpan') {
                                $rr['status'] = 'Duplikat pada database - diabaikan';
                                break;
                            }
                        }
                        continue;
                    }

                    // jika lolos maka insert ke db
                    $mDet->insert([
                        'transaction_id' => $transactionId,
                        'product_name'   => $productRaw,
                    ]);
                    $insertedDetails++;
                    $payloadSet[$nameNorm] = true;
                    $existingSet[$nameNorm] = true; // agar payload berikutnya juga terdeteksi

                    // tandai baris tersimpan
                    foreach ($rowResults as &$rr) {
                        if ((string)$rowNum === (string)$rr['row_number'] && $rr['status'] === 'Siap disimpan') {
                            $rr['status'] = 'Tersimpan';
                            break;
                        }
                    }
                }
            }

            $db->transComplete();

            return $this->respondCreated([
                'status'  => 'success',
                'summary' => [
                    'header_baru'               => $insertedHeaders,
                    'header_digunakan'          => $reusedHeaders,
                    'detail_tersimpan'          => $insertedDetails,
                    'baris_valid'               => $validRows,
                    'baris_error'               => $errors,
                    'detail_duplikat_payload'   => $skippedPayloadDup,
                    'detail_duplikat_database'  => $skippedDbDup,
                ],
                'rows' => $rowResults,
            ]);
        } catch (\Throwable $e) {
            return $this->failServerError('Gagal menyimpan transaksi: '.$e->getMessage());
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
