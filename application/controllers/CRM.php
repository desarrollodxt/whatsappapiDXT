<?php
defined('BASEPATH') or exit('No direct script access allowed');

class CRM extends CI_Controller
{
    public $body = [];

    public $respuesta = [
        "error" => false,
        "mensaje" => "",
        "data" => null
    ];

    protected function responder($error = false, $mensaje = "", $data = null, $http_code = 200)
    {
        $this->respuesta["error"] = $error;
        $this->respuesta["mensaje"] = $mensaje;
        $this->respuesta["data"] = $data;
        header('Content-Type: application/json');
        http_response_code($http_code);
        echo json_encode($this->respuesta);
        exit();
    }

    public function __construct()
    {
        parent::__construct();

        $_body = file_get_contents('php://input');
        $this->load->model('Usuario_model');
        $this->load->model('Lead_model');
        //validar que haya un body
        if (!$_body) {
            $this->body = [];
        } else {
            $this->body = json_decode($_body, true);
        }
        //validar que el body sea un json, sino responder un error de peticion
        if (!is_array($this->body)) {
            $this->responder(true, "Error en la peticion", null, 400);
        }
    }

    public function getEntidades()
    {
        $usuario = $_GET["usuario"];
        $tipo_entidad = isset($_GET["tipo_entidad"]) ? $_GET["tipo_entidad"] : 1;
        if (!isset($_GET["usuario"])) {
            $this->responder(true, "Debes enviar el usuario", null, 400);
        }

        $roles = $this->Usuario_model->getRoles($usuario);

        $leads = $this->Lead_model->getLeads($usuario, $roles, $tipo_entidad);

        $data = [
            "entidades" => $leads,
            "usuarios" => $this->Usuario_model->getUsuarioPorTipoEntidad($tipo_entidad),
            "headers" => ["Fecha Mod", "Nombre cliente", "Último comentario", "Vendedor", "Fase", "$", "Acción"]
        ];
        $this->responder(false, "", $data, 200);
    }

    public function cambiarFase()
    {
        if (!isset($_GET["usuario"])) {
            $this->responder(true, "Debes enviar el usuario", null, 400);
        }

        $roles = $this->Usuario_model->getRoles($_GET["usuario"]);

        $fase = $this->body["fase"];
        $id_lead = $this->body["lead_id"];

        if (!isset($fase) || !isset($id_lead)) {
            $this->responder(true, "Debes enviar la fase y el id_lead", null, 400);
        }

        if ($fase != 'F5 GANADO' && !validarRol($roles, ['Admin', 'Jefe comercial', 'Vendedor', 'comercial'])) {
            $this->responder(true, "No tienes permisos para cambiar a una fase diferente de Ganado", null, 400);
        }



        $this->Lead_model->cambiarFase($fase, $id_lead);

        $this->responder(false, "Fase cambiada correctamente", null, 200);
    }

    public function getComentarios()
    {
        if (!isset($_GET["lead_id"])) {
            $this->responder(true, "Debes enviar el id del lead", null, 400);
        }
        $this->load->model('Comentario_model');
        $comentarios = $this->Comentario_model->getComentariosForLead($_GET["lead_id"]);

        $lead = $this->Lead_model->getLead($_GET["lead_id"]);
        $data = [
            "comentarios" => $comentarios,
            "lead" => $lead
        ];
        $this->responder(false, "", $data, 200);
    }

    public function getInformacionGeneral()
    {
        if (!isset($_GET["lead_id"])) {
            $this->responder(true, "Debes enviar el id del lead", null, 400);
        }

        $lead = $this->Lead_model->getLead($_GET["lead_id"]);
        $contactos = $this->Lead_model->getContactos($_GET["lead_id"]);
        $data = [
            "lead" => $lead,
            "contactos" => $contactos,
            "usuarios" => $this->Usuario_model->getUsuarioPorTipoEntidad($lead["tipo_entidad"])
        ];
        $this->responder(false, "", $data, 200);
    }

    public function actualizarLead()
    {
        if (isset($this->body["tipo_unidad"])) {
            $this->responder(true, "Debes enviar el tipo de unidad", [], 200);
        }
        $this->body["fecha_modificacion"] = date("Y-m-d H:i:s");
        $lead = $this->Lead_model->crearOrUpdateLead($this->body);


        $data = [
            "lead" => $lead
        ];
        $this->responder(false, "Actualizado correctamente", $data, 200);
    }

    public function sendComentario()
    {
        $this->load->helper("uploadfile_helper");
        $this->load->model('Comentario_model');
        $isArchivo = $_POST["con_archivo"];
        $data = ["comentarios" => []];
        if (intval($isArchivo) == 1) {
            $tipo_archivo = '';
            if ($_POST['tipocomentario'] == 6) $tipo_archivo = 'foto';
            if ($_POST['tipocomentario'] == 7) $tipo_archivo = 'archivo';
            $resultUpload = $this->carga_achivo($tipo_archivo, $_SERVER["UPLOAD_IMAGE_PATH"]);
            if (!$resultUpload) {
                $this->responder(true, "Error al subir el archivo", null, 400);
            }
            $this->Comentario_model->crearComentario($_POST, $isArchivo, $resultUpload);

            $data["comentarios"] = $comentarios = $this->Comentario_model->getComentariosForLead($_POST["id_lead"]);
            $this->responder(false, "",   $data);
        }

        $this->Comentario_model->crearComentario($_POST, $isArchivo, null);
        $data["comentarios"] = $this->Comentario_model->getComentariosForLead($_POST["id_lead"]);
        $this->responder(false, "", $data);
    }

