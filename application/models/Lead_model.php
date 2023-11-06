<?php
class Lead_model extends CI_Model
{
    private $tabla = 'leads';
    // campos de la tabla
    // id,id_empresa,numero,fase,dueno,estimacion,ubicaciongoogle,contactos,clee,id_lead,estatus,nombre,razon_social,observaciones,clase_actividad,estrato,tipo_vialidad,calle,num_exterior,num_interior,colonia,cp,ubicacion,telefono,correo_e,sitio_internet,tipo,longitud,latitud,tipo_corredor_industrial,nom_corredor_industrial,numero_local,ageb,manzana,clase_actividad_id,edificio_piso,sector_actividad_id,subsector_actividad_id,rama_actividad_id,subrama_actividad_id,edificio,tipo_asentamiento,fecha_alta,areageo,usuario_modifica,usuario_crea,fecha_modificacion_lead,fecha_creacion_lead,id_cliente
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function getLeads($usuario, $roles, $all = false)
    {
        $this->db->from($this->tabla . ' l');
        $this->db->join("usuarios u", "u.id = l.dueno");
        $this->db->select("l.id lead_id, l.nombre, DATE_FORMAT(l.fecha_modificacion_lead, '%Y-%m-%d') fecha_modificacion, l.nombre, c.comentario, u.nombre vendedor, l.fase, l.estimacion");
        $this->db->join("(select max(id) lascomment, id_lead from comentarios c where id_lead is not null group by id_lead) as lc", "l.id = lc.id_lead", "left");
        $this->db->join("comentarios c", "c.id = lc.lascomment", "left");
        $this->db->where_not_in('l.fase', ['DESCARTADO']);
        if (validarRol($roles, ['Admin', 'Jefe comercial'])) {
            $this->db->where("dueno <>", "0")
                ->order_by("fecha_modificacion_lead", "DESC");
            $query = $this->db->get();
            return $query->result_array();
        } elseif (validarRol($roles, ['Vendedor', 'comercial'])) {
            $this->db->where("dueno", $usuario)
                ->order_by("fecha_modificacion_lead", "DESC");
            $query = $this->db->get();
            return $query->result_array();
        }
    }

    public function getLead($lead_id)
    {
        $this->db->from($this->tabla . ' l');
        $this->db->join("usuarios u", "u.id = l.dueno");
        $this->db->select("l.*, c.comentario, u.nombre vendedor");
        $this->db->join("(select max(id) lascomment, id_lead from comentarios c where id_lead is not null group by id_lead) as lc", "l.id = lc.id_lead", "left");
        $this->db->join("comentarios c", "c.id = lc.lascomment", "left");
        $this->db->where("l.id", $lead_id);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function cambiarFase($fase, $id_lead)
    {
        $this->db->where("id", $id_lead);
        $this->db->update($this->tabla, ["fase" => $fase, "fecha_modificacion_lead" => date("Y-m-d H:i:s")]);
    }

    public function crearOrUpdateLead($lead)
    {
        $id = null;
        //if not exists
        if (!isset($lead["id"])) {
            $this->db->insert($this->tabla, $lead);
            $id = $this->db->insert_id();
        } else {
            $this->db->where("id", $lead["id"]);
            $id = $lead["id"];
            unset($lead["id"]);
            $this->db->update($this->tabla, $lead);
        }


        return $this->getLead($id);
    }

    public function getContactos($lead)
    {
        $this->db->from("contactos_leads");
        $this->db->where("lead_id", $lead);
        $query = $this->db->get();
        return $query->result_array();
    }
}
