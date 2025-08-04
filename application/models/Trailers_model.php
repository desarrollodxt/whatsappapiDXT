<?php
class Trailers_model extends CI_Model
{
    private $tabla = 'trailers_state';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    public function obtener_estado_trailer_por_estatus($estado, $produccion, $detencion, $asignacion, $direccion)
    {
        $query = $this->db->select('cl.nombre_corto, tre.*, tr.nombre trailer, tr.id id_tabla_activo, TIMESTAMPDIFF(MINUTE,tre.fecha_cambio_estado,now()) diffTiempo, tre.latitude,tre.longitude, case when ac.load_id is not null then concat(ac.cliente,\' Load id:\',ac.load_id ,\'- origen:\',ac.origen,\'- destino:\',ac.destinatario, \' - Fecha carga: \',ac.fecha_carga ,\' - Fecha descarga: \', ac.fecha_entrega, \' - Tarifa: $\', FORMAT(ac.costo_carga,2)) else \'\' end infoViaje, ac.bol')
            ->from($this->tabla . " as tre")
            ->join("activos tr", "tr.activo_id  = tre.trailer_id and tipo = 2")
            ->join("(SELECT trailer_id, direccion_texto, trailer,latitude,longitude  from samsara_trailers_data std where (trailer_id,`timestamp` ) in (SELECT trailer_id, max(`timestamp`) from samsara_trailers_data GROUP BY trailer_id) group by trailer_id) as dn", "dn.trailer_id = tre.trailer_id", "left")
            ->join("actividad_clientes ac", " ac.load_id = tre.load_id", "left")
            ->join("clientes cl", "cl.id = tre.cliente", "left");
        if ($estado != null) {
            $query->where("tre.estado", $estado);
        }
        if ($produccion != null) {
            $query->where("tre.produccion", $produccion);
        }
        if ($detencion != null) {
            $query->where("tre.detencion", $detencion);
        }

        if ($asignacion != null) {
            $query->where("tre.asignacion", $asignacion);
        }

        $data  = $query->get();
        return $data->result_array();
    }


    public function obtener_estado_trailers()
    {
        $query = $this->db->select('cl.nombre_corto, tre.*, tr.nombre trailer, tr.id id_tabla_activo, TIMESTAMPDIFF(MINUTE,tre.fecha_cambio_estado,now()) diffTiempo, tre.latitude,tre.longitude, case when ac.load_id is not null then concat(ac.cliente,\' Load id:\',ac.load_id ,\'- origen:\',ac.origen,\'- destino:\',ac.destinatario, \' - Fecha carga: \',ac.fecha_carga ,\' - Fecha descarga: \', ac.fecha_entrega, \' - Tarifa: $\', FORMAT(ac.costo_carga,2)) else \'\' end infoViaje, ac.bol')
            ->from($this->tabla . " as tre")
            ->join("activos tr", "tr.activo_id  = tre.trailer_id and tipo = 2")
            ->join("(SELECT trailer_id, direccion_texto, trailer,latitude,longitude  from samsara_trailers_data std where (trailer_id,`timestamp` ) in (SELECT trailer_id, max(`timestamp`) from samsara_trailers_data GROUP BY trailer_id) group by trailer_id) as dn", "dn.trailer_id = tre.trailer_id", "left")
            ->join("actividad_clientes ac", " ac.load_id = tre.load_id", "left")
            ->join("clientes cl", "cl.id = tre.cliente", "left")->get();
        return $query->result_array();
    }

