<?php
include("clearpass.php");
$telegrambot=“xxx”;
include("base64.php");
$update_id="";
// Im saving the last messaged that this Script read from Telegram in a txt file.
$fichero = 'update_id.txt';
$offset=file_get_contents($fichero);
if($offset!=""){
	$update_id=$offset;
}
function telegram($msg="") {
	global $telegrambot,$update_id;
        $url='https://api.telegram.org/bot'.$telegrambot.'/getUpdates';
        $data=array('offset'=>$update_id);
//	$data=array();
        $options=array('http'=>array('method'=>'POST','header'=>"Content-Type:application/x-www-form-urlencoded\r\n",'content'=>http_build_query($data),),);
//print_r($options);
        $context=stream_context_create($options);
        $result=file_get_contents($url,false,$context);
        return $result;
}

function audio($audio_id){
	global $telegrambot;
	$url='https://api.telegram.org/bot'.$telegrambot.'/getFile?file_id='.$audio_id;
        $options=array('http'=>array('method'=>'POST','header'=>"Content-Type:application/x-www-form-urlencoded\r\n",),);
        $context=stream_context_create($options);
        $result=file_get_contents($url,false,$context);
        return $result;
}

function audio_url($audio_url){
	global $telegrambot;
	$url='https://api.telegram.org/file/bot'.$telegrambot.'/'.$audio_url;
//	echo $url;
	return($url);
}

$resultado=telegram();

$data = json_decode($resultado, TRUE);
$valores=$data["result"];
$cantidad=count($valores);

