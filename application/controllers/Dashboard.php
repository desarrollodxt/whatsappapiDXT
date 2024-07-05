<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends CI_Controller
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
        $this->load->model("Usuario_model");
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

    public function metricas($id_usuario, $metrica, $periodo)
    {
        //hay que generar metricas para graficar con chart js
        if (!$id_usuario || !$metrica || !$periodo) {
            $this->responder(true, "Error en la peticion", null, 400);
        }

        $id_usuario = intval($id_usuario);

        $this->load->model("Indicadores_model");
        $usuario = $this->Usuario_model->getUsuario($id_usuario);
        $fecha_inicio = null;
        $fecha_fin = null;

        $this->getFechasPeriodo($periodo, $fecha_inicio, $fecha_fin);
        $data=null;
        switch ($metrica) {
            case 'profit':

                $data = $this->Indicadores_model->getProfit($usuario["usuario_rainde"], $fecha_inicio, $fecha_fin);
                break;

            default:
                # code...
                break;
        }

        $this->responder(false, "", $data);
    }

    public function getFechasPeriodo($periodo, &$fecha_inicio, &$fecha_fin)
    {
        switch ($periodo) {
            case '15d':
                $fecha_inicio = date("Y-m-d");
                $fecha_fin = date("Y-m-d", strtotime("-15 days"));
                break;

            case '30d':
                $fecha_inicio = date("Y-m-d");
                $fecha_fin = date("Y-m-d", strtotime("-30 days"));
                break;
            case '60d':
                $fecha_inicio = date("Y-m-d");
                $fecha_fin = date("Y-m-d", strtotime("-60 days"));
                break;
            case '90d':
                $fecha_inicio = date("Y-m-d");
                $fecha_fin = date("Y-m-d", strtotime("-90 days"));
                break;
            case '1y':
                $fecha_inicio = date("Y-m-d");
                $fecha_fin = date("Y-m-d", strtotime("-1 year"));
                break;
            default:
                $fecha_fin = date("Y-m-d");
                $fecha_inicio = date("Y-m-d", strtotime("-7 days"));
                break;
        }
    }
}