    public function insertar_peticion_samsara($data)
    {
        //columna de la tabla samsara_trailers_data que se insertaran
        // trailer_id,timestamp,latitude,longitude,obdOdometerMeters,trailer,speedMilesPerHour,direccion_texto
        $insertOrUpdate = "";
        $dataInsertBulk = array_map(function ($caja) use (&$insertOrUpdate) {
            $trailerName = $caja['name'];
            $trailerName = str_replace("/", "", $trailerName);
            $trailerName = trim($trailerName);

            $insertOrUpdate .= "(" . $caja['id'] . ",'" . $trailerName . "'),"; //insert or update

            return array(
                'trailer_id' => $caja['id'],
                'timestamp' => $caja['gps'][0]["time"],
                'latitude' => $caja["gps"][0]['latitude'],
                'longitude' => $caja["gps"][0]['longitude'],
                'obdOdometerMeters' => isset($caja['gpsOdometerMeters']) ?  $caja['gpsOdometerMeters'][0]["value"] : "0",
                'trailer' => $trailerName,
                'speedMilesPerHour' => $caja["gps"][0]['speedMilesPerHour'],
                'direccion_texto' => $caja["gps"][0]['reverseGeo']["formattedLocation"],
            );
        }, $data);

        //insertar en la tabla samsara_trailers_data
        $this->db->insert_batch('samsara_trailers_data', $dataInsertBulk);

        //insertar en la tabla trailers o actualizar si ya existe
        $insertOrUpdate = substr($insertOrUpdate, 0, -1);
        $sql = "INSERT INTO activos (activo_id,nombre ) VALUES " . $insertOrUpdate . " ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), activo_id=VALUES(activo_id);";

        $this->db->query($sql);

        //obtener el id del ultimo registro insertado
        return true;
    }

    public function getStateTrailer($caja_id)
    {
        $query = $this->db->select('*, TIMESTAMPDIFF(MINUTE,trailers_state.tiempo,now()) diferencia')->from("trailers_state")->where('trailer_id', $caja_id)->order_by('tiempo', 'DESC')->limit(1)->get();
        return $query->row_array();
    }

    public function insertar_estado_trailers($caja_id, $estado)
    {
        $this->db->insert("trailers_state_history", $estado);
        $this->db->insert("trailers_state", $estado);

        return $this->db->insert_id();
    }

    public function  actualizar_estado_trailers($caja_id, $estado, $cambioEstado = false)
    {

        $estado["fecha_cambio_estado"] = date("Y-m-d H:i:s");

        $estado['tiempo'] = date("Y-m-d H:i:s");
        $this->db->where('trailer_id', $caja_id);
        $this->db->update("trailers_state", $estado);

        return $this->db->affected_rows();
    }

    // public function getLoadInfo($load_id)
    // {
    //     $this->from("a")
    // }

    public function insertar_estado_trailers_historial($caja_id, $estado, $cambioEstado = false)
    {
        if ($cambioEstado) {
            $estado["fecha_cambio_estado"] = date("Y-m-d H:i:s");
        }
        $this->db->insert("trailers_state_history", $estado);

        return $this->db->insert_id();
    }

    public function getTrailers()
    {
        $query = $this->db->select('nombre trailer, activo_id trailer_id')->from("activos")->where('tipo', 2)->get();
        return $query->result_array();
    }
    public function getStateHistory($trailer_id)
    {
        $query = $this->db->select('*')->from("trailers_state_history")->where('trailer_id', $trailer_id)->order_by('id', 'ASC')->get();
        return $query->result_array();
    }

    public function actualizar_propiedad_trailers($trailer_id, $propiedad, $valor, $cajaEstado, $usuarioId = null)
    {
        try {
            $this->db->trans_begin();

            //insertar en la tabla trailers_state_history el estado anterior
            $this->db->select('*, TIMESTAMPDIFF(MINUTE,trailers_state.tiempo,now()) diferencia');
            $this->db->from("trailers_state");
            $this->db->where('trailer_id', $trailer_id);
            $this->db->order_by('tiempo', 'DESC');
            $this->db->limit(1);
            $query = $this->db->get();

            $estadoAnterior = $query->row_array();
            $estadoAnterior['tiempo_estado'] = $estadoAnterior['diferencia'];
            $estadoAnterior["tiempo"] = date("Y-m-d H:i:s");
            unset($estadoAnterior["diferencia"]);
            unset($estadoAnterior["id"]);
            //actualizar la nueva propiedad $propiedad con el valor $valor
            $estadoAnterior[$propiedad] = $valor;


            $estadoAnterior["usuario_actualizo"] = $usuarioId;

            //insertar en la tabla trailers_state_history el estado anterior
            $this->db->insert("trailers_state_history", $estadoAnterior);
            //actualizar el state actual
            $this->db->where('trailer_id', $trailer_id);

            $arrayUpdate = array($propiedad => $valor, "tiempo" => date("Y-m-d H:i:s"), "usuario_actualizo" => $usuarioId);
            if ($propiedad == 'asignacion' && $valor == 1) {
                $arrayUpdate["produccion"] = 3;
            }
            $this->db->update("trailers_state", $arrayUpdate);
            $this->db->trans_commit();
            return $this->db->affected_rows();
        } catch (\Throwable $th) {
            $this->db->trans_rollback();
            return false;
        }
    }

