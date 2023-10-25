<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cotizaciones extends CI_Controller
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
        $this->load->model('Cotizacion_model');
        $_body = file_get_contents('php://input');
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

    public function solicitarCostos()
    {
        if (
            empty($this->body)
        ) {
            $this->responder(true, "Debes enviar datos", null, 400);
        }
        if (!isset($this->body["cliente"])) {
            $this->responder(true, "No se encontro el lead", null, 400);
        }
        $rutas = $this->body["rutas"];
        $cliente = $this->body["cliente"];
        $id_usuario = $this->body["id_usuario"];
        $solicitarCostos = $this->body["solicitarCostos"];
        $this->load->model("Cotizacion_model");
        $this->load->model("Rutas_model");



        $rutasToGetCost = [];
        foreach ($rutas as $i => $ruta) {
            $origen = $ruta["origen"];
            $destino = $ruta["destino"];

            $rutasToGetCost[$i]["rutaDetalle"] = $this->Rutas_model->obtener_ruta_cliente($origen, $destino, 0, $cliente)[0];
            $rutasToGetCost[$i]["targetVenta"] = $ruta["targetVenta"];
            $rutasToGetCost[$i]["tipoCarga"] = $ruta["tipoCarga"];
            $rutasToGetCost[$i]["producto"] = $ruta["tipoCargaText"];
            $rutasToGetCost[$i]["volumexsemana"] = $ruta["volumexsemana"];
            $rutasToGetCost[$i]["requisitos"] = implode(",", $ruta["especificaciones"]);

            $rutasToGetCost[$i]["observaciones"] = $ruta["observaciones"];
            $rutasToGetCost[$i]["fecha_creacion"] = date("Y-m-d H:i:s");
            $rutasToGetCost[$i]["usuario_solicita"] = $id_usuario;

            $rutasToGetCost[$i]["peso"] = $ruta["peso"];

            $rutasToGetCost[$i]["origen"] = $ruta["origenText"];
            $rutasToGetCost[$i]["destino"] = $ruta["destinoText"];


            $rutasToGetCost[$i]["tipoUnidad"] = $ruta["tipoUnidad"];
            $rutasToGetCost[$i]["tipoUnidadText"] = $ruta["tipoUnidadText"];
            $rutasToGetCost[$i]["especificacionUnidad"] = $ruta["especificacionUnidad"];
            $rutasToGetCost[$i]["especificacionUnidadText"] = $ruta["especificacionUnidadText"];
            $rutasToGetCost[$i]["cliente"] = $cliente;
            $rutasToGetCost[$i]["solicitarCostos"] = $solicitarCostos;
        }

        // var_dump($rutasToGetCost);
        // exit;
        $result = $this->Cotizacion_model->solicitadCostos($cliente, $rutasToGetCost, $id_usuario);

        if (!$result) {
            $this->responder(true, "Error al solicitar costos", null, 400);
        }

        $this->responder(false, "Solicitado con éxito", $rutasToGetCost);
    }


    public function altaCotizacion()
    {
        if (empty($this->body)) {
            $this->responder(true, "Debes enviar datos", null, 400);
        }

        $this->Cotizacion_model->altaCotizacion($this->body);



        $this->responder(false, "Cotizacion dada de alta", null, 200);
    }

    public function guardarCosto()
    {
        $body = $_POST;

        $this->Cotizacion_model->guardarCosto($body);
        $this->responder(false, "Costo guardado", null, 200);
    }

    public function guardarCotizacionLn()
    {
        $body = $this->body;
        $this->Cotizacion_model->guardarCotizacionLn($body);

        $this->responder(false, "Cotizacion ruta guardada", null, 200);
    }

    public function generarExportCotizacion()
    {

        $cotizaciones = $this->body["cotizaciones"];
        $destinatario = $this->body["destinatario"];
        $puestodestinatario = $this->body["puestoDestinatario"];
        $incluye = $this->body["incluye"];
        $noincluye = $this->body["noincluye"];
        $terminosycond = $this->body["terminosycond"];
        $usuario = $this->body["usuario"];
        $fecha = $this->body["cliente"];
        //in array de cotizaciones, cada elemento del array es el id de la cotizacion_ln si alguno de los id que incluyó, no tiene tarifa, regresar error
        $CotizacionesInfo = $this->Cotizacion_model->getCotizaciones($cotizaciones);
        if ($CotizacionesInfo == false) {
            $this->responder(true, "No puedes generar una cotización con una ruta sin precio de venta", null, 400);
        }

        // dd($cotizacion);
        $cotizacionD = $CotizacionesInfo[0]["lead_id"];
        require_once(__DIR__ . '/../third_party/fpdf-easytable-master/easyTable.php');
        ob_start();
        $this->load->library('generatepdf_library', ['cotizacion']);
        $this->generatepdf_library->AliasNbPages();
        $this->generatepdf_library->AddPage();
        $this->generatepdf_library->SetMargins(20, 20, 20);
        $this->generatepdf_library->SetFont('Arial', '', 8);
        $this->generatepdf_library->Cell(0, 7, utf8_decode("San Pedro Garza García N.L. a " . date("d") . " de " . $this->getMes(date("m")) . " de " . date("Y")), 0, 0, "R");
        $this->generatepdf_library->ln();
        $this->generatepdf_library->SetFont('Arial', '', 12);
        $this->generatepdf_library->Cell(90, 7, utf8_decode($destinatario));
        $this->generatepdf_library->ln();
        $this->generatepdf_library->Cell(90, 7, utf8_decode($puestodestinatario));
        $this->generatepdf_library->ln();
        $this->generatepdf_library->ln();
        $this->generatepdf_library->MultiCell(0, 6, utf8_decode("     Es un placer saludarte. A través de la presente, te envío nuestra cotización para las rutas previamente mencionadas."));
        $this->generatepdf_library->ln();

        $table = new easyTable($this->generatepdf_library, 4, 'border:1;font-size:12;');
        $table->rowStyle('align:{CC};valign:M;bgcolor:#d6dce4;');
        $table->easyCell(utf8_decode("Tipo de unidad"), 'valign:M;bgcolor:#d6dce4');
        $table->easyCell(utf8_decode("Origen"), 'valign:M;bgcolor:#d6dce4');
        $table->easyCell(utf8_decode("Destino"), 'valign:M;bgcolor:#d6dce4');
        $table->easyCell(utf8_decode("Tarifa"), 'valign:M;bgcolor:#d6dce4');
        $table->printRow();
        foreach ($CotizacionesInfo  as $ruta) {
            $table->rowStyle('align:{CC};valign:M;');
            $table->easyCell(utf8_decode($ruta["tipo_unidad"] . " - " . $ruta["caracteristica_unidad"]));
            $table->easyCell(utf8_decode($ruta["origen"]));
            $table->easyCell(utf8_decode($ruta["destino"]));
            $table->easyCell(utf8_decode('$' .  number_format($ruta["tarifa"], 2)));
            $table->printRow();
        }
        $table->endTable(9);


        $this->generatepdf_library->SetFont('Arial', '', 10);
        $this->generatepdf_library->Cell(90, 6, utf8_decode("Incluye:"));
        $this->generatepdf_library->ln();
        foreach ($incluye as $item) {
            $this->generatepdf_library->FancyBullet($item);
        }
        $this->generatepdf_library->ln();

        $this->generatepdf_library->Cell(90, 6, utf8_decode("No incluye:"));
        $this->generatepdf_library->ln();
        foreach ($noincluye as $item) {
            $this->generatepdf_library->FancyBullet($item);
        }
        $this->generatepdf_library->ln();
        $this->generatepdf_library->Cell(90, 6, utf8_decode("Términos y condiciones:"));
        $this->generatepdf_library->ln();
        foreach ($terminosycond as $item) {
            $this->generatepdf_library->FancyBullet($item);
        }
        $this->generatepdf_library->ln();
        $this->generatepdf_library->ln();

        $this->generatepdf_library->SetFont('Arial', '', 12);
        $this->generatepdf_library->MultiCell(0, 6, utf8_decode("Quedamos a tu disposición para cualquier consulta o aclaración que requieras. Esperamos que esta cotización sea de tu interés y estamos listos para atender cualquier requerimiento adicional que tengas."));
        $this->generatepdf_library->ln();
        $this->generatepdf_library->ln();
        //texto alineado al centro de la hoja$this->generatepdf_library->Cell(90, 7, utf8_decode($cotizacionD["nombre_completo"]));
        $this->generatepdf_library->Cell(0, 7, utf8_decode("Luis Daniel Mendoza Rodríguez"), 0, 0);
        $this->generatepdf_library->ln();
        $this->generatepdf_library->Cell(0, 7, utf8_decode("Agente de ventas"), 0, 0);



        ob_end_clean();

        $fecha = new DateTime('now');
        $nombreFile = 'Cotizacionz_' . $fecha->format('Y-m-d_H-i-s') . '.pdf';

        $res = $this->generatepdf_library->Output('F', '/home/u613393165/domains/gcsmatrix.com/public_html/dxt/vista/fotos/' . $nombreFile);
        $this->load->model("Comentario_model");
        $this->Comentario_model->guardarComentarioCotizacion($nombreFile, $destinatario, $cotizacionD, $usuario);
        $this->responder(false, "Cotizacion generada", $res, 200);
    }

    public function solicitarCostosToggle()
    {
        $id_cotizacion = $this->body["cotizacion_id"];
        $solicitarCostos = $this->body["solicitarCostos"];
        $this->Cotizacion_model->solicitarCostosToggle($id_cotizacion, $solicitarCostos);
        $this->responder(false, "Solicitud de costos actualizada", null, 200);
    }

    public function getMes($mes)
    {
        switch ($mes) {
            case 1:
                return "enero";
                break;
            case 2:
                return "febrero";
                break;
            case 3:
                return "marzo";
                break;
            case 4:
                return "abril";
                break;
            case 5:
                return "mayo";
                break;
            case 6:
                return "junio";
                break;
            case 7:
                return "julio";
                break;
            case 8:
                return "agosto";
                break;
            case 9:
                return "septiembre";
                break;
            case 10:
                return "octubre";
                break;
            case 11:
                return "noviembre";
                break;
            case 12:
                return "diciembre";
            default:
                return "";
                break;
        }
    }
}
