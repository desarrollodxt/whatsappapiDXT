<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;


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
            "headers" => ["Fecha Mod", "Nombre cliente", "Último comentario", "Vendedor", "Fase", "$", "Acción"],
            "requisitosArchivos" => $this->Lead_model->getRequisitosArchivos($tipo_entidad)
        ];
        $this->responder(false, "", $data, 200);
    }

    /**
     * Generar token
     * 
     */

    public function generarToken()
    {
        //expiracion en 1 semana
        $this->body["exp"] = time() + (60 * 60 * 24 * 7);
        $key = "11dxt2024";

        $jwt = JWT::encode($this->body, $key, 'HS256');
        $this->responder(false, "", ["token" => $jwt], 200);
    }

    /**
     * Guardar informacion de un cliente, subida por el en otra plataforma
     */
    public function guardarAltacliente()
    {
        //set timezone mty
        date_default_timezone_set('America/Monterrey');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(true, "Método no permitido", null, 400);
        }




        if (!isset($_POST["token"])) {
            $this->responder(true, "Debes enviar el token", null, 400);
        }

        $token = $_POST["token"];

        if (!isset($_POST["usuario_id"]) || !isset($_POST["entidad_id"])) {
            $this->responder(true, "Debes enviar el usuario y la entidad", null, 400);
        }
        $usuario_id = $_POST["usuario_id"];
        $entidad_id = $_POST["entidad_id"];

        $key = "11dxt2024";
        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
        } catch (ExpiredException $e) {
            $this->responder(true, "Token expirado", null, 400);
        } catch (\Exception $e) {
            $this->responder(true, "Token inválido", null, 400);
        }

        $actaConstitutiva = $_FILES["fileActa"];
        $CaratulaEdo = $_FILES["fileCaratula"];
        $comprobanteDomicilio = $_FILES["fileComprobante"];
        $constanciaFiscal = $_FILES["fileConstancia"];
        $identificacion = $_FILES["fileIdentificacion"];
        $opinionCumplimiento = $_FILES["fileOpinion"];
        $poderRepresentante = $_FILES["filePoder"];
        $tarjetaCirculacion = $_FILES["fileTarjeta"];
        $acuerdoTransportista = $_FILES["fileAcuerdo"];

        // "nombre_razon": "Joe doe",
        // "calle": "Calle siempre viva",
        // "numExterno": "301",
        // "numInterno": "",
        // "cp": "67168",
        // "colonia": "colonia",
        // "rfc": "MERL960608JQ7",
        // "ciudad": "Monterrey",
        // "telefono": "822569874521",
        // "giro": "TRANSPORTE",
        // "antiguedad": "1 año",

        $datosCaptura = [
            "nombre_razon" => $_POST["nombre_razon"],
            "calle" => $_POST["calle"],
            "numExterno" => $_POST["numExterno"],
            "numInterno" => $_POST["numInterno"],
            "cp" => $_POST["cp"],
            "colonia" => $_POST["colonia"],
            "rfc" => $_POST["rfc"],
            "ciudad" => $_POST["ciudad"],
            "telefono" => $_POST["telefono"],
            "giro" => $_POST["giro"],
            "antiguedad" => $_POST["antiguedad"],
        ];


        //hay inputs con contactos que estan compuestos por inputs con el siguiente formato: inputs de tipo text con los siguientes nombres
        //nombre1,puesto1,telefono1,email1,Whatsapp1
        //nombre2,puesto2,telefono2,email2,Whatsapp2
        //nombre3,puesto3,telefono3,email3,Whatsapp3
        //nombre4,puesto4,telefono4,email4,Whatsapp4
        //nombre5,puesto5,telefono5,email5,Whatsapp5
        //nombre6,puesto6,telefono6,email6,Whatsapp6
        //nombre7,puesto7,telefono7,email7,Whatsapp7
        //hay que juntarlos en un array y guardarlos en cada lead, solamente los que tengan nombre y puesto
        $contactos = [];

        for ($i = 1; $i <= 7; $i++) {
            if (isset($_POST["nombre" . $i]) && $_POST["nombre" . $i] != "" && isset($_POST["puesto" . $i]) && $_POST["puesto" . $i] != "") {
                $contactos[] = [
                    "nombre" => $_POST["nombre" . $i],
                    "tipo_contacto" => $_POST["puesto" . $i],
                    "telefono" => $_POST["telefono" . $i],
                    "correo" => $_POST["email" . $i],
                    "whatsapp" => $_POST["whatsapp" . $i],
                    "usuario_captura" => $usuario_id,
                    "puesto" => $_POST["puesto" . $i],
                    "id_entidad" => $entidad_id,
                    "created_at" => date("Y-m-d H:i:s")
                ];
            }
        }

        //ahora si hay que empezar a guardar los datos primero los arrchivos
        $this->load->helper("uploadfile_helper");
        $this->load->model('Archivos_model');
        $archivos = [];
        if ($actaConstitutiva["name"] != "") {
            $resultUpload = $this->carga_achivo("fileActa", $_SERVER["UPLOAD_IMAGE_PATH"]);
            if (!$resultUpload) {
                $this->responder(true, "Error al subir el archivo Acta Constitutiva", null, 400);
            }
            $archivos[] = [
                "nombre_archivo" => "Acta Constitutiva",
                "path" => $_SERVER["UPLOAD_IMAGE_PATH"] . $resultUpload["nombre_archivo"],
                "nombre" => "Acta Constitutiva",
                "tipo_archivo" => "Acta Constitutiva",
                "id_entidad" => $entidad_id,
                "extension" => $resultUpload["extension"],
                "created_at" => date("Y-m-d H:i:s"),
                "usuario_subio" => $usuario_id,
                "active" => 1,
                "nombre_archivo_subido" => $resultUpload["nombre_archivo"],
                "url" => $_SERVER["URL_RELATIVE_PATH"] . $resultUpload["nombre_archivo"],
                "extension" => $resultUpload["extension"]
            ];
        }

        if ($CaratulaEdo["name"] != "") {
            $resultUpload = $this->carga_achivo("fileCaratula", $_SERVER["UPLOAD_IMAGE_PATH"]);
            if (!$resultUpload) {
                $this->responder(true, "Error al subir el archivo Caratula del Estado", null, 400);
            }
            $archivos[] = [
                "nombre_archivo" => "Caratula del Estado",
                "path" => $_SERVER["UPLOAD_IMAGE_PATH"] . $resultUpload["nombre_archivo"],
                "nombre" => "Caratula del Estado",
                "tipo_archivo" => "Caratula del Estado",
                "id_entidad" => $entidad_id,
                "extension" => $resultUpload["extension"],
                "created_at" => date("Y-m-d H:i:s"),
                "usuario_subio" => $usuario_id,
                "active" => 1,
                "nombre_archivo_subido" => $resultUpload["nombre_archivo"],
                "url" => $_SERVER["URL_RELATIVE_PATH"] . $resultUpload["nombre_archivo"],
                "extension" => $resultUpload["extension"]
            ];
        }

        if ($comprobanteDomicilio["name"] != "") {
            $resultUpload = $this->carga_achivo("fileComprobante", $_SERVER["UPLOAD_IMAGE_PATH"]);
            if (!$resultUpload) {
                $this->responder(true, "Error al subir el archivo Comprobante de Domicilio", null, 400);
            }
            $archivos[] = [
                "nombre_archivo" => "Comprobante de Domicilio",
                "path" => $_SERVER["UPLOAD_IMAGE_PATH"] . $resultUpload["nombre_archivo"],
                "nombre" => "Comprobante de Domicilio",
                "tipo_archivo" => "Comprobante de Domicilio",
                "id_entidad" => $entidad_id,
                "extension" => $resultUpload["extension"],
                "created_at" => date("Y-m-d H:i:s"),
                "usuario_subio" => $usuario_id,
                "active" => 1,
                "nombre_archivo_subido" => $resultUpload["nombre_archivo"],
                "url" => $_SERVER["URL_RELATIVE_PATH"] . $resultUpload["nombre_archivo"],
                "extension" => $resultUpload["extension"]
            ];
        }

        if ($constanciaFiscal["name"] != "") {
            $resultUpload = $this->carga_achivo("fileConstancia", $_SERVER["UPLOAD_IMAGE_PATH"]);
            if (!$resultUpload) {
                $this->responder(true, "Error al subir el archivo Constancia Fiscal", null, 400);
            }
            $archivos[] = [
                "nombre_archivo" => "Constancia Fiscal",
                "path" => $_SERVER["UPLOAD_IMAGE_PATH"] . $resultUpload["nombre_archivo"],
                "nombre" => "Constancia Fiscal",
                "tipo_archivo" => "Constancia Fiscal",
                "id_entidad" => $entidad_id,
                "extension" => $resultUpload["extension"],
                "created_at" => date("Y-m-d H:i:s"),
                "usuario_subio" => $usuario_id,
                "active" => 1,
                "nombre_archivo_subido" => $resultUpload["nombre_archivo"],
                "url" => $_SERVER["URL_RELATIVE_PATH"] . $resultUpload["nombre_archivo"],
                "extension" => $resultUpload["extension"]
            ];
        }

        if ($identificacion["name"] != "") {
            $resultUpload = $this->carga_achivo("fileIdentificacion", $_SERVER["UPLOAD_IMAGE_PATH"]);
            if (!$resultUpload) {
                $this->responder(true, "Error al subir el archivo Identificación", null, 400);
            }
            $archivos[] = [
                "nombre_archivo" => "Identificación",
                "path" => $_SERVER["UPLOAD_IMAGE_PATH"] . $resultUpload["nombre_archivo"],
                "nombre" => "Identificación",
                "tipo_archivo" => "Identificación",
                "id_entidad" => $entidad_id,
                "extension" => $resultUpload["extension"],
                "created_at" => date("Y-m-d H:i:s"),
                "usuario_subio" => $usuario_id,
                "active" => 1,
                "nombre_archivo_subido" => $resultUpload["nombre_archivo"],
                "url" => $_SERVER["URL_RELATIVE_PATH"] . $resultUpload["nombre_archivo"],
                "extension" => $resultUpload["extension"]
            ];
        }

        if ($opinionCumplimiento["name"] != "") {
            $resultUpload = $this->carga_achivo("fileOpinion", $_SERVER["UPLOAD_IMAGE_PATH"]);
            if (!$resultUpload) {
                $this->responder(true, "Error al subir el archivo Opinión de Cumplimiento", null, 400);
            }
            $archivos[] = [
                "nombre_archivo" => "Opinión de Cumplimiento",
                "path" => $_SERVER["UPLOAD_IMAGE_PATH"] . $resultUpload["nombre_archivo"],
                "nombre" => "Opinión de Cumplimiento",
                "tipo_archivo" => "Opinión de Cumplimiento",
                "id_entidad" => $entidad_id,
                "extension" => $resultUpload["extension"],
                "created_at" => date("Y-m-d H:i:s"),
                "usuario_subio" => $usuario_id,
                "active" => 1,
                "nombre_archivo_subido" => $resultUpload["nombre_archivo"],
                "url" => $_SERVER["URL_RELATIVE_PATH"] . $resultUpload["nombre_archivo"],
                "extension" => $resultUpload["extension"]
            ];
        }

        if ($poderRepresentante["name"] != "") {
            $resultUpload = $this->carga_achivo("filePoder", $_SERVER["UPLOAD_IMAGE_PATH"]);
            if (!$resultUpload) {
                $this->responder(true, "Error al subir el archivo Poder del Representante", null, 400);
            }
            $archivos[] = [
                "nombre_archivo" => "Poder del Representante",
                "path" => $_SERVER["UPLOAD_IMAGE_PATH"] . $resultUpload["nombre_archivo"],
                "nombre" => "Poder del Representante",
                "tipo_archivo" => "Poder del Representante",
                "id_entidad" => $entidad_id,
                "extension" => $resultUpload["extension"],
                "created_at" => date("Y-m-d H:i:s"),
                "usuario_subio" => $usuario_id,
                "active" => 1,
                "nombre_archivo_subido" => $resultUpload["nombre_archivo"],
                "url" => $_SERVER["URL_RELATIVE_PATH"] . $resultUpload["nombre_archivo"],
                "extension" => $resultUpload["extension"]
            ];
        }


        if ($tarjetaCirculacion["name"] != "") {
            $resultUpload = $this->carga_achivo("fileTarjeta", $_SERVER["UPLOAD_IMAGE_PATH"]);
            if (!$resultUpload) {
                $this->responder(true, "Error al subir el archivo Tarjeta de Circulación", null, 400);
            }
            $archivos[] = [
                "nombre_archivo" => "Tarjeta de Circulación",
                "path" => $_SERVER["UPLOAD_IMAGE_PATH"] . $resultUpload["nombre_archivo"],
                "nombre" => "Tarjeta de Circulación",
                "tipo_archivo" => "Tarjeta de Circulación",
                "id_entidad" => $entidad_id,
                "extension" => $resultUpload["extension"],
                "created_at" => date("Y-m-d H:i:s"),
                "usuario_subio" => $usuario_id,
                "active" => 1,
                "nombre_archivo_subido" => $resultUpload["nombre_archivo"],
                "url" => $_SERVER["URL_RELATIVE_PATH"] . $resultUpload["nombre_archivo"],
                "extension" => $resultUpload["extension"]
            ];
        }

        if ($acuerdoTransportista["name"] != "") {
            $resultUpload = $this->carga_achivo("fileAcuerdo", $_SERVER["UPLOAD_IMAGE_PATH"]);
            if (!$resultUpload) {
                $this->responder(true, "Error al subir el archivo Acuerdo de Transportista", null, 400);
            }
            $archivos[] = [
                "nombre_archivo" => "Acuerdo de Transportista",
                "path" => $_SERVER["UPLOAD_IMAGE_PATH"] . $resultUpload["nombre_archivo"],
                "nombre" => "Acuerdo de Transportista",
                "tipo_archivo" => "Acuerdo de Transportista",
                "id_entidad" => $entidad_id,
                "extension" => $resultUpload["extension"],
                "created_at" => date("Y-m-d H:i:s"),
                "usuario_subio" => $usuario_id,
                "active" => 1,
                "nombre_archivo_subido" => $resultUpload["nombre_archivo"],
                "url" => $_SERVER["URL_RELATIVE_PATH"] . $resultUpload["nombre_archivo"],
                "extension" => $resultUpload["extension"]
            ];
        }

        $this->load->model('Comentario_model');
        $this->load->model('Contacto_model');
        $this->load->model('Archivos_model');

        foreach ($archivos as $archivo) {

            $this->Archivos_model->guardarArchivoEntidad($archivo);

            $comentario = [
                "usuario_subio" => $usuario_id,
                "comentario" => "El cliente/proveedor subió la/el " . $archivo["nombre_archivo"],
                "id_lead" => $entidad_id
            ];

            $fileInfo = [
                "nombre_archivo" => $archivo["nombre_archivo"],
                "extension" => $archivo["extension"]
            ];

            $this->Comentario_model->crearComentarioArchivoEntidad($comentario, $fileInfo);
        }
        foreach ($contactos as $contacto) {
            $this->Contacto_model->guardarContacto($contacto);

            $comentario = [
                "usuario_subio" => $usuario_id,
                "comentario" => "El cliente/proveedor subió un contacto " . $contacto["nombre"],
                "id_lead" => $entidad_id,
                "usuario_id" => $usuario_id,
                "tipocomentario" => 1,
            ];

            $this->Comentario_model->crearComentario($comentario, 0, null);
        }


        $this->Lead_model->agregarObservacion($entidad_id, json_encode($datosCaptura));

        $this->responder(false, "Cliente guardado correctamente", [], 200);
    }

    public function altaEntidadRh()
    {
        date_default_timezone_set('America/Monterrey');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(true, "Método no permitido", null, 400);
        }

        //CREAR ENTIDAD DE RH
        // //  nombre: ,
        // razon_social: ,
        // fase: fase,
        // clase_actividad: giro,
        // sitio_internet: sitiointernet,
        // ubicaciongoogle: ubicaciongoogle,
        // estimacion: estimacion,
        // tipo_entidad: tipo_entidad_Frm,
        // observaciones

        $datos_entidad = [
            "nombre" => $_POST["nombre_razon"],
            "razon_social" => $_POST["puesto"] . " - " . $_POST["ciudad"] . " - " . $_POST["utm_source"],
            "fase" => "F1 IDENTIFICADO",
            "fuente" => $_POST["utm_source"],
            "estimacion" => intval($_POST["edad"]),
            "observaciones" => "direccion: " . $_POST["calle"] .
                " " . $_POST["numExterno"] . " int: " . $_POST["numInterno"] . " colonia: " . $_POST["colonia"] . " cp: " . $_POST["cp"] . " ciudad: " . $_POST["ciudad"] . "\n fecha nacimiento: " . $_POST["fechaNacimiento"],
            "clase_actividad" => $_POST["puesto"] . " - " . $_POST["ciudad"],
            "tipo_entidad" => 3,
            "fecha_creacion" => date("Y-m-d H:i:s"),
            "fecha_modificacion" => date("Y-m-d H:i:s"),
            "usuario_creo" => 1,
            "id_reclutador" => 1,
            "ubicaciongoogle" => $_POST["rfc"],
            "sitio_internet" => $_POST["pretencionSalarial"],
        ];

        $entidad = $this->Lead_model->crearOrUpdateLead($datos_entidad);
        $entidad_id = $entidad["id"];


        echo "entidad_id: " . $entidad_id;

        $CurriculumVitae = $_FILES["fileCv"];
        $IdentificacionOficial = $_FILES["fileIdentificacionOficial"];


        $datosCapturaNuevaEntidadRH = [];


        $this->load->helper("uploadfile_helper");

        $this->load->model('Archivos_model');

        $archivos = [];

        if ($CurriculumVitae["name"] != "") {
            $resultUpload = $this->carga_achivo("fileCv", $_SERVER["UPLOAD_IMAGE_PATH"]);
            if (!$resultUpload) {
                $this->responder(true, "Error al subir el archivo Curriculum Vitae", null, 400);
            }
            $archivos[] = [
                "nombre_archivo" => "Curriculum Vitae",
                "path" => $_SERVER["UPLOAD_IMAGE_PATH"] . $resultUpload["nombre_archivo"],
                "nombre" => "Curriculum Vitae",
                "tipo_archivo" => "Curriculum Vitae",
                "id_entidad" => $entidad_id,
                "extension" => $resultUpload["extension"],
                "created_at" => date("Y-m-d H:i:s"),
                "usuario_subio" => 1,
                "active" => 1,
                "nombre_archivo_subido" => $resultUpload["nombre_archivo"],
                "url" => $_SERVER["URL_RELATIVE_PATH"] . $resultUpload["nombre_archivo"],
                "extension" => $resultUpload["extension"]
            ];
        }

        if ($IdentificacionOficial["name"] != "") {
            $resultUpload = $this->carga_achivo("fileIdentificacionOficial", $_SERVER["UPLOAD_IMAGE_PATH"]);
            if (!$resultUpload) {
                $this->responder(true, "Error al subir el archivo Identificación Oficial", null, 400);
            }
            $archivos[] = [
                "nombre_archivo" => "Identificación Oficial",
                "path" => $_SERVER["UPLOAD_IMAGE_PATH"] . $resultUpload["nombre_archivo"],
                "nombre" => "Identificación Oficial",
                "tipo_archivo" => "Identificación Oficial",
                "id_entidad" => $entidad_id,
                "extension" => $resultUpload["extension"],
                "created_at" => date("Y-m-d H:i:s"),
                "usuario_subio" => 1,
                "active" => 1,
                "nombre_archivo_subido" => $resultUpload["nombre_archivo"],
                "url" => $_SERVER["URL_RELATIVE_PATH"] . $resultUpload["nombre_archivo"],
                "extension" => $resultUpload["extension"]
            ];
        }


        $this->load->model('Comentario_model');
        $this->load->model('Contacto_model');
        $this->load->model('Archivos_model');

        foreach ($archivos as $archivo) {

            $this->Archivos_model->guardarArchivoEntidad($archivo);

            $comentario = [
                "usuario_subio" => 1,
                "comentario" => "El cliente/proveedor subió la/el " . $archivo["nombre_archivo"],
                "id_lead" => $entidad_id
            ];

            $fileInfo = [
                "nombre_archivo" => $archivo["nombre_archivo"],
                "extension" => $archivo["extension"]
            ];

            $this->Comentario_model->crearComentarioArchivoEntidad($comentario, $fileInfo);
        }

        ///

        $contactos = [];

        for ($i = 1; $i <= 7; $i++) {
            if (isset($_POST["nombre" . $i]) && $_POST["nombre" . $i] != "" && isset($_POST["puesto" . $i]) && $_POST["puesto" . $i] != "") {
                $contactos[] = [
                    "nombre" => $_POST["nombre" . $i],
                    "tipo_contacto" => $_POST["puesto" . $i],
                    "telefono" => $_POST["telefono" . $i],
                    "correo" => $_POST["email" . $i],
                    "whatsapp" => $_POST["whatsapp" . $i],
                    "usuario_captura" => 1,
                    "puesto" => $_POST["puesto" . $i],
                    "id_entidad" => $entidad_id,
                    "created_at" => date("Y-m-d H:i:s")
                ];
            }
        }


        foreach ($contactos as $contacto) {
            $this->Contacto_model->guardarContacto($contacto);

            $comentario = [
                "usuario_subio" => 1,
                "comentario" => "El cliente/proveedor subió un contacto " . $contacto["nombre"],
                "id_lead" => $entidad_id,
                "usuario_id" => 1,
                "tipocomentario" => 1,
            ];

            $this->Comentario_model->crearComentario($comentario, 0, null);
        }


        $guardar_contacto_lead = [
            "nombre" => $_POST["nombre_razon"],
            "tipo_contacto" => "aplicante",
            "telefono" => $_POST["telefono"],
            "correo" => $_POST["email"],
            "usuario_captura" => 1,
            "puesto" => "aplicante",
            "id_entidad" => $entidad_id,
            "created_at" => date("Y-m-d H:i:s")
        ];

        $this->Contacto_model->guardarContacto($guardar_contacto_lead);

        $comentario = [
            "usuario_subio" => 1,
            "comentario" => "El cliente/proveedor subió un contacto " . $_POST["nombre_razon"],
            "id_lead" => $entidad_id,
            "usuario_id" => 1,
            "tipocomentario" => 1,
        ];

        $this->Comentario_model->crearComentario($comentario, 0, null);

        //$_POST["experiencia_telemarketing"], $_POST["experiencia_logistica"],  $_POST["experiencia_ventas"],$_POST["experiencia_equipos"],
        //guardar como comentarios
        $comentario = [
            "usuario_subio" => 1,
            "comentario" => "Experiencia en telemarketing: " . $_POST["experiencia_telemarketing"] . "\n Experiencia en logística: " . $_POST["experiencia_logistica"] . "\n Experiencia en ventas: " . $_POST["experiencia_ventas"] . "\n Experiencia en equipos: " . $_POST["experiencia_equipos"],
            "id_lead" => $entidad_id,
            "usuario_id" => 1,
            "tipocomentario" => 1,
        ];

        $this->Comentario_model->crearComentario($comentario, 0, null);

        $this->responder(false, "Cliente guardado correctamente", [], 200);
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

        if ($fase != 'F5 GANADO' && !validarRol($roles, ['Admin', 'Jefe comercial', 'Vendedor', 'comercial', 'Compras'])) {
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
            "usuarios" => $this->Usuario_model->getUsuarioPorTipoEntidad($lead["tipo_entidad"]),
            "usuariosComplemento" => $this->Usuario_model->getUsuariosComplemento($lead["tipo_entidad"]),
            "usuariosMesaControl" => $this->Usuario_model->getUsuariosMesaControl($lead["tipo_entidad"]),
            "archivos" => $this->Lead_model->getArchivos($_GET["lead_id"]),
            "actividades" => $this->Lead_model->getActividades($_GET["lead_id"])
        ];
        $this->responder(false, "", $data, 200);
    }

    public function actualizarLead()
    {
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

    public function guardarContacto()
    {
        $this->load->model('Contacto_model');
        $idContacto =  $this->Contacto_model->guardarContacto($this->body);
        //crear comentario
        $this->load->model('Comentario_model');
        $comentarioText = "Agregó el contacto con id " . $idContacto . " de Nombre:" . $this->body["nombre"] . "; ";
        if (isset($this->body["telefono"]) && $this->body["telefono"] != "") $comentarioText .= " Teléfono: " . $this->body["telefono"] . "; ";
        if (isset($this->body["correo"]) && $this->body["correo"] != "") $comentarioText .= " Correo: " . $this->body["correo"] . "; ";
        if (isset($this->body["puesto"]) && $this->body["puesto"] != "") $comentarioText .= " Puesto: " . $this->body["puesto"] . "; ";
        if (isset($this->body["whatsapp"]) && $this->body["whatsapp"] != "") $comentarioText .= " Whatsapp: " . $this->body["whatsapp"] . "; ";

        $comentario = [
            "usuario_id" => $this->body["usuario_captura"],
            "comentario" => $comentarioText,
            "id_lead" => $this->body["id_entidad"],
            "tipocomentario" => 1,
        ];

        $this->Comentario_model->crearComentario($comentario, 0, null);

        $this->responder(false, "Contacto guardado correctamente", ["contacto_id" => $idContacto], 200);
    }

    public function actualizarContacto()
    {
        $this->load->model('Contacto_model');
        $this->Contacto_model->actualizarContacto($this->body);
        //crear comentario
        $this->load->model('Comentario_model');
        $comentarioText = "Actualizó el contacto con id " . $this->body["id"] . " de Nombre: " . $this->body["nombre"] . "; ";
        if (isset($this->body["telefono"]) && $this->body["telefono"] != "") $comentarioText .= " Teléfono: " . $this->body["telefono"] . "; ";
        if (isset($this->body["correo"]) && $this->body["correo"] != "") $comentarioText .= " Correo: " . $this->body["correo"] . "; ";
        if (isset($this->body["puesto"]) && $this->body["puesto"] != "") $comentarioText .= " Puesto: " . $this->body["puesto"] . "; ";
        if (isset($this->body["whatsapp"]) && $this->body["whatsapp"] != "") $comentarioText .= " Empresa: " . $this->body["whatsapp"] . "; ";

        $comentario = [
            "usuario_id" => $this->body["usuario_captura"],
            "comentario" => $comentarioText,
            "id_lead" => $this->body["id_entidad"],
            "tipocomentario" => 1,
        ];

        $this->Comentario_model->crearComentario($comentario, 0, null);

        $this->responder(false, "Contacto actualizado correctamente", ["contacto_id" => $this->body["id"]], 200);
    }
    public function guardarActividad()
    {
        $this->load->model("Lead_model");
        $actividad = $this->Lead_model->guardarActividad($this->body);

        $this->load->model('Comentario_model');
        $comentario = [
            "usuario_id" => $actividad["usuario_captura"],
            "comentario" => "Agregó la actividad " . $actividad["tipo_actividad"] . " para el día y hora " . $actividad["fecha_actividad"],
            "id_lead" => $actividad["id_entidad"],
            "tipocomentario" => 1,
        ];

        $this->load->model("Comentario_model");
        $this->Comentario_model->crearComentario($comentario, 0, null);
        $this->responder(false, "Actividad guardada correctamente", $actividad, 200);
    }

    public function uploadProfilePictura()
    {
        try {
            $lead_id = $_POST["lead_id"];
            $usuario_subio = $_POST["usuario_subio"];
            //image in base64
            $image = $_POST["image"];
            //get Tipo Image
            $tipo_imagen = explode(";", $image)[0];
            $tipo_imagen = explode("/", $tipo_imagen)[1];
            //determinar extension
            $extension = "";
            switch ($tipo_imagen) {
                case 'png':
                    $extension = "png";
                    break;
                case 'jpeg':
                    $extension = "jpeg";
                    break;
                case 'jpg':
                    $extension = "jpg";
                    break;
                default:
                    $extension = "png";
                    break;
            }
            $image = str_replace('data:image/' . $tipo_imagen . ';base64,', '', $image);
            $image = str_replace(' ', '+', $image);
            //decode image
            $data = base64_decode($image);
            //name image
            $name = 'profile_' . $lead_id . '_' . time() . '.png';
            //path image
            $path = $_SERVER["UPLOAD_IMAGE_PATH"] . $name;
            //save image
            file_put_contents($path, $data);
            //update lead
            $this->Lead_model->crearOrUpdateLead(["id" => $lead_id, "img_perfil" => $name, "fecha_modificacion" => date("Y-m-d H:i:s")]);
            //crear comentario
            $this->load->model('Comentario_model');
            $comentario = [
                "usuario_id" => $usuario_subio,
                "comentario" => "Actualizó la foto de perfil",
                "id_lead" => $lead_id,
                "tipocomentario" => 6
            ];

            $fileInfo = [
                "nombre_archivo" => $name,
                "extension" => $extension
            ];
            $this->Comentario_model->crearComentario($comentario, 1, $fileInfo);

            $this->responder(false, "Foto de perfil actualizada correctamente", ["nombre" =>  $name], 200);
        } catch (\Throwable $th) {
            $this->responder(true, "Error al actualizar la foto de perfil", ["backtrace" => $th->getMessage()], 400);
        }
    }
}
