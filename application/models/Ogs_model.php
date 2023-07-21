<?php
class Ogs_model extends CI_Model
{
    private $tabla = 'ordenes_sevicios_generales';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
}
