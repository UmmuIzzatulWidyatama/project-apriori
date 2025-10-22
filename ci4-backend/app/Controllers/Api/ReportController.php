<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\AnalisisDataModel;
use App\Models\AprioriItemsetModel;
use App\Models\TransactionModel;
use App\Models\AprioriRuleModel;

use Dompdf\Dompdf;
use Dompdf\Options;


class ReportController extends BaseController
{
    use ResponseTrait;

    protected $analisisModel;

    public function __construct()
    {
        $this->analisisModel = new AnalisisDataModel();
    }

    public function reportView()
    {
        return view('report_list');
    }
    
    public function mainInfoView($id)
    {
        if (!$id) {
            return redirect()->to(base_url('report'))
                            ->with('error', 'Data tidak ditemukan');
        }

        return view('report_detail', [
            'reportId' => (int)$id,
            'step'     => 1,
            'backUrl'  => base_url('report'),
            'nextUrl'  => base_url('report/itemset1/'. $id),
        ]);
    }

    public function itemsetView($id)
    {
        if (!$id) {
            return redirect()->to(base_url('report'))
                            ->with('error', 'Data tidak ditemukan');
        }

        return view('report_detail_itemset', [
            'reportId' => (int)$id,
            'step'     => 2,
            'backUrl'  => base_url('report/main-info/'. $id),
            'nextUrl'  => base_url('report/association-rule/'. $id)
        ]);
    }


    public function associationRuleView($id)
    {
        if (!$id) {
            return redirect()->to(base_url('report'))
                            ->with('error', 'Data tidak ditemukan');
        }

        return view('report_detail_association', [
            'reportId' => (int)$id,
            'step'     => 3,
            'backUrl'  => base_url('report/itemset/'. $id),
            'nextUrl'  => base_url('report/lift-ratio/'. $id),
        ]);
    }

    public function liftRatioView($id)
    {
        if (!$id) {
            return redirect()->to(base_url('report'))
                            ->with('error', 'Data tidak ditemukan');
        }

        return view('report_detail_lift', [
            'reportId' => (int)$id,
            'step'     => 4,
            'backUrl'  => base_url('report/association-rule/'. $id),
            'nextUrl'  => base_url('report/itemset1/'. $id),
        ]);
    }

    public function kesimpulanView($id)
    {
        if (!$id) {
            return redirect()->to(base_url('report'))
                            ->with('error', 'Data tidak ditemukan');
        }

        return view('report_detail_kesimpulan', [
            'reportId' => (int)$id,
            'step'     => 5,
            'backUrl'  => base_url('report/lift-ratio/'. $id),
            'nextUrl'  => base_url('report'. $id),
        ]);
    }

    /**
     * GET /api/report?page=1&limit=10
     */
    public function list()
    {
        $page  = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = max(1, (int) ($this->request->getGet('limit') ?? 10)); // default 10

        $rows = $this->analisisModel
            ->select('id, title, start_date, end_date, min_support, min_confidence, description, created_at')
            ->orderBy('created_at', 'DESC')
            ->paginate($limit, 'default', $page);

        $total = $this->analisisModel->pager->getTotal();
        $totalPages = (int) ceil(($total ?: 0) / $limit);

        return $this->respond([
            'status' => 'success',
            'data'   => $rows,
            'meta'   => [
                'page'       => $page,
                'limit'      => $limit,
                'total'      => (int) $total,
                'totalPages' => $totalPages,
            ],
        ]);
    }

    // main info detail
    public function detail($id = null)
    {
        if (!$id) return $this->failValidationError('ID wajib diisi');

        $row = $this->analisisModel
            ->select('id, title, start_date, end_date, min_support, min_confidence, description')
            ->find($id);

        if (!$row) return $this->failNotFound('Data tidak ditemukan');

        return $this->respond(['status' => 'success', 'data' => $row]);
    }

