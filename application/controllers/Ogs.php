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
        $this->load->model("ogs_model");
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
        $id_cliente = intval($body["cliente"]);

        $rutaInfo = $this->Rutas_model->getRutaCompare($ruta);
        $proveedor = $this->Rutas_model->findProveedor($rutaInfo);
        $especificacionesCarga = $this->ogs_model->getEspecificacionesCarga($body["especificacionesCarga"], $id_cliente);

        $ogs = $this->ogs_model->altaOrden($body, $especificacionesCarga);


        $whatsappNum = $this->Proveedor_model->obtener_whatsappContact($proveedor[0]["id"]);
        if (empty($whatsappNum)) {
            // $this->responder(true, "No se encontró un número de whatsapp para el proveedor", null, 400);
        } else {
            $whatsappNum = $whatsappNum[0]["telefono"];
        }

        $client = new \GuzzleHttp\Client();

        $mensaje = "Hola, buen día " . $proveedor[0]["nombre_corto"] . "! \n\n";
        $mensaje .= "Tenemos una nueva solicitud de flete. \n\n";
        $mensaje .= "Origen: " . $ogs["origen"] . "\n";
        $mensaje .= "Destino: " . $ogs["destino"] . " \n";
        $mensaje .= "Fecha de carga: " . $ogs["fecha_carga"] . "\n";
        $mensaje .= "Fecha de descarga: " . $ogs["fecha_descarga"] . "\n";
        $mensaje .= "Tipo de unidad: " . $ogs["unidad"] . " " . $ogs["caracteristica"] . "\n";
        $mensaje .= "Carga: " . $ogs["peso"] . " " . $ogs["tipo_peso"] . " de " . $ogs["carga"] .   " \n";
        $mensaje .= "El flete tiene los siguientes requisitos:\n";

        foreach ($especificacionesCarga as $value) {
            $mensaje .= " - $value \n";
        }

        $mensaje .= "Sí te interesa este flete y tienes disponibilidad responde: \"tengo disponibilidad\",\n si no mueves esta ruta o no tienes este tipo de unidad responde: \"no me interesan estos viajes\" \nDe otra forma solo ignora este mensaje.\n";
        $mensaje .= "¡Gracias!";

        // $ruta_id = $this->body["ruta_id"];
        // $rutaInfo = $this->Rutas_model->getRutaCompare($ruta_id);
        // $proveedor = $this->Rutas_model->findProveedor($rutaInfo);
        // $proveedor_id = $proveedor[0]["id"];
        // $whatsappContact = $this->Proveedor_model->obtener_whatsappContact($proveedor_id);
        // $telefono = $whatsappContact[0]["telefono"];
        // $nombre_corto = $whatsappContact[0]["nombre_corto"];
        // $ruta = $rutaInfo[0]["ruta"];
        // $mensaje = "Hola, soy $nombre_corto, me interesa la ruta $ruta";
        // $this->responder(false, "",  $mensaje);

        $response = $client->request('POST', "http://localhost/dxt/api/" . 'whatsapp/enviarMensaje', [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'id_chat' => 70,
                'mensaje' => $mensaje
            ]
        ]);


        $this->responder(false, "", $ogs);
    }
}