    public function update_state_trailer_ES_patio($trailer, $estado, $retirarAsignacion)
    {
        $this->db->trans_begin();

        try {
            //traer el estado actual de trailers_state
            $this->db->select('*, TIMESTAMPDIFF(MINUTE,trailers_state.tiempo,now()) diferencia');
            $this->db->from("trailers_state");
            $this->db->where('trailer', $trailer);
            $this->db->limit(1);
            $query = $this->db->get();
            $estadoAnterior = $query->row_array();
            $estadoAnterior['tiempo_estado'] = $estadoAnterior['diferencia'];
            $estadoAnterior["tiempo"] = date("Y-m-d H:i:s");
            unset($estadoAnterior["diferencia"]);
            unset($estadoAnterior["id"]);
            //insertar en la tabla trailers_state_history el estado anterior
            $this->db->insert("trailers_state_history", $estadoAnterior);

            //actualizar la columna estado con $estado
            $estadoAnterior["estado"] = $estado;
            $estadoAnterior["usuario_actualizo"] = 1;

            //si $retirarAsignacion es 1, entonce pasa a producci�n 1 y borra el load id y el cliente

            if ($retirarAsignacion == 1) {
                $estadoAnterior["produccion"] = 1;
                $estadoAnterior["load_id"] = null;
                $estadoAnterior["cliente"] = 0;

                //actualizar el state actual
                $this->db->where('trailer', $trailer);
                $this->db->update("trailers_state", array(
                    "estado" => $estado,
                    "tiempo" => date("Y-m-d H:i:s"),
                    "usuario_actualizo" => 1,
                    "produccion" => 1,
                    "load_id" => null,
                    "cliente" => 0,
                    "asignacion" => 0,
                ));
            } else {

                $this->db->where('trailer', $trailer);
                $this->db->update("trailers_state", array(
                    "estado" => $estado,
                    "tiempo" => date("Y-m-d H:i:s"),
                    "usuario_actualizo" => 1
                ));
            }

            $this->db->trans_commit();
            return true;
        } catch (\Throwable $th) {
            $this->db->trans_rollback();
            return false;
        }
    }


    public function actualizar_cliente_trailers($trailer_id, $cliente_id)
    {
        //actualizar cliente en trailer_state
        $this->db->where('trailer_id', $trailer_id);
        $this->db->update("trailers_state", array("cliente" => $cliente_id));
    }

    public function actualizar_load_trailers($trailer_id, $load_id)
    {
        //actualizar cliente en trailer_state
        $this->db->where('trailer_id', $trailer_id);
        $this->db->update("trailers_state", array("load_id" => $load_id));

        //actualizar load_id en el ultimo cambio de estatus para este trailer id en trailers_state_history
        $this->db->where('trailer_id', $trailer_id);
        $this->db->order_by('tiempo', 'DESC');
        $this->db->limit(1);
        $this->db->update("trailers_state_history", array("load_id" => $load_id));


        //agregar el load id a la lista de load id ocupados trailers_loads_asginacion
        $this->db->insert("trailers_loads_asginacion", array("trailer_id" => $trailer_id, "load_id" => $load_id));
    }

