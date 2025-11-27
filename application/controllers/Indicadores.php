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

        $profitMes = $this->Indicadores_model->getProfitMesPUsuario($usuario_rainde, $roles);
        $objetivoGlobal = $this->Indicadores_model->getobjetivoGlobalProfit($usuario_rainde, $roles);
        $recuperacion = $this->Indicadores_model->getRecuperacionPUsuario($usuario_rainde, $roles);
        $cumplimientoMeta = $this->Indicadores_model->getCumplimientoMetaPUsuario($usuario_rainde, $roles);
        $vtaPerdida = $this->Indicadores_model->getVtaPerdidaPUsuario($usuario_rainde, $roles);
        $proveedoresActivosPausadosPerdidos = $this->Indicadores_model->getProveedoresActivosPausadosPerdidosPUsuario($usuario_rainde, $roles);
        $comisiones = $this->Indicadores_model->getComisionesPUsuario($usuario_rainde, $roles);
        $data = [
            "profitMes" => $profitMes,
            "objetivoGlobal" => $objetivoGlobal,
            "recuperacion" => $recuperacion,
            "cumplimientoMeta" => $cumplimientoMeta,
            "vtaPerdida" => $vtaPerdida,
            "proveedoresActivosPausadosPerdidos" => $proveedoresActivosPausadosPerdidos,
            "comisiones" => $comisiones,
        ];
        $this->responder(false, "", $data);
    }

    public function detalleMetrica($id_usuario, $metrica)
    {
        $this->load->model('Indicadores_model');
        $this->load->model('Usuario_model');
        $getUsuario = $this->Usuario_model->getUsuario($id_usuario);
        $usuario_rainde = $getUsuario["usuario_rainde"];
        $roles = $getUsuario["roles"];
        $data = [];
        switch ($metrica) {
            case 'profitMes':
                $data = $this->Indicadores_model->getProfitMesPUsuarioDetalle($usuario_rainde, $roles);
                break;
            case 'margenMes':
                $data = $this->Indicadores_model->getProfitMesPUsuarioDetalle($usuario_rainde, $roles);
                break;
            case 'recuperacionMes':
                $data = $this->Indicadores_model->getRecuperacionPUsuarioDetalle($usuario_rainde, $roles);
                break;
            case 'cumplimientoMeta':
                $data = $this->Indicadores_model->getCumplimientoMetaPUsuarioDetalle($usuario_rainde, $roles);
                break;
            case 'vtaPerdida':
                $data = $this->Indicadores_model->getVtaPerdidaPUsuarioDetalle($usuario_rainde, $roles);
                break;
            case 'proveedoresActivos':
                $data = $this->Indicadores_model->getProveedoresActivosPausadosPerdidosPUsuarioDetalle($usuario_rainde, $roles);
                break;
            case 'comisiones':
                $data = $this->Indicadores_model->getComisionesPUsuarioDetalle($usuario_rainde, $roles);
                break;
            default:
                $this->responder(true, "Metrica no encontrada", null, 404);
                break;
        }
        $this->responder(false, "", $data);
    }


    public function getVentas()
    {
        try {
            $fecha_inicio = $this->input->get('fecha_inicio');
            $fecha_fin = $this->input->get('fecha_fin');
            $unidadNegocio = $this->input->get('unidad_negocio');
            if (!$fecha_inicio || !$fecha_fin) {
                $this->responder(true, "Faltan datos", null, 400);
            }

            //validar que las fechas sean validas
            if (!strtotime($fecha_inicio) || !strtotime($fecha_fin)) {
                $this->responder(true, "Las fechas no son validas", null, 400);
            }

            //fechas en formato yyyy-mm-dd
            $fecha_inicio = date('Y-m-d', strtotime($fecha_inicio));
            $fecha_fin = date('Y-m-d', strtotime($fecha_fin));
            if (!strtotime($fecha_inicio) || !strtotime($fecha_fin)) {
                $this->responder(true, "Las fechas no son validas", null, 400);
            }

            //agregar a fecha inicio 00
            $fecha_inicio = $fecha_inicio . ' 00:00:00';
            $fecha_fin = $fecha_fin . ' 23:59:59';

            $this->load->model('Cv_model');
            $ventas = $this->Cv_model->getVentas($fecha_inicio, $fecha_fin, $unidadNegocio);
            $this->responder(false, "Ventas obtenidas correctamente", $ventas, 200);
        } catch (\Throwable $th) {
            $this->responder(true, "Error al obtener las ventas", null, 500);
        }
    }


    public function getCartera(){
        try {
            $fecha_inicio = $this->input->get('fecha_inicio');
            $fecha_fin = $this->input->get('fecha_fin');
            $unidadNegocio = $this->input->get('unidad_negocio');
            if (!$fecha_inicio || !$fecha_fin) {
                $this->responder(true, "Faltan datos", null, 400);
            }

            // Permitir que fecha_inicio=-1 signifique "toda la cartera sin rango de fecha"
            if ($fecha_inicio !== '-1') {

                //validar que las fechas sean validas
                if (!strtotime($fecha_inicio) || !strtotime($fecha_fin)) {
                    $this->responder(true, "Las fechas no son validas", null, 400);
                }

                //fechas en formato yyyy-mm-dd
                $fecha_inicio = date('Y-m-d', strtotime($fecha_inicio));
                $fecha_fin = date('Y-m-d', strtotime($fecha_fin));
                if (!strtotime($fecha_inicio) || !strtotime($fecha_fin)) {
                    $this->responder(true, "Las fechas no son validas", null, 400);
                }

                //agregar a fecha inicio 00:00:00 fecha fin 23:59:59
                $fecha_inicio = $fecha_inicio . ' 00:00:00';
                $fecha_fin = $fecha_fin . ' 23:59:59';
            }

            $this->load->model('Cv_model');
            $carteras = $this->Cv_model->getCartera($fecha_inicio, $fecha_fin, $unidadNegocio);
            $this->responder(false, "Carteras obtenidas correctamente", $carteras, 200);
        } catch (\Throwable $th) {
            $this->responder(true, "Error al obtener las carteras", null, 500);
        }
    }

    public function getCuentasXPagar(){
        try {
            $fecha_inicio = $this->input->get('fecha_inicio');
            $fecha_fin = $this->input->get('fecha_fin');
            $unidadNegocio = $this->input->get('unidad_negocio');
            if (!$fecha_inicio || !$fecha_fin) {
                $this->responder(true, "Faltan datos", null, 400);
            }

            // Permitir que fecha_inicio=-1 signifique "toda la cartera sin rango de fecha"
            if ($fecha_inicio !== '-1') {

                //validar que las fechas sean validas
                if (!strtotime($fecha_inicio) || !strtotime($fecha_fin)) {
                    $this->responder(true, "Las fechas no son validas", null, 400);
                }

                //fechas en formato yyyy-mm-dd
                $fecha_inicio = date('Y-m-d', strtotime($fecha_inicio));
                $fecha_fin = date('Y-m-d', strtotime($fecha_fin));
                if (!strtotime($fecha_inicio) || !strtotime($fecha_fin)) {
                    $this->responder(true, "Las fechas no son validas", null, 400);
                }

                //agregar a fecha inicio 00:00:00 fecha fin 23:59:59
                $fecha_inicio = $fecha_inicio . ' 00:00:00';
                $fecha_fin = $fecha_fin . ' 23:59:59';
            }

            $this->load->model('Cv_model');
            $carteras = $this->Cv_model->getCuentasXPagar($fecha_inicio, $fecha_fin, $unidadNegocio);
            $this->responder(false, "Carteras obtenidas correctamente", $carteras, 200);
        } catch (\Throwable $th) {
            $this->responder(true, "Error al obtener las carteras", null, 500);
        }
    }
}
