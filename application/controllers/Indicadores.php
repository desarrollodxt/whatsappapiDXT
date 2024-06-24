<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Indicadores extends CI_Controller
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
        $this->load->model('Bitacora_model');
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

    public function getIndicadoresPlanner($id)
    {
        $this->load->model('Indicadores_model');
        $this->load->model('Usuario_model');
        $getUsuario = $this->Usuario_model->getUsuario($id);
        $usuario_rainde = $getUsuario["usuario_rainde"];
        $roles = $getUsuario["roles"];

        $profitMes = $this->Indicadores_model->getProfitMesPUsuario($usuario_rainde,$roles);
        $pMargenMes = $this->Indicadores_model->getPMargenMesPUsuario($usuario_rainde,$roles); 
        $recuperacion = $this->Indicadores_model->getRecuperacionPUsuario($usuario_rainde,$roles);
        $cumplimientoMeta = $this->Indicadores_model->getCumplimientoMetaPUsuario($usuario_rainde,$roles);
        $vtaPerdida = $this->Indicadores_model->getVtaPerdidaPUsuario($usuario_rainde,$roles);
        $proveedoresActivosPausadosPerdidos = $this->Indicadores_model->getProveedoresActivosPausadosPerdidosPUsuario($usuario_rainde,$roles);
        
        $data = [
            "profitMes" => $profitMes,
            "pMargenMes" => $pMargenMes,
            "recuperacion" => $recuperacion,
            "cumplimientoMeta" => $cumplimientoMeta,
            "vtaPerdida" => $vtaPerdida,
            "proveedoresActivosPausadosPerdidos" => $proveedoresActivosPausadosPerdidos
        ];
        $this->responder(false, "", $data);
    }

   
}
