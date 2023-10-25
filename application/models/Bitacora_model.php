<?php
class Bitacora_model extends CI_Model
{
    private $tabla = 'bitacora_hd';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    public function getBitacora($cv)
    {
        //CASE WHEN bh.referencia is null THEN api.referencia_cliente WHEN bh.referencia is not null then bh.referencia ELSE '' END referencia
        $encabezado = $this->db->query("SELECT bh.*,api.placas_tracto, api.placas_remolque, api.transportista_nombre_comercial,api.cliente_solicitud, api.cliente_nombre_corto,api.orig_dest_solicitud,CASE WHEN bh.referencia is null THEN api.referencia_cliente WHEN bh.referencia <> '' then bh.referencia ELSE api.referencia_cliente END referencia FROM bitacora_hd bh inner join api on api.cv = bh.cv  where api.cv = '$cv'")->row_array();
        $sql = "SELECT bh.*,
        bl.*,
        u.nombre usuario_nombre,   (
                SELECT GROUP_CONCAT(CONCAT(bitacora_files.url, bitacora_files.nombre) SEPARATOR ', ')
                FROM bitacora_files
                WHERE bitacora_files.bitacora_ln = bl.id and bitacora_files.extension in ('jpeg','jpg','png')
            ) AS archivos_relacionados FROM bitacora_hd bh inner join bitacora_ln bl on bl.id_bitacora_hd = bh.id left join usuarios u on u.id = bl.usuario where bh.cv = ? order by bl.created_at DESC";
        $query = $this->db->query($sql, [$cv]);
        $bitacora = $query->result_array();

        return ["encabezado" => $encabezado, "movimientos" => $bitacora];
    }


    public function setMovimientoNuevo($cv,  $usuario, $estatus, $observaciones, $coordenadas, $ubicacion, $fechaHoraMovimiento)
    {
        $query = $this->db->from($this->tabla)->where("cv", $cv)->get();
        $bitacora_hd = $query->row_array();
        $id_bitacora_hd = $bitacora_hd["id"];

        $estado_letra = $this->determinarEstado($estatus);

        $dataToInsert = [
            "ubicacion" => $ubicacion,
            "estatus" => $estatus,
            "observacion" => $observaciones,
            "id_bitacora_hd" => $id_bitacora_hd,
            "coordenadas" => $coordenadas,
            "created_at" => date("Y-m-d H:i:s"),
            "fecha_creacion" => date("Y-m-d H:i:s"),
            "fecha_movimiento" => $fechaHoraMovimiento,
            "estatus_nombre" => $estado_letra,
            "usuario" => $usuario,
        ];




        switch (intval($estatus)) {
            case 1:
                break;
            case 2:
                break;
            case 3:
                $this->db->update($this->tabla, ["fecha_llegada_cargar" => $fechaHoraMovimiento], ["id" => $id_bitacora_hd, "fecha_llegada_cargar" => null]);
                break;
            case 4:
                $this->db->update($this->tabla, ["fecha_posicion" => $fechaHoraMovimiento], ["id" => $id_bitacora_hd, "fecha_posicion" => null]);
                break;
            case 5:
                $this->db->update($this->tabla, ["fecha_carga_real" => $fechaHoraMovimiento], ["id" => $id_bitacora_hd, "fecha_carga_real" => null]);
                break;
            case 6:
                $this->db->update($this->tabla, ["fecha_salida_carga" => $fechaHoraMovimiento], ["id" => $id_bitacora_hd, "fecha_salida_carga" => null]);
                break;
            case 7:
                $this->db->update($this->tabla, ["fecha_llegada" => $fechaHoraMovimiento], ["id" => $id_bitacora_hd, "fecha_llegada" => null]);
                break;
            case 8:
                $this->db->update($this->tabla, ["fecha_descarga_real" => $fechaHoraMovimiento], ["id" => $id_bitacora_hd, "fecha_descarga_real" => null]);
                break;
            default:
                break;
        }



        $this->db->insert("bitacora_ln", $dataToInsert);
        return $this->db->insert_id();
    }

