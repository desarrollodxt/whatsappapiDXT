<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bitacora extends CI_Controller
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
        $this->load->model('Bitacora_model');
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

    public function getBitacora($cv)
    {
        $bitacora = $this->Bitacora_model->getBitacora($cv);
        $this->responder(false, "",  $bitacora, 200);
    }

    public function updateCuentaEspejo($cv)
    {
        $this->load->model("Bitacora_model");

        if ($this->body["cuenta_espejo_url"] == "") {
            $this->responder(true, "Debes enviar una cuenta espejo", null, 400);
        }


        $this->Bitacora_model->updateCuentaEspejo($cv, $this->body);
        $this->responder(false, "",  null, 200);
    }

    public function getCvsActivos()
    {
        $this->load->model('Usuario_model');
        if (!isset($_GET["usuario"])) {
            $this->responder(false, "Debes enviar un usuarios",  [], 400);
        }

        $id_usuario = $_GET["usuario"];
        $usuario = $this->Usuario_model->getUsuario($id_usuario);

        // $this->responder(false, "",  $usuario, 200);

        if (empty($usuario)) {
            $this->responder(false, "El usuario no existe",  [], 400);
        }
        // $this->responder(false, "",  $this->Usuario_model->getUsuario($id_usuario), 200);


        $planner = null;
        if (validarRol($usuario["roles"], ["Planner"])) {
            $planner = $usuario["usuario_rainde"];
        }


        $bitacora = $this->Bitacora_model->getBitacoraMovimientosActivos($usuario["roles"], $usuario["id"], $planner);

        $this->responder(false, "",  $bitacora, 200);
    }

    public function getCvsActivosTest()
    {
        $this->load->model('Usuario_model');
        if (!isset($_GET["usuario"])) {
            $this->responder(false, "Debes enviar un usuarios",  [], 400);
        }

        $id_usuario = $_GET["usuario"];
        $usuario = $this->Usuario_model->getUsuario($id_usuario);

        // $this->responder(false, "",  $usuario, 200);

        if (empty($usuario)) {
            $this->responder(false, "El usuario no existe",  [], 400);
        }
        // $this->responder(false, "",  $this->Usuario_model->getUsuario($id_usuario), 200);


        $planner = null;
        if (validarRol($usuario["roles"], ["Planner"])) {
            $planner = $usuario["usuario_rainde"];
        }

        $bitacora = $this->Bitacora_model->getBitacoraMovimientosActivos($planner);
        $this->responder(false, "",  $bitacora, 200);
    }

    public function generarCvOnePage($cv)
    {
        require_once(__DIR__ . '/../third_party/fpdf-easytable-master/easyTable.php');
        $bitacora = $this->Bitacora_model->getBitacora($cv);
        $encabezado = $bitacora["encabezado"];
        $movimientos = $bitacora["movimientos"];

        // $this->responder(false, __DIR__,  $bitacora, 200);
        ob_start();
        $this->load->library('generatepdf_library');
        $this->generatepdf_library->AliasNbPages();
        $this->generatepdf_library->AddPage();

        $this->generatepdf_library->SetFont('Arial', 'B', 10);
        $this->generatepdf_library->Cell(90, 7, utf8_decode('Datos del viaje'));
        $this->generatepdf_library->Ln();
        // 
        $this->generatepdf_library->SetFont('Arial', '', 10);
        $this->generatepdf_library->Cell(90, 7, utf8_decode('Cliente: ' . $encabezado["cliente_solicitud"]));
        $this->generatepdf_library->Ln();

        $this->generatepdf_library->Cell(90, 7, utf8_decode('Ruta: ' . $encabezado["orig_dest_solicitud"]));
        $this->generatepdf_library->Ln();

        $this->generatepdf_library->Cell(90, 7, utf8_decode('Referencia cliente: ' . $encabezado["referencia"]));
        $this->generatepdf_library->Ln();

        $this->generatepdf_library->Cell(90, 7, utf8_decode('Referencia DXT: ' . $encabezado["cv"]));
        $this->generatepdf_library->Ln();

        $this->generatepdf_library->SetFont('Arial', 'B', 10);
        $this->generatepdf_library->Cell(90, 7, utf8_decode('Datos de la unidad:'));
        $this->generatepdf_library->Ln();
        $this->generatepdf_library->SetFont('Arial', '', 10);
        $this->generatepdf_library->Cell(20, 7, utf8_decode('Placas Tracto: ' . $encabezado["placas_tracto"]), 0);
        $this->generatepdf_library->Cell(20, 7, '', 0);
        $this->generatepdf_library->Cell(30, 7, utf8_decode('Placas Remolque: ' . $encabezado["placas_remolque"]), 0);
        $this->generatepdf_library->Ln();
        $this->generatepdf_library->Cell(90, 7, utf8_decode('Operador: ' . $encabezado["operador"]));
        $this->generatepdf_library->Ln();

        $this->generatepdf_library->SetFont('Arial', 'B', 10);
        $this->generatepdf_library->Cell(90, 7, utf8_decode('Fechas:'));
        $this->generatepdf_library->Ln();
        $this->generatepdf_library->SetFont('Arial', '', 10);
        $this->generatepdf_library->Cell(90, 7, utf8_decode('Fecha carga solicitud: ' . $encabezado["fecha_carga"]), 0);
        $this->generatepdf_library->Cell(5, 7, '', 0);
        $this->generatepdf_library->Cell(90, 7, utf8_decode('Fecha de carga: ' . $encabezado["fecha_carga_real"]), 0);
        $this->generatepdf_library->Ln();
        $this->generatepdf_library->Cell(90, 7, utf8_decode('Fecha descarga solicitud: ' . $encabezado["fecha_descarga"]), 0);
        $this->generatepdf_library->Cell(5, 7, '', 0);
        $this->generatepdf_library->Cell(90, 7, utf8_decode('Fecha descarga: ' . $encabezado["fecha_descarga_real"]), 0);
        $this->generatepdf_library->Ln();
        $this->generatepdf_library->Ln();

        $this->generatepdf_library->SetFont('Arial', 'B', 19);
        $this->generatepdf_library->Cell(90, 7, utf8_decode('Movimientos'), 0, 'C');
        $this->generatepdf_library->Ln();
        $this->generatepdf_library->Ln();
        $this->generatepdf_library->SetFont('Arial', '', 10);

        $w = array(30, 30, 40, 90);
        $headers = ["Fecha", "Ubicación", "Estatus", "Observación"];

        $table = new easyTable($this->generatepdf_library, 4, 'border:1;font-size:12;');

        //fill header table
        $table->rowStyle('align:{CC};valign:M;bgcolor:#EA7427;font-style:B;');
        for ($i = 0; $i < count($headers); $i++) {
            $table->easyCell(utf8_decode($headers[$i]), 'valign:M;bgcolor:#EA7427;font-style:B,color:#fff;');
        }

        $table->printRow();

        $table->rowStyle('align:{CC};valign:M;');
        $this->generatepdf_library->SetFont('Arial', '', 10);
        $movimientos = array_reverse($movimientos);
        foreach ($movimientos as $movimiento) {
            $table->easyCell(utf8_decode($movimiento["fecha_movimiento"]), 'valign:M;');
            $table->easyCell(utf8_decode($movimiento["ubicacion"]), 'valign:M;');
            $table->easyCell(utf8_decode($movimiento["estatus_nombre"]), 'valign:M;');
            $table->easyCell(utf8_decode(strtolower($movimiento["observacion"])), 'valign:M;');
            $table->printRow();

            if ($movimiento["archivos_relacionados"] != null) {
                $archivos = explode(", ", $movimiento["archivos_relacionados"]);
                $count = 0;
                foreach ($archivos as $archivo) {
                    $table->easyCell('', 'img:/home/u613393165/domains/gcsmatrix.com/public_html/dxt/' . $archivo . ', w100;');
                    $count++;
                    if ($count == 4) {
                        $count = 0;
                        $table->rowStyle('border:0;');
                        $table->printRow();
                    }
                }
                $table->rowStyle('border:0;');
                $table->printRow();
            }

            $table->rowStyle('border:1;');
        }



        $table->endTable(4);
        ob_end_clean();
        header("Content-type:application/pdf");
        $fechaUTCMAZATLAN = new DateTimeZone('America/Mazatlan');
        $fecha = new DateTime('now', $fechaUTCMAZATLAN);
        $this->generatepdf_library->Output('D', 'Reporte_viaje_' . $encabezado["cv"]  . '_' . $fecha->format('Y-m-d_H-i-s') . '.pdf');
    }


    public function nuevo_movimiento($cv)
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->responder(true, "Error en la peticion", null, 400);
        }

        $datos = $_POST;

        $fechaMovimiento = $datos["fechaMovimiento"];
        $horaMovimiento = $datos["horaMovimiento"];


        $estatus = $datos["estatuscv"];
        $observaciones = $datos["observacion"];
        $coordenadas = $datos["coordenadas"];

        $ubicacion = $datos["ubicacion"];

        if ($estatus == '') {
            $this->responder(true, "Debes seleccionar un estatus", null, 200);
        }

        if ($observaciones == '') {
            $this->responder(true, "Debes agregar una observación", null, 200);
        }

        if ($fechaMovimiento == '' || $horaMovimiento == '') {

            $this->responder(true, "Debes agregar una fecha y hora de movimiento", null, 200);
        }

        $fechaHoraMovimiento = $fechaMovimiento . " " . $horaMovimiento . ":00";


        $evidencias = [];
        for ($i = 0; $i < count($_FILES["evidencia"]["name"]); $i++) {
            $evidencias[] = [
                "name" => $_FILES["evidencia"]["name"][$i],
                "type" => $_FILES["evidencia"]["type"][$i],
                "tmp_name" => $_FILES["evidencia"]["tmp_name"][$i],
                "error" => $_FILES["evidencia"]["error"][$i],
                "size" => $_FILES["evidencia"]["size"][$i],
            ];
        }

        $usuario = $_POST["usuario"];

        $id_ln = $this->Bitacora_model->setMovimientoNuevo($cv, $usuario, $estatus, $observaciones, $coordenadas, $ubicacion, $fechaHoraMovimiento);
        $this->load->helper('uploadfile_helper');

        if ($evidencias[0]["name"] != "") {
            foreach ($evidencias as $evidencia) {
                $nombre_archivo = $this->carga_achivo($evidencia, "/home/u613393165/domains/gcsmatrix.com/public_html/dxt/vista/bitacora/", '');
                // $type = "evidencia_movimiento", $bitacora_hd = null, $bitacora_ln = null
                $this->Bitacora_model->setEvidencia($nombre_archivo["nombre_archivo"], $nombre_archivo["extension"], "/vista/bitacora/", "evidencia_movimiento", null, $id_ln);
            }
        }


        if ($_FILES["capturagps"]["name"] != "") {
            $nombre_archivo = $this->carga_achivo($_FILES["capturagps"], "/home/u613393165/domains/gcsmatrix.com/public_html/dxt/vista/bitacora/", '');
            $this->Bitacora_model->setEvidencia($nombre_archivo["nombre_archivo"], $nombre_archivo["extension"], "/vista/bitacora/", "captura_gps", null, $id_ln);
        }


        $this->responder(false, "",  $id_ln, 200);
    }


    private function carga_achivo($file, $carpeta, $permitidos = '')
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

        if (is_uploaded_file($file['tmp_name'])) {
            // Extrae datos de archivo
            $f_nombre = $file['name'];


            $f_temporal = $file['tmp_name'];
            $f_tipo = $file['type'];
            $f_peso = $file['size'];

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

    public function saveContacto($cv)
    {
        $this->load->model("Bitacora_model");
        $this->Bitacora_model->saveContacto($cv, $this->body);

        $contactos = $this->Bitacora_model->getContactos($cv);
        $this->responder(false, "",   $contactos, 200);
    }
}
