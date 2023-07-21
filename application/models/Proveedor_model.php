<?php
class Proveedor_model extends CI_Model
{
    private $tabla = 'proveedores';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    public function obtener_proveedores()
    {
        $this->db->select("p.id, p.nombre_corto");
        $this->db->from("proveedores as p");
        $query = $this->db->get();
        return $query->result_array();
    }

    public function obtener_whatsappContact($proveedor_id)
    {
        $this->db->select("p.id, p.nombre_corto, wt.telefono");
        $this->db->from("proveedores as p");
        $this->db->join("proveedores_whatsapps as pw", "pw.proveedor_id = p.id");
        $this->db->join("whatsapp_contacto as wt", "wt.id = pw.whatsapp_id");
        $this->db->where("p.id", $proveedor_id);
        $query = $this->db->get();
        return $query->result_array();
    }
}