foreach($valores as $key=>$value){
        $update_id=$value["update_id"];

if($update_id!=""){
        $fichero = 'update_id.txt'; 
	$texto=$update_id+1;
        file_put_contents($fichero, $texto);
}
        if($value["message"]){
//		if($value["message"]["text"]){
		if(array_key_exists("text",$value["message"])){
//		if(is_array($value["message"]["text"])){
//		if(in_array("text",$value["message"])){
	                $text=$value["message"]["text"];
                	$date=$value["message"]["date"];
                	$date2=date('H:i:s d/m/Y', $date);
			$from=$value["message"]["from"]["id"];
			$from_name=$value["message"]["from"]["first_name"];
#			$respuesta="Hola <b>".$from_name."</b>";
#                	enviar($respuesta,$from); // send message to Telegram
			if(stripos($text,"/start")!==false){
				$siregistrado=dimesiusuarioregistrado($from);
				if($siregistrado==true){
//					enviar("Bienvenido de nuevo al Bot de ClearPass, ya estas registrado. Dime que quieres hacer ahora:",$from);
					$menu=array('inline_keyboard' => array(
						array(array("text"=>"Listar dispositivos con alarma", "callback_data"=>"listado1"),array("text"=>"Borrar", "callback_data"=>"borrar"))
					));
					enviaropciones("Bienvenido de nuevo al Bot de ClearPass, ya estas registrado. Dime que quieres hacer ahora:",$from,$menu);
				}else{
//					enviar("Bienvenido al Bot de ClearPass, dime que quieres hacer:",$from);
                                        $menu=array('inline_keyboard' => array(
                                                array(array("text"=>"Registrar", "callback_data"=>"registrar"),array("text"=>"Informacion", "callback_data"=>"infoinicial"))
                                        ));
                                        enviaropciones("Bienvenido al Bot de ClearPass, elige una opcion:",$from,$menu);
				}
/*
$menu=array(
    'inline_keyboard' => array(
        array(array("text"=>"Registrar", "callback_data"=>"registrar"),array("text"=>"Borrar", "callback_data"=>"borrar"))
    )
)
;
enviaropciones("Bienvenido al Bot de ClearPass, dime que quieres hacer.",$from,$menu);
*/
			}
			else{
				$respuesta="Hola <b>".$from_name."</b>";
                        	enviar($respuesta,$from); // send message to Telegram
			}
		}else if($value["message"]["voice"]){
//		}else if(in_array("voice",$value["message"])){
			// print_r($value["message"]["voice"]);
			$duration=$value["message"]["voice"]["duration"];
                        $mime_type=$value["message"]["voice"]["mime_type"];
                        $file_id=$value["message"]["voice"]["file_id"];
                        $file_size=$value["message"]["voice"]["file_size"];
			$from=$value["message"]["from"]["id"];
			$from_name=$value["message"]["from"]["first_name"];
                        $respuesta="Procesando AUDIO...";
                        enviar($respuesta,$from); // send message to Telegram
			$audio=json_decode(audio($file_id),true);
			$file_path=$audio["result"]["file_path"];
//			enviar("Audio: $file_path",$from);
			$url=audio_url($file_path);
//			enviar("URL: $url",$from);
			$datos=transcode($url,$from);
//			enviar("procesando ".$datos,$from);
			if(stripos($datos,"hola")!==false){
				if(stripos($datos,"clearpass")!==false){
					enviar("HOLA, ".$from_name."! Dime qué necesitas.",$from);
				}
			}
                        if(stripos($datos,"denegar")!==false){
				enviar("Procesando peticion...",$from);
				$fichero = 'mac.txt';
				$offset=file_get_contents($fichero);
				if($offset!=""){
				        $mac_rx=$offset;
				}
//				enviar($mac_rx,$from);
				$estado="NOK";
				$token=conexion(); // open API to ClearPass
				$data["attributes"]=array("Estado"=>$estado); // Attribute to write in the Endpoint
				update_endpoint($token, $mac_rx,$data); // Action to write in ClearPass with the API
        	                if($estado=="Cuarentena"){
                	                $estado_texto="Cuarentena \xE2\x80\xBC";
                        	}else if($estado=="OK"){
                                	$estado_texto="Valido \xE2\x9C\x85";
	                        }else if($estado=="NOK"){
        	                        $estado_texto="NO Valido \xF0\x9F\x9A\xAB";
                	        }else{
                        	        $estado_texto=$estado;
	                        }
        	                $respuesta="Estado Dispositivo: <b>".$estado_texto."</b>";
				enviar($respuesta,$from);
				$tr=coa($mac_rx);
                        	$pos=strpos($tr,"successful");
                        	if($pos){
                                	enviar("Reautenticando el dispositivo...",$from);
                        	}
                        }
                        if(stripos($datos,"autorizar")!==false){
                                enviar("Procesando peticion...",$from);
                                $fichero = 'mac.txt';
                                $offset=file_get_contents($fichero);
                                if($offset!=""){
                                        $mac_rx=$offset;
                                }
//                              enviar($mac_rx,$from);
                                $estado="OK";
                                $token=conexion(); // open API to ClearPass
                                $data["attributes"]=array("Estado"=>$estado); // Attribute to write in the Endpoint
                                update_endpoint($token, $mac_rx,$data); // Action to write in ClearPass with the API
                                if($estado=="Cuarentena"){
                                        $estado_texto="Cuarentena \xE2\x80\xBC";
                                }else if($estado=="OK"){
                                        $estado_texto="Valido \xE2\x9C\x85";
                                }else if($estado=="NOK"){
                                        $estado_texto="NO Valido \xF0\x9F\x9A\xAB";
                                }else{
                                        $estado_texto=$estado;
                                }
                                $respuesta="Estado Dispositivo: <b>".$estado_texto."</b>";
                                enviar($respuesta,$from);
                                $tr=coa($mac_rx);
                                $pos=strpos($tr,"successful");
                                if($pos){
                                        enviar("Reautenticando el dispositivo...",$from);
                                }
                        }
                        if(stripos($datos,"cuarentena")!==false){
                                enviar("Procesando peticion...",$from);
                                $fichero = 'mac.txt';
                                $offset=file_get_contents($fichero);
                                if($offset!=""){
                                        $mac_rx=$offset;
                                }
//                              enviar($mac_rx,$from);
                                $estado="Cuarentena";
                                $token=conexion(); // open API to ClearPass
                                $data["attributes"]=array("Estado"=>$estado); // Attribute to write in the Endpoint
                                update_endpoint($token, $mac_rx,$data); // Action to write in ClearPass with the API
                                if($estado=="Cuarentena"){
                                        $estado_texto="Cuarentena \xE2\x80\xBC";
                                }else if($estado=="OK"){
                                        $estado_texto="Valido \xE2\x9C\x85";
                                }else if($estado=="NOK"){
                                        $estado_texto="NO Valido \xF0\x9F\x9A\xAB";
                                }else{
                                        $estado_texto=$estado;
                                }
                                $respuesta="Estado Dispositivo: <b>".$estado_texto."</b>";
                                enviar($respuesta,$from);
                                $tr=coa($mac_rx);
                                $pos=strpos($tr,"successful");
                                if($pos){
                                        enviar("Reautenticando el dispositivo...",$from);
                                }
                        }
			if(stripos($datos,"/start")!==false){
                                enviar("Bienvenido al Bot de ClearPass, dime que quieres hacer.",$from);
			}
// 			print_r($datos);
		}
        }else if($value["callback_query"]){
                $text=$value["callback_query"]["data"];
                $from=$value["callback_query"]["from"]["id"];
                $valores=explode(";",$text);
                $text=$valores[0];
		if($text=="registrar"){
			registrar($from);
		}
        if($text=="infoinicial"){
            enviar("Con este Bot ".$unicodeString."\xF0\x9F\xA4\x96 podrás interactuar con el ClearPass de Agora. Es una forma facil de gestionar la politica de seguridad \xF0\x9F\x94\x92 de tu red pudiendo interactuar con el medio.",$from);
        }
        if($text=="listado1"){
            enviar("Listando dispositivos con alarma:",$from);
        }
        if($text=="borrar"){
			deregistrar($from);
        }
                if($text=="echar" OR $text=="autorizar" OR $text=="cuarentena"){
                        $respuesta="OK, voy a ello...";
			$respuesta="Procesando peticion...";
                        enviar($respuesta,$from);
                        $estado=$valores[2];

			$token=conexion(); // open API to ClearPass

			$data["attributes"]=array("Estado"=>$estado); // Attribute to write in the Endpoint
			$mac_rx=$valores[1];

			update_endpoint($token, $mac_rx,$data); // Action to write in ClearPass with the API
			if($estado=="Cuarentena"){
				$estado_texto="Cuarentena \xE2\x80\xBC";
				$nrole="iot_cuarentena";
			}else if($estado=="OK"){
				$estado_texto="Valido \xE2\x9C\x85";
				$nrole="iot";
			}else if($estado=="NOK"){
				$estado_texto="NO Valido \xF0\x9F\x9A\xAB";
				$nrole="iot_denegado";
			}else{
				$estado_texto=$estado;
			}
			$respuesta="Estado Dispositivo: <b>".$estado_texto."</b>";
			enviar($respuesta,$from); // send message to Telegram 
//			$tr=coa($mac_rx);
			$tr=role($mac_rx,$nrole);
			$pos=strpos($tr,"successful");
			if($pos){
				enviar("Reautenticando el dispositivo...",$from);
				if($estado=="OK"){
					enviar("Video: rtsp://10.239.66.5:554/mpeg4/media.amp",$from);
				}
			}
			guardarmac($mac_rx);
			$data["filter"]=array("mac_address"=>$mac_rx); // Attribute to write in the Endpoint
                        $mac_rx=$valores[1];
			$datos=get_endpoint_detail($token, $mac,$data);
//			enviar("HOLA",$from); 
}
        }
}
/*
if($update_id!=""){
        $fichero = 'update_id.txt'; 
        $texto=$update_id+1;
        file_put_contents($fichero, $texto);
}
*/
function enviar($msg,$from) {
	global $telegrambot;
        $url='https://api.telegram.org/bot'.$telegrambot.'/sendMessage';
        $data=array('chat_id'=>$from,'text'=>$msg,'parse_mode'=>'html');
        $options=array('http'=>array('method'=>'POST','header'=>"Content-Type:application/x-www-form-urlencoded\r\n",'content'=>http_build_query($data),),);
        $context=stream_context_create($options);
        $result=file_get_contents($url,false,$context);
        return $result;
}


