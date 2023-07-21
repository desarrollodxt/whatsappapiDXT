<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Whatsapp extends CI_Controller
{
    public $body = [];
    public $BASE_URL_WHATSAPP_API = "";
    public $BEARER_TOKEN = "";
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
        $this->load->model('Unidades_model');
        $this->load->model('Clientes_model');
        $this->load->model("Rutas_model");
        $this->load->model("Proveedor_model");
        $this->load->model("Whatsapp_model");
        $_body = file_get_contents('php://input');
        $this->BASE_URL_WHATSAPP_API = "https://graph.facebook.com/v17.0/116875228131847/messages";
        $this->BEARER_TOKEN = "EAAOVScZAgYmIBAOVzrO08zEOZCQaT8xBQVKmqVJjW7L3xNmXKv9OeDeZAz1ruiPTL2FegXSd5yp5Cd371LeRYgH2c9vi3cTBKZCfXbcXItjywi0M6OhNjJseNzlDzpif4Bt4ESv0UsCgZC3FLgszWyin0DCdFfxh7GQy4oajoxZCFIA6OBzX01ejZBmlaPFL4QflvWCElh4CAZDZD";
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

    public function enviarMensajeProveedorOgs()
    {
        $ruta_id = $this->body["ruta_id"];
        $rutaInfo = $this->Rutas_model->getRutaCompare($ruta_id);
        $proveedor = $this->Rutas_model->findProveedor($rutaInfo);
        $proveedor_id = $proveedor[0]["id"];
        $whatsappContact = $this->Proveedor_model->obtener_whatsappContact($proveedor_id);
        $telefono = $whatsappContact[0]["telefono"];
        $nombre_corto = $whatsappContact[0]["nombre_corto"];
        $ruta = $rutaInfo[0]["ruta"];
        $mensaje = "Hola, soy $nombre_corto, me interesa la ruta $ruta";



        $this->responder(false, "",  $mensaje);
    }

    public function recibirMensaje()
    {
        $challenge = $this->input->get("hub_challenge");
        $this->Whatsapp_model->salvarMensajeRecibido($this->body);
        $array = [
            "hub.challenge" => $challenge
        ];
        // $this->responder(false, "",  $array);
        echo $challenge;
    }
}
