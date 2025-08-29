<?php

namespace App\Models;
use CodeIgniter\Model;

class AprioriRuleModel extends Model
{
    protected $table = 'apriori_rules';
    protected $primaryKey = 'id';
    protected $allowedFields = ['analisis_id', 'antecedents', 'consequents', 'support', 'confidence', 'lift','itemset_number','support_antecedents','support_consequents','created_at'];
    public $timestamps = true;
}
