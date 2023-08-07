<?php
class Roles_model extends CI_Model
{
    private $tabla = 'Roles';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    public function obtener_roles()
    {
        ob_clean();
        $query =  $this->db->query("select r.rol_id, r.nombre rol, p.nombre permiso from roles r left join roles_permisos rp on r.rol_id  = rp.id_rol left join permisos p on p.permiso_id = rp.id_permiso;");
        $roles = $query->result_array();
        // agrupar todos los permisos de cada rol
        $roles = array_reduce($roles, function ($acumulador, $item) {
            $acumulador[$item["rol_id"]]["nombre"] = $item["rol"];
            $acumulador[$item["rol_id"]]["permisos"][] = $item["permiso"];
            return $acumulador;
        }, []);
        return $roles;
    }

    public function obtener_permisos($proveedor_id = null)
    {
        $this->db->select("permiso_id, nombre accion");
        $this->db->from("permisos");

        $query = $this->db->get();
        return $query->result_array();
    }

    public function CrearRol($rol)
    {
        $this->db->insert("roles", ["nombre" => $rol]);
        return $this->db->insert_id();
    }

    public function actualizarPermisos($nombre, $permisos)
    {
        $this->db->trans_begin();
        try {
            $query = $this->db->where("nombre", $nombre)->from("roles")->get();
            $rol = $query->row_array();
            $this->db->table("roles_permisos")->where("id_rol", $rol["rol_id"])->delete();
            foreach ($permisos as $permiso) {
                $query = $this->db->where("nombre", $permiso)->from("permisos")->get();
                $permiso = $query->row_array();




                $this->db->insert("roles_permisos", ["id_rol" => $rol["rol_id"], "id_permiso" => $permiso["permiso_id"]]);
            }

            $this->db->trans_commit();
        } catch (\Throwable $th) {
            $this->db->trans_rollback();
        }
    }
}
