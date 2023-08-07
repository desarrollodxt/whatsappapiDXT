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
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Methods: GET, POST");
        header("Access-Control-Allow-Headers: Content-Type");
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

    public function enviarMensaje()
    {

        ob_clean();
        $client = new \GuzzleHttp\Client();


        $response = $client->request('POST', "https://develop.focusmedia-agency.com/apidxt/enviarMensaje", [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'from' => $this->body["from"],
                'content' => $this->body["mensaje"],
                "file" => false
            ]
        ]);
        $resJson = json_decode($response->getBody()->getContents(), true);
        //{ estatus: "ok", messageId: "dsadsadsa2121" } respuesta esperada, obtener messageId
        $newMessage = $this->Whatsapp_model->newMessage($this->body, $resJson["messageId"]);
        $this->responder(false, "",  $newMessage);
    }

    public function recibirMensaje()
    {
        ob_clean();
        $this->Whatsapp_model->salvarMensajeRecibido($this->body);
        $array = [];
        $this->responder(false, "",  $array);
    }

    public function getChats()
    {
        ob_clean();
        $chats = $this->Whatsapp_model->getChats();
        $this->responder(false, "",  $chats);
    }

    public function getMensajesPorChat($id)
    {
        ob_clean();
        $id_chat = intval($id);
        $mensajes = $this->Whatsapp_model->getMensajesPorChat($id_chat, 10, null);

        $this->responder(false, "",  $mensajes);
    }

    public function enviarMensajeFile()
    {
        ob_clean();
        $client = new \GuzzleHttp\Client();

        //subir el archivo a esta ruta /domains/gcsmatrix.com/public_html/dxt/public/uploads
        $file = $_FILES["file"];
        $file_tmp = $file["tmp_name"];
        $file_name = $file["name"];
        $file_size = $file["size"];
        $mimetype = $_POST["mimetype"];
        $file_error = $file["error"];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_name = uniqid() . "." . $file_ext;
        $file_path = "/domains/gcsmatrix.com/public_html/dxt/public/uploads/" . $file_name;

        if (!move_uploaded_file($file_tmp, $file_path)) {
            $this->responder(true, "Error al subir el archivo", null, 400);
        }

        $response = $client->request('POST', "http://localhost:3025/" . 'enviarMensajeFile', [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'from' => $this->body["from"],
                'content' => $this->body["content"],
                'fileName' => $file_name,
                'file' => true,
                'fileExtension' => $file_ext,
                'url' => "https://gcsmatrix.com/dxt/public/uploads/",
            ]
        ]);

        $resJson = json_decode($response->getBody()->getContents(), true);
        //{ estatus: "ok", messageId: "dsadsadsa2121" } respuesta esperada, obtener messageId
        $this->body["filename"] = $file_name;
        $this->body["mimetype"] = $mimetype;
        $newMessage = $this->Whatsapp_model->newMessage($this->body, $resJson["messageId"]);

        $this->responder(false, "",  $newMessage);
    }
}
