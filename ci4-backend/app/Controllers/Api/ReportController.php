<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\AnalisisDataModel;
use App\Models\AprioriItemsetModel;

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
    
    public function detailView()
    {
        $id = $this->request->getGet('id');
        if (!$id) {
            return redirect()->to(base_url('report'))
                            ->with('error', 'Data tidak ditemukan');
        }

        return view('report_detail', [
            'reportId' => $id,
            'step'     => 1,
            'backUrl'  => base_url('report'),
        ]);
    }

    public function itemset1View()
    {
        $id = $this->request->getGet('id');
        if (!$id) {
            return redirect()->to(base_url('report/main-info'))
                            ->with('error', 'Data tidak ditemukan');
        }

        return view('report_detail_itemset1', [
            'reportId' => $id,
            'step'     => 2,
            'backUrl'  => base_url('report/main-info'),
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
        $analisisId = (int) $analisisId;
        if ($analisisId <= 0) {
            return $this->failValidationErrors('Data tidak ditemukan');
        }

        $limit = (int) ($this->request->getGet('limit') ?? 1000);
        if ($limit <= 0 || $limit > 100000) $limit = 1000;

        $model = new AprioriItemsetModel();   // pastikan returnType = 'array' di model
        $rows  = $model->select('id, analisis_id, itemsets, itemset_number, support, frequency, created_at')
                       ->where('analisis_id', $analisisId)
                       ->where('itemset_number', 1)
                       ->orderBy('frequency', 'DESC')
                       ->orderBy('support', 'DESC')
                       ->findAll($limit);

        // format output: decode itemsets + tambah support_percent
        $data = [];
        foreach ($rows as $r) {
            $support = (float)$r['support'];
            $data[] = [
                'id'              => (int)$r['id'],
                'analisis_id'     => (int)$r['analisis_id'],
                'itemsets'        => json_decode($r['itemsets'], true), // ["Burger"], ...
                'itemset_number'  => (int)$r['itemset_number'],         // harusnya 1 di endpoint ini
                'support'         => $support,                          // 0..1 (sesuai teori)
                'support_percent' => round($support * 100, 2),          // untuk tampilan
                'frequency'       => (int)$r['frequency'],
                'created_at'      => $r['created_at'],
            ];
        }

        return $this->respond([
            'analisis_id'    => $analisisId,
            'itemset_number' => 1,
            'count'          => count($data),
            'data'           => $data,
        ]);
    }

    public function itemset2($analisisId)
    {
        $analisisId = (int) $analisisId;
        if ($analisisId <= 0) {
            return $this->failValidationErrors('Data tidak ditemukan');
        }

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
            'itemset_number' => 2,
            'count'          => count($data),
            'data'           => $data,
        ]);
    }

    public function itemset3($analisisId)
    {
        $analisisId = (int) $analisisId;
        if ($analisisId <= 0) {
            return $this->failValidationErrors('analisis_id tidak valid');
        }

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
            'itemset_number' => 3,
            'count'          => count($data),
            'data'           => $data,
        ]);
    }
}
