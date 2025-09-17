<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\AnalisisDataModel;
use App\Models\AprioriItemsetModel;
use App\Models\TransactionModel;
use App\Models\AprioriRuleModel;

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

    public function itemset1View($id)
    {
        if (!$id) {
            return redirect()->to(base_url('report'))
                            ->with('error', 'Data tidak ditemukan');
        }

        return view('report_detail_itemset1', [
            'reportId' => (int)$id,
            'step'     => 2,
            'backUrl'  => base_url('report/main-info/'. $id),
            'nextUrl'  => base_url('report/itemset2/'. $id),
        ]);
    }

    public function itemset2View($id)
    {
        if (!$id) {
            return redirect()->to(base_url('report'))
                            ->with('error', 'Data tidak ditemukan');
        }

        return view('report_detail_itemset2', [
            'reportId' => (int)$id,
            'step'     => 3,
            'backUrl'  => base_url('report/itemset1/'. $id),
            'nextUrl'  => base_url('report/itemset3/'. $id),
        ]);
    }

    public function itemset3View($id)
    {
        if (!$id) {
            return redirect()->to(base_url('report'))
                            ->with('error', 'Data tidak ditemukan');
        }

        return view('report_detail_itemset3', [
            'reportId' => (int)$id,
            'step'     => 4,
            'backUrl'  => base_url('report/itemset2/'. $id),
            'nextUrl'  => base_url('report/association-rule/'. $id),
        ]);
    }

    public function associationRule($id)
    {
        if (!$id) {
            return redirect()->to(base_url('report'))
                            ->with('error', 'Data tidak ditemukan');
        }

        return view('report_detail_association', [
            'reportId' => (int)$id,
            'step'     => 5,
            'backUrl'  => base_url('report/itemset3/'. $id),
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
            'step'     => 6,
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
            'step'     => 7,
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

    public function itemset1($analisisId)
    {
        $analisisId = (int)$analisisId;
        if ($analisisId <= 0) {
            return $this->failValidationErrors('Data tidak ditemukan');
        }

        // --- Ambil periode dari analisis_data
        $analisis = (new AnalisisDataModel())->find($analisisId);
        if (!$analisis) {
            return $this->failNotFound('analisis data tidak ditemukan');
        }
        $start = $analisis['start_date'] ?? null;
        $end   = $analisis['end_date'] ?? null;
        $minSupport  = isset($analisis['min_support']) ? (float)$analisis['min_support'] : null;

        // --- Hitung total transaksi pada periode tsb dari tabel transactions
        $txModel = new TransactionModel();
        if ($start && $end) {
            $transactionTotal = $txModel
                ->where('sale_date >=', $start)
                ->where('sale_date <=', $end)
                ->countAllResults();
        } else {
            // fallback kalau periodenya kosong: hitung semua
            $transactionTotal = $txModel->countAllResults();
        }

        // --- Ambil itemset 1
        $limit = (int)($this->request->getGet('limit') ?? 1000);
        if ($limit <= 0 || $limit > 10000) $limit = 1000;

        $model = new AprioriItemsetModel(); // returnType 'array'
        $rows  = $model->select('id, analisis_id, itemsets, itemset_number, support, frequency, created_at')
                       ->where('analisis_id', $analisisId)
                       ->where('itemset_number', 1)
                       ->orderBy('frequency', 'DESC')
                       ->orderBy('support', 'DESC')
                       ->findAll($limit);

        $data = [];
        foreach ($rows as $r) {
            $support = (float)$r['support'];
            $data[] = [
                'id'              => (int)$r['id'],
                'analisis_id'     => (int)$r['analisis_id'],
                'itemsets'        => json_decode($r['itemsets'], true),
                'itemset_number'  => (int)$r['itemset_number'], // 1
                'support'         => $support,                  // 0..1
                'support_percent' => round($support * 100, 2),  // untuk tampilan
                'frequency'       => (int)$r['frequency'],
                'created_at'      => $r['created_at'],
            ];
        }

        return $this->respond([
            'analisis_id'       => $analisisId,
            'transaction_total'  => (int)$transactionTotal,
            'min_support'       => $minSupport,
            'itemset_number'    => 1,
            'count'             => count($data),
            'data'              => $data,
        ]);
    }

    public function itemset2($analisisId)
    {
        $analisisId = (int) $analisisId;
        if ($analisisId <= 0) {
            return $this->failValidationErrors('Data tidak ditemukan');
        }

        // --- Ambil periode dari analisis_data
        $analisis = (new AnalisisDataModel())->find($analisisId);
        if (!$analisis) {
            return $this->failNotFound('analisis_data tidak ditemukan');
        }
        $start = $analisis['start_date'] ?? null;
        $end   = $analisis['end_date'] ?? null;
        $minSupport  = isset($analisis['min_support']) ? (float)$analisis['min_support'] : null;

        // --- Hitung total transaksi pada periode tsb dari tabel transactions
        $txModel = new TransactionModel();
        if ($start && $end) {
            $transactionTotal = $txModel
                ->where('sale_date >=', $start)
                ->where('sale_date <=', $end)
                ->countAllResults();
        } else {
            // fallback kalau periodenya kosong: hitung semua
            $transactionTotal = $txModel->countAllResults();
        }

        // --- Ambil itemset 2
        $limit = (int) ($this->request->getGet('limit') ?? 1000);
        if ($limit <= 0 || $limit > 10000) $limit = 1000;

        $model = new AprioriItemsetModel(); // pastikan returnType='array' di model
        $rows  = $model->select('id, analisis_id, itemsets, itemset_number, support, frequency, created_at')
                       ->where('analisis_id', $analisisId)
                       ->where('itemset_number', 2)
                       ->orderBy('frequency', 'DESC')
                       ->orderBy('support', 'DESC')
                       ->findAll($limit);

        $data = [];
        foreach ($rows as $r) {
            $support = (float)$r['support'];
            $data[] = [
                'id'              => (int)$r['id'],
                'analisis_id'     => (int)$r['analisis_id'],
                'itemsets'        => json_decode($r['itemsets'], true),
                'itemset_number'  => (int)$r['itemset_number'], // 2
                'support'         => $support,                  // 0..1
                'support_percent' => round($support * 100, 2),  // untuk UI
                'frequency'       => (int)$r['frequency'],
                'created_at'      => $r['created_at'],
            ];
        }

        return $this->respond([
            'analisis_id'    => $analisisId,
            'transaction_total'  => (int)$transactionTotal,
            'min_support'       => $minSupport,
            'itemset_number' => 2,
            'count'          => count($data),
            'data'           => $data,
        ]);
    }

    public function itemset3($analisisId)
    {
        $analisisId = (int) $analisisId;
        if ($analisisId <= 0) {
            return $this->failValidationErrors('Data tidak ditemukan');
        }

        // --- Ambil periode dari analisis_data
        $analisis = (new AnalisisDataModel())->find($analisisId);
        if (!$analisis) {
            return $this->failNotFound('analisis_data tidak ditemukan');
        }
        $start = $analisis['start_date'] ?? null;
        $end   = $analisis['end_date'] ?? null;
        $minSupport  = isset($analisis['min_support']) ? (float)$analisis['min_support'] : null;

        // --- Hitung total transaksi pada periode tsb dari tabel transactions
        $txModel = new TransactionModel();
        if ($start && $end) {
            $transactionTotal = $txModel
                ->where('sale_date >=', $start)
                ->where('sale_date <=', $end)
                ->countAllResults();
        } else {
            // fallback kalau periodenya kosong: hitung semua
            $transactionTotal = $txModel->countAllResults();
        }

        // --- Ambil itemset 3
        $limit = (int) ($this->request->getGet('limit') ?? 1000);
        if ($limit <= 0 || $limit > 10000) $limit = 1000;

        $model = new AprioriItemsetModel(); // returnType='array'
        $rows  = $model->select('id, analisis_id, itemsets, itemset_number, support, frequency, created_at')
                       ->where('analisis_id', $analisisId)
                       ->where('itemset_number', 3)
                       ->orderBy('frequency', 'DESC')
                       ->orderBy('support', 'DESC')
                       ->findAll($limit);

        $data = [];
        foreach ($rows as $r) {
            $support = (float)$r['support'];
            $data[] = [
                'id'              => (int)$r['id'],
                'analisis_id'     => (int)$r['analisis_id'],
                'itemsets'        => json_decode($r['itemsets'], true),
                'itemset_number'  => (int)$r['itemset_number'], // 3
                'support'         => $support,
                'support_percent' => round($support * 100, 2),
                'frequency'       => (int)$r['frequency'],
                'created_at'      => $r['created_at'],
            ];
        }

        return $this->respond([
            'analisis_id'    => $analisisId,
            'transaction_total'  => (int)$transactionTotal,
            'min_support'       => $minSupport,
            'itemset_number' => 3,
            'count'          => count($data),
            'data'           => $data,
        ]);
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

    public function kesimpulan($analisisId)
    {
        $analisisId = (int)$analisisId;
        if ($analisisId <= 0) {
            return $this->failValidationErrors('analisis_id tidak valid');
        }

        // --- models
        $mAnalisis = new AnalisisDataModel();
        $mItemset  = new AprioriItemsetModel();
        $mRule     = new AprioriRuleModel();
        $mTx       = new TransactionModel();

        // --- meta analisis
        $a = $mAnalisis->find($analisisId);
        if (!$a) return $this->failNotFound('analisis_data tidak ditemukan');

        $start = $a['start_date'] ?? null;
        $end   = $a['end_date']   ?? null;

        // total transaksi pada periode analisis
        if ($start && $end) {
            $transactionTotal = $mTx->where('sale_date >=', $start)->where('sale_date <=', $end)->countAllResults();
        } else {
            $transactionTotal = $mTx->countAllResults();
        }

        // helper
        $fmtSet = function(array $xs): string {
            return '{'.implode(', ', $xs).'}';
        };
        $andJoin = function(array $xs): string {
            $n = count($xs);
            if ($n === 0) return '';
            if ($n === 1) return $xs[0];
            if ($n === 2) return $xs[0].' dan '.$xs[1];
            return implode(', ', array_slice($xs, 0, $n-1)).', dan '.$xs[$n-1];
        };
        $decode = function($json){ return json_decode($json, true) ?: []; };

        // --- top frequent itemset k=1,2,3 (urut freq desc, lalu support desc)
        $top1 = $mItemset->where('analisis_id',$analisisId)->where('itemset_number',1)
                        ->orderBy('frequency','DESC')->orderBy('support','DESC')->first();
        $top2 = $mItemset->where('analisis_id',$analisisId)->where('itemset_number',2)
                        ->orderBy('frequency','DESC')->orderBy('support','DESC')->first();
        $top3 = $mItemset->where('analisis_id',$analisisId)->where('itemset_number',3)
                        ->orderBy('frequency','DESC')->orderBy('support','DESC')->first();

        // --- top association rules (confidence tertinggi) utk 2-itemset & 3-itemset
        $rule2 = $mRule->where('analisis_id',$analisisId)->where('itemset_number',2)
                    ->orderBy('confidence','DESC')->orderBy('support','DESC')->first();
        $rule3 = $mRule->where('analisis_id',$analisisId)->where('itemset_number',3)
                    ->orderBy('confidence','DESC')->orderBy('support','DESC')->first();

        // --- rule lift tertinggi (semua k)
        $topLift = $mRule->where('analisis_id',$analisisId)
                        ->orderBy('lift','DESC')->first();

        // kalimat insight
        $insF1 = $top1 ? ($andJoin($decode($top1['itemsets'])) . ' merupakan produk individual yang paling sering muncul dalam transaksi')
                    : 'Tidak ada produk individual yang dominan pada periode ini';
        $insF2 = $top2 ? ($andJoin($decode($top2['itemsets'])) . ' merupakan pasangan produk yang sering dibeli bersama')
                    : 'Tidak ada pasangan produk yang dominan pada periode ini';
        $insF3 = $top3 ? ($andJoin($decode($top3['itemsets'])) . ' merupakan kombinasi tiga produk yang sering dibeli bersama')
                    : 'Tidak ada kombinasi tiga produk yang dominan pada periode ini';

        $insA2 = $rule2 ? ('Rule '.$fmtSet($decode($rule2['antecedents'])).' -> '.$fmtSet($decode($rule2['consequents']))
                        .' menunjukkan hubungan dua produk yang sering dibeli bersama')
                        : 'Tidak ada rule 2-itemset yang memenuhi kriteria';
        $insA3 = $rule3 ? ('Rule '.$fmtSet($decode($rule3['antecedents'])).' -> '.$fmtSet($decode($rule3['consequents']))
                        .' menunjukkan hubungan tiga produk yang sering dibeli bersama')
                        : 'Tidak ada rule 3-itemset yang memenuhi kriteria';

        $insLift = $topLift ? ('Rule '.$fmtSet($decode($topLift['antecedents'])).' -> '.$fmtSet($decode($topLift['consequents']))
                            .' menunjukkan nilai kekuatan antar produk paling tinggi')
                            : 'Tidak ada rule dengan nilai lift pada periode ini';

        $insStrategis = 'Hasil analisis menunjukkan bahwa produk dengan frekuensi tinggi dan asosiasi kuat layak dijadikan target promosi atau penempatan bersama untuk meningkatkan penjualan';

        // payload
        $out = [
            'id'               => (string)$a['id'],
            'title'            => $a['title'],
            'start_date'       => $start,
            'end_date'         => $end,
            // simpan sebagai string agar match contoh; ganti ke (float) bila mau numeric
            'min_support'      => isset($a['min_support']) ? (string)$a['min_support'] : null,
            'min_confidence'   => isset($a['min_confidence']) ? (string)$a['min_confidence'] : null,
            'description'      => $a['description'],
            'transaction_total'=> (int)$transactionTotal,

            'insight_frequent1itemset'   => $insF1,
            'insight_frequent2itemset'   => $insF2,
            'insight_frequent3itemset'   => $insF3,
            'insight_association2itemset'=> $insA2,
            'insight_association3itemset'=> $insA3,
            'insight_lift_ratio'         => $insLift,
            'insight_strategis'          => $insStrategis,
        ];

        return $this->respond($out);
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


}
