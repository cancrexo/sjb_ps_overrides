<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 *  Override para permitir imaxes png transparentes. Parece que falla
 *  Preferences -> Images -> Engadiur e poñerlle de nombre whatever_sjb (engadir '_sjb' ao final do name)
 *
 *  @author Cancrexo. cancrexo@gmail.com
 *  @copyright 2024 SJB Dixital
 *  @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class ImageManager extends ImageManagerCore
{
    const SJB_CROP_HARD    = 0; // Recorte da foto das dimensións indicadas, a pelo e sin redimension (hard-crop)
    const SJB_CROP_AXUSTAR = 1; // Redimensionar ata o límite e cortar o que sobra $PUNTO_ORIGEN  (0 top-left, 1-top-center, ..., 8 bottom-right)
    const SJB_CROP_FIT     = 2; // Redimensionar hasta que entre no "marco" (poden quedar ocos!!!)
    const SJB_CROP_DISTORT = 3; // Redimensionar completamente sin proporcionalidade!! --> distorsiona
    const SJB_CROP_JCROP   = 4; // Recorte jCrop    debes recibir as 4 coordenadas (X1,Y1), (X2,Y2)

    /**
     * Resize, cut and optimize image
     *
     *
     * @param string $src_file Image object from $_FILE
     * @param string $dst_file Destination filename
     * @param integer $dst_width Desired width (optional)
     * @param integer $dst_height Desired height (optional)
     * @param string $file_type
     * @return boolean Operation result
     */

    public static function resize($src_file, $dst_file, $dst_width = null, $dst_height = null, $file_type = 'jpg', $force_type = false,
        &$error = 0, &$tgt_width = null, &$tgt_height = null, $quality = 5, &$src_width = null, &$src_height = null) {

        if (! Module::isEnabled('sjboverrides') || ! ($SJBOVERRIDES = Module::getInstanceByName('sjboverrides'))
            || ! ($override_enabled = (bool) $SJBOVERRIDES->getSingleData('general', 'hookimagemanager'))
        ) {
            return parent::resize($src_file, $dst_file, $dst_width, $dst_height, $file_type, $force_type, $error, $tgt_width, $tgt_height, $quality, $src_width, $src_height);
        }



        if (! file_exists($src_file)) {
            return false;
        }


        // Fotos coa extension chula: sjb, _sjba. Esto haberia que configuralo
        if (array_filter(['_sjb'], fn($ext) => strpos($dst_file, $ext) !== false)) {
            return self::sjb_new_resize($src_file, $dst_file, $dst_width, $dst_height, $file_type, $force_type);
        }

        list($src_width, $src_height, $type) = getimagesize($src_file);
        $zoomCrop                            = true;

        /*
         If PS_IMAGE_QUALITY is activated, the generated image will be a PNG with .jpg as a file extension.
         This allow for higher quality and for transparency. JPG source files will also benefit from a higher quality
         because JPG reencoding by GD, even with max quality setting, degrades the image.
      */

        if (Configuration::get('PS_IMAGE_QUALITY') == 'png_all'
            || (Configuration::get('PS_IMAGE_QUALITY') == 'png' && $type == IMAGETYPE_PNG) && ! $force_type) {
            $file_type = 'png';
        }

        if (! $src_width) {
            return false;
        }

        if (! $dst_width) {
            $dst_width = $src_width;
        }

        if (! $dst_height) {
            $dst_height = $src_height;
        }

        $src_image = ImageManager::create($type, $src_file);

        $width_diff  = $dst_width / $src_width;
        $height_diff = $dst_height / $src_height;

        if ($zoomCrop == true) {

            if ($dst_width > 0 && $dst_height < 1) {
                $dst_height = floor($src_height * ($dst_width / $src_width));
            } else if ($dst_height > 0 && $dst_width < 1) {
                $dst_width = floor($src_width * ($dst_height / $src_height));
            }

            $src_x = $src_y = 0;
            $src_w = $src_width;
            $src_h = $src_height;

            $cmp_x = $src_width / $dst_width;
            $cmp_y = $src_height / $dst_height;

            if ($cmp_x > $cmp_y) {

                $src_w = round(($src_width / $cmp_x * $cmp_y));
                $src_x = round(($src_width - ($src_width / $cmp_x * $cmp_y)) / 2);

            } else if ($cmp_y > $cmp_x) {

                $src_h = round(($src_height / $cmp_y * $cmp_x));
                $src_y = round(($src_height - ($src_height / $cmp_y * $cmp_x)) / 2);

            }

        } else if ($width_diff > 1 && $height_diff > 1) {
            $next_width  = $src_width;
            $next_height = $src_height;
        } else {
            if (Configuration::get('PS_IMAGE_GENERATION_METHOD') == 2 || (! Configuration::get('PS_IMAGE_GENERATION_METHOD') && $width_diff > $height_diff)) {
                $next_height = $dst_height;
                $next_width  = round(($src_width * $next_height) / $src_height);
                $dst_width   = (int) (! Configuration::get('PS_IMAGE_GENERATION_METHOD') ? $dst_width : $next_width);
            } else {
                $next_width  = $dst_width;
                $next_height = round($src_height * $dst_width / $src_width);
                $dst_height  = (int) (! Configuration::get('PS_IMAGE_GENERATION_METHOD') ? $dst_height : $next_height);
            }
        }

        $DEST_IMAGE = imagecreatetruecolor($dst_width, $dst_height);

        // If image is a PNG and the output is PNG, fill with transparency. Else fill with white background.
        if ($file_type == 'png' && $type == IMAGETYPE_PNG) {
            imagealphablending($DEST_IMAGE, false);
            imagesavealpha($DEST_IMAGE, true);
            $transparent = imagecolorallocatealpha($DEST_IMAGE, 255, 255, 255, 127);
            imagefilledrectangle($DEST_IMAGE, 0, 0, $dst_width, $dst_height, $transparent);
        } else {
            $white = imagecolorallocate($DEST_IMAGE, 255, 255, 255);
            imagefilledrectangle($DEST_IMAGE, 0, 0, $dst_width, $dst_height, $white);
        }

        if ($zoomCrop == true) {
            imagecopyresampled($DEST_IMAGE, $src_image, 0, 0, $src_x, $src_y, $dst_width, $dst_height, $src_w, $src_h);
        } else {
            imagecopyresampled($DEST_IMAGE, $src_image, (int) (($dst_width - $next_width) / 2), (int) (($dst_height - $next_height) / 2), 0, 0, $next_width, $next_height, $src_width, $src_height);
        }

        return (ImageManager::write($file_type, $DEST_IMAGE, $dst_file));
    }

    /**
     * Create an image with GD extension from a given type
     *
     * @param string $type
     * @param string $filename
     * @return resource
     */
    public static function create($type, $filename)
    {
        // Incluimos oporte webp
        switch ($type) {
            case IMAGETYPE_GIF: // int(1)
                // \PrestaShopLogger::addLog('Creating GIF image');
                return imagecreatefromgif($filename);
                break;

            case IMAGETYPE_PNG: //int(3)
                // \PrestaShopLogger::addLog('Creating PNG image');
                return imagecreatefrompng($filename);
                break;

            case IMAGETYPE_WEBP: //int(18)
                // \PrestaShopLogger::addLog('Creating WEBP image');
                return imagecreatefromwebp($filename);
                break;

            case IMAGETYPE_JPEG: // int (2)
            default:
                // \PrestaShopLogger::addLog('Creating JPEG image');
                return imagecreatefromjpeg($filename);
                break;
        }
    }

    public static function isRealImage($filename, $file_mime_type = null, $mime_type_list = null)
    {
        // Incluimos oporte webp
        $mime_type = false;
        if (! $mime_type_list) {
            $mime_type_list = ['image/webp', 'image/gif', 'image/jpg', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png'];
        }
        return parent::isRealImage($filename, $file_mime_type, $mime_type_list);
    }

    /*
      Crea unha copia da imaxe cargada no servidor para traballar con ela
      Podemos facer 4 cousas con ela

      - SJB_CROP_HARD (0) - Recorte da foto das dimensións indicadas, a pelo e sin redimension (hard-crop)
               Colle a foto orixen e saca unha porcion dela das dimensións indicadas.  TODO -->podes indicar por onde facer o recorte
               si especificas o valor de $PUNTO_ORIGEN  (0 top-left, 1-top-center, ..., 8 bottom-right)  De momento top left

      - SJB_CROP_AXUSTAR (1) - Redimensionar ata o límite e cortar o que sobra -->PODES INDICAR POR ONDE FACERO RECORTE
                  si especificas o valor de $PUNTO_ORIGEN  (0 top-left, 1-top-center, ..., 8 bottom-right)

      - SJB_CROP_FIT (2) - Redimensionar hasta que entre no "marco" (poden quedar ocos!!!) -- Centrarase no marco destino. Si se trata dun PNG pods definir o fondo transparente. Resto de caso porase un color blanco de fondo (TODO: defini ese color )

      - SJB_CROP_DISTORT (3) - Redimensionar completamente sin proporcionalidade!! -->distoriona

      - SJB_CROP_JCROP (4) - Recorte jCrop debes recibir as 4 coordenadas (X1,Y1), (X2,Y2)
   */

    public static function sjb_new_resize($src_file, $dst_file, $dst_width, $dst_height, $file_type, $force_type)
    {
        \PrestaShopLogger::addLog('new resize, recibe filetype: '.$file_type); // esto saca jph

        // Para telo mais claro
        $canvas_width  = $dst_width;
        $canvas_height = $dst_height;

        list($src_width, $src_height, $type) = getimagesize($src_file);
        // \PrestaShopLogger::addLog('filetype real: '.$type); // cadena  'jpg' ou png

        if (! $src_width) {
            return false;
        }

        $src_image = static::create($type, $src_file); //-->usará imagecreatefrompng | imagecreatefromwebp , etc.

        if (Configuration::get('PS_IMAGE_QUALITY') == 'png_all'
            || (Configuration::get('PS_IMAGE_QUALITY') == 'png' && $type == IMAGETYPE_PNG) && ! $force_type) {
            $file_type = 'png';
        }
        //  \PrestaShopLogger::addLog('filetype usado: '.$file_type); // cadena  'jpg' ou 'png'

        $src_w = $src_width;
        $src_h = $src_height;

        $SRC_RATIO = $src_w / $src_h; // Relacion aspecto imaxe orixinal




        if (strpos($dst_file, '_sjba') !== false ) {
            // REDIMENSIONAR ata que entre no "marco" (poden quedar ocos)
            $TIPO_REDIMENSION = self::SJB_CROP_FIT;
            $POS_RECORTE      = 1; // Top-center (0,1,2,3,4,5,6,7,8)
        }
        else {
            // REDIMENSIONAR ata o  LIMITE E CORTAR O QUE SOBRE
            $TIPO_REDIMENSION = self::SJB_CROP_AXUSTAR;
            $POS_RECORTE      = 1; // Top-center
        }


        // A salida
        $DEST_IMAGE = null;

        /*
            Toca manipular a foto e gardar un recorte ou copia en $DEST_IMAGE.
        */

        switch ($TIPO_REDIMENSION) {

            case self::SJB_CROP_HARD: // Recorte sin redimensionar, a pelo.  (si e menor estírase!!)

                $DEST_IMAGE = static::myCrearCanvas($dst_width, $dst_height, $file_type);
                // PREPARAR DESTINO PARA TRANSPARENCIA
                // if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
                //     imagealphablending($DEST_IMAGE, false);
                //     imagesavealpha($DEST_IMAGE, true);
                // }

                // if (! imagecopyresampled($DEST_IMAGE, $src_image, 0, 0, 0, 0, $dst_width, $dst_height, $dst_width, $dst_height)) {
                //     \PrestaShopLogger::addLog('Error de imagecopy preparando Imagen, caso 0');
                //     return false;
                // }

            break;

            case self::SJB_CROP_AXUSTAR:


                // (1) Axustar o máximo posible e recortar o que sobre posicionandose sobre a foto según sexa. si e mais pequena, aumentaa. Ostia!
                // Calculo canto podo redimensionar axustando ao máximo posible
                // Probamos a reducir en horizontal a ver...
                if ($dst_width / $SRC_RATIO >= $dst_height) {
                    $anchoR = $dst_width;
                    $altoR  = (int) $dst_width / $SRC_RATIO; // Reducindo en horizontal quedame alto para recortar ou 0

                } else {
                    // Probanos vetical lo
                    $anchoR = (int) $dst_height * $SRC_RATIO;
                    $altoR  = $dst_height; // Reducindo en vertical queda ancho para recortar
                }

                // 1. Crear imágenes
                $DEST_IMAGE = static::myCrearCanvas($dst_width, $dst_height, $file_type);
                $src_img_redim = static::myCrearCanvas($anchoR, $altoR, $file_type);


                // Colleas a imaxe orixinal e metela no canvas redimensionado proporcionlamentre que acabas de crear
                if (! @imagecopyresampled($src_img_redim, $src_image, 0, 0, 0, 0, $anchoR, $altoR, $src_w, $src_h)) {
                    \PrestaShopLogger::addLog('Error de imagecopyresampled preparando Imagen, caso 1a');
                    return false;
                }



                /* E SEJUNDO despois recortas o que sobre para deixar so o que entra no canvas destino*/

                // Calculas posicion de recorte

                // Agora calculo donde posicionar o recadro de corte sobre src_img_redim (según $POS_RECORTE)
                $posX = [
                    0 => 0, 1 => ($anchoR - $dst_width) / 2, 2 => $anchoR - $dst_width,
                    3 => 0, 4 => ($anchoR - $dst_width) / 2, 5 => $anchoR - $dst_width,
                    6 => 0, 7 => ($anchoR - $dst_width / 2), 8 => $anchoR - $dst_width,
                ];

                $posY = [
                    0 => 0, 1                          => 0, 2                          => 0,
                    3 => ($altoR - $dst_height) / 2, 4 => ($altoR - $dst_height) / 2, 5 => ($altoR - $dst_height) / 2,
                    6 => $altoR - $dst_height, 7       => $altoR - $dst_height, 8       => $altoR - $dst_height,
                ];

                // TODO: Si NON se define o parámetro posicion colocase de forma que quede centrado sobre a imaxe redimensionadsa src_img_redim
                $srcX = $posX[$POS_RECORTE];
                $srcY = $posY[$POS_RECORTE];



                // E colles imaxe redimensionada e copias un RECORTE EN DESTINO:
                if (! @imagecopy($DEST_IMAGE, $src_img_redim, 0, 0, $srcX, $srcY, $dst_width, $dst_height)) {
                    \PrestaShopLogger::addLog('Error de imagecopy posicionando o recadro de corte, caso 1');
                    return false;
                }



            break;

            case self::SJB_CROP_FIT: // Reducir hasta que entre toda a foto nas dimensións finales (que quede "dentro") Ajustar



                if ($dst_width / $SRC_RATIO >= $dst_height) {
                    /*
                        $altoLimite= $dst_height;
                        $anchoLimite = $dst_height * $this->ratio; // Reducimos o ancho ate o limite e recortamos altura (si fai falta)
                    */
                    $dst_width = $dst_height * $SRC_RATIO; // Reducimos o ancho ate o limite e recortamos altura (si fai falta)

                } else {
                    /*
                        $altoLimite= $dst_width/$this->ratio;
                        $anchoLimite = $dst_width; // Reducimos o alto ate o limite e recortamos anchura (si fai falta)
                    */
                    $dst_height = (int) ($dst_width / $SRC_RATIO);
                }

                $DEST_IMAGE = static::myCrearCanvas($dst_width, $dst_height, $file_type);
                if (! $DEST_IMAGE) {
                    \PrestaShopLogger::addLog('Error de imagecreatetruecolor , caso 2');
                    return false;
                }
                if (! @imagecopyresampled($DEST_IMAGE, $src_image, 0, 0, 0, 0, $dst_width, $dst_height, $src_w, $src_h)) {
                    \PrestaShopLogger::addLog('Error de imagecopyresized , caso 2');
                    return false;
                }

            break;

            case self::SJB_CROP_DISTORT: //Redimensionar completamente sin proporcionalidade!!!

                $DEST_IMAGE = static::myCrearCanvas($dst_width, $dst_height, $file_type);
                if (! @imagecopyresampled($DEST_IMAGE, $src_image, 0, 0, 0, 0, $dst_width, $dst_height, $src_w, $src_h)) {
                    \PrestaShopLogger::addLog('Error de imagecopyresized preparando Imagen, caso 3');
                    return false;
                }

            break;

            case SJB_CROP_JCROP: // Recorte jCrop  - TODO.
                // Colles unha imaxe, sacas un recorte e redimensionas ese recorte coas dimesnions desexadas
                $DEST_IMAGE = static::myCrearCanvas($dst_width, $dst_height, $file_type);
                // info da area seleccionada
                $origX = (int) $_POST['x'];
                $origY = (int) $_POST['y'];

                $anchoRecorte = (int) $_POST['w'];
                $altoRecorte  = (int) $_POST['h'];

                // \PrestaShopLogger::addLog('Dimensións imaxe JCrop:' . $anchoRecorte . ' X ' . $altoRecorte);
                if (! @imagecopyresampled(
                    $DEST_IMAGE, $src_image, 0, 0, $origX, $origY, $dst_width, $dst_height, $anchoRecorte, $altoRecorte)) {
                    \PrestaShopLogger::addLog('Error de imagecopyresized preparando Imagen, caso 4');
                    return false;
                }
            break;
        }

        //  \PrestaShopLogger::addLog('esxcribiendo imagen tipo ' . $file_type);
        return (ImageManager::write($file_type, $DEST_IMAGE, $dst_file)); // oll que usa strins co filetype

    }

    // Crea imaxe valdeira, e si soporta transparencia, tense en conta
    public static function myCrearCanvas($canvas_width, $canvas_height, $type)
    {
        $novaimaxe = imagecreatetruecolor($canvas_width, $canvas_height);

        if(in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
            // \PrestaShopLogger::addLog(__function__. ' Imagen transparente');
            imagealphablending($novaimaxe, false);    // Cambiado a TRUE
            imagesavealpha($novaimaxe, true);
            $transparente = imagecolorallocatealpha($novaimaxe, 0, 0, 0, 127);
            //imagefilledrectangle($novaimaxe, 0, 0, $canvas_width, $canvas_height, $bgcolor);
            imagefill($novaimaxe, 0, 0, $transparente);
        } else {
            // \PrestaShopLogger::addLog(__function__. ' Imagen NO transparente');
            $bgcolor = imagecolorallocate($novaimaxe, 255, 255, 255);
            imagefilledrectangle($novaimaxe, 0, 0, $canvas_width, $canvas_height, $bgcolor);
        }
        return $novaimaxe;
    }


    public static function alphaBefore(&$img, $type)
    {
        if(in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
            imagealphablending($img, true);      // TRUE para copiar
            imagesavealpha($img, true);
        }
    }

    public static function alphaAfter(&$img, $type)
    {
        if(in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
            imagealphablending($img, false);     // FALSE para preservar
            imagesavealpha($img, true);
        }
    }
}
