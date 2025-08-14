<?php
class Cv_model extends CI_Model
{
    private $tabla = 'api a';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    public function getInfoFactura($id_cliente, $cv, $factura)
    {
        $this->db->select("a.cliente_nombre_corto cliente, a.cv, a.fact_dxt factura, a.referencia_cliente referencia,a.fecha_fact_dxt fecha, a.vta_total_autorizada monto ")->from($this->tabla)
            ->where("a.id_cliente", $id_cliente)
            ->where("a.cv", $cv)
            ->where("a.fact_dxt", $factura);

        $query = $this->db->get();
        return $query->result_array();
    }


    public function getVentasCRM($fecha_inicio, $fecha_fin)
    {
        $this->db->from($this->tabla);

        $sql = "SELECT 
                    cv.id_cliente,
                    cv.cliente_nombre_corto,
                    cv.transportista_nombre_comercial,
                    cv.id_transportista,
                    cv.orig_dest_cv,
                    cv.orig_dest_loc_cv,
                    cv.fecha_carga_ci,
                    cv.fecha_fact_dxt,
                    cv.fecha_cobro_dxt,
                    cv.fecha_pago_prov,
                    cv.vta_total_autorizada,
                    cv.saldo_x_cobrar,
                    cv.costo_total_autorizada,
                    cv.saldo_x_pagar,
                    NOW() as fecha_insert,
                    cv.moneda,
                    cv.tipo_de_cambio,
                    cv.utilidad,
                    cv.porcentaje,
                    cv.cv,
                    cv.referencia_cliente,
                    cv.num_fact_prov
                FROM api cv 
                WHERE cv.fecha_carga_ci BETWEEN ? AND ? AND cv.estatus_cv = 'ACTIVO'";

        $query = $this->db->query($sql, array($fecha_inicio, $fecha_fin));
        $results = $query->result_array();

        $ventas = array();
        foreach ($results as $row) {
            $venta = array(
                'id_cliente' => $row['id_cliente'],
                'nombre_cliente' => $row['cliente_nombre_corto'],
                'nombre_proveedor' => $row['transportista_nombre_comercial'],
                'id_proveedor' => $row['id_transportista'],
                'fecha_servicio' => $row['fecha_carga_ci'],
                'fecha_factura' => $row['fecha_fact_dxt'],
                'fecha_pago_cliente' => $row['fecha_cobro_dxt'],
                'fecha_pago_proveedor' => $row['fecha_pago_prov'],
                'cliente_subtotal' => null,
                'cliente_total' => $row['vta_total_autorizada'],
                'balance_cliente' => $row['saldo_x_cobrar'],
                'proveedor_subtotal' => null,
                'proveedor_total' => $row['costo_total_autorizada'],
                'balance_proveedor' => $row['saldo_x_pagar'],
                'fecha_insert' => $row['fecha_insert'],
                'costo_moneda' => $row['moneda'],
                'costo_tipo_cambio' => $row['tipo_de_cambio'],
                'margen' => $row['utilidad'],
                'porcentaje' => $row['porcentaje'],
                'folio_sistema' => $row['cv'],
                'referencia' => $row['referencia_cliente'],
                'folio_proveedor' => $row['num_fact_prov'],
                'items' => array(
                    array(
                        'nombre_producto' => $row['orig_dest_cv'],
                        'descripcion' => $row['orig_dest_cv'],
                        'fecha' => $row['fecha_carga_ci'],
                        'cantidad' => 1,
                        'precio_unit' => $row['vta_total_autorizada'],
                        'precio_total' => $row['vta_total_autorizada'],
                        'costo_unit' => $row['costo_total_autorizada'],
                        'costo_total' => $row['costo_total_autorizada'],
                        'balance' => $row['saldo_x_pagar'],
                        'folio_proveedor' => $row['num_fact_prov'],
                        'folio_sistema' => $row['cv'],
                        'referencia' => $row['referencia_cliente'],
                        'porcentaje_margen' => $row['porcentaje'],
                        'margen' => $row['utilidad'],
                        'moneda' => $row['moneda'],
                        'venta_tipo_cambio' => $row['tipo_de_cambio'],
                        'costo_moneda' => $row['moneda'],
                        'costo_tipo_cambio' => $row['tipo_de_cambio']
                    )
                )
            );
            $ventas[] = $venta;
        }

        return $ventas;
    }
}
