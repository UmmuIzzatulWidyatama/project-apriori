<?php

namespace App\Models;
use CodeIgniter\Model;

class AnalisisDataModel extends Model
{
    protected $table = 'analisis_data';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'title',
        'description',
        'start_date',
        'end_date',
        'total_transactions',
        'min_support',
        'min_confidence'
    ];
    public $timestamps = true;
}
