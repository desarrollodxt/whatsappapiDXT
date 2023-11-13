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

    public function getUsuarioPorTipoEntidad($tipo_entidad)
    {
        $this->db->select("u.nombre,u.id,r.nombre rol")
            ->from("usuarios u")->join("usuarios_roles ur", "ur.id_usuario = u.id")
            ->join("roles r", "r.rol_id = ur.id_rol");

        switch ($tipo_entidad) {
            case '1':
                $this->db->where_in("r.nombre", ["Vendedor", "comercial", "Jefe comercial"]);
                break;
            case '2':
                $this->db->where_in("r.nombre", ["Compras", "Jefe operaciones"]);
                break;
            default:
                # code...
                break;
        }

        $query = $this->db->get();
        return $query->result_array();
    }
}