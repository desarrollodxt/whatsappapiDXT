<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Proveedor extends CI_Controller
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
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Methods: GET, POST");
        header("Access-Control-Allow-Headers: Content-Type");
        parent::__construct();
        $this->load->model('Roles_model');
        $this->load->model('Rutas_model');
        $this->load->model('Proveedor_model');
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

    public function altaGrupoContacto()
    {
        $body = $this->body;

        $result = $this->Proveedor_model->altaGrupoContacto($body);

        $this->responder(false, "",  $result);
    }
    public function getCatalogosProveedor($usuario_id)
    {
        ob_clean();
        $data = [];
        $proveedores = $this->Proveedor_model->obtener_proveedores_carga($usuario_id);
        $data["proveedores"] = $proveedores;
        // $ciudades = $this->Rutas_model->obtener_ciudades();
        $ciudades = $this->Rutas_model->obtener_estados();
        $data["ciudades"] = $ciudades;
        $tipoUnidades = $this->Rutas_model->obtener_tipo_unidades();
        $data["tipoUnidades"] = $tipoUnidades;



        $this->responder(false, "",  $data);
    }

    public function altaContactosProveedor()
    {
        $usuarioId = $this->body["usuarioId"];
        $proveedorId = $this->body["proveedorId"];
        $contactos = $this->body["contactos"];
        $rutas = $this->body["rutas"];
        $unidades = $this->body["unidades"];
        $result = $this->Proveedor_model->altaContactosProveedor($usuarioId, $proveedorId, $contactos, $rutas, $unidades);

        if ($result === true) {
            $this->responder(false, "",  $this->body);
        }

        $this->responder(true, "Error al guardar los datos", $result, 400);
    }


    public function getProveedores()
    {
        ob_clean();
        $data = [];
        $proveedores = $this->Proveedor_model->obtener_proveedores();
        foreach ($proveedores as $key => $proveedor) {
            $proveedores[$key]["text"] = $proveedor["nombre_corto"];
        }
        $data["proveedores"] = $proveedores;


        $this->responder(false, "",  $data);
    }

    public function getUsuariosProveedores()
    {
        ob_clean();
        $data = [];
        $usuarios = $this->Proveedor_model->obtener_usuarios_proveedores();

        $this->responder(false, "",  $usuarios);
    }


    public function getInfoProveedor($proveedorId)
    {

        ob_clean();
        $data = [];
        $contactos = $this->Proveedor_model->obtener_contactos_proveedor($proveedorId);
        $data["contactos"] = $contactos;
        $rutas = $this->Proveedor_model->obtener_rutas_proveedor($proveedorId);
        $data["rutas"] = $rutas;
        $unidades = $this->Proveedor_model->obtener_unidades_proveedor($proveedorId);
        $data["unidades"] = $unidades;
        // $whatsapp = $this->Proveedor_model->obtener_whatsapp_proveedor($proveedorId);
        // $data["whatsapp"] = $whatsapp;

        $this->responder(false, "",  $data);
    }
}
