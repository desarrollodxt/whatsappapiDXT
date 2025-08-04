<?php
class Geocerca_model extends CI_Model
{
    private $tabla = 'geocercas';

    private $patioCavsco = [
        "latitud" => 25.8400195,
        "longitud" =>  -100.2377910
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function obtener_geocercas()
    {
        $query = $this->db->select('*')->from($this->tabla)->get();
        return $query->result_array();
    }
    public function registrarGeocercas($geocercas)
    {
        //geocerca
        //     "id": "27200417",
        //     "name": "CS Transportes",
        //     "createdAtTime": "2021-11-05T17:49:34.929746Z",
        //     "formattedAddress": "Av. Huinala 128 Int. 3, Regio Parque Industrial, 66633 Cd Apodaca, N.L., Mexico",
        //     "geofence": {
        //         "polygon": {
        //             "vertices": [
        //                 {
        //                     "latitude": 25.751685005311014,
        //                     "longitude": -100.21206141733549
        //                 },
        //                 {
        //                     "latitude": 25.75188310293251,
        //                     "longitude": -100.21135599636457
        //                 },
        //                 {
        //                     "latitude": 25.752008725643247,
        //                     "longitude": -100.21103279017828
        //                 },
        //                 {
        //                     "latitude": 25.752086031860742,
        //                     "longitude": -100.21066666864775
        //                 },
        //                 {
        //                     "latitude": 25.751542471452186,
        //                     "longitude": -100.2105674269142
        //                 },
        //                 {
        //                     "latitude": 25.75104722535868,
        //                     "longitude": -100.21048964285276
        //                 },
        //                 {
        //                     "latitude": 25.748955089807634,
        //                     "longitude": -100.21025897287748
        //                 },
        //                 {
        //                     "latitude": 25.74920150953521,
        //                     "longitude": -100.21138550066374
        //                 },
        //                 {
        //                     "latitude": 25.749324719207305,
        //                     "longitude": -100.21201850199125
        //                 },
        //                 {
        //                     "latitude": 25.749447928751614,
        //                     "longitude": -100.21265150331877
        //                 },
        //                 {
        //                     "latitude": 25.7505568088997,
        //                     "longitude": -100.21239937567137
        //                 }
        //             ]
        //         },
        //         "settings": {
        //             "showAddresses": true
        //         }
        //     },
        //     "notes": "YARD",
        //     "latitude": 25.75052056083419,
        //     "longitude": -100.21145523809813
        // }
        $values = '';
        $insert = "INSERT INTO geocercas(nombre,fecha_creacion,direccion,poligono,latitud, longitud,coordenadas, notas, geocerca_id, active, created_at)";
        foreach ($geocercas as $geocerca) {
            $notas = array_key_exists("notes", $geocerca) ? $geocerca["notes"] : '';

            $values .= "( '" . str_replace("'", "", $geocerca["name"]) . "', '" . $geocerca["createdAtTime"] . "', '" . $geocerca["formattedAddress"] . "', '" . json_encode($geocerca["geofence"]) . "', '" . $geocerca["latitude"] . "', '" . $geocerca["longitude"] . "', '" . $geocerca["latitude"] . "," . $geocerca["longitude"] . "',";
            $values .=  "'" .  $notas . "', '" . $geocerca["id"] . "', 1, now()),";
        }

        $values = substr($values, 0, -1);
        $values .= " ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), fecha_creacion=VALUES(fecha_creacion), direccion=VALUES(direccion), poligono=VALUES(poligono), latitud=VALUES(latitud), longitud=VALUES(longitud), coordenadas=VALUES(coordenadas), notas=VALUES(notas), geocerca_id=VALUES(geocerca_id), active=VALUES(active), created_at=VALUES(created_at)";
        $insert = $insert . " VALUES " . $values;
        // var_dump($insert);
        // exit;
        $this->db->query($insert);

        return true;
    }

    public function getGeocercaMasCercana($latitud, $longitud)
    {
        $query = $this->db->select('*')->from($this->tabla)->where('active', 1)->get();
        $geocercas = $query->result_array();
        $geocercaMasCercana = null;
        //distancia más cercana 100 metros
        $distanciaMasCercanaMetros = 1000;
        foreach ($geocercas as $geocerca) {
            $distancia = $this->getDistancia($latitud, $longitud, $geocerca["latitud"], $geocerca["longitud"]);
            if ($distancia < $distanciaMasCercanaMetros) {
                $distanciaMasCercanaMetros = $distancia;
                $geocercaMasCercana = $geocerca;
                return $geocercaMasCercana;
            }
        }
        return $geocercaMasCercana;
    }

    /**
     * Validar si un punto está dentro de una geocerca
     * @param array $geocerca punto para trazar la geocerca Latitud y longitud de la geocerca [latitud, longitud]
     * @param array $punto Latitud y longitud del punto [latitud, longitud]
     * @param int $radio Radio de la geocerca en metros, es decir la distancia máxima que puede haber entre el punto y la geocerca para considerarse dentro de la geocerca
     * @return boolean true si el punto está dentro de la geocerca, false si no
     */

    public function puntoEnGeocerca($geocerca, $punto, $radio = 1000)
    {
        $distancia = $this->getDistancia($geocerca["latitud"], $geocerca["longitud"], $punto["latitud"], $punto["longitud"]);
        return $distancia <= $radio;
    }


    public function toRad($value)
    {
        return $value * pi() / 180;
    }

    /**
     * Calcular la distancia entre dos puntos
     * @param string $latitud Latitud del punto
     * @param string $longitud Longitud del punto
     * @param string $latitudGeocerca Latitud de la geocerca
     * @param string $longitudGeocerca Longitud de la geocerca
     * @return float Distancia en metros
     */

    public function getDistancia($latitud, $longitud, $latitudGeocerca, $longitudGeocerca)
    {
        $lat1 = $latitud;
        $lon1 = $longitud;
        $lat2 = $latitudGeocerca;
        $lon2 = $longitudGeocerca;
        $R = 6371; // km
        $dLat = $this->toRad($lat2 - $lat1);
        $dLon = $this->toRad($lon2 - $lon1);
        $lat1 = $this->toRad($lat1);
        $lat2 = $this->toRad($lat2);

        $a = sin($dLat / 2) * sin($dLat / 2) + sin($dLon / 2) * sin($dLon / 2) * cos($lat1) * cos($lat2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $d = $R * $c * 1000;

        return $d;
    }

    /**
     * Determinar la ubicación de un punto, puede ser que este en el patio, en monterrey o en estados unidos
     * @param string $latitud Latitud del punto
     * @param string $longitud Lalongitud del punto
     * @return string Patio, Monterrey, Estados Unidos
     */

    public function determinarUbicacion($latitud, $longitud)
    {
        $distanciaPatio = $this->getDistancia($latitud, $longitud, $this->patioCavsco["latitud"], $this->patioCavsco["longitud"]);
        if ($distanciaPatio < 300) {
            return "Patio";
        }
        return "Monterrey";
    }
}
