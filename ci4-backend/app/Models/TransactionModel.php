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
        $builder = $this->db->table('transactions t');
        $builder->select('t.id, GROUP_CONCAT(d.product_name) AS items');
        $builder->join('transaction_details d', 'd.transaction_id = t.id');

        if ($startDate && $endDate) {
            $builder->where('t.sale_date >=', $startDate);
            $builder->where('t.sale_date <=', $endDate);
        }

        $builder->groupBy('t.id');
        return $builder->get()->getResult();
    }
}
