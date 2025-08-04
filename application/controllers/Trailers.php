<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Trailers extends CI_Controller
{
    public $body = [];

    public $BASE_URL_API_SAMSARA = "https://api.samsara.com/";

    private $PRODUCCION_VACIO = 1;
    private $PRODUCCION_A_CARGAR = 2;
    private $PRODUCCION_CARGADO = 3;
    private $PRODUCCION_A_DESCARGAR = 4;

    private $DIRECION_NONE = 0;
    private $DIRECION_NORTH = 1;
    private $DIRECION_SOUTH = 2;

    private $ASIGNACION_SIN_ASIGNAR = 0;
    private $ASIGNACION_ASIGNADO = 1;
    private $ASIGNACION_VACIO_AUTORIZADO = 2;

    private $ESTADO_DETENIDO_LOCAL = 0;
    private $ESTADO_TRANSITO_LOCAL = 1;
    private $ESTADO_DETENIDO_PATIO = 2;
    private $ESTADO_TRANSITO_CARRETERA = 3;
    private $ESTADO_DETENIDO_CARRETERA = 4;
    private $ESTADO_FUERA_DE_SERVICIO = 5;

    private $DETENCION_DETENIDO_SIN_AUTORIZACION = 1;
    private $DETENCION_DETENIDO_CON_AUTORIZACION = 2;
    private $DETENCION_DETENIDO_CLIENTE_REPARTO = 3;
    private $DETENCION_DETENIDO_CLIENTE_FINAL = 4;
    private $DETENCION_DETENIDO_DESCOMPUESTO = 5;
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

    public function obtener_estado_trailers()
    {
        $states = $this->Trailers_model->obtener_estado_trailers();
        $this->responder(false, "OK", $states);
    }

    public function obtener_estado_trailer_por_estatus()
    {
        $estado = $_GET["estado"];
        $produccion = $_GET["produccion"];
        $detencion = $_GET["detencion"];
        $asignacion = $_GET["asignacion"];
        $direccion = $_GET["direccion"];

        $state = $this->Trailers_model->obtener_estado_trailer_por_estatus($estado, $produccion, $detencion, $asignacion, $direccion);
        $this->responder(false, "OK", $state);
    }

    public function estadoSetear()
    {
        $this->Trailers_model->estadoSetear();
    }
    public function consultarTrailers($interno = false)
    {
        //poner timezone de mexico
        date_default_timezone_set('America/Monterrey');
        $hasNextPage = true;
        $endCursor = null;


        $response = $this->peticionTrailersSamsara($endCursor); // Llama a tu m�todo peticionTrailersSamsara
        $body = $response['data'];

        $id = $this->Trailers_model->insertar_peticion_samsara($body); // Llama a tu m�todo insertar_estado_trailers

        $cajas = $body;
        $geocerca = [];
        $this->load->model("Circuito_model");
        $this->load->model("Load_model");

        foreach ($cajas as $caja) {
            //ver si la caja tiene un circuito asignado en proceso de exportacion 
            $circuito_exportacion_en_proceso = $this->Circuito_model->getCircuitoActivo($caja["name"], "Exportacion en proceso");

            if (!empty($circuito_exportacion_en_proceso)) {

                $coordenadas_destino_exportacion = $circuito_exportacion_en_proceso[0]["destino_coordenadas_e"];
                $coordenadas_destino_exportacion = explode(",", $coordenadas_destino_exportacion);

                $caja_posicion_gps["latitud"] = $caja["gps"][0]["latitude"];
                $caja_posicion_gps["longitud"] = $caja["gps"][0]["longitude"];

                $punto_destino_exportacion = ["latitud" => $coordenadas_destino_exportacion[0], "longitud" => $coordenadas_destino_exportacion[1]];

                $cajaLlego_a_su_destino_exportacion = $this->Geocerca_model->puntoEnGeocerca($punto_destino_exportacion, $caja_posicion_gps, 20000);
                // var_dump($cajaLlego_a_su_destino_exportacion);
                if ($cajaLlego_a_su_destino_exportacion) {
                    // actualizar circuito fecha_llegada_e 
                    //actualizar estado circuito a exportacion en descarga
                    $dataActualizar = [
                        "fecha_llegada_e" => date("Y-m-d H:i:s"),
                        "estatus" => "Exportacion en descarga",
                        "updated_at" => date("Y-m-d H:i:s")
                    ];

                    $id_circuito = $circuito_exportacion_en_proceso[0]["id"];
                    $this->Circuito_model->actualizarCircuito($id_circuito, $dataActualizar);
                    unset($dataActualizar);
                    unset($id_circuito);
                }
                //ver si ya existe una importaci�n en pedidos para esta caja y agregarla al circuito
                //extraer id del pedido de exportacion
                $id_pedido_exportacion = intval(explode("LLC", $circuito_exportacion_en_proceso[0]["pedido_e"])[1]);


                if ($circuito_exportacion_en_proceso[0]["pedido_i"] == null) {
                    $importacion = $this->Load_model->buscarImportacionesPostExportacion($circuito_exportacion_en_proceso[0]["caja"], $id_pedido_exportacion);


                    if (!empty($importacion)) {
                        $id_circuito = $circuito_exportacion_en_proceso[0]["id"];
                        $dataActualizar = [
                            "load_confirmation_i" => $importacion[0]["load_confirmation"],
                            "pedido_i" => "LLC" . $importacion[0]["pedido_id_llc"],
                            "factura_i" => null,
                            "convenio_i" => $importacion[0]["convenio"],
                            "cliente_i" => $importacion[0]["cliente_nombre"],
                            "origen_ubicacion_i" => $importacion[0]["origen_nombre"],
                            "origen_coordenadas_i" => $importacion[0]["origen_coordenadas"],
                            "destino_ubicacion_i" => $importacion[0]["destino_nombre"],
                            "destino_coordenadas_i" => $importacion[0]["destino_coordendas"],
                            "tracto_i" => $importacion[0]["tractor"],
                            "operador_i" => $importacion[0]["operador_titular_nombre"],
                            "millas_cargado_i" => $importacion[0]["millas_cargado"],
                            "millas_vacio_i" => $importacion[0]["millas_vacias"],
                            "millas_totales_i" => $importacion[0]["millas_cargado"] + $importacion[0]["millas_vacias"],
                            "tarifa_i" => $importacion[0]["tarifa_pedido"],
                            "updated_at" => date("Y-m-d H:i:s")
                        ];
                        $this->Circuito_model->actualizarCircuito($id_circuito, $dataActualizar);
                    }
                }

                unset($coordenadas_destino_exportacion);
                unset($punto_destino_exportacion);
                unset($cajaLlego_a_su_destino_exportacion);
                unset($importacion);
                unset($id_pedido_exportacion);
                unset($caja_posicion_gps);
            } else {
            }

            //Ver si la caja tiene un circuito asignado con Exportacion en descarga, y validar si la caja ya salio de la geocerca, descarga en cliente
            $circuito_exportacion_en_descarga = $this->Circuito_model->getCircuitoActivo($caja["name"], "Exportacion en descarga");

            if (!empty($circuito_exportacion_en_descarga)) {
                $coordenadas_destino_exportacion_descarga = $circuito_exportacion_en_descarga[0]["destino_coordenadas_e"];
                $coordenadas_destino_exportacion_descarga = explode(",", $coordenadas_destino_exportacion_descarga);

                $caja_posicion_gps["latitud"] = $caja["gps"][0]["latitude"];
                $caja_posicion_gps["longitud"] = $caja["gps"][0]["longitude"];

                $punto_destino_exportacion = ["latitud" => $coordenadas_destino_exportacion_descarga[0], "longitud" => $coordenadas_destino_exportacion_descarga[1]];
                // ver si la caja sigue en la geocerca de descarga
                $caja_siguen_en_descarga = $this->Geocerca_model->puntoEnGeocerca($punto_destino_exportacion, $caja_posicion_gps, 20000);

                // ver si la caja ya salio de la geocerca de descarga
                if (!$caja_siguen_en_descarga) {
                    //actualizar circuito fecha_salida_e 
                    //actualizar estado circuito a exportacion en proceso
                    $dataActualizar = [
                        "fecha_salida_e" => date("Y-m-d H:i:s"),
                        "estatus" => "Importacion en iniciada",
                        "updated_at" => date("Y-m-d H:i:s")
                    ];

                    $id_circuito = $circuito_exportacion_en_descarga[0]["id"];
                    $this->Circuito_model->actualizarCircuito($id_circuito, $dataActualizar);
                    unset($dataActualizar);
                    unset($id_circuito);
                }
                //ver si ya existe una importaci�n en pedidos para esta caja y agregarla al circuito
                //extraer id del pedido de exportacion
                $id_pedido_exportacion_descarga = intval(explode("LLC", $circuito_exportacion_en_descarga[0]["pedido_e"])[1]);

                if ($circuito_exportacion_en_descarga[0]["pedido_i"] == null) {
                    $importacion = $this->Load_model->buscarImportacionesPostExportacion($circuito_exportacion_en_descarga[0]["caja"], $id_pedido_exportacion_descarga);

                    if (!empty($importacion)) {
                        $id_circuito = $circuito_exportacion_en_proceso[0]["id"];
                        $dataActualizar = [
                            "load_confirmation_i" => $importacion[0]["load_confirmation"],
                            "pedido_i" => "LLC" . $importacion[0]["pedido_id_llc"],
                            "factura_i" => null,
                            "convenio_i" => $importacion[0]["convenio"],
                            "cliente_i" => $importacion[0]["cliente_nombre"],
                            "origen_ubicacion_i" => $importacion[0]["origen_nombre"],
                            "origen_coordenadas_i" => $importacion[0]["origen_coordenadas"],
                            "destino_ubicacion_i" => $importacion[0]["destino_nombre"],
                            "destino_coordenadas_i" => $importacion[0]["destino_coordendas"],
                            "tracto_i" => $importacion[0]["tractor"],
                            "operador_i" => $importacion[0]["operador_titular_nombre"],
                            "millas_cargado_i" => $importacion[0]["millas_cargado"],
                            "millas_vacio_i" => $importacion[0]["millas_vacias"],
                            "millas_totales_i" => $importacion[0]["millas_cargado"] + $importacion[0]["millas_vacias"],
                            "tarifa_i" => $importacion[0]["tarifa_pedido"],
                            "updated_at" => date("Y-m-d H:i:s")
                        ];
                        $this->Circuito_model->actualizarCircuito($id_circuito, $dataActualizar);
                    }
                }

                unset($coordenadas_destino_exportacion_descarga);
                unset($punto_destino_exportacion);
                unset($caja_siguen_en_descarga);
                unset($id_pedido_exportacion_descarga);
                unset($caja_posicion_gps);
            }


            // Verificar si la caja ya tiene un pedido importacion asignado y si ya tiene ver si ya llego a su origen a cargar
            $circuito_importacion = $this->Circuito_model->getCircuitoActivo($caja["name"], "Importacion en iniciada");
            $ya_hay_impo = false;

            if (!empty($circuito_importacion)) {

                if ($circuito_importacion[0]["pedido_i"] == null) {
                    $id_pedido_exportacion = intval(explode("LLC", $circuito_importacion[0]["pedido_e"])[1]);
                    $importacion = $this->Load_model->buscarImportacionesPostExportacion($circuito_importacion[0]["caja"], $id_pedido_exportacion);

                    if (!empty($importacion)) {
                        $id_circuito = $circuito_importacion[0]["id"];
                        $dataActualizar = [
                            "load_confirmation_i" => $importacion[0]["load_confirmation"],
                            "pedido_i" => "LLC" . $importacion[0]["pedido_id_llc"],
                            "factura_i" => null,
                            "convenio_i" => $importacion[0]["convenio"],
                            "cliente_i" => $importacion[0]["cliente_nombre"],
                            "origen_ubicacion_i" => $importacion[0]["origen_nombre"],
                            "origen_coordenadas_i" => $importacion[0]["origen_coordenadas"],
                            "destino_ubicacion_i" => $importacion[0]["destino_nombre"],
                            "destino_coordenadas_i" => $importacion[0]["destino_coordendas"],
                            "tracto_i" => $importacion[0]["tractor"],
                            "operador_i" => $importacion[0]["operador_titular_nombre"],
                            "millas_cargado_i" => $importacion[0]["millas_cargado"],
                            "millas_vacio_i" => $importacion[0]["millas_vacias"],
                            "millas_totales_i" => $importacion[0]["millas_cargado"] + $importacion[0]["millas_vacias"],
                            "tarifa_i" => $importacion[0]["tarifa_pedido"],
                            "updated_at" => date("Y-m-d H:i:s")
                        ];
                        $this->Circuito_model->actualizarCircuito($id_circuito, $dataActualizar);
                    }
                }
            }

            //ver si la caja ya llego a su origen de importacion

            $circuito_importacion_proceso = $this->Circuito_model->getCircuitoActivoConImportacion($caja["name"], "Importacion en iniciada");

            if (!empty($circuito_importacion_proceso)) {
                $coordenadas_destino_importacion = $circuito_importacion_proceso[0]["origen_coordenadas_i"];
                $coordenadas_destino_importacion = explode(",", $coordenadas_destino_importacion);

                $caja_posicion_gps["latitud"] = $caja["gps"][0]["latitude"];
                $caja_posicion_gps["longitud"] = $caja["gps"][0]["longitude"];

                $punto_destino_importacion = ["latitud" => $coordenadas_destino_importacion[0], "longitud" => $coordenadas_destino_importacion[1]];

                $caja_llego_carga_importacion = $this->Geocerca_model->puntoEnGeocerca($punto_destino_importacion, $caja_posicion_gps, 20000);
                // var_dump($caja_llego_carga_importacion);
                if ($caja_llego_carga_importacion) {
                    // actualizar circuito fecha_llegada_i 
                    //actualizar estado circuito a importacion en descarga
                    $dataActualizar = [
                        "fecha_llegada_i" => date("Y-m-d H:i:s"),
                        "estatus" => "Importacion en carga",
                        "updated_at" => date("Y-m-d H:i:s")
                    ];

                    $id_circuito = $circuito_importacion_proceso[0]["id"];
                    $this->Circuito_model->actualizarCircuito($id_circuito, $dataActualizar);
                    unset($dataActualizar);
                    unset($id_circuito);
                }
                unset($coordenadas_destino_importacion);
                unset($punto_destino_importacion);
                unset($caja_llego_carga_importacion);
                unset($caja_posicion_gps);
            }

            //ver si la caja ya llego a su destino de importacion a cargar
            $circuito_importacion_carga = $this->Circuito_model->getCircuitoActivoConImportacion($caja["name"], "Importacion en carga");

            if (!empty($circuito_importacion_carga)) {
                $coordenadas_destino_importacion_carga = $circuito_importacion_carga[0]["origen_coordenadas_i"];
                $coordenadas_destino_importacion_carga = explode(",", $coordenadas_destino_importacion_carga);

                $caja_posicion_gps["latitud"] = $caja["gps"][0]["latitude"];
                $caja_posicion_gps["longitud"] = $caja["gps"][0]["longitude"];

                $punto_destino_importacion_carga = ["latitud" => $coordenadas_destino_importacion_carga[0], "longitud" => $coordenadas_destino_importacion_carga[1]];

                $cajaSalioDeOrigenCargaImportacion = $this->Geocerca_model->puntoEnGeocerca($punto_destino_importacion_carga, $caja_posicion_gps, 20000);
                // var_dump($cajaSalioDeOrigenCargaImportacion);
                if (!$cajaSalioDeOrigenCargaImportacion) {
                    // actualizar circuito fecha_llegada_i 
                    //actualizar estado circuito a importacion en descarga
                    $dataActualizar = [
                        "fecha_salida_i" => date("Y-m-d H:i:s"),
                        "estatus" => "Importacion en proceso",
                        "updated_at" => date("Y-m-d H:i:s")
                    ];

                    $id_circuito = $circuito_importacion_carga[0]["id"];
                    $this->Circuito_model->actualizarCircuito($id_circuito, $dataActualizar);
                    unset($dataActualizar);
                    unset($id_circuito);
                }
                unset($coordenadas_destino_importacion_carga);
                unset($punto_destino_importacion_carga);
                unset($cajaSalioDeOrigenCargaImportacion);
                unset($caja_posicion_gps);
            }


            //ver si la caja ya llego a su destino de importacion a cargar
            $circuito_importacion_en_proceso = $this->Circuito_model->getCircuitoActivoConImportacion($caja["name"], "Importacion en proceso");

            if (!empty($circuito_importacion_en_proceso)) {
                $coordenadas_destino_importacion_en_proceso = $circuito_importacion_en_proceso[0]["destino_coordenadas_i"];
                $coordenadas_destino_importacion_en_proceso = explode(",", $coordenadas_destino_importacion_en_proceso);

                $caja_posicion_gps["latitud"] = $caja["gps"][0]["latitude"];
                $caja_posicion_gps["longitud"] = $caja["gps"][0]["longitude"];

                $punto_destino_importacion_en_proceso = ["latitud" => $coordenadas_destino_importacion_en_proceso[0], "longitud" => $coordenadas_destino_importacion_en_proceso[1]];

                $cajaLlego_a_su_destino_importacion = $this->Geocerca_model->puntoEnGeocerca($punto_destino_importacion_en_proceso, $caja_posicion_gps, 20000);
                // var_dump($cajaLlego_a_su_destino_importacion);
                if ($cajaLlego_a_su_destino_importacion) {
                    // actualizar circuito fecha_llegada_i 
                    //actualizar estado circuito a importacion en descarga
                    $dataActualizar = [
                        "fecha_llegada_i" => date("Y-m-d H:i:s"),
                        "estatus" => "Importacion en descarga",
                        "updated_at" => date("Y-m-d H:i:s")
                    ];

                    $id_circuito = $circuito_importacion_en_proceso[0]["id"];
                    $this->Circuito_model->actualizarCircuito($id_circuito, $dataActualizar);
                    unset($dataActualizar);
                    unset($id_circuito);
                }
                unset($coordenadas_destino_importacion_en_proceso);
                unset($punto_destino_importacion_en_proceso);
                unset($cajaLlego_a_su_destino_importacion);
                unset($caja_posicion_gps);
            }

            $circuito_abierto = $this->Circuito_model->getCircuitoAbierto($caja["name"]);
            //ver si la caja ya llego a patio cavsco pero sigue cargada
            if (!empty($circuito_abierto)) {
                $coordenadas_patio_cavsco["latitud"] = 25.8400195;
                $coordenadas_patio_cavsco["longitud"] = -100.2377910;

                $caja_posicion_gps["latitud"] = $caja["gps"][0]["latitude"];
                $caja_posicion_gps["longitud"] = $caja["gps"][0]["longitude"];

                //$circuito_abierto[0][""]
                if (true) {
                    $cajaLlego_a_patio_cavsco = $this->Geocerca_model->puntoEnGeocerca($coordenadas_patio_cavsco, $caja_posicion_gps, 100);
                    // var_dump($cajaLlego_a_patio_cavsco);
                    if ($cajaLlego_a_patio_cavsco) {
                        // actualizar circuito fecha_llegada_i 
                        //actualizar estado circuito a importacion en descarga
                        $dataActualizar = null;
                        $hoy = date("Y-m-d H:i:s");
                        if ($circuito_importacion_en_proceso[0]["estatus"] == "Importacion en descarga") {
                            $dataActualizar = [
                                "fecha_descarga" => $hoy,
                                "estatus" => "Circuito cerrado",
                                "updated_at" => $hoy,
                                "fecha_fin_circuito" => $hoy
                            ];
                        } else if ($circuito_importacion_en_proceso[0]["estatus"] == "Importacion en proceso") {
                            $dataActualizar = [
                                "estatus" => "Circuito cerrado",
                                "updated_at" => $hoy,
                                "fecha_fin_circuito" => $hoy
                            ];
                        } else if ($circuito_importacion_en_proceso[0]["estatus"] == "Importacion en iniciada") {
                            $dataActualizar = [
                                "fecha_llegada_i" => $hoy,
                                "estatus" => "Circuito cerrado",
                                "updated_at" => $hoy,
                                "fecha_fin_circuito" => $hoy
                            ];
                        } else {
                            $dataActualizar = [
                                "estatus" => "Circuito cerrado",
                                "updated_at" => $hoy,
                                "fecha_fin_circuito" => $hoy
                            ];
                        }



                        $id_circuito = $circuito_abierto[0]["id"];
                        $this->Circuito_model->actualizarCircuito($id_circuito, $dataActualizar);
                        unset($dataActualizar);
                        unset($id_circuito);
                    }
                }
            }

            //ver si la caja ya salio del sitio de descarga

            $circuito_importacion_en_descarga = $this->Circuito_model->getCircuitoActivo($caja["name"], "Importacion en descarga");

            if (!empty($circuito_importacion_en_descarga)) {
                $coordenadas_destino_importacion_en_descarga = $circuito_importacion_en_descarga[0]["destino_coordenadas_i"];
                $coordenadas_destino_importacion_en_descarga = explode(",", $coordenadas_destino_importacion_en_descarga);

                $caja_posicion_gps["latitud"] = $caja["gps"][0]["latitude"];
                $caja_posicion_gps["longitud"] = $caja["gps"][0]["longitude"];

                $punto_destino_importacion_en_descarga = ["latitud" => $coordenadas_destino_importacion_en_descarga[0], "longitud" => $coordenadas_destino_importacion_en_descarga[1]];

                $cajaSalioDeDestinoDescargaImportacion = $this->Geocerca_model->puntoEnGeocerca($punto_destino_importacion_en_descarga, $caja_posicion_gps, 5000);
                // var_dump($cajaSalioDeDestinoDescargaImportacion);
                if (!$cajaSalioDeDestinoDescargaImportacion) {
                    // actualizar circuito fecha_llegada_i 
                    //actualizar estado circuito a importacion en descarga
                    $dataActualizar = [
                        "fecha_salida_i" => date("Y-m-d H:i:s"),
                        "estatus" => "Importacion descargada",
                        "updated_at" => date("Y-m-d H:i:s")
                    ];

                    $id_circuito = $circuito_importacion_en_descarga[0]["id"];
                    $this->Circuito_model->actualizarCircuito($id_circuito, $dataActualizar);
                    unset($dataActualizar);
                    unset($id_circuito);
                }
                unset($coordenadas_destino_importacion_en_descarga);
                unset($punto_destino_importacion_en_descarga);
                unset($cajaSalioDeDestinoDescargaImportacion);
                unset($caja_posicion_gps);
            }


            // unset($circuito_importacion);
            // $circuito_importacion = $this->Circuito_model->getCircuitoActivo($caja["name"], "Importacion en proceso");
            // if()




            continue;
            $tem = $this->determinarEstadoCaja($caja);
            $geocerca[] = ["geocerca" => $tem, "caja" => $caja];
            unset($tem);
        }

        if ($interno) {
            return true;
        }
        $this->responder(false, "OK", $geocerca);
    }

    /**
     * Verificar si una caja ya llego a x coordenada o si sigue en esas coordenadas, retorna true si ya llego, false si no. O retorna true si sigue en esas coordenadas, false si no.
     * @param string $coordenadas_destino Coordenadas de destino en string "latitud,longitud"
     * @param string $caja_posicion_gps Coordenadas de la caja en string "latitud,longitud"
     * @param int $radio Radio en metros
     * @return bool 
     */
    private function cajaLlegoASuDestino($coordenadas_destino, $caja_posicion_gps, $radio = 1000)
    {
        $punto_destino = ["latitud" => $coordenadas_destino[0], "longitud" => $coordenadas_destino[1]];
        $cajaLlego_a_su_destino = $this->Geocerca_model->puntoEnGeocerca($punto_destino, $caja_posicion_gps, $radio);
        return $cajaLlego_a_su_destino;
    }

    public function consultarTrailer($trailer_id)
    {
        $hasNextPage = true;
        $endCursor = null;


        $response = $this->peticionTrailersSamsara($endCursor, $trailer_id); // Llama a tu m�todo peticionTrailersSamsara
        $body = $response['data'];

        $id = $this->Trailers_model->insertar_peticion_samsara($body); // Llama a tu m�todo insertar_estado_trailers

        $cajas = $body;
        $geocerca = [];

        foreach ($cajas as $caja) {


            continue;
            $tem = $this->determinarEstadoCaja($caja);
            $geocerca[] = ["geocerca" => $tem, "caja" => $caja];
            unset($tem);
        }

        return true;
    }

    public function peticionTrailersSamsara($endCursor = null, $trailer_id = null)
    {
        $client = new GuzzleHttp\Client();
        $url = "https://api.samsara.com/beta/fleet/trailers/stats/feed?types=gps,gpsOdometerMeters";

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

    public function determinarEstadoCaja($caja)
    {
        //debo determinar el estado de la caja, los estados son
        //Estado: 0 = detenido cliente, 1 = transito local, 2 = detenido en patio, 3 = transito carretera, 4 = detenido carretera
        //asignacion: 0 = sin asignar, 1 = asignado, 2 = vacio autorizado
        //direccion: 0 = none, 1 = norht, 2 = south
        //Detencion: 1 = detenido sin autorizacion, 2 = detenido con autorizacion, 3 = detenido cliente reparto, 4 = detenido cliente final
        // produccion: 1 = vacio, 2 = a cargar, 3 = cargado, 4 = a descargar
        $caja_id = $caja['id'];
        $estado = [
            "estado" => 0,
            "asignacion" => 0,
            "direccion" => 0,
            "detencion" => 0,
            "produccion" => 0,
            "tiempo" => 0,
            "metros" => 0
        ];


        $cajaStado = $this->Trailers_model->getStateTrailer($caja_id);
        $loadInfo = null;
        // if ($cajaStado["load_id"] != 0) {
        //     $loadInfo = $this->Trailers_model->getLoadInfo($cajaStado["load_id"]);
        // }
        //obtener la geocerca mas cercana
        if ($cajaStado["produccion"] == 5) {
            if ($cajaStado["estado"] == 5) {
                return;
            }

            $cajaStado["estado"] = 5;
            $estado["tiempo_estado"] = $cajaStado["diferencia"];
            unset($cajaStado["diferencia"]);
            unset($cajaStado["id"]);
            $this->Trailers_model->actualizar_estado_trailers($caja_id, $cajaStado);
            $cajaStado["tiempo_estado"] = $estado["tiempo_estado"];
            $this->Trailers_model->insertar_estado_trailers_historial($caja_id, $cajaStado);
            return;
        }

        $estado["produccion"] = $cajaStado["produccion"];
        $geocerca = $this->Geocerca_model->getGeocercaMasCercana($caja["gps"][0]["latitude"], $caja["gps"][0]["longitude"]);
        if ($caja["gps"][0]["speedMilesPerHour"] == 0) { //esta detenido
            $estado["estado"] = 0;
            $estado["detencion"] = 1;
            if ($geocerca != null) {
                if ($geocerca["geocerca_id"] == "41341939") {
                    $estado["estado"] = 2;
                    $estado["detencion"] = 2;
                } else if ($geocerca["geocerca_id"] == "27200417") {
                    $estado["estado"] = 2;
                    $estado["detencion"] = 2;
                } else if ($cajaStado["produccion"] == 2) {
                    if ($this->verificarSiEstaEnLocal($caja["gps"][0]["latitude"], $caja["gps"][0]["longitude"])) {

                        $estado["estado"] = 0;
                        $estado["detencion"] = 2;
                    } else {
                        $estado["estado"] = 4;
                        $estado["detencion"] = 2;
                    }
                } else if ($cajaStado["produccion"] == 3) {
                    if (trim($geocerca["notas"]) == "CLIENTE") {
                        $estado["estado"] = 4;
                        $estado["detencion"] = 4;
                    } else if (false) {
                    } else {
                        $estado["estado"] = 4;
                        $estado["detencion"] = 2;
                    }
                    if ($this->verificarSiEstaEnLocal($caja["gps"][0]["latitude"], $caja["gps"][0]["longitude"])) {
                        $estado["estado"] = 0;
                    }
                } else {
                    if ($this->verificarSiEstaEnLocal($caja["gps"][0]["latitude"], $caja["gps"][0]["longitude"])) {
                        $estado["estado"] = 0;
                        $estado["detencion"] = 2;
                    } else {
                        $estado["estado"] = 4;
                        $estado["detencion"] = 2;
                    }
                }

                $estado["geocerca_name"] = $geocerca["nombre"];
            } else if ($geocerca == null) {
                if ($cajaStado["produccion"] == 2) {
                    if ($this->verificarSiEstaEnLocal($caja["gps"][0]["latitude"], $caja["gps"][0]["longitude"])) {
                        $estado["estado"] = 0;
                        $estado["detencion"] = 1;
                    } else {
                        $estado["estado"] = 4;
                        $estado["detencion"] = 1;
                    }
                } else {
                    if ($this->verificarSiEstaEnLocal($caja["gps"][0]["latitude"], $caja["gps"][0]["longitude"])) {
                        $estado["estado"] = 0;
                        $estado["detencion"] = 1;
                    } else {
                        $estado["estado"] = 4;
                        $estado["detencion"] = 1;
                    }
                }
            }

            $obdometro = isset($caja["gpsOdometerMeters"]) ? $caja["gpsOdometerMeters"][0]["value"] : 0;
            $estado["metros"] =  $obdometro;
            $estado["cliente"] = 0;
            $estado["direccion"] = 0;
        } else { //esta en movimiento
            if ($geocerca == null) {
                $estado["detencion"] = 0;
                $obdometro = isset($caja["gpsOdometerMeters"]) ? $caja["gpsOdometerMeters"][0]["value"] : 0;
                $estado["metros"] =  $obdometro;
                if ($cajaStado["produccion"] == 2) {
                    if ($this->verificarSiEstaEnLocal($caja["gps"][0]["latitude"], $caja["gps"][0]["longitude"])) {
                        $estado["estado"] = 1;
                    } else {
                        $estado["estado"] = 3;
                    }
                } else if ($cajaStado["produccion"] == 4) {
                    if ($this->verificarSiEstaEnLocal($caja["gps"][0]["latitude"], $caja["gps"][0]["longitude"])) {
                        $estado["estado"] = 1;
                    } else {
                        $estado["estado"] = 3;
                    }
                } else {
                    if ($this->verificarSiEstaEnLocal($caja["gps"][0]["latitude"], $caja["gps"][0]["longitude"])) {
                        $estado["estado"] = 1;
                    } else {
                        $estado["estado"] = 3;
                    }
                }
                //si el estado anterior era cargada (produccion 3), detenida en carretera (estado 4) y detenido con autorizacion en cliente (detencion 4)
                //y ahora estoy en movimiento, cambia produccion a vacia (produccion 1) y el estado es en carreter
                if ($cajaStado["produccion"] == 3 && $cajaStado["estado"] == 4 && $cajaStado["detencion"] == 4) {
                    $estado["estado"] = 3;
                    $estado["produccion"] = 1;
                }

                $estado["geocerca_name"] = "";
            } else {
                if ($geocerca["geocerca_id"] == "41341939") {
                    $estado["estado"] = 2;
                    $estado["detencion"] = 2;
                } else if ($geocerca["geocerca_id"] == "27200417") {
                    $estado["estado"] = 2;
                    $estado["detencion"] = 2;
                } else if ($cajaStado["produccion"] == 2) {
                    if ($this->verificarSiEstaEnLocal($caja["gps"][0]["latitude"], $caja["gps"][0]["longitude"])) {
                        $estado["estado"] = 0;
                        $estado["detencion"] = 2;
                    } else {
                        $estado["estado"] = 3;
                        $estado["detencion"] = 2;
                    }
                } else if ($cajaStado["produccion"] == 3) {
                    if (trim($geocerca["notas"]) == "CLIENTE") {
                        if ($this->verificarSiEstaEnLocal($caja["gps"][0]["latitude"], $caja["gps"][0]["longitude"])) {
                            $estado["estado"] = 0;
                            $estado["detencion"] = 4;
                        } else {
                            $estado["estado"] = 4;
                            $estado["detencion"] = 4;
                        }
                    } else {
                        if ($this->verificarSiEstaEnLocal($caja["gps"][0]["latitude"], $caja["gps"][0]["longitude"])) {
                            $estado["estado"] = 0;
                            $estado["detencion"] = 2;
                        } else {
                            $estado["estado"] = 4;
                            $estado["detencion"] = 2;
                        }
                    }
                } else {
                    $estado["estado"] = 4;
                    $estado["detencion"] = 2;
                }

                $estado["geocerca_name"] = $geocerca["nombre"];
            }
        }

        $estado["trailer_id"] = $caja["id"];

        if ($cajaStado) {
            $cliente = $cajaStado["cliente"];
            $load_id = $cajaStado["load_id"];
            $estado["cliente"] = $cliente;
            $estado["load_id"] = $load_id;
            $estado["asignacion"] = $cajaStado["asignacion"];
            //comparar $cajaStado y $estado si son iguales no hacer nada, si son diferentes actualizar trailers_state e insertar trailers_state_history
            if (
                $cajaStado["estado"] != $estado["estado"] ||
                $cajaStado["asignacion"] != $estado["asignacion"] ||
                $cajaStado["direccion"] != $estado["direccion"] ||
                $cajaStado["detencion"] != $estado["detencion"] ||
                $cajaStado["produccion"] != $estado["produccion"] ||
                $cajaStado["cliente"] != $estado["cliente"]
                || $cajaStado["load_id"] != $estado["load_id"]
            ) {
                $cambioEstado = false;
                if ($cajaStado["estado"] != $estado["estado"]) {
                    $cambioEstado = true;
                }
                $estado["tiempo"] = $caja["gps"][0]["time"];
                $estado["latitude"] = $caja["gps"][0]["latitude"];
                $estado["longitude"] = $caja["gps"][0]["longitude"];
                $estado["speedMilesPerHour"] = $caja["gps"][0]["speedMilesPerHour"];
                $estado["direccion_texto"] = $caja["gps"][0]["reverseGeo"]["formattedLocation"];
                $estado["trailer"] = $caja["name"];



                $this->Trailers_model->actualizar_estado_trailers($caja_id, $estado, $cambioEstado);
                $estado["tiempo_estado"] = $cajaStado["diferencia"];
                if ($estado["tiempo"] == '0000-00-00 00:00:00') {
                    $estado["tiempo"] = date("Y-m-d H:i:s");
                }
                $this->Trailers_model->insertar_estado_trailers_historial($caja_id, $estado, $cambioEstado);
            }
            // $this->Trailers_model->actualizar_estado_trailers($caja_id, $estado);
            // $this->Trailers_model->insertar_estado_trailers_historial($caja_id, $estado);
        } else {
            $this->Trailers_model->insertar_estado_trailers($caja_id, $estado);
        }
    }

    public function actualizar_estado_trailers()
    {
        $body = $this->body;

        $trailer_id = $body["trailer_id"];
        $propiedad = $body["propiedad"];
        $valor = $body["valor"];
        $caja = $body["caja"];
        $usuarioId = $body["usuarioID"];

        $this->Trailers_model->actualizar_propiedad_trailers($trailer_id, $propiedad, $valor, $caja, $usuarioId);

        if (isset($body["cliente_id"])) {
            $cliente_id = $body["cliente_id"];
            $this->Trailers_model->actualizar_cliente_trailers($trailer_id, $cliente_id);
        }

        if (isset($body["load_id"])) {
            $load_id = $body["load_id"];
            $this->Trailers_model->actualizar_load_trailers($trailer_id, $load_id);
        }

        if (isset($body["retirar"])) {
            //quitar de trialer_state load id y cliente
            $this->Trailers_model->quitar_load_cliente_trailers($trailer_id, $usuarioId);
        }

        $this->consultarTrailer($trailer_id);
        $this->responder(false, "Se actualizo correctamente", $body);
    }

    private function verificarSiEstaEnLocal($latitud, $longitud)
    {
        $minLongitud = -100.512044;
        $maxLongitud = -100.077008;
        $minLatitud = 25.610830;
        $maxLatitud = 25.830418;

        // Convertir latitud y longitud a n�meros flotantes
        $latitud = floatval($latitud);
        $longitud = floatval($longitud);

        // Verificar si el punto est� dentro del pol�gono
        if (
            $longitud >= $minLongitud && $longitud <= $maxLongitud
            && $latitud >= $minLatitud && $latitud <= $maxLatitud
        ) {
            return true;
        } else {
            return false;
        }
    }


    public function generarFolios()
    {
        $trailers =  $this->Trailers_model->getTrailers();
        foreach ($trailers as $trailer) {
            $historiaTrailer =  $this->Trailers_model->getStateHistory($trailer["trailer_id"]);
            $this->responder(false, "OK", $historiaTrailer);
        }
    }

    public function salidaEntrada()
    {
        $body = $this->body;

        $salida_entrada = [];
        $payload["revisionTracktor"] = $body["revisionTracktor"];
        $payload["revisionTrailer"] = $body["revisionTrailer"];
        $payload["llantasTractor"] = $body["llantasTractor"];
        $payload["llantasTrailer"] = $body["llantasTrailer"];

        $salida_entrada["payload"] = json_encode($payload);
        $salida_entrada["cliente_id"] = $body["datosGenerales"]["cliente"];
        $salida_entrada["conductor_id"] = $body["datosGenerales"]["operador"];
        $salida_entrada["tracktor_id"] = $body["datosGenerales"]["tractor"];
        $salida_entrada["trailer_id"] = $body["datosGenerales"]["caja"];

        $salida_entrada["type"] = $body["typeMove"];

        $this->Trailers_model->insertar_salida_entrada($salida_entrada);

        $this->responder(false, "OK", $salida_entrada);
    }



    public function iniciarCircuito()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->responder(true, "Metodo no permitido", null);
            return;
        }

        if (!isset($this->body["pedido_llc"]) || !isset($this->body["caja"])) {
            $this->responder(true, "Faltan parametros", null);
            return;
        }

        $pedido_llc = $this->body["pedido_llc"];
        $caja = $this->body["caja"];

        $fecha_salida = date("Y-m-d H:i:s");
        $this->load->model("Load_model");
        $this->load->model("Circuito_model");
        $getInfoPedidoSalida = $this->Load_model->getInfoLoadid_rainde($pedido_llc);

        if (!empty($getInfoPedidoSalida)) {
            $infoInicioCircuito = [
                "caja_ng" => $caja,
                "caja" => $getInfoPedidoSalida[0]["remolque_no_economico"],
                "load_confirmation_e" => $getInfoPedidoSalida[0]["load_confirmation"],
                "pedido_e" => "LLC" . $getInfoPedidoSalida[0]["pedido_id_llc"],
                "factura_e" => null,
                "convenio_e" => $getInfoPedidoSalida[0]["convenio"],
                "cliente_e" => $getInfoPedidoSalida[0]["cliente_nombre"],
                "fecha_cita_carga_e" => $getInfoPedidoSalida[0]["fecha_cita_carga"],
                "fecha_despacho_e" => $getInfoPedidoSalida[0]["fecha_despacho"],
                "origen_ubicacion_e" => $getInfoPedidoSalida[0]["origen_nombre"],
                "origen_coordenadas_e" => $getInfoPedidoSalida[0]["origen_coordenadas"],
                "fecha_llegada_e" => null,
                "fecha_salida_e" => null,
                "fecha_inicio_circuito" => $fecha_salida,
                "destino_ubicacion_e" => $getInfoPedidoSalida[0]["destino_nombre"],
                "destino_coordenadas_e" => $getInfoPedidoSalida[0]["destino_coordendas"],
                "tracto_e" => $getInfoPedidoSalida[0]["tractor"],
                "operador_e" => $getInfoPedidoSalida[0]["operador_titular_nombre"],
                "millas_cargado_e" => $getInfoPedidoSalida[0]["millas_cargado"],
                "millas_vacio_e" => $getInfoPedidoSalida[0]["millas_vacias"],
                "total_millas_e" => $getInfoPedidoSalida[0]["millas_cargado"] + $getInfoPedidoSalida[0]["millas_vacias"],
                "tarifa_e" => $getInfoPedidoSalida[0]["tarifa_pedido"],
                "estatus" => "Exportacion en proceso"
            ];

            $circuitoAbiertoId = $this->Circuito_model->abrirCircuito($infoInicioCircuito);
            $infoInicioCircuito["id"] = $circuitoAbiertoId;
            $this->responder(false, "OK", $infoInicioCircuito);
        }

        $this->responder(true, "No se encontro el pedido", null);
    }
}
