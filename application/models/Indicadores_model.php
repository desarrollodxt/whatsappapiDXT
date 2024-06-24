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
       if(in_array("Planner", $roles)){
           $condicion = "and a.user_autoriza = '$usuario_rainde'";
         }else{
              $condicion = "and a.vendedor = '$usuario_rainde'";
         }
        $query = $this->db->query("select sum(utilidad*tipo_de_cambio) profit from api a where year(a.fecha_carga_ci)*100+month(a.fecha_carga_ci) = year(now())*100+month(now()) and estatus_cv = 'ACTIVO' $condicion");
        $result = $query->row_array();
        return $result["profit"] ?? 0;
    }

    public function getPMargenMesPUsuario($usuario_rainde, $roles)
    {
        $condicion = "";
        if(in_array("Planner", $roles)){
            $condicion = "and a.user_autoriza = '$usuario_rainde'";
          }else{
               $condicion = "and a.vendedor = '$usuario_rainde'";
          }
         $query = $this->db->query("select (sum(utilidad)/sum(vta_total_autorizada))*tipo_de_cambio pMargen from api a where year(a.fecha_carga_ci)*100+month(a.fecha_carga_ci) = year(now())*100+month(now()) and estatus_cv = 'ACTIVO' $condicion");
         $result = $query->row_array();
         return floatval($result["pMargen"]*100) ?? 0;
        
        return [];
    }

    public function getRecuperacionPUsuario($usuario_rainde, $roles)
    {
        $condicion = "";
        if(in_array("Planner", $roles)){
            $condicion = "and a.user_autoriza = '$usuario_rainde'";
          }else{
               $condicion = "and a.vendedor = '$usuario_rainde'";
          }
         $query = $this->db->query("SELECT SUM(sub_fact_dxt)*		
            (SUM(importe_cobrado_dxt)/SUM(tot_fact_dxt))*
            (SUM(utilidad)/SUM(vta_total_autorizada))*tipo_de_cambio recuperacion from api a where year(fecha_cobro_dxt)*100 + month(fecha_cobro_dxt) = year(now())*100+month(now()) and estatus_cv = 'ACTIVO' $condicion");
         $result = $query->row_array();
         return $result["recuperacion"] ?? 0;
    
    }

    public function getCumplimientoMetaPUsuario($usuario_rainde, $roles)
    {
        $condicion = "";
       if(in_array("Planner", $roles)){
           $condicion = "and a.user_autoriza = '$usuario_rainde'";
           $tipoObjetvio = "COMPRA";
         }else{
              $condicion = "and a.vendedor = '$usuario_rainde'";
                $tipoObjetvio = "VENTA";
         }
        $query = $this->db->query("select sum(utilidad*tipo_de_cambio) profit from api a where year(a.fecha_carga_ci)*100+month(a.fecha_carga_ci) = year(now())*100+month(now()) and estatus_cv = 'ACTIVO' $condicion");
        $result = $query->row_array();

        $profit = $result["profit"] ?? 0;
        if($profit==0){
            return 0;
        }
        
        $query = $this->db->query("SELECT objetivo from objetivos where anomes = year(now())*100+month(now()) and tipo = '$tipoObjetvio' and tipo_objetivo = 'MRG' and entidad = '$usuario_rainde'");
        $result = $query->row_array();
        $objetivo = $result["objetivo"] ?? 0;
        if($objetivo==0){
            return 0;
        }

        return (floatval($profit)/floatval($objetivo))*100;
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
        $total = $perdidos+$cvs;

        if($perdidos== 0 && $cvs == 0){
            return 0;
        }

        if($cvs == 0){
            return 100;
        }

        return $perdidos/$total*100;
      
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

}
