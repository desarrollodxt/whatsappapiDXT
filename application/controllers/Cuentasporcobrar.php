<?php
defined('BASEPATH') or exit('No direct script access allowed');


class Cuentasporcobrar extends CI_Controller
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
        header('Access-Control-Allow-Origin: https://dxt.com.mx');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            header('Access-Control-Allow-Origin: https://dxt.com.mx');
            header('Access-Control-Allow-Headers: Content-Type');
            exit;
        }
        $_body = file_get_contents('php://input');
        // var_dump($_body);
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
            // $this->responder(true, "Error en la peticion", null, 400);
        }
    }

    /**
     * Servicio para obtener los comentarios de una factura o cv
     */

    public function getComentariosfc()
    {
        if (!isset($_GET["id_cliente"])) {
            $this->responder(true, "Debes enviar el id del cliente", null, 400);
        }

        if (!isset($_GET["cv"])) {
            $this->responder(true, "Debes enviar el numero de cv", null, 400);
        }
        $this->load->model('Cv_model');
        $id_cliente = $_GET["id_cliente"];
        $cv = $_GET["cv"];
        $factura = $_GET["factura"];
        $this->load->model('Comentario_model');
        $comentarios = $this->Comentario_model->getComentariosfc($id_cliente, $cv, $factura);
        $infoFactura = $this->Cv_model->getInfoFactura($id_cliente, $cv, $factura);
        $data = [
            "cliente" => $infoFactura[0]["cliente"],
            "factura" => $infoFactura[0]["factura"],
            "cv" => $infoFactura[0]["cv"],
            "referencia" => $infoFactura[0]["referencia"],
            "fecha" => explode(" ", $infoFactura[0]["fecha"])[0],
            "monto" => $infoFactura[0]["monto"],
            "comentarios" => $comentarios
        ];

        $this->responder(false, "ok", $data);
    }


    public function sendComentariofc()
    {
        // var_dump(__DIR__);
        // exit;
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
            $this->Comentario_model->crearComentariofc($_POST, $isArchivo, $resultUpload);

            $data["comentarios"] = $comentarios = $this->Comentario_model->getComentariosfc($_POST["id_cliente"], $_POST["cv"], $_POST["factura"]);

            $this->responder(false, "",   $data);
        }

        $this->Comentario_model->crearComentariofc($_POST, $isArchivo, null);
        $data["comentarios"] = $this->Comentario_model->getComentariosfc($_POST["id_cliente"], $_POST["cv"], $_POST["factura"]);
        $this->responder(false, "", $data);
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


    public function guardarArchivoEntidad()
    {

        $this->load->helper("uploadfile_helper");
        $this->load->model('Comentario_model');
        $entidad_id = $_POST["lead_id"];
        $tipo_archivo = $_POST["tipoArchivo"];
        $nombre_archivo = $_POST["nombre_archivo"];
        //Get extension file $_FILES["inputFile"]["name"]

        $resultUpload = $this->carga_achivo("inputFile", $_SERVER["UPLOAD_IMAGE_PATH"]);
        if (!$resultUpload) {
            $this->responder(true, "Los archivos de tipo " . pathinfo($_FILES["inputFile"]["name"], PATHINFO_EXTENSION) . " no están permitidos", null, 400);
        }
        $nombre_archivo_subido = $resultUpload["nombre_archivo"];
        $extension = $resultUpload["extension"];

        $archivoEntidad = [
            "nombre_archivo" => $nombre_archivo . "." . $extension,
            "path" => $_SERVER["UPLOAD_IMAGE_PATH"] . $nombre_archivo_subido,
            "nombre" => $nombre_archivo,
            "tipo_archivo" => $tipo_archivo,
            "id_entidad" => $entidad_id,
            "extension" => $extension,
            "created_at" => date("Y-m-d H:i:s"),
            "usuario_subio" => $_POST["usuario_subio"],
            "active" => 1,
            "nombre_archivo_subido" => $nombre_archivo_subido,
            "url" => $_SERVER["URL_RELATIVE_PATH"] . $nombre_archivo_subido
        ];

        $this->load->model('Archivos_model');
        $archivo = $this->Archivos_model->guardarArchivoEntidad($archivoEntidad);

        $comentario = [
            "usuario_subio" => $_POST["usuario_subio"],
            "comentario" => "Subió la/el " . $tipo_archivo . " " . $nombre_archivo,
            "id_lead" => $entidad_id
        ];
        $this->Comentario_model->crearComentarioArchivoEntidad($comentario, $resultUpload);
        $this->responder(false, "", $archivo, 200);
    }
}
