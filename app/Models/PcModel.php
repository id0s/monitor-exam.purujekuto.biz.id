<?php

namespace App\Models;

use CodeIgniter\Model;

class PcModel extends Model
{
    protected $table            = 'status_pc';
    protected $primaryKey       = 'ip_address';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ip_address', 'mac_address', 'nama_pc', 'vlan_id', 'status_chrome', 'is_locked', 'last_ping'];

    // Dates
    protected $useTimestamps = false;
}
