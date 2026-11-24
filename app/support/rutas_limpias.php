<?php
/** Alias públicos. Los archivos y controladores internos conservan sus nombres. */
function preparar_rutas_limpias(array $routes, string $base): array
{
    $overrides=['index.php'=>'','login.php'=>'ingresar','reserva.php'=>'reservar','admin.php'=>'administracion'];
    $forward=[];$reverse=[];
    foreach($routes as $route=>$file){
        $slug=$overrides[$route]??str_replace('_','-',preg_replace('/\.php$/','',$route));
        if(isset($reverse[$slug]))throw new LogicException('Alias de navegación duplicado.');
        $forward[$route]=$slug;$reverse[$slug]=$route;
    }
    $GLOBALS['rutas_limpias']=['base'=>$base,'forward'=>$forward,'reverse'=>$reverse];
    header_register_callback(function(){
        foreach(headers_list() as $header)if(str_starts_with(strtolower($header),'location:')){
            $location=trim(substr($header,9));$clean=url_limpia($location);
            if($clean!==$location)header('Location: '.$clean,true);
        }
    });
    ob_start(function($html){
        if(stripos($html,'<!doctype html')===false&&stripos($html,'<html')===false)return $html;
        return preg_replace_callback('~\b(href|action)\s*=\s*([\x22\x27])([^\x22\x27]*)\2~i',fn($m)=>$m[1].'='.$m[2].url_limpia($m[3]).$m[2],$html);
    });
    return $reverse;
}
function url_limpia(string $url): string
{
    $config=$GLOBALS['rutas_limpias']??null;
    if(!$config||$url===''||preg_match('~^(?:[a-z][a-z0-9+.-]*:|//|#)~i',$url))return $url;
    $parts=preg_split('/(?=[?#])/',$url,2);$path=$parts[0];$suffix=$parts[1]??'';$base=$config['base'];
    if($base!==''&&str_starts_with($path,$base.'/'))$path=substr($path,strlen($base)+1);
    else $path=ltrim($path,'/');
    if(str_contains($path,'/'))return $url;
    if(str_ends_with($path,'.html'))$path=substr($path,0,-5).'.php';
    if(!array_key_exists($path,$config['forward']))return $url;
    return $base.'/'.$config['forward'][$path].$suffix;
}
