<?php

namespace App\Models;
use CodeIgniter\Model;

class TransactionModel extends Model
{
    protected $table = 'transactions';
    protected $primaryKey = 'id';
    protected $allowedFields = ['sale_date'];
    public $timestamps = false;

    // Ambil transaksi lengkap dengan daftar item per transaksi
    public function getAllWithItems()
    {
        return $this->db->query("
            SELECT t.id, GROUP_CONCAT(d.product_name) AS items
            FROM transactions t
            JOIN transaction_details d ON d.transaction_id = t.id
            GROUP BY t.id
        ")->getResult();
    }
}
