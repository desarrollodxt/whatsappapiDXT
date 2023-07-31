<?php
class Ogs_model extends CI_Model
{
    private $tabla = 'ordenes_sevicios_generales';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function getEspecificacionesCarga(array $especificacionesCarga, int $id_cliente)
    {
        foreach ($especificacionesCarga as $ix => $value) {

            if (!is_numeric($value)) {
                //validar sino existe, si existe extraer el id
                $query = $this->db->select('id')->from("caracteristicas_extras")->where('nombre', $value)->get();
                $id = $query->result_array();
                if (count($id) > 0) {
                    $especificacionesCarga[$ix] = $id[0]["id"];
                } else {
                    $id =  $this->altaEspecificacionesCarga($id_cliente, $value);
                    $especificacionesCarga[$ix] = $id;
                }
            }
        }
        $array = $this->db->select('nombre as especificaciones_viaje')->from("caracteristicas_extras")->where_in('id', $especificacionesCarga)->get()->result_array();

        $arrayColumn = array_column($array, 'especificaciones_viaje');
        return $arrayColumn;
    }

    public function altaEspecificacionesCarga($id_cliente, $especificacionesCarga)
    {
        $this->db->insert('caracteristicas_extras', [
            'id_cliente' => $id_cliente,
            'nombre' => $especificacionesCarga
        ]);

        return $this->db->insert_id();
    }

    public function altaOrden($body, $especificacionesCarga)
    {
        $this->db->trans_begin();
        try {

            $dataToInsert = [
                "cliente_id" => $body["cliente"],
                "ruta" => $body["ruta"],
                "fecha_carga" => $body["fechaCarga"],
                "fecha_descarga" => $body["fechaDescarga"],
                "referencia_cliente" => $body["referencia"],
                "tipo_movimiento" => "",
                "es_redondo" => 0,
                "especificacion_unidad" => $body["especificacionUnidad"],
                "observaciones" => serialize($especificacionesCarga),
                "productos_cargar" => $body["tipoCarga"],
                "tipo_unidad" => $body["tipoUnidad"],
                "peso" => $body["peso"],
                "tipo_peso" => $body["tipoUnidadPeso"],
            ];

            $this->db->insert($this->tabla, $dataToInsert);
            $id_ogs = $this->db->insert_id();

            //traer ogs que acabo de insertar
            $query = $this->db->select("og.id ogs, c.nombre_corto cliente, or.ciudad origen, des.ciudad destino,og.fecha_carga, og.fecha_descarga, og.referencia_cliente, og.tipo_movimiento, og.es_redondo, cu.unidad, ccu.caracteristica,tc.carga, og.observaciones, og.peso, og.tipo_peso, og.created_at fecha_creacion")->from($this->tabla . " as og")
                ->join("rutas as r", "r.id = og.ruta")
                ->join("direcciones as or", "or.id = r.origen")
                ->join("direcciones as des", "des.id = r.destino")
                ->join("cat_caracteristicas_unidades as ccu", "ccu.id = og.especificacion_unidad")
                ->join("cat_unidades as cu", "cu.id = og.tipo_unidad")
                ->join("clientes as c", "c.id = og.cliente_id")
                ->join("tipos_cargas as tc", "tc.id = og.productos_cargar")
                ->where("og.id", $id_ogs)->get();

            $ogs = $query->result_array();
            // $this->db->trans_commit();

            $ogs = $ogs[0];
            $ogs["observaciones"] = unserialize($ogs["observaciones"]);
            $ogs["observaciones"] = array_values($ogs["observaciones"]);
            return $ogs;
        } catch (\Throwable $th) {
            //throw $th;
            $this->db->trans_rollback();
            return false;
        }
    }
}
