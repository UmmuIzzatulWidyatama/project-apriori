<?php

namespace App\Controllers\Api;
use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

use App\Models\TransactionModel;
use App\Models\AnalisisDataModel;
use App\Models\AprioriItemsetModel;
use App\Models\AprioriRuleModel;

class AprioriController extends BaseController
{
    use ResponseTrait;

    public function aprioriView()
    {
        return view('apriori_list');
    }

    public function run()
    {
        $request = $this->request->getJSON(true);
        $minSupport = $request['min_support'] ?? 0.5;
        $minConfidence = $request['min_confidence'] ?? 0.7;
        $startDate = $request['start_date'] ?? null;
        $endDate = $request['end_date'] ?? null;
        $title = $request['title'] ?? null;
        $description = $request['description'] ?? null;

        // 1. Ambil data transaksi dari DB
        $transactionModel = new TransactionModel();
        $data = $transactionModel->getAllWithItems($startDate, $endDate);

        $transactions = [];
        foreach ($data as $row) {
            $arr = json_decode($row->items_json ?? '[]', true);
            if (!is_array($arr)) $arr = [];
            // trim, buang kosong, unik
            $arr = array_map('trim', $arr);
            $arr = array_values(array_filter($arr, fn($x) => $x !== ''));
            $arr = array_values(array_unique($arr));
            if (!empty($arr)) {
                $transactions[] = ['items' => $arr];
            }
        }

        // 2. Kirim ke Python API
        try {
            $client = \Config\Services::curlrequest();
            $response = $client->post('http://localhost:5000/apriori', [
                'json' => [
                    'transactions' => $transactions,
                    'min_support' => $minSupport,
                    'min_confidence' => $minConfidence
                ]
            ]);

            $result = json_decode($response->getBody(), true);

            // 3. Simpan metadata ke analisis_data
            $analisisModel = new AnalisisDataModel();
            $analisisModel->insert([
                'title' => $title,
                'description' => $description,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_transactions' => count($transactions),
                'min_support' => $minSupport,
                'min_confidence' => $minConfidence
            ]);
            $analisisId = $analisisModel->getInsertID();

            // 4. Simpan itemsets
            $itemsetModel = new AprioriItemsetModel();
            foreach ($result['itemsets'] as $itemset) {
                $itemsetModel->insert([
                    'analisis_id' => $analisisId,
                    'itemsets' => json_encode($itemset['itemsets']),
                    'support' => $itemset['support'],
                    'itemset_number' => $itemset['itemset_number'],
                    'frequency'   => $itemset['frequency']
                ]);
            }

            // 5. Simpan rules
            $ruleModel = new AprioriRuleModel();
            foreach ($result['rules'] as $rule) {
                $ruleModel->insert([
                    'analisis_id' => $analisisId,
                    'antecedents' => json_encode($rule['antecedents']),
                    'consequents' => json_encode($rule['consequents']),
                    'support' => $rule['support'],
                    'confidence' => $rule['confidence'],
                    'lift' => $rule['lift'],
                    'itemset_number' => $rule['itemset_number'] ?? (count($rule['antecedents']??[]) + count($rule['consequents']??[])),
                    'support_antecedents'  => isset($rule['support_antecedents'])  ? (float)$rule['support_antecedents']  : null,
                    'support_consequents'  => isset($rule['support_consequents'])  ? (float)$rule['support_consequents']  : null
                ]);
            }

            // 6. Return response ke client 
            return $this->respond([
                'message'      => 'Analisis berhasil dibuat',
                'analisis_id'  => (int)$analisisId,
                'redirect_to'  => site_url('report/main-info/'.$analisisId),
                'itemsets'     => $result['itemsets'],
                'rules'        => $result['rules']
            ]);
        } catch (\Exception $e) {
            log_message('debug', json_encode($transactions));
            return $this->failServerError('Gagal koneksi ke Python API: ' . $e->getMessage());
        }
    }
}
