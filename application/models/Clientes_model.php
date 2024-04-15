<?php
class Clientes_model extends CI_Model
{
    private $tabla = 'clientes';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function obtener_clientes()
    {
        $query = $this->db->get($this->tabla);
        return $query->result();
    }

    public function cat_obtener_especificaciones_carga($id)
    {
        $query = $this->db->where('id_cliente', $id)->select("id, nombre text")->get("caracteristicas_extras");
        return $query->result();
    }
    public function cat_obtener_clientes()
    {
        $query = $this->db->select('id , nombre_corto text')->get($this->tabla);

        return $query->result();
    }

    public function cat_obtener_cargas_cliente($id_cliente)
    {
        $query = $this->db->select('id , carga text')->where("id_cliente", $id_cliente)->get("tipos_cargas");

        return $query->result();
    }

    public function cat_obtener_origenes_destinos()
    {
        $query = $this->db->query("select cn.id_nombre id, CONCAT(cn.nombre, ' - ', c.estado) text  from cps_nombres cn inner join cps c on c.id_nombre = cn.id_nombre GROUP by cn.nombre, c.estado");
        return $query->result();
    }

    // Otros métodos relacionados con el modelo pueden ir aquí
}
