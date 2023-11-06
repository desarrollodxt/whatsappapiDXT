<?php

class Usuario_model extends CI_Model
{
    public $tabla = "usuarios";

    public function __construct()
    {
        parent::__construct();
    }


    public function getUsuario($id)
    {
        $query = $this->db->from($this->tabla)->where("id", $id)->get();
        $usuario = $query->row_array();
        if (!$usuario) return [];
        $usuario["roles"] = $this->getRoles($id);
        return $usuario;
    }

    public function getRoles($id_usuario)
    {
        $query = $this->db->select("r.nombre rol")->from("usuarios_roles ur")->join("roles r", "r.rol_id = ur.id_rol")->where("ur.id_usuario", $id_usuario)->get();

        $roles = $query->result_array();

        $roles = array_column($roles, "rol");
        return $roles;
    }
}
