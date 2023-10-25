<?php
class Cotizacion_model extends CI_Model
{
    private $tabla = 'cotizaciones';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function getLead($lead_id)
    {
        $query = $this->db->get_where("leads", ["id" => $lead_id]);
        return $query->row_array();
    }

    public function altaCotizacion($data)
    {
        $this->db->insert($this->tabla, $data);
        return $this->db->insert_id();
    }

    public function solicitarCostosToggle($id_cotizacion_ln, $solicitarCostos)
    {
        $dataToUpdate = [
            "solicitarCostos" => $solicitarCostos
        ];
        $this->db->where("id", $id_cotizacion_ln);
        $this->db->update("cotizaciones_ln", $dataToUpdate);
    }
    public function solicitadCostos($cliente, $rutas, $id_usuario)
    {
        //r.id ruta_id, rc.id_cliente, origenDetalle.nombre origen, origenDetalle.id_estado estado_origen,
        //origen.ciudad, destinoDetalle.nombre destino, destino.ciudad, destinoDetalle.id_estado estado_destino
        try {
            $insertDataChunk = [];
            foreach ($rutas as $i => $ruta) {

                $insertDataChunk[] = [
                    "lead_id" => $cliente,
                    "ruta" => $ruta["rutaDetalle"]["ruta_id"],
                    "origen" => $ruta["origen"],
                    "destino" => $ruta["destino"],
                    "target" => $ruta["targetVenta"],
                    "tipo_carga" => $ruta["tipoCarga"],
                    "producto" => $ruta["producto"],
                    "volumen_semanal" => $ruta["volumexsemana"],
                    "requisitos" => $ruta["requisitos"],
                    "observaciones" => $ruta["observaciones"],
                    "fecha_creacion" => $ruta["fecha_creacion"],
                    "usuario_solicita" => $id_usuario,
                    "peso" => $ruta["peso"],
                    "tipoUnidadPeso" => "Toneladas",
                    "id_unidad" => $ruta["tipoUnidad"],
                    "tipo_unidad" => $ruta["tipoUnidadText"],
                    "id_caracteristica_unidad" => $ruta["especificacionUnidad"],
                    "caracteristica_unidad" => $ruta["especificacionUnidadText"],
                    "solicitarCostos" => $ruta["solicitarCostos"],
                ];
            }

            $this->db->insert_batch("cotizaciones_ln", $insertDataChunk);
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }


    public function guardarCosto($data)
    {
        $dataInsert = [
            "costo" => $data["costo"],
            "usuario" => $data["usuario"],
            "fecha_creacion" => date("Y-m-d H:i:s"),
            "id_cotizacion_ruta" => $data["cotizacionln"],
            "id_cotizacion" => $data["id_cotizacion"],
            "proveedor" => $data["proveedor"],
        ];
        $this->db->insert("costos_cotizacion_ln", $dataInsert);
        return $this->db->insert_id();
    }

    public function guardarCotizacionLn($data)
    {
        $dataToUpdate = [
            "porcentajeMargen" => $data["pMargen"],
            "profit" => $data["profit"],
            "costo" => $data["costo"],
            "tarifa" => $data["venta"],
            "estatus" => 3,
            "estatus_nombre" => 'Tarifa cargada'
        ];

        $this->db->where("id", $data["cotizacion_ln"]);
        $this->db->update("cotizaciones_ln", $dataToUpdate);
    }

    public function getCotizacionDetalles($id_cotizacion)
    {
        //         SELECT * FROM cotizaciones c
        // inner join cotizaciones_ln cl on c.id = cl.cotizacion_id
        // WHERE cl.cotizacion_id =9 and cl.tarifa <> 0
        $query = $this->db->select("cl.*, c.*, u.nombre_completo,u.puesto")
            ->from("cotizaciones c")
            ->join("cotizaciones_ln cl", "cl.cotizacion_id = c.id")
            ->join("usuarios u", "u.id = c.id_usuario")
            ->where("c.id", $id_cotizacion)
            ->where("cl.tarifa <>", 0);

        return $query->get()->result_array();
    }

    public function getCotizaciones($cotizaciones)
    {
        $query = $this->db->from("cotizaciones_ln cl")
            ->where_in("cl.id", $cotizaciones)
            ->where("cl.tarifa", 0)->where("cl.tarifa", null);

        $sinTarifa = $query->get()->result_array();
        if (count($sinTarifa) > 0) {
            return false;
        }


        return $this->db->from("cotizaciones_ln cl")
            ->where_in("cl.id", $cotizaciones)->get()->result_array();
    }
}
