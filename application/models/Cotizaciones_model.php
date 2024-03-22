<?php
class Cotizacion_model extends CI_Model
{
    private $tabla = 'cotizaciones';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
}
