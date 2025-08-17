<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\AnalisisDataModel;

class ReportController extends BaseController
{
    use ResponseTrait;

    protected $analisisModel;

    public function __construct()
    {
        $this->analisisModel = new AnalisisDataModel();
    }

    // View non-API
    public function reportView()
    {
        return view('report_list');
    }

    /**
     * GET /api/report?page=1&limit=10
     * Pagination only (no filters).
     */
    public function getList()
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

    // (opsional) detail satu report
    public function show($id = null)
    {
        if (!$id) return $this->failValidationError('ID wajib diisi');

        $row = $this->analisisModel
            ->select('id, title, start_date, end_date, min_support, min_confidence, description, created_at, updated_at')
            ->find($id);

        if (!$row) return $this->failNotFound('Data tidak ditemukan');

        return $this->respond(['status' => 'success', 'data' => $row]);
    }
}
