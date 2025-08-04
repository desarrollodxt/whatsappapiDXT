<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Tractores extends CI_Controller
{
    public $body = [];

    public $BASE_URL_API_SAMSARA = "https://api.samsara.com/";


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
        /*
        Cargar modelos 
        */
        $this->load->model('Trailers_model');
        $this->load->model("Geocerca_model");
        // Cargar modelos

        //obtener y procesar el body JSON
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
    public function index()
    {
    }


    public function determinarEstatusTractor()
    {
        //mazatlan
        date_default_timezone_set('America/Mazatlan');
        $this->load->model("Tractor_model");
        $response = $this->peticionTrailersSamsara();
        $body = $response['data'];

        foreach ($body as $samsaraInfoTracto) {
            $estatus = [
                "estatus" => null,
                "tractor_id" => null,
                "tractor" => null,
                "caja_actual" => null,
                "operador" => null,
                "operador_team" => null,
                "ubicacion_direccion" => null,
                "coordenadas" => null,
                "fecha_actualizacion" => null,
                "pedido_llc" => null,
                "fecha_inicio_ruta" => null,
                "eta" => null,
                "id_samsara" => null,
            ];
            $tractor = $this->Tractor_model->getTractorByExternalId($samsaraInfoTracto['id']);
            if (empty($tractor)) {
                continue;
            }

            $estatus["id_samsara"] = $samsaraInfoTracto['id'];
            $estatus["tractor_id"] = $tractor['idUnidad'];
            $estatus["tractor"] = $tractor['economico'];
            $estatus["ubicacion_direccion"] = $samsaraInfoTracto['gps']["reverseGeo"]["formattedLocation"];


            $determinarUbicacion = $this->Geocerca_model->determinarUbicacion($samsaraInfoTracto['gps']['latitude'], $samsaraInfoTracto['gps']['longitude']);
            if ($determinarUbicacion == 'Patio') {
                $estatus["estatus"] = 'En patio';
                $estatus["coordenadas"] = $samsaraInfoTracto['gps']['latitude'] . "," . $samsaraInfoTracto['gps']['longitude'];
                $estatus["fecha_actualizacion"] = date("Y-m-d H:i:s");
            } else {
                //buscar bitacora activa
                $bitacora = $this->Tractor_model->getBitacoraActiva($tractor['idUnidad']);

                if (empty($bitacora)) {
                    $estatus["estatus"] = 'Sin pedido';
                    $estatus["coordenadas"] = $samsaraInfoTracto['gps']['latitude'] . "," . $samsaraInfoTracto['gps']['longitude'];
                    $estatus["fecha_actualizacion"] = date("Y-m-d H:i:s");
                } else {
                    $estatus["estatus"] = 'En ruta';
                    $estatus["pedido_llc"] = $bitacora['folio_pedido_usa'];
                    $estatus["fecha_inicio_ruta"] = $bitacora['fecha_inicio_ruta'];
                    $estatus["coordenadas"] = $samsaraInfoTracto['gps']['latitude'] . "," . $samsaraInfoTracto['gps']['longitude'];
                    $estatus["fecha_actualizacion"] = date("Y-m-d H:i:s");
                    $estatus["caja_actual"] = $bitacora['remolque_1_no_economico'];
                    $estatus["operador"] = $bitacora['operador_titular_nombre'];
                    $estatus["operador_team"] = $bitacora['operador_team_nombre'];
                }
            }

            //demas estados




            $estatus_actual = $this->Tractor_model->getEstatusTractorBySamsaraId($samsaraInfoTracto['id']);

            if (empty($estatus_actual)) {
                $this->Tractor_model->insertarEstatus($estatus);
            } else {
                $estado["fecha_actualizacion"] = date("Y-m-d H:i:s");
                $this->Tractor_model->actualizarEstatus($estatus, $estatus_actual);
            }

            //pasar $estatus por referencia
            $this->limpiarEstatus($estatus);
        }
    }

    public function limpiarEstatus(&$estatus)
    {
        $estatus["estatus"] = null;
        $estatus["tractor_id"] = null;
        $estatus["tractor"] = null;
        $estatus["caja_actual"] = null;
        $estatus["operador"] = null;
        $estatus["operador_team"] = null;
        $estatus["ubicacion_direccion"] = null;
        $estatus["coordenadas"] = null;
        $estatus["fecha_actualizacion"] = null;
        $estatus["pedido_llc"] = null;
        $estatus["fecha_inicio_ruta"] = null;
        $estatus["eta"] = null;
        $estatus["id_samsara"] = null;
    }

    public function insertarEstatus($tractor, $estado)
    {
        $this->load->model("Tractor_model");
        $this->Tractor_model->insertarEstatus($tractor);
    }

    public function peticionTrailersSamsara($endCursor = null, $trailer_id = null)
    {
        $client = new GuzzleHttp\Client();
        $url = "https://api.samsara.com/fleet/vehicles/stats?types=gps,obdOdometerMeters";

        if ($endCursor) {
            $url .= "&after=" . $endCursor;
        }

        if ($trailer_id) {
            $url .= "&trailerIds=" . $trailer_id;
        }

        $response = $client->request('GET', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                "Authorization" => "Bearer " . $this->TOKEN_SAMSARA
            ]
        ]);

        $body = $response->getBody();
        $body = json_decode($body, true);
        return $body;
    }
}
