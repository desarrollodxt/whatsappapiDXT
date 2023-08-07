<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Roles extends CI_Controller
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

    public function getRolesYPermisos()
    {
        ob_clean();
        $data = [];

        $roles = $this->Roles_model->obtener_roles();
        $permisos = $this->Roles_model->obtener_permisos();

        $data["roles"] = $roles;
        $data["permisos"] = $permisos;
        $this->responder(false, "",  $data);
    }

    public function crearRol()
    {
        $rol = $this->body["rol"];
        $rol_id = $this->Roles_model->CrearRol($rol);

        $this->responder(false, "",  ["rol_id" => $rol_id]);
    }

    public function actualizarPermisos()
    {
        $nombre = $this->body["nombre"];
        $permisos = $this->body["permisos"];

        $this->Roles_model->actualizarPermisos($nombre, $permisos);
        $this->responder(false, "",  []);
    }
}
