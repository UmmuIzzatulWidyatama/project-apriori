<?php

namespace App\Models;
use CodeIgniter\Model;

class TransactionModel extends Model
{
    protected $table = 'transactions';
    protected $primaryKey = 'id';
    protected $allowedFields = ['sale_date', 'transaction_number'];
    public $timestamps = false;

    // Ambil transaksi lengkap dengan daftar item per transaksi
    public function getAllWithItems($startDate = null, $endDate = null)
    {
        // Hindari string terpotong kalau item per transaksi banyak
        $this->db->query('SET SESSION group_concat_max_len = 1048576');

        $builder = $this->db->table('transactions t');
        $builder->select("
            t.id,
            CONCAT(
            '[',
            GROUP_CONCAT(DISTINCT JSON_QUOTE(TRIM(d.product_name)) ORDER BY d.product_name SEPARATOR ','),
            ']'
            ) AS items_json
        ", false);
        $builder->join('transaction_details d', 'd.transaction_id = t.id');

        if ($startDate) $builder->where('t.sale_date >=', $startDate);
        if ($endDate)   $builder->where('t.sale_date <=', $endDate);

        $builder->groupBy('t.id');
        return $builder->get()->getResult();
    }

}
