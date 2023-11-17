<?php
defined('BASEPATH') or exit('No direct script access allowed');

class OperacionesVarios_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function getLeads()
    {
        return $this->db->select("id, nombre, razon_social,fase,fecha_creacion_lead,estimacion,clase_actividad,observaciones,ubicaciongoogle,sitio_internet,dueno,1 tipo_empresa,usuario_crea,usuario_modifica,fecha_modificacion_lead ")
            ->where("isSync", 0)
            ->limit(500)
            ->get("leads")->result_array();
    }

    public function insertEmpresa($entidades)
    {
        try {
            $this->db->trans_begin();
            echo 1;
            foreach ($entidades as $value) {
                $empresa = [
                    "nombre" => $value["nombre"],
                    "razon_social" => $value["razon_social"],
                    "estimacion" => $value["estimacion"],
                    "fase" => $value["fase"],
                    "observaciones" => $value["observaciones"],
                    "clase_actividad" => $value["clase_actividad"],
                    "ubicaciongoogle" => $value["ubicaciongoogle"],
                    "sitio_internet" => $value["sitio_internet"],
                    "usuario_creo" => $value["usuario_crea"],
                    "fecha_creacion" => $value["fecha_creacion_lead"],
                    "activo" => 1,
                    "tipo_entidad" => $value["tipo_empresa"],
                    "id_lead_tabla_anterior" => $value["id"],
                    "fecha_modificacion" => $value["fecha_modificacion_lead"],
                ];
                $this->db->insert("entidades", $empresa);
                $id_empresa = $this->db->insert_id();
                if (intval($value["dueno"]) != 0) {
                    $this->db->insert("usuarios_entidades", ["id_usuario" => $value["dueno"], "id_entidad" => $id_empresa]);
                }

                $this->db->update("leads", ["isSync" => 1], ["id" => $value["id"]]);
                $this->db->update("comentarios", ["id_entidad" => $id_empresa], ["id_lead" => $value["id"]]);
            }
            $this->db->trans_commit();
        } catch (\Throwable $th) {
            echo $th->getMessage();
            $this->db->trans_rollback();
        }
    }

    public function getVendedoresYEntidades()
    {
        return $this->db->get("usuarios_entidades")->result_array();
    }

    public function setVendedor($vendedoresYEntidades)
    {
        foreach ($vendedoresYEntidades as $value) {
            $this->db->update("entidades", ["id_vendedor" => $value["id_usuario"]], ["id" => $value["id_entidad"]]);
        }
    }
}
