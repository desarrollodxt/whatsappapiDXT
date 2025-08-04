<?php
class Indicadores_model extends CI_Model
{

    // campos de la tabla comentario
    //id,id_empresa,id_lead,id_usuario,id_comentario_tipo,comentario,url,nombre_archivo,extension,fecha,factura,cv,id_cliente
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function getProfitMesPUsuario($usuario_rainde, $roles)
    {
        $condicion = "";
        if (in_array("Planner", $roles)) {
            $condicion = "and a.user_autoriza = '$usuario_rainde'";
        } else {
            $condicion = "and a.vendedor = '$usuario_rainde'";
        }
        $query = $this->db->query("select sum(utilidad*tipo_de_cambio) profit from api a where year(a.fecha_carga_ci)*100+month(a.fecha_carga_ci) = year(now())*100+month(now()) and estatus_cv = 'ACTIVO' $condicion");
        $result = $query->row_array();
        return $result["profit"] ?? 0;
    }

    public function getPMargenMesPUsuario($usuario_rainde, $roles)
    {
        $condicion = "";
        if (in_array("Planner", $roles)) {
            $condicion = "and a.user_autoriza = '$usuario_rainde'";
        } else {
            $condicion = "and a.vendedor = '$usuario_rainde'";
        }
        $query = $this->db->query("select (sum(utilidad)/sum(vta_total_autorizada))*tipo_de_cambio pMargen from api a where year(a.fecha_carga_ci)*100+month(a.fecha_carga_ci) = year(now())*100+month(now()) and estatus_cv = 'ACTIVO' $condicion");
        $result = $query->row_array();
        return floatval($result["pMargen"] * 100) ?? 0;

        return [];
    }

