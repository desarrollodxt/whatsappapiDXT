<?php
class Contacto_model extends CI_Model
{
    private $tabla = 'contactos';
    // campos de la tabla
    // id,id_empresa,numero,fase,dueno,estimacion,ubicaciongoogle,contactos,clee,id_lead,estatus,nombre,razon_social,observaciones,clase_actividad,estrato,tipo_vialidad,calle,num_exterior,num_interior,colonia,cp,ubicacion,telefono,correo_e,sitio_internet,tipo,longitud,latitud,tipo_corredor_industrial,nom_corredor_industrial,numero_local,ageb,manzana,clase_actividad_id,edificio_piso,sector_actividad_id,subsector_actividad_id,rama_actividad_id,subrama_actividad_id,edificio,tipo_asentamiento,fecha_alta,areageo,usuario_modifica,usuario_crea,fecha_modificacion_lead,fecha_creacion_lead,id_cliente
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function guardarContacto($info)
    {

        try {
            $this->db->insert($this->tabla, $info);
            return $this->db->insert_id();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
