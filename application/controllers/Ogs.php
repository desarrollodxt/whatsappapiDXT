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
        $this->load->model("Ogs_model");
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
        $catOrigenesDestino = $this->Clientes_model->cat_obtener_origenes_destinos();
        $caracteristicasUnidades = $this->Unidades_model->obtener_caracteristicas_unidades();
        // Agregar el catálogo de unidades a la respuesta
        $catalogos["unidades"] = $unidades;
        $catalogos["clientes"] = $clientes;
        $catalogos["origenesDestinos"] = $catOrigenesDestino;
        $catalogos["caracteristicasUnidades"] = $caracteristicasUnidades;
        $this->responder(false, "",  $catalogos);
    }
    public function addCarga()
    {
        $body = $this->body;
        $carga = $body["carga"];
        $id_cliente = intval($body["id_cliente"]);
        $carga_id = $this->Ogs_model->addCarga($carga, $id_cliente);

        $cargaNueva = [
            "id" => $carga_id,
            "text" => $carga
        ];
        $this->responder(false, "",   $cargaNueva);
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

        $cargas = $this->Clientes_model->cat_obtener_cargas_cliente($id_cliente);
        $especificacionesCarga = ["IMSS", "Caja fumigada"];
        $response = [];
        $response["cargas"] = $cargas;
        $response["especificacionesCarga"] = $this->Clientes_model->cat_obtener_especificaciones_carga($id_cliente);
        $this->responder(false, "",  $response);
    }

    public function crearOrden()
    {
        $body = $this->body;

        $origen = $body["origen"];
        $destino = $body["destino"];

        $id_cliente = intval($body["cliente"]);

        $ruta = $this->Rutas_model->obtener_ruta_cliente($origen, $destino, $id_cliente);
        if (empty($ruta)) {
            $this->responder(true, "No se pudo generar la ruta", null, 400);
        }

        $proveedores = $this->Rutas_model->findProveedor($ruta[0]);


        $especificacionesCarga = $this->Ogs_model->getEspecificacionesCarga($body["especificacionesCarga"], $id_cliente);
        $ogs = $this->Ogs_model->altaOrden($body, $especificacionesCarga, $ruta);

        if (!$ogs) {
            $this->responder(true, "No se pudo generar la orden", null, 400);
        }

        if (empty($proveedores)) {
            $this->responder(false, "No se encontró un proveedor para esta ruta, Pero se genero la orden con éxito.", null, 200);
        }

        $ProvedoresQueNoSePudieronAvisar = [];

        foreach ($proveedores as $proveedor) {
            if ($proveedor["from_"] == null) {
                $ProvedoresQueNoSePudieronAvisar[] = $proveedor;
                continue;
            }
            $mensaje = "Ocupamos " . $ogs["unidad"]  . " " . $ogs["caracteristica"] . " ";
            $mensaje .= $ruta[0]["origen"] . " a " . $ruta[0]["destino"] . " \n";
            $mensaje .= "Carga: " . $ogs["fecha_carga"] . "\n";
            $mensaje .= "Descarga: " . $ogs["fecha_descarga"] . "\n";
            $mensaje .= "Carga: " . $ogs["carga"] . " peso " . $ogs["peso"] . " " . $ogs["tipo_peso"] .  "\n";
            $mensaje .= "Adicionales:\n";

            foreach ($especificacionesCarga as $value) {
                $mensaje .= " - $value \n";
            }
            $mensaje .= "Favor de confirmar dispo";

            $this->sendMessage($proveedor["from_"], $mensaje);
        }

        if (empty($ProvedoresQueNoSePudieronAvisar)) {
            $this->responder(false, "La orden se ha generado con éxito", []);
        } else {
            $mensajeAlerta =  "La orden se ha generado con éxito, pero no se pudo avisar a los siguientes proveedores por que no tiene asignado un grupo de whatsapp: ";
            $proveedoresLista = implode(",", array_column($ProvedoresQueNoSePudieronAvisar, "nombre_corto"));

            $ms = "No se pudo alertar estos proveedores: " . $proveedoresLista . ". para esta ruta " . $ruta[0]["origen"] . " a " . $ruta[0]["destino"] . " Debes dar de alta un grupo de whatsapp con el proveedor y asignarlo en la sección de proveedores.";

            $this->sendMessage("120363025084570901@g.us", $ms);
            $this->responder(false,  $mensajeAlerta .  $proveedoresLista, []);
        }
    }


    private function sendMessage($from, $mensaje)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', "https://gcsmatrix.com/dxt/api/" . 'whatsapp/enviarMensaje', [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'id_chat' => false,
                "from" => $from,
                'mensaje' => $mensaje
            ]
        ]);
        return $response;
    }
}
