<?php
class Factura_model extends CI_Model
{
    private $tabla = 'facturas_factoraje';
    // campos de la tabla
    // id,id_empresa,numero,fase,dueno,estimacion,ubicaciongoogle,contactos,clee,id_lead,estatus,nombre,razon_social,observaciones,clase_actividad,estrato,tipo_vialidad,calle,num_exterior,num_interior,colonia,cp,ubicacion,telefono,correo_e,sitio_internet,tipo,longitud,latitud,tipo_corredor_industrial,nom_corredor_industrial,numero_local,ageb,manzana,clase_actividad_id,edificio_piso,sector_actividad_id,subsector_actividad_id,rama_actividad_id,subrama_actividad_id,edificio,tipo_asentamiento,fecha_alta,areageo,usuario_modifica,usuario_crea,fecha_modificacion_lead,fecha_creacion_lead,id_cliente
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    public function capturarFactura($factura)
    {
        $this->db->insert($this->tabla, $factura);
        return $this->db->insert_id();
    }

    public function getCvInfo($cv)
    {
        $query =  $this->db->select('*')
            ->from('api')
            ->where('cv', $cv);

        $query = $this->db->get();
        return $query->result_array();
    }

    public function getFacturaByCv($cv)
    {
        $query = $this->db->select('*')
            ->from($this->tabla)
            ->where('cv', $cv);

        $query = $this->db->get();
        return $query->result_array();
    }

    public function capturarPagoFactura($pago)
    {
        // Get the current balance of the factura
        $factura = $this->db->select('balance')
            ->from($this->tabla)
            ->where('id', $pago['factura_factoraje_id'])
            ->get()
            ->row();

        if (!$factura) {
            throw new Exception('Factura not found.');
        }

        // Calculate the new balance
        $new_balance = $factura->balance - $pago['monto_capturar'];

        // Check if the new balance is negative
        if ($new_balance < 0) {
            throw new Exception('The payment amount exceeds the current balance.');
        }

        // Update the balance of the factura
        $this->db->set('balance', $new_balance)
            ->where('id', $pago['factura_factoraje_id'])
            ->update($this->tabla);

        $pagoNew = [
            "monto" => floatval($pago["monto_capturar"]),
            "fecha_pago" => $pago["fecha_captura"],
            "factura_factoraje_id" => $pago["factura_factoraje_id"],
            "fecha_creacion" => date('Y-m-d H:i:s'),
            "usuario_registro_pago" => $pago["usuario"],
        ];

        // Insert the payment into pago_factoraje
        $this->db->insert('pago_factoraje', $pagoNew);
        return $this->db->insert_id();
    }
}
