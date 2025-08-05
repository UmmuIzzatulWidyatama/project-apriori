<?php

namespace App\Models;
use CodeIgniter\Model;

class TransactionDetailModel extends Model
{
    protected $table = 'transaction_details';
    protected $primaryKey = 'id';
    protected $allowedFields = ['transaction_id', 'product_name'];
    public $timestamps = false;
}