function enviaropciones($msg,$from,$opciones) {
        global $telegrambot;
        $url='https://api.telegram.org/bot'.$telegrambot.'/sendMessage';
        $data=array('chat_id'=>$from,'text'=>$msg,'parse_mode'=>'html','reply_markup'=>json_encode($opciones));
        $options=array('http'=>array('method'=>'POST','header'=>"Content-Type:application/x-www-form-urlencoded\r\n",'content'=>http_build_query($data),),);
        $context=stream_context_create($options);
        $result=file_get_contents($url,false,$context);
        return $result;
}

function guardarmac($mac){
       $fichero = 'mac.txt'; 
       $texto=$mac;
       file_put_contents($fichero, $texto);
}

function registrar($from){

	$fichero = 'usuarios.txt';


	$file_handle = fopen($fichero, "r");
	$line="";
	while (!feof($file_handle)) {
		$line .= fgets($file_handle);
	}
	fclose($file_handle);
	$contenido=$line;

	if($contenido!=""){
		$contenido=json_decode($contenido);
	}else{
		$contenido=array();
	}

	if(in_array($from,$contenido)){
		enviar("Ya estas registrado.",$from);
		enviarmenuregistrado($from,"Opciones disponibles:");
	}else{
		$myFile = "usuarios.txt";
		$fh = fopen($myFile, 'w') or die("can't open file");
		$datos=array_push($contenido,$from); 
		$stringData = json_encode($contenido);
		fwrite($fh, $stringData);
		fclose($fh);
		enviar("Usuario registrado correctamente.",$from);
		enviarmenuregistrado($from,"Opciones disponibles:");
	}
}

