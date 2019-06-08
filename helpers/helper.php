<?php

class VMTrackerHelper {
	static function getData($order_number, $virtuemart_order_id, $virtuemart_order_item_id='', $tracking_id = null) {
		$db = JFactory::getDBO();

		if (!empty($tracking_id)) {
			$sql_tracking_id = " AND id = ".$tracking_id;
		} else {
			$sql_tracking_id = "";
		}

		if ($virtuemart_order_item_id!='') {
			$sql_virtuemart_order_item_id = " AND virtuemart_order_item_id = ".$virtuemart_order_item_id;
		} else {
			$sql_virtuemart_order_item_id = "";
		}

		$query = "SELECT tracking_code, post_date, estimate_date, id, virtuemart_order_item_id, nfe
				FROM  #__virtuemart_shipment_tracking
				WHERE order_number = '".$order_number."' 
				AND virtuemart_order_id = '".$virtuemart_order_id."' ".$sql_tracking_id." ".$sql_virtuemart_order_item_id;
		$db->setQuery($query);
		$retorno = $db->loadObjectList();
		if (empty($retorno)) {
			$retorno = array();
			$retorno[0] = new StdClass();
			$retorno[0]->id 						= '';
			$retorno[0]->tracking_code 				= '';
			$retorno[0]->post_date 					= '';
			$retorno[0]->estimate_date 				= '';
			$retorno[0]->virtuemart_order_item_id 	= '';
		}
		return $retorno;
	}

	static function getOrderStatus($virtuemart_order_id){
		$db = JFactory::getDBO();
		$query = "SELECT order_status
				FROM  #__virtuemart_orders
				WHERE virtuemart_order_id = ".$virtuemart_order_id." ";
		$db->setQuery($query);
		$retorno = $db->loadObjectList();
		return $retorno[0]->order_status;
	}

	static function getProductItem($orderitems, $virtuemart_order_item_id='') {
		foreach ($orderitems as $product) {
			if ($product->virtuemart_order_item_id == $virtuemart_order_item_id) {
				return $product;
			}
		}
		return '';
	}

	/*
	static function getTrackPreview($tracking_code, $virtuemart_shipmentmethod_id){
		vmJsApi::js ('facebox');
		vmJsApi::css ('facebox');
		$document = &JFactory::getDocument();
		$document->addScriptDeclaration ("
		//<![CDATA[
			jQuery(document).ready(function($) {		
				$('a.url_track').click( function(event){			
					event.preventDefault();
		      		$.facebox({
		        		iframe: $(this).attr('href'),
		        		rev: 'iframe|600|800'
		      		}); 
		      		return false;
		    	});
			});
		//]]>
		");
		$tracking_preview_url = JURI::root () . 'plugins'.DS.'system'.DS.'vmtrackingshipment'.DS.'backend'.DS.'url.php?tracking_code='.$tracking_code.'&shipment_id='.$virtuemart_shipmentmethod_id;

		$tracking_result = VMTrackerHelper::getData($order->order_number, $order->virtuemart_order_id);
	}
	*/
}

?>