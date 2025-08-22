<?php

namespace App\Models;
use CodeIgniter\Model;

class AprioriItemsetModel extends Model
{
    protected $table = 'apriori_itemsets';
    protected $primaryKey = 'id';
    protected $allowedFields = ['analisis_id', 'itemsets', 'support','itemset_number','frequency','created_at'];
    public $timestamps = true;
}
