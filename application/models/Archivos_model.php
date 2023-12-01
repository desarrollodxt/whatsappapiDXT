<?php
class Archivos_model extends CI_Model
{
    private $tabla = 'archivos';
    // campos de la tabla comentario
    //id,id_empresa,id_lead,id_usuario,id_comentario_tipo,comentario,url,nombre_archivo,extension,fecha,factura,cv,id_cliente
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function guardarArchivoEntidad($archivo)
    {

        $this->db->insert($this->tabla, $archivo);
        return $this->db->where('id', $this->db->insert_id())->get($this->tabla)->result_array();
    }
}