    // GET /api/report/association-itemset2/{analisisId}
    public function associationItemset2($analisisId)
    {
        $analisisId = (int)$analisisId;
        if ($analisisId <= 0) {
            return $this->failValidationErrors('analisis_id tidak valid');
        }

        // --- Ambil periode & min_confidence dari analisis_data
        $analisis = (new AnalisisDataModel())->find($analisisId);
        if (!$analisis) return $this->failNotFound('analisis_data tidak ditemukan');

        $start      = $analisis['start_date'] ?? null;
        $end        = $analisis['end_date'] ?? null;
        $minConfidence = isset($analisis['min_confidence']) ? (float)$analisis['min_confidence'] : null;

        // --- Hitung total transaksi pada periode tsb dari tabel transactions
        $txModel = new TransactionModel();
        if ($start && $end) {
            $transactionTotal = $txModel->where('sale_date >=', $start)
                                        ->where('sale_date <=', $end)
                                        ->countAllResults();
        } else {
            $transactionTotal = $txModel->countAllResults();
        }

        // --- Parameter opsional
        $limit = (int)($this->request->getGet('limit') ?? 1000);
        if ($limit <= 0 || $limit > 10000) $limit = 1000;

        $order = strtolower($this->request->getGet('order') ?? 'confidence'); 
        $dir   = strtoupper($this->request->getGet('dir') ?? 'DESC');        
        if (!in_array($order, ['confidence','support','lift'])) $order = 'confidence';
        if (!in_array($dir, ['ASC','DESC'])) $dir = 'DESC';

        // --- Ambil rules total size = 2 (1→1)
        $model = new AprioriRuleModel(); // pastikan returnType='array'
        $rows  = $model->select('id, analisis_id, antecedents, consequents, itemset_number, support, confidence, support_antecedents, support_consequents, created_at')
                       ->where('analisis_id', $analisisId)
                       ->where('itemset_number', 2)
                       ->orderBy($order, $dir)
                       ->findAll($limit);

        $fmt = function(array $xs): string {
            // gabung jadi "{a, b, c}"
            $xs = array_map('strval', $xs);
            return '{' . implode(', ', $xs) . '}';
        };

        $data = array_map(function ($r) use ($fmt) {
            $aArr = json_decode($r['antecedents'], true) ?: [];
            $cArr = json_decode($r['consequents'], true) ?: [];

            $supp = (float)$r['support'];
            $conf = (float)$r['confidence'];

            return [
                'id'                          => (int)$r['id'],
                'antecedents'                 => $aArr,
                'consequents'                 => $cArr,
                'itemset_number'              => (int)$r['itemset_number'], // 2
                'items'                       => $fmt($aArr) . ' -> ' . $fmt($cArr),   // <— TAMBAHAN
                'support'                     => $supp,
                'support_percent'             => round($supp * 100, 2),
                'confidence'                  => $conf,
                'confidence_percent'          => round($conf * 100, 2),
                'support_antecedents'         => isset($r['support_antecedents']) ? (float)$r['support_antecedents'] : null,
                'support_antecedents_percent' => isset($r['support_antecedents']) ? round((float)$r['support_antecedents'] * 100, 2) : null,
                'support_consequents'         => isset($r['support_consequents']) ? (float)$r['support_consequents'] : null,
                'support_consequents_percent' => isset($r['support_consequents']) ? round((float)$r['support_consequents'] * 100, 2) : null,
                'created_at'                  => $r['created_at'],
            ];
        }, $rows);

        return $this->respond([
            'analisis_id'       => $analisisId,
            'min_confidence'       => $minConfidence,   // tetap desimal 0..1
            'itemset_number'    => 2,
            'order_by'          => $order,
            'order_dir'         => $dir,
            'count'             => count($data),
            'data'              => $data,
        ]);
    }