    public function setEvidencia($nombre, $extension, $url, $type = "evidencia_movimiento", $bitacora_hd = null, $bitacora_ln = null)
    {
        $data = [
            "nombre" => $nombre,
            "extension" => $extension,
            "url" => $url,
            "type" => $type,
            "bitacora_hd" => $bitacora_hd,
            "bitacora_ln" => $bitacora_ln,
            "created_at" => date("Y-m-d H:i:s")
        ];
        $this->db->insert("bitacora_files", $data);
        return $this->db->insert_id();
    }

    public function getBitacoraMovimientosActivos($planer = null)
    {
        if ($planer != null) {
            $this->db->where("a.user_add_cv", $planer);
        }

        // $sql = "SELECT bh.* ,left(a.cliente_nombre_corto, 12) cliente,a.orig_dest_solicitud,a.user_mod_cv planner, bl.*
        // FROM bitacora_hd bh inner join api a on a.cv = bh.cv
        // inner join  (SELECT bln.id_bitacora_hd, bln.ubicacion, bln.estatus, bln.observacion,bln.coordenadas, bln.created_at, bln.fecha_creacion, bln.estatus_nombre
        //                 FROM bitacora_ln AS bln
        //                 INNER JOIN (
        //                 SELECT id_bitacora_hd, MAX(created_at) AS ultima_fecha
        //                 FROM bitacora_ln
        //                 GROUP BY id_bitacora_hd
        //                 ) AS max_fecha
        //                 ON bln.id_bitacora_hd = max_fecha.id_bitacora_hd AND bln.created_at = max_fecha.ultima_fecha
        //                 where estatus < 8) bl on bl.id_bitacora_hd = bh.id  ORDER BY bh.fecha_carga ASC";
        // $query = $this->db->query($sql); CASE WHEN bh.referencia is null THEN a.referencia_cliente WHEN bh.referencia is not null then bh.referencia ELSE '' END referencia
        // $sql = "bh.cv,bh.ogs,";
        // $sql .= "a.referencia_cliente referencia";
        // $sql .= ",bh.unidad,bh.operador,bh.placas,bh.cp_origen,bh.cp_destino,bh.origen_destino,DATE_FORMAT(bh.fecha_carga,'%Y-%m-%d %H:%i') fecha_carga, DATE_FORMAT(bh.fecha_descarga,'%Y-%m-%d %H:%i') fecha_descarga, DATE_FORMAT(bh.fecha_posicion,'%Y-%m%-%d %H:%i') fecha_posicion,DATE_FORMAT(bh.fecha_salida_carga,'%Y-%m%-%d %H:%i') fecha_salida_carga, DATE_FORMAT(bh.fecha_llegada,'%Y-%m%-%d %H:%i') fecha_llegada, ";
        // $sql .= "DATE_FORMAT(bh.fecha_descarga_real,'%Y-%m%-%d %H:%i') fecha_descarga_real,DATE_FORMAT(bh.fecha_carga_real,'%Y-%m%-%d %H:%i') fecha_carga_real,bh.ultimo_estatus,DATE_FORMAT(bh.created_at,'%Y-%m-%d %H:%i') created_at, DATE_FORMAT(bh.salida_ruta,'%Y-%m-%d %H:%i') salida_ruta,";
        // $sql .= "a.cd_origen origen,a.cd_destino destino,CONCAT('caja: <b>',a.placas_remolque,'</b> <br />','Tracto: <b>',a.placas_tracto,'</b>') unidad ,left(a.cliente_nombre_corto, 12) cliente, a.cliente_solicitud,a.orig_dest_solicitud,a.user_add_cv planner, bl.*";
        // $this->db->select($sql);
        // $this->db->from("bitacora_hd bh")->join("api a", "a.cv = bh.cv")
        //     ->join("(SELECT bln.id_bitacora_hd, bln.ubicacion, bln.estatus, bln.observacion,bln.coordenadas,CONVERT_TZ(bln.created_at,'+00:00','-06:00') fecha_act, bln.created_at, bln.fecha_creacion, bln.estatus_nombre
        // FROM bitacora_ln AS bln
        // INNER JOIN (
        // SELECT id_bitacora_hd, MAX(created_at) AS ultima_fecha
        // FROM bitacora_ln
        // GROUP BY id_bitacora_hd
        // ) AS max_fecha
        // ON bln.id_bitacora_hd = max_fecha.id_bitacora_hd AND bln.created_at = max_fecha.ultima_fecha
        // where estatus in (1,2,3,4,5,6,7,8,9)) bl", "bl.id_bitacora_hd = bh.id")->where("a.fecha_descarga_cv > DATE_SUB(now(), INTERVAL 10 day)")->order_by("bh.fecha_carga ASC");

        $sql = "SELECT `bh`.`cv`, `bh`.`ogs`, `a`.`referencia_cliente` `referencia`, `bh`.`unidad`, `bh`.`operador`,
        `bh`.`placas`, `bh`.`cp_origen`, `bh`.`cp_destino`, `bh`.`origen_destino`, DATE_FORMAT(bh.fecha_carga, '%Y-%m-%d %H:%i')
        fecha_carga, DATE_FORMAT(bh.fecha_descarga, '%Y-%m-%d %H:%i') fecha_descarga, DATE_FORMAT(bh.fecha_posicion, '%Y-%m%-%d
        %H:%i') fecha_posicion, DATE_FORMAT(bh.fecha_salida_carga, '%Y-%m%-%d %H:%i') fecha_salida_carga,
        DATE_FORMAT(bh.fecha_llegada, '%Y-%m%-%d %H:%i') fecha_llegada, DATE_FORMAT(bh.fecha_descarga_real, '%Y-%m%-%d %H:%i')
        fecha_descarga_real, DATE_FORMAT(bh.fecha_carga_real, '%Y-%m%-%d %H:%i') fecha_carga_real, `bh`.`ultimo_estatus`,
        DATE_FORMAT(bh.created_at, '%Y-%m-%d %H:%i') created_at, DATE_FORMAT(bh.salida_ruta, '%Y-%m-%d %H:%i') salida_ruta,
        `a`.`cd_origen` `origen`, `a`.`cd_destino` `destino`, CONCAT('caja: <b>', `a`.`placas_remolque`, '</b> <br />', 'Tracto:
        <b>', `a`.`placas_tracto`, '</b>') unidad, left(a.cliente_nombre_corto, 12) cliente, `a`.`cliente_solicitud`,
        `a`.`orig_dest_solicitud`, `a`.`user_add_cv` `planner`, `bl`.*,
        CONVERT_TZ(bl.created_at,'+00:00','-06:00') fecha_act
        FROM bitacora_ln bl inner join (SELECT max(bl.id) last_move from bitacora_ln bl group by bl.id_bitacora_hd) as lbl on bl.id = lbl.last_move
        inner join bitacora_hd bh on bl.id_bitacora_hd = bh.id
        inner join api a on a.cv = bh.cv 
        where bl.estatus in (1,2,3,4,5,6,7,8,9) AND `a`.`fecha_descarga_cv` > DATE_SUB(now(), INTERVAL 10 day) ORDER BY a.fecha_carga_ci ASC";

        $query = $this->db->query($sql);
        $bitacora = $query->result_array();
        return $bitacora;
    }
    private function determinarEstado($estatus)
    {
        switch (intval($estatus)) {
            case 1:
                return "Unidad confirmada";
            case 2:
                return "Tránsito a cargar";
            case 3:
                return "En punto de carga";
            case 4:
                return "En posición de carga";
            case 5:
                return "Salida de Punto de Carga";
            case 6:
                return "En Tránsito";
            case 7:
                return "En punto de descarga";
            case 8:
                return "Descargada";
            case 9:
                return "Acuerdo de Llegada a Cargar";
            case 10:
                return "Cancelado";
            default:
                return "";
        }
    }
}