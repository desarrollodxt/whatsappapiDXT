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
        $this->db->select("p.id, p.nombre_corto");
        $this->db->from("rutas_proveedores as rp");
        $this->db->join("proveedores as p", "p.id = rp.id_proveedor");
        $this->db->join("rutas as ru", "ru.id = rp.id_ruta");
        $this->db->where("ru.origen", $rutaInfo[0]["origen"]);
        $this->db->where("ru.destino", $rutaInfo[0]["destino"]);
        $query = $this->db->get();
        return $query->result_array();
    }


    // Otros métodos relacionados con el modelo pueden ir aquí
}