<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;


class FacturasController extends CI_Controller
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
        header('Access-Control-Allow-Origin: https://dxt.com.mx');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            header('Access-Control-Allow-Origin: https://dxt.com.mx');
            header('Access-Control-Allow-Headers: Content-Type');
            exit;
        }
        $_body = file_get_contents('php://input');
        // var_dump($_body);
        $this->load->model('Usuario_model');
        $this->load->model('Lead_model');
        $this->load->model('Factura_model');
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


    public function capturaFacturaFactoraje()
    {
        // Validar que el método de la solicitud sea POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(true, "Método no permitido", null, 405);
        }


        $this->body = $_POST;
        // Validar que el body no esté vacío
        if (empty($this->body)) {
            $this->responder(true, "El cuerpo de la solicitud está vacío", null, 400);
        }

        // Validar los campos requeridos
        $required_fields = [
            'proveedor',
            'fecha_liberacion_factoraje',
            'folio',
            'cv',
            'dias_credito',
            'moneda',
            'subtotal',
            'iva',
            'total',
            'usuario'
        ];

        foreach ($required_fields as $field) {
            if (!isset($this->body[$field]) || empty($this->body[$field])) {
                $this->responder(true, "El campo $field es obligatorio", null, 400);
            }
        }

        $cv = $this->Factura_model->getCvInfo($this->body["cv"]);

        if (empty($cv)) {
            $this->responder(true, "El cv no existe", null, 400);
        }

        if ($cv[0]["saldo_x_pagar"] > 0) {
            $this->responder(true, "El cv tiene saldo pendiente", null, 400);
        }

        $factura = [
            "proveedor" => $this->body["proveedor"],
            "documento" => "factura",
            "fecha_liberacion_factoraje" => $this->body["fecha_liberacion_factoraje"],
            "folio" => $this->body["folio"],
            "cv" => $this->body["cv"],
            "dias_credito" => $this->body["dias_credito"],
            "moneda" => $this->body["moneda"],
            "subtotal" => $this->body["subtotal"],
            "iva" => $this->body["iva"],
            "iva_ret" => $this->body["iva_ret"],
            "total" => $this->body["total"],
            "usuario" => $this->body["usuario"],
            "referencia" => $this->body["referencia"],
            "area_gasto" => 'Operacion',
            "comentarios" => $this->body["comentarios"],
            "fecha_creacion" => date('Y-m-d H:i:s'),
            "fecha_descarga_cv" => $cv[0]["fecha_descarga_cv"],
            "balance" => $this->body["total"],
        ];

        // Llamar a la función capturarFactura del modelo Factura_model
        $result = $this->Factura_model->capturarFactura($factura);

        if ($result) {
            $this->responder(false, "Factura capturada exitosamente", $result, 200);
        } else {
            $this->responder(true, "Error al capturar la factura", null, 500);
        }
    }


    public function capturarPagoFactura()
    {
        try {
            // Validar que el método de la solicitud sea POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->responder(true, "Método no permitido", null, 405);
            }

            $this->body = $_POST;
            // Validar que el body no esté vacío
            if (empty($this->body)) {
                $this->responder(true, "El cuerpo de la solicitud está vacío", null, 400);
            }

            // Validar los campos requeridos
            $required_fields = [
                'cv',
                'fecha_captura',
                'monto_capturar',
                'usuario'
            ];

            foreach ($required_fields as $field) {
                if (!isset($this->body[$field]) || empty($this->body[$field])) {
                    $this->responder(true, "El campo $field es obligatorio", null, 400);
                }
            }

            $cv = $this->Factura_model->getCvInfo($this->body["cv"]);

            if (empty($cv)) {
                $this->responder(true, "El cv no existe", null, 400);
            }

            $factura = $this->Factura_model->getFacturaByCv($this->body["cv"]);
            $factura = $factura[0];
            if (empty($factura)) {
                $this->responder(true, "No existe una factura asociada al cv", null, 400);
            }

            if ($factura["balance"] <= 0) {
                $this->responder(true, "El balance de la factura es cero o negativo", null, 400);
            }

            $pago = [
                "cv" => $this->body["cv"],
                "fecha_captura" => $this->body["fecha_captura"],
                "monto_capturar" => $this->body["monto_capturar"],
                "usuario" => $this->body["usuario"],
                "factura_factoraje_id" => $factura["id"]
            ];

            $result = $this->Factura_model->capturarPagoFactura($pago);

            if ($result) {
                $this->responder(false, "Pago capturado exitosamente", $result, 200);
            } else {
                $this->responder(true, "Error al capturar el pago", null, 500);
            }
        } catch (\Throwable $th) {
            $this->responder(true, $th->getMessage(), null, 500);
        }
    }
}
