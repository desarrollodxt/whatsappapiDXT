<?php
class Comentario_model extends CI_Model
{
    private $tabla = 'comentarios';
    // campos de la tabla comentario
    //id,id_empresa,id_lead,id_usuario,id_comentario_tipo,comentario,url,nombre_archivo,extension,fecha,factura,cv,id_cliente
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function guardarComentarioCotizacion($filename, $destinatario, $id_lead, $usuario)
    {

        $data = [
            "id_empresa" => 1,
            "id_lead" => $id_lead,
            "id_usuario" => $usuario,
            "id_comentario_tipo" => 7,
            "comentario" => "Cotización para " . $destinatario,
            "url" => "vista/fotos/$filename",
            "nombre_archivo" => $filename,
            "extension" => "pdf",
            "fecha" => date("Y-m-d H:i:s"),
            "factura" => 0,
            "cv" => 0,
            "id_cliente" => 0
        ];
        $this->db->insert($this->tabla, $data);
        return $this->db->insert_id();
    }
}
