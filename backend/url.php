<?php
header('Content-Type: text/html; charset=iso-8859-1');

define('_JEXEC', 1);
define( 'DS', DIRECTORY_SEPARATOR );
define( 'JPATH_BASE', realpath(dirname(__FILE__).DS.'..'.DS.'..'.DS.'..'.DS.'..'.DS ));
require_once JPATH_BASE .DS.'includes'.DS.'defines.php';
require_once JPATH_BASE .DS.'includes'.DS.'framework.php';
// require_once JPATH_BASE .DS.'includes'.DS.'application.php';
$app = JFactory::getApplication('site');

// recupera os parametros do plugin
$plugin = JPluginHelper::getPlugin('system', 'vmtrackingshipment');
$pluginParams = new JRegistry();
$pluginParams->loadString($plugin->params);

// parametros
$default_url 	= $pluginParams->get('default_url','');
$shipment_url 	= $pluginParams->get('shipment_url','');

$shipment_id 	= JRequest::getInt('shipment_id','');
$tracking_code 	= JRequest::getVar('tracking_code','');

if (isset($shipment_url->{$shipment_id}->{'url'}) and $shipment_url->{$shipment_id}->{'url'} != '')  {
	$url_busca = ($shipment_url->{$shipment_id}->{'url'}).$tracking_code;
} elseif ($default_url != '') {
	$url_busca = $default_url.$tracking_code;
} else {
	echo $tracking_code;
	exit;
}

$conteudo = file_get_contents($url_busca);
if (!$conteudo) {
	if (!function_exists('curl_init')){ 
        die('CURL is not installed!');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_busca);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $conteudo = curl_exec($ch);
    curl_close($ch);    
}

echo str_replace(
	array('../correios/Img/correios.gif','type=button'),
	array('http://websro.correios.com.br/correios/Img/correios.gif','type=button style="display:none"'),
	$conteudo
);

?>