    // GET /api/report/association-itemset3/{analisisId}
    public function associationItemset3($analisisId)
    {
        $analisisId = (int)$analisisId;
        if ($analisisId <= 0) {
            return $this->failValidationErrors('analisis_id tidak valid');
        }

        // --- Ambil periode & min_confidence dari analisis_data
        $analisis = (new AnalisisDataModel())->find($analisisId);
        if (!$analisis) return $this->failNotFound('analisis_data tidak ditemukan');

        $start      = $analisis['start_date'] ?? null;
        $end        = $analisis['end_date'] ?? null;
        $minConfidence = isset($analisis['min_confidence']) ? (float)$analisis['min_confidence'] : null;

        // --- Hitung total transaksi pada periode tsb dari tabel transactions
        $txModel = new TransactionModel();
        if ($start && $end) {
            $transactionTotal = $txModel->where('sale_date >=', $start)
                                        ->where('sale_date <=', $end)
                                        ->countAllResults();
        } else {
            $transactionTotal = $txModel->countAllResults();
        }

        // --- Parameter opsional
        $limit = (int)($this->request->getGet('limit') ?? 1000);
        if ($limit <= 0 || $limit > 10000) $limit = 1000;

        $order = strtolower($this->request->getGet('order') ?? 'confidence'); 
        $dir   = strtoupper($this->request->getGet('dir') ?? 'DESC'); 
        if (!in_array($order, ['confidence','support','lift'])) $order = 'confidence';
        if (!in_array($dir, ['ASC','DESC'])) $dir = 'DESC';

        // --- Ambil rules total size = 3 (1→1)
        $model = new AprioriRuleModel(); // pastikan returnType='array'
        $rows  = $model->select('id, analisis_id, antecedents, consequents, itemset_number, support, confidence, support_antecedents, support_consequents, created_at')
                       ->where('analisis_id', $analisisId)
                       ->where('itemset_number', 3)
                       ->orderBy($order, $dir)
                       ->findAll($limit);

        $fmt = function(array $xs): string {
            // gabung jadi "{a, b, c}"
            $xs = array_map('strval', $xs);
            return '{' . implode(', ', $xs) . '}';
        };

        $data = array_map(function ($r) use ($fmt) {
            $aArr = json_decode($r['antecedents'], true) ?: [];
            $cArr = json_decode($r['consequents'], true) ?: [];

            $supp = (float)$r['support'];
            $conf = (float)$r['confidence'];

            return [
                'id'                          => (int)$r['id'],
                'antecedents'                 => $aArr,
                'consequents'                 => $cArr,
                'itemset_number'              => (int)$r['itemset_number'], // 3
                'items'                       => $fmt($aArr) . ' -> ' . $fmt($cArr),   // <— TAMBAHAN
                'support'                     => $supp,
                'support_percent'             => round($supp * 100, 2),
                'confidence'                  => $conf,
                'confidence_percent'          => round($conf * 100, 2),
                'support_antecedents'         => isset($r['support_antecedents']) ? (float)$r['support_antecedents'] : null,
                'support_antecedents_percent' => isset($r['support_antecedents']) ? round((float)$r['support_antecedents'] * 100, 2) : null,
                'support_consequents'         => isset($r['support_consequents']) ? (float)$r['support_consequents'] : null,
                'support_consequents_percent' => isset($r['support_consequents']) ? round((float)$r['support_consequents'] * 100, 2) : null,
                'created_at'                  => $r['created_at'],
            ];
        }, $rows);

        return $this->respond([
            'analisis_id'       => $analisisId,
            'min_confidence'       => $minConfidence,   // tetap desimal 0..1
            'itemset_number'    => 3,
            'order_by'          => $order,
            'order_dir'         => $dir,
            'count'             => count($data),
            'data'              => $data,
        ]);
    }

    // GET /api/report/lift/{analisisId}
    public function lift($analisisId)
    {
        $analisisId = (int) $analisisId;
        if ($analisisId <= 0) {
            return $this->failValidationErrors('analisis_id tidak valid');
        }

        // --- meta analisis: periode & min_support
        $analisis = (new AnalisisDataModel())->find($analisisId);
        if (!$analisis) {
            return $this->failNotFound('analisis_data tidak ditemukan');
        }
        $start      = $analisis['start_date'] ?? null;
        $end        = $analisis['end_date'] ?? null;
        $minSupport = isset($analisis['min_support']) ? (float)$analisis['min_support'] : null;

        // --- hitung total transaksi pada periode
        $txModel = new TransactionModel();
        if ($start && $end) {
            $transactionTotal = $txModel->where('sale_date >=', $start)
                                        ->where('sale_date <=', $end)
                                        ->countAllResults();
        } else {
            $transactionTotal = $txModel->countAllResults();
        }

        // --- query params opsional
        $k     = $this->request->getGet('k');                 // itemset_number (2/3/...)
        $k     = is_null($k) ? null : (int)$k;
        $limit = (int) ($this->request->getGet('limit') ?? 1000);
        if ($limit <= 0 || $limit > 10000) $limit = 1000;

        $order = strtolower($this->request->getGet('order') ?? 'lift'); // lift|confidence|support
        $dir   = strtoupper($this->request->getGet('dir') ?? 'DESC');   // ASC|DESC
        if (!in_array($order, ['lift','confidence','support'], true)) $order = 'lift';
        if (!in_array($dir, ['ASC','DESC'], true)) $dir = 'DESC';

        // --- ambil rules
        $model = new AprioriRuleModel(); // returnType='array'
        $builder = $model->select('id, analisis_id, antecedents, consequents, itemset_number, support, confidence, lift, support_antecedents, support_consequents, created_at')
                         ->where('analisis_id', $analisisId);
        if (!is_null($k)) {
            $builder->where('itemset_number', $k);
        }
        $rows = $builder->orderBy($order, $dir)->findAll($limit);

        // helper format "{a, b} -> {c}"
        $fmt = function(array $xs): string {
            $xs = array_map('strval', $xs);
            return '{' . implode(', ', $xs) . '}';
        };

        $data = array_map(function($r) use ($fmt) {
            $aArr = json_decode($r['antecedents'], true) ?: [];
            $cArr = json_decode($r['consequents'], true) ?: [];
            $supp = (float)$r['support'];
            $conf = (float)$r['confidence'];

            return [
                'id'                          => (int)$r['id'],
                'items'                       => $fmt($aArr) . ' -> ' . $fmt($cArr),
                'lift'                        => (float)$r['lift'],
                'created_at'                  => $r['created_at'],
            ];
        }, $rows);

        return $this->respond([
            'analisis_id'       => $analisisId,
            'filter_itemset_k'  => $k,               // null jika tidak difilter
            'order_by'          => $order,
            'order_dir'         => $dir,
            'count'             => count($data),
            'data'              => $data,
        ]);
    }


