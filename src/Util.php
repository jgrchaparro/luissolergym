<?php

namespace App;
class Util
{
    /**
     * @param $linea
     * @param $arrayIndices
     * @return array
     */
    public static function loadLine($linea, $arrayIndices)
    {
        $arrCampos = array();
        foreach ($arrayIndices as $item => $valor) {
            $idChar = 0;
            while ($idChar++ < strlen($linea) - 1) {
                $campo = substr($linea, $valor["inicio"], $valor["largo"]);
                $arrCampos[] = array(
                    "nombre" => $item,
                    "valor" => $campo
                );
                $idChar += strlen($campo);
            }
        }
        return $arrCampos;
    }
}
