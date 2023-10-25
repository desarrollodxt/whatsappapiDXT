<?php
class Rutas_model extends CI_Model
{
    private $tabla = 'rutas';
    /*
        Las rutas se componen de:
         origen - Que es un id en la tabla direcciones
         destino - que es un id en la tabla direcciones

        La tabla ruta hace un join con la tabla direcciones para obtener los datos de origen y destino y ademas hay una tabla que se llama rutas_proveedores que es la que relaciona las rutas con los proveedores y rutas_clientes que relaciona las rutas con los clientes
        que hace la relacion con un join de id_ruta y id_client y id_proveedor segun corresponda el caso, este modelo es una abstracción de esa ralción compleja
    */

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function cat_obtener_rutas_cliente($id_cliente)
    {
        //hacer raw query pdo
        $sql = "SELECT
                rc.id, ruta text
            from
                rutas_clientes rc
            inner join (select ru.id id_ruta, concat('Origen: ',d.ciudad, ' - ','Destino: ' , d2.ciudad) ruta from rutas ru inner join direcciones d on ru.origen = d.id	inner join direcciones d2 on d2.id = ru.destino ) as r on
                rc.id_ruta = r.id_ruta
                where rc.id_cliente = ?";

        $query = $this->db->query($sql, array($id_cliente));
        return $query->result();
    }

    public function obtener_ruta_cliente($origen, $destino, $id_cliente, $id_lead = 0)
    {
        $id_lead_where = $id_lead == 0 ? "id_cliente = ?" : "id_lead = ?";
        $id_lead = intval($id_lead);
        $sql = "SELECT r.id ruta_id, rc.id_cliente, origenDetalle.nombre origen, origenDetalle.id_estado estado_origen, origen.ciudad, destinoDetalle.nombre destino, destino.ciudad, destinoDetalle.id_estado estado_destino  FROM rutas_clientes rc
        INNER JOIN rutas r ON r.id = rc.id_ruta 
        INNER JOIN direcciones origen ON origen.id = r.origen 
        INNER JOIN (SELECT DISTINCT  cn.id_nombre, c.id_estado, cn.nombre  FROM cps_nombres cn INNER JOIN cps c ON c.id_nombre = cn.id_nombre) AS origenDetalle ON origenDetalle.id_nombre = origen.ciudad
        INNER JOIN direcciones destino ON destino.id = r.destino 
        INNER JOIN (SELECT DISTINCT  cn.id_nombre, c.id_estado, cn.nombre  FROM cps_nombres cn INNER JOIN cps c ON c.id_nombre = cn.id_nombre) AS destinoDetalle ON destinoDetalle.id_nombre = destino.ciudad
        where $id_lead_where AND origen.ciudad = ? AND destino.ciudad = ?;";
        $query = null;
        if ($id_lead == 0) {
            $query = $this->db->query($sql, array($id_cliente, $origen, $destino));
        } else {
            $query = $this->db->query($sql, array($id_lead, $origen, $destino));
        }


        $result = $query->result_array();

        if (empty($result)) {
            $this->db->trans_begin();
            try {
                //dar de alta direccion
                $this->db->insert("direcciones", ["ciudad" => $origen]);
                $origen_id = $this->db->insert_id();
                $this->db->insert("direcciones", ["ciudad" => $destino]);
                $destino_id = $this->db->insert_id();
                $this->db->insert("rutas", ["origen" => $origen_id, "destino" => $destino_id]);
                if ($id_lead == 0) {
                    $this->db->insert("rutas_clientes", ["id_ruta" => $this->db->insert_id(), "id_cliente" => $id_cliente]);
                } else {
                    $this->db->insert("rutas_clientes", ["id_ruta" => $this->db->insert_id(), "id_lead" => $id_lead]);
                }
                $this->db->trans_commit();
            } catch (\Throwable $th) {
                $this->db->trans_rollback();
            }
        } else {
            return $result;
        }

        $id_lead_where = $id_lead == 0 ? "id_cliente = ?" : "id_lead = ?";
        $id_lead = intval($id_lead);
        $sql = "SELECT r.id ruta_id, rc.id_cliente, origenDetalle.nombre origen, origenDetalle.id_estado estado_origen, origen.ciudad, destinoDetalle.nombre destino, destino.ciudad, destinoDetalle.id_estado estado_destino  FROM rutas_clientes rc
        INNER JOIN rutas r ON r.id = rc.id_ruta 
        INNER JOIN direcciones origen ON origen.id = r.origen 
        INNER JOIN (SELECT DISTINCT  cn.id_nombre, c.id_estado, cn.nombre  FROM cps_nombres cn INNER JOIN cps c ON c.id_nombre = cn.id_nombre) AS origenDetalle ON origenDetalle.id_nombre = origen.ciudad
        INNER JOIN direcciones destino ON destino.id = r.destino 
        INNER JOIN (SELECT DISTINCT  cn.id_nombre, c.id_estado, cn.nombre  FROM cps_nombres cn INNER JOIN cps c ON c.id_nombre = cn.id_nombre) AS destinoDetalle ON destinoDetalle.id_nombre = destino.ciudad
        where $id_lead_where AND origen.ciudad = ? AND destino.ciudad = ?;";
        $query = null;
        if ($id_lead == 0) {
            $query = $this->db->query($sql, array($id_cliente, $origen, $destino));
        } else {
            $query = $this->db->query($sql, array($id_lead, $origen, $destino));
        }

        $result = $query->result_array();

        return $result;
    }


    public function getRutaCompare($ruta_id)
    {
        $this->db->select("concat(or.ciudad,des.ciudad) as ruta, ru.origen, ru.destino");
        $this->db->from("rutas as ru");
        $this->db->join("direcciones as or", "or.id = ru.origen");
        $this->db->join("direcciones as des", "des.id = ru.destino");
        $this->db->where("ru.id", $ruta_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function findProveedor($rutaInfo)
    {
        $sql = "SELECT rp.id_proveedor, p.nombre_corto, origen.estado_id origen, destino.estado_id destino, gw.from_  FROM rutas_proveedores rp 
        inner join proveedores p on p.id = rp.id_proveedor 
        inner join rutas r on r.id = rp.id_ruta 
        inner join direcciones origen on origen.id = r.origen  and origen.proveedor = 1
        inner join direcciones destino on destino.id = r.destino  and destino.proveedor = 1
        left join grupos_whatsapp gw on gw.id_proveedor = p.id 
        where origen.estado_id = ? and destino.estado_id = ?";
        $query = $this->db->query($sql, array($rutaInfo["estado_origen"], $rutaInfo["estado_destino"]));

        return $query->result_array();
    }

    public function obtener_ciudades()
    {
        $this->db->select("id_nombre id, nombre text");
        $this->db->from("cps_nombres");
        $query = $this->db->get();
        return $query->result_array();
    }

    public function obtener_estados()
    {
        $sql = "SELECT DISTINCT id_estado id, estado text FROM cps ORDER BY estado ASC";
        $qury = $this->db->query($sql);

        return $qury->result_array();
    }

    public function obtener_tipo_unidades()
    {
        $this->db->select("id, unidad text");
        $this->db->from("cat_unidades");
        $query = $this->db->get();
        return $query->result_array();
    }

    // Otros métodos relacionados con el modelo pueden ir aquí
}