    public function delete($analisisId)
    {
        $id = (int)$analisisId;
        $mAnalisis = new AnalisisDataModel();
        $row = $mAnalisis->find($id);
        if (!$row) {
            return $this->failNotFound('Analisis tidak ditemukan');
        }

        if (! $mAnalisis->delete($id)) {
            return $this->failServerError('Gagal menghapus data');
        }

        return $this->respondDeleted([
            'message'     => 'Data berhasil dihapus',
            'analisis_id' => $id,
            'redirect_to' => site_url('report'),
        ]);
    }

    public function itemset($analisisId)
    {
        $id = (int)$analisisId;
        if ($id <= 0) {
            return $this->failValidationErrors('analisis_id tidak valid');
        }

        $mAnalisis = new AnalisisDataModel();
        $mItemset  = new AprioriItemsetModel();
        $mTx       = new TransactionModel();

        // meta analisis
        $a = $mAnalisis->find($id);
        if (!$a) return $this->failNotFound('Analisis tidak ditemukan');

        $start = $a['start_date'] ?? null;
        $end   = $a['end_date']   ?? null;

        // total transaksi dalam rentang analisis (kalau ada tanggal)
        $transactionTotal = ($start && $end)
            ? $mTx->where('sale_date >=', $start)->where('sale_date <=', $end)->countAllResults()
            : $mTx->countAllResults();

        // ambil semua itemset untuk analisis ini
        $rows = $mItemset->select('itemsets, itemset_number, support, frequency')
                         ->where('analisis_id', $id)
                         ->orderBy('itemset_number', 'ASC')
                         ->orderBy('frequency', 'DESC')
                         ->orderBy('support', 'DESC')
                         ->findAll();

        // kelompokkan per ukuran itemset: 1,2,3, ...
        $grouped = [];
        foreach ($rows as $r) {
            $k = (int)$r['itemset_number'];
            $grouped[$k] = $grouped[$k] ?? [];
            $grouped[$k][] = [
                'itemset_number' => $k,
                'itemsets'       => json_decode($r['itemsets'], true) ?: [],
                'support'        => isset($r['support']) ? (float)$r['support'] : null,
                'frequency'      => isset($r['frequency']) ? (int)$r['frequency'] : null,
            ];
        }

        // bentuk payload
        return $this->respond([
            'analisis_id'       => $id,
            'title'             => $a['title'] ?? null,
            'start_date'        => $start,
            'end_date'          => $end,
            'min_support'       => isset($a['min_support']) ? (float)$a['min_support'] : null,
            'transaction_total' => (int)$transactionTotal,
            // data dikelompokkan: { "1": [...], "2": [...], "3": [...], "4": [...], ... }
            'data'              => $grouped,
        ]);
    }