    public function getRecuperacionPUsuario($usuario_rainde, $roles)
    {
        $condicion = "";
        if (in_array("Planner", $roles)) {
            $condicion = "and a.user_autoriza = '$usuario_rainde'";
        } else {
            $condicion = "and a.vendedor = '$usuario_rainde'";
        }
        $query = $this->db->query("SELECT SUM(sub_fact_dxt)*		
            (SUM(importe_cobrado_dxt)/SUM(tot_fact_dxt))*
            (SUM(utilidad)/SUM(vta_total_autorizada))*tipo_de_cambio recuperacion from api a where year(fecha_cobro_dxt)*100 + month(fecha_cobro_dxt) = year(now())*100+month(now()) and estatus_cv = 'ACTIVO' $condicion");
        $result = $query->row_array();
        return $result["recuperacion"] ?? 0;
    }

    public function getobjetivoGlobalProfit()
    {
        $sql = "SELECT entidad, 
        objetivos.objetivo, 
        a.diashabiles,
        left(habiles.fecha,10) as Fecha,
        DAY(habiles.fecha) as dianum, 
        habiles.multiplo,
        objetivos.objetivo/a.diashabiles*multiplo as objetivodia 
        FROM habiles 
        INNER JOIN objetivos
        ON(YEAR(habiles.fecha)*100+month(habiles.fecha)=objetivos.anomes)
        INNER JOIN (SELECT YEAR(habiles.fecha)*100+month(habiles.fecha) as anomes, sum(multiplo) as diashabiles FROM habiles WHERE YEAR(habiles.fecha)*100+month(habiles.fecha) = YEAR(CONVERT_TZ(now(),'+00:00', '-06:00'))*100+MONTH(CONVERT_TZ(now(),'+00:00', '-06:00'))) as a
        ON(objetivos.anomes = a.anomes)
        where objetivos.anomes = YEAR(CONVERT_TZ(now(),'+00:00', '-06:00'))*100+MONTH(CONVERT_TZ(now(),'+00:00', '-06:00')) and tipo_objetivo = 'MRG'
        AND objetivos.entidad = 'DXT' and objetivos.tipo = 'VTA TOT'
        ORDER BY dianum ASC";
        $query = $this->db->query($sql);
        $result = $query->result_array();

        $objetivo_a_dia_hoy = 0;

        foreach ($result as $row) {
            if ($row["dianum"] <= date("d")) {
                $objetivo_a_dia_hoy += $row["objetivodia"];
            }
        }

        $queryProfit = $this->db->query("SELECT sum(utilidad*tipo_de_cambio) profit from api a where year(a.fecha_carga_ci)*100+month(a.fecha_carga_ci) = year(now())*100+month(now()) and estatus_cv = 'ACTIVO'");
        $resultProfit = $queryProfit->row_array();
        $profit = $resultProfit["profit"] ?? 0;
        if ($profit == 0) {
            return 0;
        }

        if($objetivo_a_dia_hoy == 0){
            return 0;
        }

        return (floatval($profit) / floatval($objetivo_a_dia_hoy)) * 100;

    }
    public function getCumplimientoMetaPUsuario($usuario_rainde, $roles)
    {
        $condicion = "";
        if (in_array("Planner", $roles)) {
            $condicion = "and a.user_autoriza = '$usuario_rainde'";
            $tipoObjetvio = "COMPRA";
        } else {
            $condicion = "and a.vendedor = '$usuario_rainde'";
            $tipoObjetvio = "VENTA";
        }
        $query = $this->db->query("select sum(utilidad*tipo_de_cambio) profit from api a where year(a.fecha_carga_ci)*100+month(a.fecha_carga_ci) = year(now())*100+month(now()) and estatus_cv = 'ACTIVO' $condicion");
        $result = $query->row_array();

        $profit = $result["profit"] ?? 0;
        if ($profit == 0) {
            return 0;
        }
        $sql = "SELECT entidad, 
                    objetivos.objetivo, 
                    a.diashabiles,
                    left(habiles.fecha,10) as Fecha,
                    DAY(habiles.fecha) as dianum, 
                    habiles.multiplo,
                    objetivos.objetivo/a.diashabiles*multiplo as objetivodia 
                    FROM habiles 
                    INNER JOIN objetivos
                    ON(YEAR(habiles.fecha)*100+month(habiles.fecha)=objetivos.anomes)
                    INNER JOIN (SELECT YEAR(habiles.fecha)*100+month(habiles.fecha) as anomes, sum(multiplo) as diashabiles FROM habiles WHERE YEAR(habiles.fecha)*100+month(habiles.fecha) = YEAR(CONVERT_TZ(now(),'+00:00', '-06:00'))*100+MONTH(CONVERT_TZ(now(),'+00:00', '-06:00'))) as a
                    ON(objetivos.anomes = a.anomes)
                    where objetivos.anomes = YEAR(CONVERT_TZ(now(),'+00:00', '-06:00'))*100+MONTH(CONVERT_TZ(now(),'+00:00', '-06:00')) and tipo_objetivo = 'MRG'
                    AND objetivos.entidad = ? and objetivos.tipo = ?
                    ORDER BY dianum ASC";
        $query = $this->db->query($sql, [$usuario_rainde, $tipoObjetvio]);
        $result = $query->result_array();

        $objetivo_a_dia_hoy = 0;

        foreach ($result as $row) {
            if ($row["dianum"] <= date("d")) {
                $objetivo_a_dia_hoy += $row["objetivodia"];
            }
        }


        return (floatval($profit) / floatval($objetivo_a_dia_hoy)) * 100;
    }

    public function getVtaPerdidaPUsuario($usuario_rainde, $roles)
    {
        $query = $this->db->query("SELECT COUNT(case when (estatus_ogs ='RECHAZADA' OR estatus_cv = 'eliminado') then fecha_add end) as perdidos,
       COUNT(case when (estatus_ogs ='AUTORIZADA' AND estatus_cv = 'ACTIVO')  then fecha_add end) as cvs,
       COUNT(case when (estatus_ogs ='SOLICITADA' AND estatus_cv = '')        then fecha_add end) as pend from api a
       WHERE LEFT(a.fecha_carga_solicitud,7) = LEFT(now(),7)");
        $result = $query->row_array();
        $perdidos = floatval($result["perdidos"]) ?? 0;
        $cvs = floatval($result["cvs"]) ?? 0;
        $total = $perdidos + $cvs;

        if ($perdidos == 0 && $cvs == 0) {
            return 0;
        }

        if ($cvs == 0) {
            return 100;
        }

        return $perdidos / $total * 100;
    }

    public function getProveedoresActivosPausadosPerdidosPUsuario($usuario_rainde, $roles)
    {
        //api fecha_insercion,id,ogs,id_cliente,cliente_nombre_corto,vendedor,estatus_ogs,comentarios_rechazo,fecha_rechazo,user_add,fecha_add,user_mod,fecha_mod,user_autoriza,fecha_autoriza,fecha_ogs,fecha_carga_solicitud,fecha_cita_solicitud,fecha_carga_ci,fecha_descarga_cv,orig_dest_solicitud,orig_dest_loc_solicitud,cliente_solicitud,cv,estatus_cv,comentarios_cancelacion_cv,fecha_cancelacion_cv,user_add_cv,fecha_add_cv,user_mod_cv,fecha_mod_cv,orig_dest_cv,orig_dest_loc_cv,cliente_cv,rfc_cliente,transportista_razon_social,transportista_nombre_comercial,rfc_transportista,id_transportista,origen,destino,mnpio_origen,mnpio_destino,observaciones,tipo_movimiento,tipo_unidad,caracteristica,llegada_a_cargar,salida_inicia_viaje,llegada_a_descargar,llegada_real,viaje_terminado,estatus_actual,recuperacion_de_evidencias,venta_autorizada,total_cargos_adicionales_autorizados,vta_total_autorizada,moneda,tipo_de_cambio,costo_autorizado,costo_adicionales_autorizado,costo_total_autorizada,utilidad,porcentaje,referencia_cliente,fecha_fact_prov,fecha_autoriza_fact_prov,num_fact_prov,sub_prov,iva_prov,ret_prov,tot_prov,dias_credito_prov,fecha_vencimiento,sum_pagos_al_proveedor,saldo_x_pagar,dias_fac_prov,fact_dxt,estatus_fact_dxt,fecha_fact_dxt,sub_fact_dxt,iva_fact_dxt,iva_ret,tot_fact_dxt,dias_cred_dxt,fecha_venc_fact_dxt,importe_cobrado_dxt,fecha_cobro_dxt,saldo_x_cobrar,dias_fac_dxt,dias_serv_dxt,cp_origen,cp_destino,cd_origen,comprador,cd_destino,operador,tracto,permiso,placas_tracto,placas_remolque,placas_remolque_2,direccion_origen,direccion_destino
        //ver proveedores que estan activos, pausados y perdidos
        // activos son los que tienen cvs activos en los ultimos 30 días
        // pausados son los que no tienen cvs activos en los ultimos 30 días pero si en los ultimos 90 días
        // perdidos son los que no tienen cvs activos en los ultimos 90 días
        $query = $this->db->query("SELECT 
            SUM(CASE WHEN max_fecha_carga_ci < DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 1 ELSE 0 END) AS perdidos,
            SUM(CASE WHEN max_fecha_carga_ci BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS pausados,
            SUM(CASE WHEN max_fecha_carga_ci >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS activos
        FROM (
            SELECT a.id_transportista, MAX(a.fecha_carga_ci) AS max_fecha_carga_ci
            FROM api a
            WHERE a.user_autoriza ='$usuario_rainde'
            GROUP BY a.id_transportista
        ) subquery");
        $result = $query->row_array();
        return $result;
    }

    public function getProfitMesPUsuarioDetalle($usuario_rainde, $roles)
    {
        $condicion = "";
        if (in_array("Planner", $roles)) {
            $condicion = "and user_autoriza = '$usuario_rainde'";
        } else {
            $condicion = "and vendedor = '$usuario_rainde'";
        }
        $query = $this->db->query("SELECT api.cv, 
                                    LEFT(api.fecha_carga_ci,10) AS fecha_cv,
                                    api.orig_dest_cv,
                                    api.user_add_cv as plann,
                                    api.vendedor,
                                    api.cliente_cv,
                                    api.comprador,
                                    api.transportista_nombre_comercial AS Prov,
                                    YEAR(api.fecha_carga_ci)*100+ MONTH(api.fecha_carga_ci) fecha_carga,
                                    (utilidad*tipo_de_cambio) profit
                                    From api where estatus_cv = 'ACTIVO'
                                    AND YEAR(api.fecha_carga_ci)*100+ MONTH(api.fecha_carga_ci) = YEAR(now())*100+ MONTH(now()) $condicion");
        $result = $query->result_array();

        $data = [
            "datos" => $result,
            "header" => explode(",", "cv,fecha_cv,orig_dest_cv,plann,vendedor,cliente_cv,comprador,Prov,fecha_carga,profit")
        ];

        return $data;
    }


    public function getPMargenMesPUsuarioDetalle($usuario_rainde, $roles)
    {
        $condicion = "";
        if (in_array("Planner", $roles)) {
            $condicion = "and a.user_autoriza = '$usuario_rainde'";
        } else {
            $condicion = "and a.vendedor = '$usuario_rainde'";
        }
        $query = $this->db->query("select a.id,a.fecha_carga_ci,a.cliente_nombre_corto,a.vta_total_autorizada,a.utilidad,a.tipo_de_cambio from api a where year(a.fecha_carga_ci)*100+month(a.fecha_carga_ci) = year(now())*100+month(now()) and estatus_cv = 'ACTIVO' $condicion");
        $result = $query->result_array();
        return $result;
    }

    public function getRecuperacionPUsuarioDetalle($usuario_rainde, $roles)
    {
        $condicion = "";
        if (in_array("Planner", $roles)) {
            $condicion = "and a.user_autoriza = '$usuario_rainde'";
        } else {
            $condicion = "and a.vendedor = '$usuario_rainde'";
        }
        $query = $this->db->query("SELECT
                                `cliente_nombre_corto`,
                                `cliente_cv`,
                                `cv`,
                                left(`fecha_carga_ci`,
                                10),
                                datediff(left(`fecha_cobro_dxt`, 10), left(`fecha_carga_ci`, 10)) as DiasServicio,
                                left(`fecha_fact_dxt`,
                                10),
                                datediff(left(`fecha_cobro_dxt`, 10), left(`fecha_fact_dxt`, 10)) as DiasFactura,
                                `fact_dxt`,
                                `tot_fact_dxt`,
                                left(`fecha_cobro_dxt`,
                                10),
                                `saldo_x_cobrar`
                            FROM
                                `api`
                            WHERE
                                `estatus_cv` = 'ACTIVO'
                                AND `saldo_x_cobrar` = 0
                                and `fact_dxt` <> '-1'
                                and api.user_autoriza = '$usuario_rainde'
                                and YEAR(api.fecha_cobro_dxt)*100 + MONTH(api.fecha_cobro_dxt) = YEAR(now())*100 + MONTH(now())");
        $result = $query->result_array();

        $data = [
            "datos" => $result,
            "header" => explode(",", "cliente_nombre_corto,cliente_cv,cv,fecha_carga_ci,DiasServicio,fecha_fact_dxt,DiasFactura,fact_dxt,tot_fact_dxt,fecha_cobro_dxt,saldo_x_cobrar")
        ];
        return $data;
    }

    public function getCumplimientoMetaPUsuarioDetalle($usuario_rainde, $roles)
    {
        $condicion = "";
        if (in_array("Planner", $roles)) {
            $condicion = "and a.user_autoriza = '$usuario_rainde'";
            $tipoObjetvio = "COMPRA";
        } else {
            $condicion = "and a.vendedor = '$usuario_rainde'";
            $tipoObjetvio = "VENTA";
        }
        $query = $this->db->query("select a.id,a.fecha_carga_ci,a.cliente_nombre_corto,a.vta_total_autorizada,a.utilidad,a.tipo_de_cambio from api a where year(a.fecha_carga_ci)*100+month(a.fecha_carga_ci) = year(now())*100+month(now()) and estatus_cv = 'ACTIVO' $condicion");
        $result = $query->result_array();
        return $result;
    }

    public function getVtaPerdidaPUsuarioDetalle($usuario_rainde, $roles)
    {
        $query = $this->db->query("SELECT a.estatus_ogs, a.orig_dest_loc_solicitud, a.tipo_unidad, left(a.fecha_cita_solicitud ,7)  from api a
                                    where left(a.fecha_cita_solicitud ,7 ) = left(now(),7)");
        $result = $query->result_array();
        $data = [
            "datos" => $result,
            "header" => explode(",", "estatus,ruta,tipo_unidad,fecha_cita_solicitud")
        ];
        return $data;
    }

    public function getProveedoresActivosPausadosPerdidosPUsuarioDetalle($usuario_rainde, $roles)
    {
        $query = $this->db->query("SELECT transportista_nombre_comercial,ultimo_viaje, TIMESTAMPDIFF(month,ultimo_viaje,now())meses_ultimo_viaje from (SELECt a.transportista_nombre_comercial, MAX(a.fecha_carga_ci) AS ultimo_viaje FROM api a where a.user_autoriza ='$usuario_rainde' AND estatus_cv = 'ACTIVO' AND estatus_ogs = 'AUTORIZADA' GROUP BY a.id_transportista) as rutas order by TIMESTAMPDIFF(month,ultimo_viaje,now())");
        $result = $query->result_array();
        $data = [
            "datos" => $result,
            "header" => explode(",", "proveedor,ultimo_viaje,meses_ultimo_viaje")
        ];
        return $data;
    }

    public function getComisionesPUsuario($usuario_rainde, $roles)
    {
        $condicion = "";
        if (in_array("Planner", $roles)) {
            $condicion = "and a.user_autoriza = '$usuario_rainde'";
        } else {
            $condicion = "and a.vendedor = '$usuario_rainde'";
        }
        $query = $this->db->query("SELECT SUM(sub_fact_dxt)*		
            (SUM(importe_cobrado_dxt)/SUM(tot_fact_dxt))*
            (SUM(utilidad)/SUM(vta_total_autorizada))*tipo_de_cambio recuperacion from api a where year(fecha_cobro_dxt)*100 + month(fecha_cobro_dxt) = year(now())*100+month(now()) and estatus_cv = 'ACTIVO' $condicion");
        $result = $query->row_array();
        return $result["recuperacion"] * .035 ?? 0;
    }


    public function getComisionesPUsuarioDetalle($usuario_rainde, $roles)
    {
        $condicion = "";
        if (in_array("Planner", $roles)) {
            $condicion = "AND a.user_autoriza = '$usuario_rainde'";
        } else {
            $condicion = "AND a.vendedor = '$usuario_rainde'";
        }
        $query = $this->db->query("SELECT a.cv,fecha_carga_ci, a.cliente_nombre_corto cliente, a.transportista_nombre_comercial proveedor,
                (sub_fact_dxt)*((importe_cobrado_dxt)/(tot_fact_dxt))*((utilidad)/(vta_total_autorizada))*tipo_de_cambio recuperacion,
                ((sub_fact_dxt)*((importe_cobrado_dxt)/(tot_fact_dxt))*((utilidad)/(vta_total_autorizada))*tipo_de_cambio)*.035 comision
                from api a where year(fecha_cobro_dxt)*100+month(fecha_cobro_dxt)=year(now())*100+month(now()) and estatus_cv = 'ACTIVO' $condicion");
        $result = $query->result_array();
        $data = [
            "datos" => $result,
            "header" => explode(",", "cv, fecha carga,cliente,proveedor,profit,comision")
        ];
        return $data;
    }



    public function getProfit($usuario_rainde, $fecha_inicio, $fecha_fin)
    {
        $query = $this->db->query("select sum(utilidad*tipo_de_cambio) profit, DATE_FORMAT(a.fecha_carga_ci, '%Y-%m-%d') fecha from api a where a.fecha_carga_ci between '$fecha_fin' and '$fecha_inicio' and estatus_cv = 'ACTIVO' and user_autoriza = '$usuario_rainde' ORDER BY DATE_FORMAT(a.fecha_carga_ci, '%Y-%m-%d')");
        $result = $query->result_array();
        return [
            "labels" => $labels,
            "data" => $datos
        ];
    }
}
