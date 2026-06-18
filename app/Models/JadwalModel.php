<?php

namespace App\Models;

use CodeIgniter\Model;

class JadwalModel extends Model
{
    protected $table            = 'jadwal_ujian';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['nama_event', 'url_target', 'waktu_mulai', 'waktu_selesai', 'target_lab', 'browser_mode', 'status', 'created_at'];

    // Dates
    protected $useTimestamps = false;
}
