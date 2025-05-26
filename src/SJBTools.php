<?php
/**
 * Utiles
 * By cancrexo 2025-03-24
 * paso a  composer
 */
namespace Sjboverrides;

class SJBTools
{
    const _SELECTED_ = 'selected="selected"';
    const _CHECKED_ = 'checked="checked"';
    const _DISABLED_ = 'disabled="disabled"';

    public static function color_inverse($color){
        return '#' . dechex((int) 0xffffff - (int) ('0x' . str_replace('#', '', $color)));
    }

    // biolanor@gmail.com (sacada de php.net)
    public static function format_size($size) {
        $sizes = [' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB'];
        if ($size == 0) {return ('n/a');} else {
            return (round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $sizes[$i]);
        }
    }

    // Para  creacion de arquivos excel, no caso de que os campos teñan tabs ou newline
    // https://www.the-art-of-web.com/php/dataexport/
    public static function cleanData(&$str) {
        $str = preg_replace("/\t/", "\\t", $str);
        $str = preg_replace("/\r?\n/", "\\n", $str);
    }

    /*
        Función que xenera un string aleatorio de n caracteres.
        Recibe a lonx da cadena a xenerar.
    ------------------------------------------------------------------*/
    public static function xeneraString($lonx = 6) {
        $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
        $cad = '';
        while (strlen($cad) < $lonx) {
            $cad .= substr($str, mt_rand(0, 62), 1);
        }
        return $cad;
    }

    /*
        OPERACIONS DE BITS (https://www.geeksforgeeks.org/find-value-k-th-bit-binary-representation/)
    */

    /* Lee valor de bit en la posicion dada  (empiezan en INDICE 1, RTL)
       Numero a modificar, posicion do bit */
    public static function bit_get($n, $bit) {
        return ($n & (1 << ($bit - 1))); // Salida 0 ou 1, evidentemente
    }

    // setea el valor del bit indicado (empezando en INDICE 0, RTL)
    public static function bit_set($n, $bit) {
        $mask = 1 << $bit;
        return ($n & ~$mask) |
            ((1 << $bit) & $mask); // Devuelve numero con el bit modificado (a 1 en este caso)
    }

    // setea el valor del bit indicado (empezando en INDICE 0, RTL)
    public static function bit_clear($n, $bit) {
        $mask = 1 << $bit;
        return ($n & ~$mask) |
            ((0 << $bit) & $mask); // Devuelve numero con el bit modificado (a 0 en este caso)
    }

    /*  Devolve color hexadecimal aleatorio (en fomato #ff)
        @return string
    ------------------------------------------------------------------*/
    public static function randomHexColor() {
        $col = '';
        for ($x = 0; $x <= 2; $x++) {
            $col .= str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
        }

        return $col;
    }

    /* Convirte fecha formato ES (dd-mm-YYYY)  a mysql (YYYY-mm-dd)
    -------------------------------------------------------------------*/
    public static function fecha2Sql($fecha) {
        preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{2,4})$/', $fecha, $mifecha);
        // Ollo: en $mifecha[0] vai acadena orixinal
        if (count($mifecha) != 4) {
            return false;
        }

        $lafecha = $mifecha[3] . '-' . $mifecha[2] . '-' . $mifecha[1];
        return $lafecha;
    }

    /* Convirte fecha mysql (YYYY-mm-dd) a  formato ES (dd-mm-YYYY)
    -------------------------------------------------------------------*/
    public static function Sql2Fecha($fecha) {
        preg_match('/^([0-9]{2,4})-([0-9]{1,2})-([0-9]{1,2})$/', $fecha, $mifecha);
        if (count($mifecha) != 4) {
            return false;
        }
        //Ollo: en $mifecha[0] vai acadena orixinal
        $lafecha = $mifecha[3] . '-' . $mifecha[2] . '-' . $mifecha[1];
        return $lafecha;
    }

    /* in_array para arrays multidimensionais
      https://stackoverflow.com/a/4128378 */
    public static function in_array_r($needle, $haystack, $strict = false) {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && static::in_array_r($needle, $item, $strict))) {
                return true;
            }
        }
        return false;
    }

    /*
    *	Método que fai select a partir de array.
    *  Devolve as option e dito select
    *	@return string
    --------------------------------------------------------------------*/
    public static function doSelectFromArray($arr, $parametros = null) {
        $select = '';
        if (isset($parametros) && isset($parametros['antes'])) {
            array_unshift($arr, ($parametros['antes'] == 1 ? 'Seleccione...' : $parametros['antes']));
        }
        foreach ($arr as $k => $v) {
            if ( ( isset( $parametros['indiceSel'] ) && $k == $parametros['indiceSel']) ||
                 ( isset( $parametros['valorSel'] ) && $v == $parametros['valorSel'])) {
                $select .= '<option value="' . $k . '" ' . static::_SELECTED_ . '>' . $v . '</option>' . "\n";
            } else {
                $select .= '<option value="' . $k . '">' . $v . '</option>' . "\n";
            }
        }
        if (!@$parametros['echo']) return $select;
        echo $select;

    }

    /*  Trunca unha cadena co separador e lonxitude que lle pases.
        REsibe array de params
    ------------------------------------------------------------------*/
    public static function truncar($params) {

        $lonxitude = strlen($cadena);
        if ($lonxitude <= $limite) return $cadena;

        $sep = (is_array($params) && isset($params['sep'])) ? $params['sep'] : '...';
        $limite    = (is_array($params) && isset($params['limite'])) ? $params['limite'] : 70;
        $cadena    = (is_array($params) && isset($params['cadena'])) ? $params['cadena'] : (is_string($params) ? $params : $sep);
        $fullwords = (is_array($params) && isset($params['fullwords'])) ? true : false;
        $salida    = '';

        if (!$fullwords)
            $salida = substr($cadena, 0, $limite);
        else {
            $temp = explode(' ', $cadena);
            while ((strlen($salida) < $limite) && count($temp) > 0) {
                $salida .= ($salida ? ' ' : '') . array_shift($temp);
            }
        }

        return rtrim($salida) . $sep;
    }

    // https://stackoverflow.com/a/23560569
    public static function toFixed($num, $precision = 2) {
        if ($num >= 0) {
            $mode = PHP_ROUND_HALF_UP;
        } else {
            $mode = PHP_ROUND_HALF_DOWN;
        }

        return round($num, $precision, $mode);
    }
}