function deregistrar($from){

        $fichero = 'usuarios.txt';


        $file_handle = fopen($fichero, "r");
        $line="";
        while (!feof($file_handle)) {
                $line .= fgets($file_handle);
        }
        fclose($file_handle);
        $contenido=$line;

        if($contenido!=""){
                $contenido=json_decode($contenido);
        }else{
                $contenido=array();
        }

        if(in_array($from,$contenido)){
                $myFile = "usuarios.txt";
                $fh = fopen($myFile, 'w') or die("can't open file");
		if(($key = array_search($from, $contenido)) !== false) {
			unset($contenido[$key]);
		}
                $stringData = json_encode($contenido);
                fwrite($fh, $stringData);
                fclose($fh);
                enviar("Usuario borrado correctamente, hasta pronto.",$from);
        }
	enviarmenunoregistrado($from,$mensaje="Elige una opcion:");
}

function dimesiusuarioregistrado($from){
	$fichero = 'usuarios.txt';
        $file_handle = fopen($fichero, "r");
        $line="";
        while (!feof($file_handle)) {
                $line .= fgets($file_handle);
        }
        fclose($file_handle);
        $contenido=$line;

        if($contenido!=""){
                $contenido=json_decode($contenido);
        }else{
                $contenido=array();
        }
	$respuesta="";
        if(in_array($from,$contenido)){
		$respuesta=true;
        }else{
		$respuesta=false;
        }
	return($respuesta);
}

function enviarmenuregistrado($from,$mensaje=""){
	if($mensaje==""){
		$mensaje="Bienvenido de nuevo al Bot de ClearPass, ya estas registrado. Dime que quieres hacer ahora:";
	}
	$menu=array('inline_keyboard' => array(
	array(array("text"=>"Dispositivos con alarma", "callback_data"=>"listado1")),array(array("text"=>"Darte de baja", "callback_data"=>"borrar"))
	));
	enviaropciones($mensaje,$from,$menu);
}

function enviarmenunoregistrado($from,$mensaje=""){
        if($mensaje==""){
		$mensaje="Bienvenido al Bot de ClearPass, elige una opcion:";
        }
        $menu=array('inline_keyboard' => array(
	array(array("text"=>"Registrarte", "callback_data"=>"registrar"),array("text"=>"Informacion", "callback_data"=>"infoinicial"))
        ));
        enviaropciones($mensaje,$from,$menu);
}
?>

