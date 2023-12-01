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

    public function getComentariosForLead($id_lead)
    {
        $this->db->select("comentarios.*,comentario_tipo.id as id_tipo_comentario,comentario_tipo.icono as tipo_comentario, u.nombre autor");
        $this->db->from($this->tabla);
        $this->db->join("comentario_tipo", "comentario_tipo.id = comentarios.id_comentario_tipo");
        $this->db->join("usuarios u", "u.id = comentarios.id_usuario");
        $this->db->where("comentarios.id_entidad", $id_lead);
        $this->db->order_by("fecha", "desc");
        $query = $this->db->get();
        return $query->result();
    }

    public function crearComentario($comentario, $isArchive, $fileInfo)
    {
        $comentarioInfo = array("id_empresa" => 1, "id_usuario" => $comentario["usuario_id"], "id_comentario_tipo" => $comentario["tipocomentario"], "comentario" => $comentario["comentario"], "id_entidad" => $comentario["id_lead"]);
        if (intval($isArchive) == 1) {
            $comentarioInfo["url"] = $_SERVER["URL_RELATIVE_PATH"] . $fileInfo["nombre_archivo"];
            $comentarioInfo["nombre_archivo"] = $fileInfo["nombre_archivo"];
            $comentarioInfo["extension"] = $fileInfo["extension"];
            $this->db->insert("comentarios", $comentarioInfo);
            $id = $this->db->insert_id();
        } else {
            $this->db->insert("comentarios", $comentarioInfo);
            $id = $this->db->insert_id();
        }
        return $id;
    }

    public function crearComentarioArchivoEntidad($comentario, $fileInfo)
    {
        $comentarioInfo = array("id_empresa" => 1, "id_usuario" => $comentario["usuario_subio"], "comentario" => $comentario["comentario"], "id_entidad" => $comentario["id_lead"]);


        $comentarioInfo["url"] = $_SERVER["URL_RELATIVE_PATH"] . $fileInfo["nombre_archivo"];
        $comentarioInfo["nombre_archivo"] = $fileInfo["nombre_archivo"];
        $comentarioInfo["extension"] = $fileInfo["extension"];

        switch ($comentarioInfo["extension"]) {
            case 'pdf':
                $comentarioInfo["id_comentario_tipo"] = 7;
                break;
            case 'png':
                $comentarioInfo["id_comentario_tipo"] = 6;
                break;
            case 'jpg':
                $comentarioInfo["id_comentario_tipo"] = 6;
                break;
            case 'jpeg':
                $comentarioInfo["id_comentario_tipo"] = 6;
                break;
            default:
                $comentarioInfo["id_comentario_tipo"] = 7;

                break;
        }
        $this->db->insert("comentarios", $comentarioInfo);
        $id = $this->db->insert_id();

        return $id;
    }
}
