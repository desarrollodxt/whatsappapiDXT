<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Ogs extends CI_Controller
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
        $this->load->model('Unidades_model');
        $this->load->model('Clientes_model');
        $this->load->model("Rutas_model");
        $this->load->model("Proveedor_model");
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
        $catalogos = [];
        $unidades = $this->Unidades_model->obtener_cat_unidades();
        $clientes = $this->Clientes_model->cat_obtener_clientes();
        $caracteristicasUnidades = $this->Unidades_model->obtener_caracteristicas_unidades();
        // Agregar el catálogo de unidades a la respuesta
        $catalogos["unidades"] = $unidades;
        $catalogos["clientes"] = $clientes;
        $catalogos["caracteristicasUnidades"] = $caracteristicasUnidades;
        $this->responder(false, "",  $catalogos);
    }

    public function getCatRutasCliente($id)
    {

        $id_cliente = intval($id);

        $rutas = $this->Rutas_model->cat_obtener_rutas_cliente($id_cliente);
        $this->responder(false, "", $rutas);
    }

    public function getCatalogosPorCliente($id)
    {
        $id_cliente = intval($id);

        $rutas = $this->Rutas_model->cat_obtener_rutas_cliente($id_cliente);
        $cargas = $this->Clientes_model->cat_obtener_cargas_cliente($id_cliente);
        $especificacionesCarga = ["IMSS", "Caja fumigada"];
        $response = [];
        $response["rutas"] = $rutas;
        $response["cargas"] = $cargas;
        $response["especificacionesCarga"] = $this->Clientes_model->cat_obtener_especificaciones_carga($id_cliente);
        $this->responder(false, "",  $response);
    }

    public function crearOrden()
    {
        $body = $this->body;

        $ruta = $body["ruta"];

        $rutaInfo = $this->Rutas_model->getRutaCompare($ruta);
        $proveedor = $this->Rutas_model->findProveedor($rutaInfo);

        $whatsappNum = $this->Proveedor_model->obtener_whatsappContact($proveedor[0]["id"]);
        if (empty($whatsappNum)) {
            // $this->responder(true, "No se encontró un número de whatsapp para el proveedor", null, 400);
        } else {
            $whatsappNum = $whatsappNum[0]["telefono"];
        }

        $client = new \GuzzleHttp\Client();

        $response = $client->request('POST', 'https://api.chat-api.com/instance' . '100000' . '/sendMessage?token=' . 'z2j2q2q2q2q2q2q2q2q2q2q2q2q2q2', [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'phone' => $whatsappNum,
                'body' => 'Hola, soy un mensaje de prueba'
            ]
        ]);

        var_dump($whatsappNum);
    }
}