    public function association($analisisId)
    {
        $id = (int)$analisisId;
        if ($id <= 0) {
            return $this->failValidationErrors('analisis_id tidak valid');
        }

        $mAnalisis = new AnalisisDataModel();
        $mRule     = new AprioriRuleModel();

        // meta analisis
        $a = $mAnalisis->find($id);
        if (!$a) return $this->failNotFound('Analisis tidak ditemukan');

        // ambil semua rules utk analisis ini
        $rows = $mRule->select('antecedents, consequents, itemset_number, support, confidence, lift, support_antecedents, support_consequents')
                      ->where('analisis_id', $id)
                      ->orderBy('itemset_number', 'ASC')
                      ->orderBy('confidence', 'DESC')
                      ->orderBy('support', 'DESC')
                      ->findAll();

        // kelompokkan per ukuran itemset
        $grouped = [];
        foreach ($rows as $r) {
            $k   = (int)($r['itemset_number'] ?? 0);
            $ant = json_decode($r['antecedents'], true) ?: [];
            $con = json_decode($r['consequents'], true) ?: [];

            $item = '{'.implode(', ', $ant).'} -> {'.implode(', ', $con).'}';

            $grouped[$k] = $grouped[$k] ?? [];
            $grouped[$k][] = [
                'itemset_number'       => $k,
                'antecedents'          => $ant,
                'consequents'          => $con,
                'items'                => $item, // "{A, B} -> {C}"
                'support'              => isset($r['support']) ? (float)$r['support'] : null,                 // support X ∪ Y
                'support_antecedents'  => isset($r['support_antecedents']) ? (float)$r['support_antecedents'] : null, // support X
                'support_consequents'  => isset($r['support_consequents']) ? (float)$r['support_consequents'] : null, // support Y
                'confidence'           => isset($r['confidence']) ? (float)$r['confidence'] : null,
                'lift'                 => isset($r['lift']) ? (float)$r['lift'] : null,
            ];
        }

        // payload
        return $this->respond([
            'analisis_id'     => $id,
            'title'           => $a['title'] ?? null,
            'start_date'      => $a['start_date'] ?? null,
            'end_date'        => $a['end_date'] ?? null,
            'min_confidence'  => isset($a['min_confidence']) ? (float)$a['min_confidence'] : null,
            // data dikelompokkan: { "2": [...], "3": [...], ... }
            'data'            => $grouped,
        ]);
    }

    // === REPLACE: kesimpulan() ===
    public function kesimpulan(int $analisisId)
    {
        if ($analisisId <= 0) {
            return $this->failValidationErrors('analisis_id tidak valid');
        }

        $data = $this->makeKesimpulanData($analisisId);
        if (isset($data['error'])) {
            return $this->failNotFound($data['error']);
        }

        return $this->respond($data);
    }

