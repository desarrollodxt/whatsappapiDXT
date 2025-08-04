<?php
class Tractor_model extends CI_Model
{
    private $tabla = 'unidades u';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    public function getTractores()
    {
        $query = $this->db->from($this->tabla)->where("sub_tipo_unidad", "TRACTO")->get();
        return $query->result_array();
    }

    public function getTractorByExternalId($external_id)
    {
        $query = $this->db->from($this->tabla)->where("id_samsara", $external_id)->get();
        return $query->row_array();
    }

    public function getEstatusTractorBySamsaraId($external_id)
    {
        $query = $this->db->from("estatus_tractor")->where("id_samsara", $external_id)->get();
        return $query->row_array();
    }

    public function insertarEstatus($estatus)
    {
        $this->db->insert("estatus_tractor", $estatus);
        return $this->db->insert_id();
    }

    public function actualizarEstatus($estatus, $estatus_actual)
    {
        if ($estatus["estatus"] == $estatus_actual["estatus"] && $estatus["ubicacion_direccion"] == $estatus_actual["ubicacion_direccion"] && $estatus["coordenadas"] == $estatus_actual["coordenadas"]) {
            return false;
        }
        $this->db->where("id", $estatus_actual["id"])->update("estatus_tractor", $estatus);



        $estatus["estatus_anterior"] = $estatus_actual["estatus"];
        $estatus["estatus_nuevo"] = $estatus["estatus"];

        $this->db->insert("estatus_tractor_history", $estatus);

        return true;
    }

    public function getBitacoraActiva($tractor_id)
    {

        // where b.unidad_no_economico = 88 and b.folio_pedido_usa is not null 
        // and fecha_fin_viaje is null and fecha_inicio_ruta is not null order by folio_pedido_usa DESC;
        $sql = "SELECT * from bitacoras b
        where b.unidad_no_economico = ? and b.folio_pedido_usa is not 
        null and fecha_fin_viaje is null and fecha_inicio_ruta is not null 
        and b.fecha_inicio_ruta >  DATE_SUB(now(), INTERVAL 10 DAY)
        order by folio_pedido_usa DESC";
        $query = $this->db->query($sql, array($tractor_id));

        return $query->row_array();
    }
}
