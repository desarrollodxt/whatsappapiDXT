<?php
class Unidades_model extends CI_Model
{
    private $tabla = 'cat_unidades';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function obtener_unidades()
    {
        $query = $this->db->get($this->tabla);
        return $query->result();
    }

    public function obtener_cat_unidades()
    {
        $query = $this->db->select("id, unidad text")->get($this->tabla);
        return $query->result();
    }


    public function obtener_caracteristicas_unidades()
    {
        $query = $this->db->select("id, nombre text")->get("cat_caracteristicas_unidades");
        return $query->result();
    }

    public function insertar_unidad($data)
    {
        // Establecer el timestamp para created_at y updated_at
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->insert($this->tabla, $data);
        return $this->db->insert_id();
    }

    public function actualizar_unidad($id, $data)
    {
        // Establecer el timestamp para updated_at
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where('id', $id);
        return $this->db->update($this->tabla, $data);
    }

    // Otros métodos relacionados con el modelo pueden ir aquí
}