    // === REPLACE: downloadReport() ===
    public function downloadReport(int $analisisId)
    {
        if ($analisisId <= 0) {
            return $this->failValidationErrors('analisis_id tidak valid');
        }

        $data = $this->makeKesimpulanData($analisisId);
        if (isset($data['error'])) {
            return $this->failNotFound($data['error']);
        }

        // view PDF yang sudah kamu buat sebelumnya
        $html = view('download_report', $data);

        $opt = new \Dompdf\Options();
        $opt->set('isRemoteEnabled', false);          
        $opt->set('isHtml5ParserEnabled', true);
        $opt->set('isFontSubsettingEnabled', true);

        $pdf = new \Dompdf\Dompdf($opt);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        $filename = 'Laporan_Apriori_'.preg_replace('/[^\w\-]+/u','_',$data['title'] ?? 'report').'.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="'.$filename.'"')
            ->setBody($pdf->output());
    }

    /**
     * === NEW: Satu-satunya sumber logic kesimpulan (pengganti buildKesimpulanData yg DIHAPUS) ===
     * Mengembalikan array payload yang sama dengan API /kesimpulan.
     */
    private function makeKesimpulanData(int $analisisId): array
    {
        $mAnalisis = new AnalisisDataModel();
        $mItemset  = new AprioriItemsetModel();
        $mRule     = new AprioriRuleModel();
        $mTx       = new TransactionModel();

        $a = $mAnalisis->find($analisisId);
        if (!$a) {
            return ['error' => 'analisis_data tidak ditemukan'];
        }

        $start = $a['start_date'] ?? null;
        $end   = $a['end_date']   ?? null;

        $transactionTotal = ($start && $end)
            ? $mTx->where('sale_date >=', $start)->where('sale_date <=', $end)->countAllResults()
            : $mTx->countAllResults();

        // helpers
        $decode = static fn($json) => json_decode($json, true) ?: [];
        $fmtSet = static fn(array $xs) => '{'.implode(', ', $xs).'}';

        // Frequent (top support per k)
        $freqRows = $mItemset->where('analisis_id', $analisisId)
                            ->orderBy('itemset_number','ASC')
                            ->orderBy('support','DESC')
                            ->orderBy('frequency','DESC')
                            ->findAll();
        $bestFreqByK = [];
        foreach ($freqRows as $r) {
            $k=(int)($r['itemset_number'] ?? 0);
            if (!isset($bestFreqByK[$k])) $bestFreqByK[$k] = $r;
        }
        $insightsFreq = [];
        foreach ($bestFreqByK as $k=>$r) {
            $items = $decode($r['itemsets'] ?? '[]');
            $frasa = ($k===1)?'produk individual':(($k===2)?'pasangan produk':"kombinasi {$k} produk");
            $insightsFreq[(string)$k] = [[
                'itemsets'       => $items,
                'itemset_number' => $k,
                'frequency'      => isset($r['frequency']) ? (int)$r['frequency'] : null,
                'support'        => isset($r['support']) ? (float)$r['support'] : null,
                'text'           => sprintf('%s %s yang sering muncul dalam transaksi.', $fmtSet($items), $frasa),
            ]];
        }

        // Association (top support per k; konsisten pakai angka untuk frasa)
        $rulesAll = $mRule->where('analisis_id', $analisisId)
                        ->orderBy('itemset_number','ASC')
                        ->orderBy('support','DESC')
                        ->orderBy('confidence','DESC')
                        ->findAll();
        $bestRuleByK = [];
        foreach ($rulesAll as $rr) {
            $k = (int)($rr['itemset_number'] ?? 0);
            if ($k < 2) continue;
            if (!isset($bestRuleByK[$k])) $bestRuleByK[$k] = $rr;
        }
        $insightsAssoc = [];
        foreach ($bestRuleByK as $k=>$rr){
            $aItems = $decode($rr['antecedents'] ?? '[]');
            $cItems = $decode($rr['consequents'] ?? '[]');
            $insightsAssoc[(string)$k] = [[
                'itemset_number' => $k,
                'antecedents'    => $aItems,
                'consequents'    => $cItems,
                'support'        => isset($rr['support']) ? (float)$rr['support'] : null,
                'confidence'     => isset($rr['confidence']) ? (float)$rr['confidence'] : null,
                'lift'           => isset($rr['lift']) ? (float)$rr['lift'] : null,
                'text'           => sprintf(
                    'Rule %s -> %s menunjukkan hubungan %d produk yang sering dibeli bersama.',
                    $fmtSet($aItems), $fmtSet($cItems), $k
                ),
            ]];
        }

        // Lift tertinggi
        $topLift = $mRule->where('analisis_id',$analisisId)->orderBy('lift','DESC')->first();
        $insLift = $topLift
            ? ('Rule '.$fmtSet($decode($topLift['antecedents'])).' -> '.$fmtSet($decode($topLift['consequents'])).' menunjukkan nilai kekuatan antar produk paling tinggi')
            : 'Tidak ada rule dengan nilai lift pada periode ini';

        $insStrategis = 'Hasil analisis menunjukkan bahwa produk dengan frekuensi tinggi dan asosiasi kuat layak dijadikan target promosi atau penempatan bersama untuk meningkatkan penjualan';

        return [
            'id'                     => (string)$a['id'],
            'title'                  => $a['title'],
            'description'            => $a['description'],
            'start_date'             => $start,
            'end_date'               => $end,
            'min_support'            => isset($a['min_support']) ? (string)$a['min_support'] : null,
            'min_confidence'         => isset($a['min_confidence']) ? (string)$a['min_confidence'] : null,
            'transaction_total'      => (int)$transactionTotal,
            'insights_frequent_all'  => $insightsFreq,
            'insights_association_all'=> $insightsAssoc,
            'insight_lift_ratio'     => $insLift,
            'insight_strategis'      => $insStrategis,
        ];
    }

    
}
