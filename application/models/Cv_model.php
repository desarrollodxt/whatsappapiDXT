<?php
class Cv_model extends CI_Model
{
    private $tabla = 'api a';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    public function getInfoFactura($id_cliente, $cv, $factura)
    {
        $this->db->select("a.cliente_nombre_corto cliente, a.cv, a.fact_dxt factura, a.referencia_cliente referencia,a.fecha_fact_dxt fecha, a.vta_total_autorizada monto ")->from($this->tabla)
            ->where("a.id_cliente", $id_cliente)
            ->where("a.cv", $cv)
            ->where("a.fact_dxt", $factura);

        $query = $this->db->get();
        return $query->result_array();
    }
}
