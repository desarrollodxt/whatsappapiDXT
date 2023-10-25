<?php

function redimensionar_imagen($nombreimg, $rutaimg, $xmax, $ymax)
{
    $ext = explode(".", $nombreimg);
    $ext = $ext[count($ext) - 1];

    if ($ext == "jpg" || $ext == "jpeg")
        $imagen = imagecreatefromjpeg($rutaimg);
    elseif ($ext == "png")
        $imagen = imagecreatefrompng($rutaimg);
    elseif ($ext == "gif")
        $imagen = imagecreatefromgif($rutaimg);

    $x = imagesx($imagen);
    $y = imagesy($imagen);

    if ($x <= $xmax && $y <= $ymax) {
        return $imagen;
    }

    if ($x >= $y) {
        $nuevax = $xmax;
        $nuevay = $nuevax * $y / $x;
    } else {
        $nuevay = $ymax;
        $nuevax = $x / $y * $nuevay;
    }

    $img2 = imagecreatetruecolor($nuevax, $nuevay);
    imagecopyresized($img2, $imagen, 0, 0, 0, 0, floor($nuevax), floor($nuevay), $x, $y);
    return $img2;
}

function extension($archivo)
{
    $ext = explode('.', $archivo);
    $ext = array_pop($ext);
    return strtolower($ext);
}

function fn_aleatorio($tipo, $longitud)
{
    $resultado = '';

    switch ($tipo) {
        case 'numerico':
            $numerico = '1234567890';
            $numerico = [
                '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'
            ];
            for ($i = 1; $i <= $longitud; ++$i)
                // $resultado .= $numerico{mt_rand( 0, 9 )};
                $resultado .= $numerico[mt_rand(0, 9)];
            return $resultado;
            break;
        case 'alfabetico':
            // $alfabetico = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
            $alfabetico = [
                'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K',
                'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V',
                'W', 'X', 'Y', 'Z'
            ];
            for ($i = 1; $i <= $longitud; ++$i)
                // $resultado .= $alfabetico{
                // 	mt_rand(0, 25)};
                $resultado .= $alfabetico[mt_rand(0, 25)];
            return $resultado;
            break;
        case 'alfanumerico':
            // $alfanumerico = 'AB1CD2EF3GH4JK5LM6NP7QR8ST9UV0WXYZ';
            $alfanumerico = [
                'A', 'B', '1', 'C', 'D', '2', 'E', 'F', '3', 'G',
                'H', '4', 'J', 'K', '5', 'L', 'M', '6', 'N', 'P',
                '7', 'Q', 'R', '8', 'S', 'T', '9', 'U', 'V', '0',
                'W', 'X', 'Y', 'Z'
            ];
            for ($i = 1; $i <= $longitud; ++$i)
                // $resultado .= $alfanumerico{
                // 	mt_rand(0, 35)};
                $resultado .= $alfanumerico[mt_rand(0, 32)];
            return $resultado;
            break;
    }
}