    public function getCotizacionesLead()
    {
        if (!isset($_GET["lead"])) {
            $this->responder(true, "Debes enviar el id del lead", null, 400);
        }

        $this->load->model('Cotizacion_model');
        $cotizaciones = $this->Cotizacion_model->getCotizacionesForLead($_GET["lead"]);
        $costos = $this->Cotizacion_model->getCostosForLead($_GET["lead"]);
        $lead = $this->Lead_model->getLead($_GET["lead"]);
        $data = [
            "cotizaciones" => $cotizaciones,
            "costos" => $costos,
            "lead" =>  $lead
        ];
        $this->responder(false, "", $data, 200);
    }

    private function carga_achivo($campo, $carpeta, $permitidos = '')
    {
        // carpeta = /fotos/
        //$permitidos = array('image/jpg','image/png','image/jpeg','image/gif','video/mov','video/mp4', 'video/quicktime');
        $permitidos =
            array(
                'image/jpg',
                'image/png',
                'image/jpeg',
                'image/gif',
                'video/mov',
                'video/mp4',
                'video/quicktime',
                'application/pdf',
                'text/plain',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.ms-powerpoint',
                'application/x-zip-compressed'
            );

        $uploadPath = $carpeta;

        if (is_uploaded_file($_FILES[$campo]['tmp_name'])) {
            // Extrae datos de archivo
            $f_nombre = $_FILES[$campo]['name'];


            $f_temporal = $_FILES[$campo]['tmp_name'];
            $f_tipo = $_FILES[$campo]['type'];
            $f_peso = $_FILES[$campo]['size'];

            if ($permitidos  && $f_nombre != '' && !in_array($f_tipo, $permitidos)) {
                return false;
            } else {
                $comprimir = 1;
                if (
                    $f_tipo == 'video/mov'
                    || $f_tipo == 'video/mp4'
                    || $f_tipo == 'video/quicktime'
                    || $f_tipo == 'application/pdf'
                    || $f_tipo == 'text/plain'
                    || $f_tipo == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    || $f_tipo == 'application/vnd.ms-excel'
                    || $f_tipo == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    || $f_tipo == 'application/msword'
                    || $f_tipo == 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
                    || $f_tipo == 'application/vnd.ms-powerpoint'
                    || $f_tipo == 'application/x-zip-compressed'
                ) $comprimir = 0;
                // Define nuevo nombre
                $f_ext = extension($f_nombre);
                //$f_nombre_original = substr($f_nombre, 0, strlen($f_nombre)-strlen($f_ext)-1);
                $f_nombre_original = str_replace("." . $f_ext, "",  $f_nombre);
                $f_nombre_original = str_replace(" ", "_", $f_nombre_original);



                $f_aleatorio = fn_aleatorio('alfanumerico', 8);
                $f_nombre_nuevo = $f_nombre_original . '_' . $f_aleatorio . '.' . $f_ext;

                $f_aleatorio_borrar = fn_aleatorio('alfanumerico', 8);
                $f_nombre_nuevo_borrar = $f_aleatorio_borrar . '.' . $f_ext;

                if ($comprimir == 1) {

                    $copiado = copy($f_temporal, $uploadPath . $f_nombre_nuevo_borrar);

                    $imagen_ajustada = redimensionar_imagen($f_nombre_nuevo_borrar, $uploadPath . $f_nombre_nuevo_borrar, 1200, 1200);
                    imagejpeg($imagen_ajustada, $uploadPath . $f_nombre_nuevo);

                    $imagen_optimizada = redimensionar_imagen($f_nombre_nuevo, $uploadPath . $f_nombre_nuevo, 500, 500);
                    imagejpeg($imagen_optimizada, $uploadPath . "bajares/bres_" . $f_nombre_nuevo);
                    unlink($uploadPath . $f_nombre_nuevo_borrar);
                } else {
                    $copiado = copy($f_temporal, $uploadPath . $f_nombre_nuevo);
                }

                if (!$copiado) {
                    return false;
                } else {
                    return [
                        "nombre_archivo" => $f_nombre_nuevo,
                        "extension" => $f_ext
                    ];
                }
            }
        } else {
            return false;
        }
    }

    public function getCotizacionAnterior($lead)
    {
        $this->load->model('Cotizacion_model');
        $cotizacion = $this->Cotizacion_model->getCotizacionAnterior($lead);

        $this->responder(false, "", $cotizacion, 200);
    }
}
