<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Login extends CI_Controller
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
        $_body = file_get_contents('php://input');
        // var_dump($_body);

        $this->load->model('Usuario_model');
        $this->load->model('Lead_model');
        //validar que haya un body
        if (!$_body) {
            $this->body = [];
        } else {
            $this->body = json_decode($_body, true);
        }
        //validar que el body sea un json, sino responder un error de peticion
        if (!is_array($this->body)) {
            // $this->responder(true, "Error en la peticion", null, 400);
        }
    }

    public function login_soporte_sistema()
    {
        try {

            $usuario = $this->body['usuario'];
            $password = $this->body['password'];
            $usuario = $this->Usuario_model->login_soporte_sistema($usuario, $password);
            if ($usuario) {
                $this->responder(false, "Usuario encontrado", $usuario);
            } else {
                $this->responder(true, "Usuario no encontrado", null, 404);
            }
        } catch (\Throwable $th) {
            $this->responder(true, "Error en el servidor", [
                "error" => $th->getMessage(),
                "trace" => $th->getTrace()
            ], 500);
        }
    }
}
