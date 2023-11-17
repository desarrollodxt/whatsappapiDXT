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

    public function getLeads($usuario, $roles, $tipo_entidad = 1)
    {
        $this->db->from('entidades e');

        if ($tipo_entidad == 1) {
            $this->db->join("usuarios u", "u.id = e.id_vendedor", "left");
        } else if ($tipo_entidad == 2) {
            $this->db->join("usuarios u", "u.id = e.id_comprador", "left");
        }

        $this->db->select("e.tipo_entidad, e.id lead_id,e.id_vendedor, e.id_comprador, e.nombre, DATE_FORMAT(e.fecha_modificacion, '%Y-%m-%d') fecha_modificacion, e.nombre, c.comentario, u.nombre usuarioAsignado, e.fase, e.estimacion");
        $this->db->join("(select max(id) lascomment, id_entidad from comentarios c where id_entidad is not null group by id_entidad) as lc", "e.id = lc.id_entidad", "left");
        $this->db->join("comentarios c", "c.id = lc.lascomment", "left");

        if (validarRol($roles, ['Admin', 'Direccion'])) {
            $this->db->where("e.tipo_entidad", $tipo_entidad);
            $this->db->order_by("e.fecha_modificacion", "DESC");
            $query = $this->db->get();
            return $query->result_array();
        } else if (validarRol($roles, ['Jefe comercial'])) {
            if ($tipo_entidad != 1) {
                return [];
            }
            $this->db->where("e.tipo_entidad", $tipo_entidad)
                ->order_by("e.fecha_modificacion", "DESC");
            $query = $this->db->get();
            return $query->result_array();
        } else if (validarRol($roles, ['Jefe operaciones'])) {
            if ($tipo_entidad != 2) {
                return [];
            }
            $this->db->where("e.tipo_entidad", $tipo_entidad)
                ->order_by("e.fecha_modificacion", "DESC");
            $query = $this->db->get();
            return $query->result_array();
        } else if (validarRol($roles, ['Compras'])) {
            $this->db->where("e.tipo_entidad", $tipo_entidad);
            $this->db->where("u.id", $usuario)
                ->order_by("e.fecha_modificacion", "DESC");
            $query = $this->db->get();
            return $query->result_array();
        } elseif (validarRol($roles, ['Vendedor', 'comercial', 'Comercial'])) {
            $this->db->where("e.tipo_entidad", $tipo_entidad);
            $this->db->where("u.id", $usuario)
                ->order_by("e.fecha_modificacion", "DESC");
            $query = $this->db->get();
            return $query->result_array();
        }
    }

    public function getLead($lead_id)
    {
        $this->db->from('entidades e');
        $this->db->join("usuarios u", "u.id = e.id_vendedor", "left");
        $this->db->join("usuarios com", "com.id = e.id_comprador", "left");

        $this->db->select("e.*, c.comentario, u.nombre vendedor, com.nombre comprador");
        $this->db->join("(select max(id) lascomment, id_entidad from comentarios c where id_entidad is not null group by id_entidad) as lc", "e.id = lc.id_entidad", "left");
        $this->db->join("comentarios c", "c.id = lc.lascomment", "left");
        $this->db->where("e.id", $lead_id);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function cambiarFase($fase, $id_lead)
    {
        $this->db->where("id", $id_lead);
        $this->db->update("entidades", ["fase" => $fase, "fecha_modificacion" => date("Y-m-d H:i:s")]);
    }

    public function crearOrUpdateLead($lead)
    {
        $id = null;
        //if not exists
        if (!isset($lead["id"])) {
            $this->db->insert("entidades", $lead);
            $id = $this->db->insert_id();
        } else {
            $this->db->where("id", $lead["id"]);
            $id = $lead["id"];
            unset($lead["id"]);
            $this->db->update("entidades", $lead);
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
