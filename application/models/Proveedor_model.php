<?php
class Proveedor_model extends CI_Model
{
    private $tabla = 'proveedores';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    public function obtener_proveedores()
    {
        $this->db->select("p.id, p.nombre_corto");
        $this->db->from("proveedores as p");
        $query = $this->db->get();
        return $query->result_array();
    }

    public function obtener_usuarios_proveedores()
    {


        //El query anterior traera varios registros por usuario segun tenga proveedores, hay que agruparlos para generar esta estructura
        // [
        //     [
        //         "usuario_id" => "1",
        //         "nombre" => "Juan",
        //         "apellido_paterno" => "Perez",
        //         "email" => "emai@dsa.com"
        //         "proveedores" => [
        //             [
        //                 "id" => "1",
        //                 "nombre_corto" => "Proveedor 1"
        //             ],
        //             [
        //                 "id" => "2",
        //                 "nombre_corto" => "Proveedor 2"
        //             ]
        //         ]
        //     ]
        // ]
        $this->db->select("u.id as usuario_id, u.nombre, u.email, p.id as proveedor_id, p.nombre_corto");
        $this->db->from("usuarios as u");
        $this->db->join("usuarios_proveedores as up", "up.usuario_id = u.id", "left");
        $this->db->join("proveedores as p", "p.id = up.proveedor_id", "left");
        $query = $this->db->get();
        $result = $query->result_array();
        $usuarios = [];

        foreach ($result as $key => $value) {
            $usuario_id = $value["usuario_id"];
            $usuario = $usuarios[$usuario_id] ?? null;
            if (!$usuario) {
                $usuarios[$usuario_id] = [
                    "usuario_id" => $value["usuario_id"],
                    "nombre" => $value["nombre"],
                    "email" => $value["email"],
                    "proveedores" => []
                ];
            }
            $usuarios[$usuario_id]["proveedores"][] = [
                "id" => $value["proveedor_id"],
                "nombre_corto" => $value["nombre_corto"]
            ];
        }

        return array_values($usuarios);
    }

