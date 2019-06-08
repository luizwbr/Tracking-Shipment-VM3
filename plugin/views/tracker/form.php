<?php
/**
 *
 * Description
 *
 * @package	VirtueMart
 * @subpackage Coupon
 * @author RickG
 * @author Valérie Isaksen
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: edit.php 6347 2012-08-14 15:49:02Z Milbo $
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

if (!class_exists('VmHTML'))
	require_once(JPATH_VM_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'html.php');
if (!class_exists('VirtueMartModelOrders'))
	require_once( JPATH_VM_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'orders.php' );
if (!class_exists('VMTrackerHelper'))
	include_once JPATH_ROOT.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.'vmtrackingshipment'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';	

if(!class_exists('VmViewAdmin')) {
	if(file_exists(JPATH_VM_ADMINISTRATOR.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'vmviewadmin.php')) {
		if(!class_exists('VmViewAdmin'))require(VMPATH_ADMIN.DS.'helpers'.DS.'vmviewadmin.php');
	}	
}

VmConfig::loadConfig();
vmJsApi::jQuery();
vmJsApi::jSite();

// AdminUIHelper::startAdminArea(new VmViewAdmin());

$orderStatusModel			= VmModel::getModel('orderstatus');
$orderstatuses 				= $orderStatusModel->getOrderStatusList();

$tracking_id 		 		= JRequest::getInt('tracking_id',0);
$tracking_code_admin 		= JSession::getFormToken();
$virtuemart_order_id 		= JRequest::getVar('virtuemart_order_id');
$order_number 		 		= JRequest::getVar('order_number');
$post_date 		 	 		= JRequest::getVar('post_date');
$estimate_date 		 		= JRequest::getVar('estimate_date');
$order_status 		 		= JRequest::getVar('order_status');
$virtuemart_order_item_id 	= JRequest::getVar('virtuemart_order_item_id');
$nfe 						= JRequest::getVar('nfe');

// recupera informações salvas
$tracking_data		 		= VMTrackerHelper::getData($order_number, $virtuemart_order_id, $virtuemart_order_item_id, $tracking_id);
$tracking_code_sql   		= $tracking_data[0]->tracking_code;
$tracking_id_sql  	 		= $tracking_data[0]->id;
$order_status_sql 	 		= VMTrackerHelper::getOrderStatus($virtuemart_order_id);

// troca o status do pedido para o desejado
$modelOrder = new VirtueMartModelOrders();
$orderitems = $modelOrder->getOrder($virtuemart_order_id);
$nb_history = count($orderitems['history']);

$task2 = JRequest::getVar('task2');
// salva no banco 
if ($task2 == 'salvar') {
	$db = JFactory::getDBO();
	$tracking_code = JRequest::getVar('tracking_code');

	$erro = false;
	$inserir = false;

	if (empty($tracking_code_sql) and empty($tracking_id_sql)) {
		$query = "INSERT INTO #__virtuemart_shipment_tracking 
				SET order_number = '".$order_number."',
				virtuemart_order_id = '".$virtuemart_order_id."',
				tracking_code = '".$tracking_code."',				
				post_date = '".$post_date."',
				estimate_date = '".$estimate_date."',
				virtuemart_order_item_id = '".$virtuemart_order_item_id."',
				nfe = '".$nfe."'
				";
		$inserir = true;
	} else {
		if (!empty($tracking_id)) {
			$query = "UPDATE #__virtuemart_shipment_tracking 
				SET tracking_code = '".$tracking_code."',
				post_date = '".$post_date."',
				estimate_date = '".$estimate_date."',
				virtuemart_order_item_id = '".$virtuemart_order_item_id."',
				nfe = '".$nfe."'
				WHERE id = ".$tracking_id;
		} elseif (!empty($tracking_id_sql)){
			$query = "UPDATE #__virtuemart_shipment_tracking 
				SET tracking_code = '".$tracking_code."',
				post_date = '".$post_date."',
				estimate_date = '".$estimate_date."',
				virtuemart_order_item_id = '".$virtuemart_order_item_id."',
				nfe = '".$nfe."'
				WHERE id = ".$tracking_id_sql;

		} else {
			JFactory::getApplication()->enqueueMessage( 'Tracking ID não-encontrado', 'error' );	
			$erro = true;
		}
	}

	if(!$erro) {
		$db->setQuery($query);
		if ($db->query()) {
			JFactory::getApplication()->enqueueMessage( 'Tracking Code salvo com sucesso' );		

			// datas 		
			if (strpos($post_date,"23:59:59") === false) {
				$post_date .= " 23:59:59";
			}

			if (strpos($estimate_date,"23:59:59") === false) {
				$estimate_date .= " 23:59:59";
			}

			$status_name = '';
			$status_descricao = '';
			foreach ($orderstatuses as $key => $order_status_obj) {
			    if ($order_status_obj->order_status_code == $order_status) {
				    $status_name   		= JText::_($order_status_obj->order_status_name);
			    	$status_descricao  	= $order_status_obj->order_status_description;
			     	break;
			    }
			}

			$data_postagem = vmJsApi::date ($post_date,'LC3',true);
			$data_estimada = vmJsApi::date ($estimate_date,'LC3', true );

						// recupera os parametros do plugin
			$plugin = JPluginHelper::getPlugin('system', 'vmtrackingshipment');
			$pluginParams = new JRegistry();
			$pluginParams->loadString($plugin->params);

			// parametros
			$default_url 	= $pluginParams->get('default_url','');
			$shipment_url 	= $pluginParams->get('shipment_url','');

			// código de entrega
			$shipment_id 	= $orderitems['details']['BT']->virtuemart_shipmentmethod_id;

			if (isset($shipment_url->{$shipment_id}->{'url'}) and $shipment_url->{$shipment_id}->{'url'} != '')  {
				$url_busca = ($shipment_url->{$shipment_id}->{'url'}).$tracking_code;
			} elseif ($default_url != '') {
				$url_busca = $default_url.$tracking_code;
			} else {
				$url_busca = "";				
			}

			$url_site = str_replace("administrator/","",JURI::base());
			$url_pedido = $url_site."index.php?option=com_virtuemart&view=orders";

			$nfe_text = '';
			if (!empty($nfe)) {
				$nfe_text = 'Número da NF-e: '.$nfe;
			}

			// notificação
			$notificacao = "Rastreio da Mercadoria para este pedido: <b><a href='".$url_busca."'>#".$tracking_code."</a>. </b><br/>
			Data da Postagem: ".$data_postagem." <br/>
			Data Estimada da Entrega: ".$data_estimada." <br/>
			Status do pedido: ".$status_name." <br/>
			Descrição: ".$status_descricao." <br />
			".$nfe_text."
			<br />
			<a href='".$url_pedido."'>Verifique nos Detalhes de seu Pedido</a>.";

			// $modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);

			if (!empty($virtuemart_order_item_id)) {
				JRequest::setVar('calculate_billTaxAmount',1);

				// novo status
				$order_data = VMTrackerHelper::getProductItem($orderitems['items'],$virtuemart_order_item_id);
				$order_data->order_status = $order_status;
				$modelOrder->updateSingleItem($virtuemart_order_item_id, $order_data, true);

			} else {
				$order = array();
				$order['order_status'] 			= $order_status;
				$order['virtuemart_order_id'] 	= $virtuemart_order_id;
				$order['comments'] 				= $notificacao;
				$order['customer_notified'] 	= 0;
				$modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);				
			}

			$mailer = JFactory::getMailer();
			$config = JFactory::getConfig();
			$sender = array( 
			    $config->get( 'mailfrom' ),
			    $config->get( 'fromname' ) 
			);			 

			$mailer->setSender($sender);

			$body   = "<html><body><h1>Rastreio - Pedido N. ".$order_number."</h1>
			<br/>
			".$notificacao."
			<br/>
			<br/>
			<hr />
			<a href='".$url_site."'>".$url_site."</a>
			</body></html>
			";
			$recipient = $orderitems['details']['BT']->email;
			$mailer->addRecipient($recipient);
			$mailer->isHTML(true);
			$mailer->Encoding = 'base64';
			
			$mailer->setSubject("Rastreio - Pedido N. ".$order_number.' - Status: '.$status_name);
			$mailer->setBody($body); 
			$send = $mailer->Send();
			if ( $send !== true ) {
			    JError::raiseWarning( 100, 'Erro ao enviar e-mail'. $send->__toString() );
			} else {
				JFactory::getApplication()->enqueueMessage( 'E-mail enviado com sucesso' );
			}

			
		} else {
			JFactory::getApplication()->enqueueMessage( 'Erro ao salvar o Tracking Code', 'error' );
		}
		if ($inserir) {
			$tracking_id = $db->insertid();
		}
	}

	$tracking_data 		 = VMTrackerHelper::getData($order_number, $virtuemart_order_id, $virtuemart_order_item_id, $tracking_id);

} else {

	// pega o tracking code recarregado do banco
	$tracking_code 	= $tracking_code_sql;	
	$order_status 	= $order_status_sql;
}

$post_date 	 				= $tracking_data[0]->post_date;
$estimate_date 				= $tracking_data[0]->estimate_date;
$virtuemart_order_item_id 	= $tracking_data[0]->virtuemart_order_item_id;
$nfe 						= $tracking_data[0]->nfe;

if (!empty($virtuemart_order_item_id)) {
	$order_data = VMTrackerHelper::getProductItem($orderitems['items'],$virtuemart_order_item_id);
	$order_status = $order_data->order_status;
}

// adiciona o item 0
$produtos 		= $orderitems['items'];
$item0 			= new stdClass();
$item0->virtuemart_order_item_id = '';
$item0->order_item_name = '- Todos os produtos -';

array_unshift($produtos, $item0);

?>
<form action="index.php" method="post" name="adminForm" id="adminForm">

	<fieldset>
	    <legend><?php echo JText::_('Rastreio da mercadoria para o pedido N. '.$order_number); ?></legend>
	    <table class="admintable">
			<?php echo VmHTML::row('input','Código de Rastreio','tracking_code',$tracking_code,'class="inputbox"','',20,32); 
			if (!empty($tracking_code)) {
				// imagens do form
				$url_assets_track 		= JURI::root().'/plugins/system/vmtrackingshipment/assets/images/';				
				$img_preview_rastreio 	= $url_assets_track.'rastreio_micro.png';
				$tracking_preview_url 	= JURI::root () . 'plugins/system/vmtrackingshipment/backend/url.php?tracking_code='.$tracking_code;
				echo "<tr><td></td><td><a href=\"$tracking_preview_url\"  class='url_track' target='_blank'>" . '<span title="Visualizar código de rastreio: '.$tracking_code.'"><img src="'.$img_preview_rastreio.'" border="0"/> #'.$tracking_code.'</span></a></td></tr>';				
			}
			?>
			<?php echo VmHTML::row('raw','<b>Produto</b>',JHTML::_ ('select.genericlist', $produtos, "virtuemart_order_item_id", 'class="orderstatus_select"', 'virtuemart_order_item_id', 'order_item_name', $virtuemart_order_item_id, 'virtuemart_order_item_id', TRUE)); ?>		
			<?php echo VmHTML::row('raw','Data da Postagem',  vmJsApi::jDate(str_replace('00:00:00','23:59:59',$post_date) , 'post_date') ); ?>
			<?php echo VmHTML::row('raw','Data Estimada da Entrega',  vmJsApi::jDate(str_replace('00:00:00','23:59:59',$estimate_date) , 'estimate_date') ); ?>
			<?php echo VmHTML::row('raw','Status do pedido',JHTML::_ ('select.genericlist', $orderstatuses, "order_status", 'class="orderstatus_select"', 'order_status_code', 'order_status_name', $order_status, 'order_status', TRUE)); ?>		
			<?php echo VmHTML::row('input','Número da NF-e','nfe',$nfe,'class="inputbox"','',20,32);  ?>

	    </table>
		<input type="submit" value="Salvar" class="submit"/>
	</fieldset>

    <input type="hidden" name="tracking_id" value="<?php echo $tracking_id; ?>" />
    <input type="hidden" name="tracking_code_admin" value="<?php echo $tracking_code_admin; ?>" />    
    <input type="hidden" name="virtuemart_order_id" value="<?php echo $virtuemart_order_id; ?>" />        
    <input type="hidden" name="order_number" value="<?php echo $order_number;?>" />    
    <input type="hidden" name="option" value="com_virtuemart" />    
    <input type="hidden" name="tmpl" value="component" />    
    <input type="hidden" name="task2" value="salvar" />
</form>
<?php
// AdminUIHelper::endAdminArea();
echo vmJsApi::writeJS();