    public function quitar_load_cliente_trailers($trailer_id, $usuarioId = null)
    {
        //actualizar cliente en trailer_state
        $this->db->where('trailer_id', $trailer_id);
        $this->db->update("trailers_state", array("load_id" => null, "cliente" => 0, "usuario_actualizo" => $usuarioId));

        // $this->db->delete("trailers_loads_asginacion", array("trailer_id" => $trailer_id));
    }

    public function estadoSetear()
    {
        try {
            $this->db->trans_begin();
            $query = $this->db->query("SELECT  trailer_id, MAX(`timestamp`) `timestamp` FROM samsara_trailers_data group by trailer_id");
            $cuentas = $query->result_array();

            foreach ($cuentas as $values) {
                $this->db->update("trailers_state", array("fecha_cambio_estado" => $values["timestamp"]), array("trailer_id" => $values["trailer_id"]));
            }
            $this->db->trans_commit();
        } catch (\Throwable $th) {
            //throw $th;
        }
    }


    public function insertar_salida_entrada($salida_entrada)
    {
        $this->db->insert("entradas_salidas_patio", $salida_entrada);
        return $this->db->insert_id();
    }

    public function cerrarFolio()
    {
    }

    public function crearFolioSalida($trailer, $tracktor, $fecha, $idSalida)
    {
        $datos = array(
            "trailer_id" => null,
            "trailer" => $trailer,
            "fecha_inicio" => $fecha,
            "fecha_fin" => null,
            "created_at" => $fecha,
            "salida_patio" => $idSalida,
            "entrada_patio" => null,
            "fecha" => $fecha,
            "tracktor" => $tracktor,
            "close" => 0,
        );
        $this->db->insert("viajes_trailers",);
        return $this->db->insert_id();
    }

    public function obtenerEntradasSalidas($type, $activo, $fechaInicio, $fechaFin)
    {
        $campoDeCondicion = $type == 'trailer' ? "trailer_id" : "tracktor_id";

        $this->db->from("entradas_salidas_patio")->where($campoDeCondicion, $activo)->where("fecha >= ", $fechaInicio)->where("fecha <= ", $fechaFin)
            ->order_by("fecha", "asc");
        // $sql = $this->db->get_compiled_select();
        // var_dump($sql);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getActivos($tipo = null)
    {
        $query = $this->db->select('nombre, (CASE WHEN a.tipo = 1 THEN "Tractor" ELSE "Trailer" END) tipo, activo_id')
            ->from("activos a");
        if ($tipo != null) {
            $query->where("a.tipo", $tipo);
        }
        $query = $query->get();
        return $query->result_array();
    }
    public function getFirstData($fechaInicio, $trailer_id)
    {
        $sql = "SELECT * FROM samsara_trailers_data std where std.`timestamp` > CONVERT_TZ(?, '-06:00','+00:00') and trailer = ? order by std.`timestamp` ASC limit 1;";
        $query = $this->db->query($sql, array($fechaInicio, $trailer_id));
        return $query->row_array();
    }

    public function getEstados($fechaInicio, $fechaFin, $activo)
    {
        $sql = "SELECT CONVERT_TZ(?, '-06:00','+00:00') tiempoInicio,CONVERT_TZ(?, '-06:00','+00:00') tiempoFin , tsh.* FROM trailers_state_history tsh where tsh.created_at >= ? and tsh.created_at <=  ? and tsh.trailer = ? order by tsh.created_at ASC";
        $query = $this->db->query($sql, array($fechaInicio, $fechaFin, $fechaInicio, $fechaFin, $activo));
        return $query->result_array();
    }

    public function obtenerTractoYOperadorByLLCId($pedido_id)
    {
        // $sql = "SELECT * from bitacora where folio_pedido_usa = ? order by id desc limit 1;";
        $query = $this->db->select("*")->from("bitacoras")->where("folio_pedido_usa", $pedido_id)->order_by("id", "desc")->limit(1)->get();
        return $query->row_array();
    }
}