    public function obtener_whatsappContact($proveedor_id)
    {
        $this->db->select("p.id, p.nombre_corto, wt.telefono");
        $this->db->from("proveedores as p");
        $this->db->join("proveedores_whatsapps as pw", "pw.proveedor_id = p.id");
        $this->db->join("whatsapp_contacto as wt", "wt.id = pw.whatsapp_id");
        $this->db->where("p.id", $proveedor_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function obtener_proveedores_carga($usuario_id)
    {
        $this->db->select("p.id, p.nombre_corto text");
        $this->db->from("proveedores as p");
        $this->db->where("ultimo_planer", $usuario_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    // 
    public function altaContactosProveedor($usuarioId, $proveedorId, $contactos, $rutas, $unidades)
    {
        $this->db->trans_begin();
        try {
            //code...
            $qe = $this->db->from("usuarios_proveedores")->where("proveedor_id", $proveedorId)->where("usuario_id", $usuarioId)->get();
            $exist = $qe->row_array();

            if ($exist) {
                $this->db->insert("usuarios_proveedores", ["proveedor_id" => $proveedorId, "usuario_id" => $usuarioId]);
            }


            $dataInsertContactos = [];
            $this->db->delete("contactos", ["id_empresa" => $proveedorId]);
            foreach ($contactos as $contacto) {
                $dataInsertContactos[] = [
                    "nombre" => $contacto["nombre"],
                    "whatsapp" => "521" . $contacto["whatsapp"] . "@c.us", //5218124485945@c.us
                    "correo" => $contacto["correo"],
                    "tipo_contacto" => $contacto["tipoContacto"],
                    "id_empresa" => $proveedorId,
                    "usuario_captura" => $usuarioId,
                ];
            }

            $this->db->insert_batch("contactos", $dataInsertContactos);

            $ids_insertados_contactos_whatsapp = [];
            $this->db->delete("whatsapp_contacto", ["telefono" => "521" . $contacto["whatsapp"] . "@c.us"]);
            foreach ($contactos as $contacto) {
                $this->db->insert("whatsapp_contacto", ["telefono" => "521" . $contacto["whatsapp"] . "@c.us", "type" =>  "fleteNuevo"]);
                $ids_insertados_contactos_whatsapp[] = $this->db->insert_id();
            }
            $this->db->delete("proveedores_whatsapps", ["proveedor_id" => $proveedorId]);
            foreach ($ids_insertados_contactos_whatsapp as $id_insertado) {
                $this->db->insert("proveedores_whatsapps", ["proveedor_id" => $proveedorId, "whatsapp_id" => $id_insertado]);
            }

            //insertar direcciones 

            foreach ($rutas as $ruta) {
                //Formato de $ruta
                // [
                //     "origen" => "01",
                //     "origenText" => "Aguascalientes",
                //     "destinos" => [
                //         [
                //             "id" => "01",
                //             "text" => "Aguascalientes"
                //         ],
                //         [
                //             "id" => "02",
                //             "text" => "Baja california"
                //         ]
                //     ]
                // ]
                //Validar si existe una dirección proveedor con este origen $ruta['origen'] = estado_id de la tabla direcciones and proveedor =1
                //Si no existe insertarla
                $origenQuery = $this->db->select("d.estado_id,d.id")
                    ->from("direcciones d")
                    ->where("d.estado_id", $ruta["origen"])
                    ->where("d.proveedor", 1)
                    ->limit(1)
                    ->get();
                $origen = $origenQuery->row_array();
                $origen_id = null;
                if ($origen) {
                    $origen_id = $origen["id"];
                } else {
                    $this->db->insert("direcciones", ["estado_id" => $ruta["origen"], "proveedor" => 1]);
                    $origen_id = $this->db->insert_id();
                }

                foreach ($ruta["destinos"] as $destino) {
                    $destinoQuery = $this->db->select("d.id")
                        ->from("direcciones d")
                        ->where("d.estado_id", $destino["id"])
                        ->where("d.proveedor", 1)
                        ->limit(1)
                        ->get();
                    $destinoRes = $destinoQuery->row_array();
                    if ($destinoRes) {
                        $destino_id = $destinoRes["id"];
                    } else {
                        $this->db->insert("direcciones", ["estado_id" => $destino["id"], "proveedor" => 1]);
                        $destino_id = $this->db->insert_id();
                    }

                    $rutaQuery = $this->db->select("r.id")
                        ->from("rutas r")
                        ->where("r.origen", $origen_id)
                        ->where("r.destino", $destino_id)
                        ->limit(1)
                        ->get();
                    $ruta = $rutaQuery->row_array();
                    if ($ruta) {
                        $ruta_id = $ruta["id"];
                    } else {
                        $this->db->insert("rutas", ["origen" => $origen_id, "destino" => $destino_id]);
                        $ruta_id = $this->db->insert_id();
                    }

                    $rutaProveedorQuery = $this->db->select("rp.id")
                        ->from("rutas_proveedores rp")
                        ->where("rp.id_ruta", $ruta_id)
                        ->where("rp.id_proveedor", $proveedorId)
                        ->limit(1)
                        ->get();
                    $rutaProveedor = $rutaProveedorQuery->row_array();
                    if (!$rutaProveedor) {
                        $this->db->insert("rutas_proveedores", ["id_ruta" => $ruta_id, "id_proveedor" => $proveedorId]);
                    }
                }
            }




            foreach ($unidades as  $value) {
                $unidadQuery =  $this->db->select("id")->from("unidades_proveedores")->where("id_unidad", $value["id"])->where("id_proveedor", $proveedorId)->get();
                $unidad = $unidadQuery->row_array();
                if (!$unidad) {
                    $this->db->insert("unidades_proveedores", ["id_unidad" => $value["id"], "id_proveedor" => $proveedorId]);
                }
            }


            $this->db->trans_commit();
            return true;
        } catch (\Throwable $th) {
            $this->db->trans_rollback();
            return $th->getMessage();
        }
    }


    public function obtener_contactos_proveedor($proveedorId)
    {
        $this->db->select("c.id, c.nombre, SUBSTRING(SUBSTRING_INDEX(whatsapp, '521', -1), 1, 10) whatsapp, c.correo, c.tipo_contacto tipoContacto");
        $this->db->from("contactos as c");
        $this->db->where("c.id_empresa", $proveedorId);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function altaGrupoContacto($grupo)
    {

        $this->db->update(
            "grupos_whatsapp",
            [
                "id_proveedor" => $grupo["id_proveedor"], "usuario_capturo" => $grupo["usuarioId"]
            ],
            ["id" => $grupo["id_grupo"]]
        );
        return true;
    }


    public function get_grupos_whatsapp()
    {
        $query = $this->db->query("SELECT from_ from grupos_whatsapp where from_ not in (SELECT DISTINCT(trim(`from`)) FROM `whatsapp_messages` where content like '%Cd Juárez / Con Reparto en Chihuahua, Chihuahua.%')");
        $this->db->select("from_")->from("grupos_whatsapp")->get();

        return $query->result_array();
    }


    public function obtener_rutas_proveedor($proveedorId)
    {
        //formato de ruta que debemos devolver
        // [
        //     [
        //         "origen" => "01",
        //         "origenText" => "Aguascalientes",
        //         "destinos" => [
        //             [
        //                 "id" => "01",
        //                 "text" => "Aguascalientes"
        //             ],
        //             [
        //                 "id" => "02",
        //                 "text" => "Baja california"
        //             ]
        //         ]
        //     ]
        // ]

        $this->db->select("orig.estado_id origen,cor.estado origenText, core.estado destino, dest.id destino_id");
        $this->db->from("rutas_proveedores rp");
        $this->db->join("rutas r", "r.id = rp.id_ruta");
        $this->db->join("direcciones orig", "orig.id = r.origen and orig.proveedor = 1");
        $this->db->join("(select distinct estado, id_estado from cps) cor", "cor.id_estado = orig.estado_id");
        $this->db->join("direcciones dest", "dest.id = r.destino and dest.proveedor = 1");
        $this->db->join("(select distinct estado, id_estado from cps) core", "core.id_estado = dest.estado_id");
        $this->db->where("rp.id_proveedor", $proveedorId);
        $this->db->order_by("orig.estado_id", "desc");
        $query = $this->db->get();
        $rutas = $query->result_array();
        if (empty($rutas)) {
            return [];
        }

        $origenes = array_unique(array_column($rutas, "origen"));

        $result = [];
        $i = 0;
        foreach ($origenes as $index => $value) {
            $filtrar = array_values(array_filter($rutas, function ($ruta) use ($value) {
                return $ruta["origen"] == $value;
            }));
            $origenTemp = "";
            $origenTextTemp = "";
            foreach ($filtrar as $value) {
                $origenTemp = $value["origen"];
                $origenTextTemp = $value["origenText"];
                break;
            }
            $result[$i]["origen"] = $origenTemp;
            $result[$i]["origenText"] =  $origenTextTemp;
            $result[$i]["destinos"] = array_map(function ($ruta) {
                return [
                    "id" => $ruta["destino_id"],
                    "text" => $ruta["destino"]
                ];
            }, $filtrar);
            $i++;
        }

        return $result;
    }


    public function obtener_unidades_proveedor($proveedorId)
    {
        $this->db->select("u.id, u.unidad nombre");
        $this->db->from("unidades_proveedores as up");
        $this->db->join("cat_unidades as u", "u.id = up.id_unidad")
            ->where("up.id_proveedor", $proveedorId);
        $query = $this->db->get();
        return $query->result_array();
    }
}
