<?php
/**
 * $Id: vmtrackingshipment.php 1.0.0 2013-06-24 05:39:14 Luiz Weber $
 * @package     Joomla! 
 * @subpackage  vmtrackingshipment
 * @version     1.0.0
 * @description VM Template Override
 * @copyright     Copyright © 2013 - Weber TI All rights reserved.
 * @license       GNU General Public License v2.0
 * @author        Luiz Felipe Weber
 * @author mail virtuemartpro@gmail.com
 * @website       http://virtuemartpro.com.br
 * 
 *
 * The events triggered in Joomla!
 * -------------------------------
 * onAfterInitialise()
 * onAfterRoute()
 * onAfterDispatch()
 * onAfterRender()
 *
 */


// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

/**
 * Example System Plugin
 *
 * @package     Joomla! 
 * @subpackage  Webservice VM Teste
 * @class       plgSystemWebservicevmteste
 * @since       1.5
 */
 
class plgSystemVmtrackingshipment extends JPlugin {
    /**
     * Constructor
     *
     * For php4 compatability we must not use the __constructor as a constructor for plugins
     * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
     * This causes problems with cross-referencing necessary for the observer design pattern.
     *
     * @access        protected
     * @param   object  $subject The object to observe
     */
    function plgSystemVmtrackingshipment( &$subject, $config ) {
       parent::__construct( $subject, $config );
    }    

    function onAfterDispatch() {        
//        jimport( 'joomla.application.component.view' );

        $app    = JFactory::getApplication();
        $doc    = JFactory::getDocument();
        $option = JRequest::getVar('option');
        $view   = JRequest::getVar('view'); 
        $layout = JRequest::getVar('layout');
        $task   = JRequest::getVar('task');        

        if($app->isAdmin()) {
            
           
            if ($option == 'com_virtuemart' and $view == 'orders' and $task != 'edit') {


                $dbt    = JFactory::getDBO();
                $sql = 'SELECT template as template2 FROM #__template_styles WHERE client_id=1 and home = 1';
                $dbt->setQuery($sql);
                $templateAtual = $dbt->loadResult();
                if ($templateAtual == '') {
                    // Get the current default template
                    $query = ' SELECT template '
                            .' FROM #__templates_menu '
                            .' WHERE client_id = 1'
                            .' AND menuid = 0 ';
                    $dbt->setQuery($query);
                    $templateAtual = $dbt->loadResult();
                }

                $origem     = JPATH_ROOT . DIRECTORY_SEPARATOR. 'plugins'. DIRECTORY_SEPARATOR .'system'. DIRECTORY_SEPARATOR .'vmtrackingshipment'. DIRECTORY_SEPARATOR .'backend'. DIRECTORY_SEPARATOR .'views'. DIRECTORY_SEPARATOR;
                $destino    = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $templateAtual . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR .'com_virtuemart'. DIRECTORY_SEPARATOR .'orders'. DIRECTORY_SEPARATOR;

                if (!file_exists($destino.'orders.php')) {
                    if (!is_dir($destino)) {
                        JFolder::create($destino);
                    }
                    if (JFile::copy($origem.'orders.php', $destino.'orders.php','',true)) {      
                        JFactory::getApplication()->enqueueMessage( '[Rastreio Integrado] - Template do rastreio instalado com sucesso, atualize a página para continuar. ');
                    } else {
                        JError::raiseWarning(100, '[Rastreio Integrado] - Erro ao instalar template do rastreio.');
                    }
                }


                // recupera a configuração do vm do template
                /* $config = VmConfig::loadConfig();
                
                $doc->setBuffer('','component');

                $_class     = 'VirtueMartController'.ucfirst($view);
                $_viewPath  = JPATH_ROOT.DIRECTORY_SEPARATOR.'plugins/system/vmtrackingshipment/backend/views/';
                $controller = new $_class();

                $view = $controller->getView($view, 'html');
                $view->addTemplatePath($_viewPath);

                //get view content
                ob_start();
                $view->display();
                $content = ob_get_contents();
                ob_end_clean();

                $doc->setBuffer($content,'component');
                */
            }           

            // mostra o formulário pra salvar 
            $tracking_code_admin = JRequest::getVar('tracking_code_admin');
            $token = JSession::getFormToken();            

            if ($token == $tracking_code_admin) {

                if (!class_exists('ShopFunctions')) require(VMPATH_ADMIN . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'shopfunctions.php');
                if (!class_exists( 'VmConfig' )) require(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_virtuemart'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'config.php');            

                $doc->setBuffer('','component');
                $dontInclude = array(
                    '/components/com_virtuemart/assets/js/vmkeepalive.js',
                );

                foreach($doc->_scripts as $key => $script){
                    if(in_array(strtok($key, '?'), $dontInclude)){
                        unset($doc->_scripts[$key]);
                    }
                }

                ob_start();                
                include_once JPATH_ROOT.DIRECTORY_SEPARATOR.'plugins/system/vmtrackingshipment/plugin/views/tracker/form.php';                
                $conteudo = ob_get_contents();
                ob_end_clean();

                $doc->setBuffer($conteudo,'component');
                // JFactory::getApplication()->close();
            }

        } else {
            // exibição do plugin no frontend
            if ($option == 'com_virtuemart' and $view == 'orders' and $layout == 'details') {
                if (!class_exists('VirtueMartModelOrders'))
                    require( JPATH_VM_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'orders.php' );
                $order_number = JRequest::getVar('order_number');
                $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
                
                include_once JPATH_ROOT.DIRECTORY_SEPARATOR.'plugins/system/vmtrackingshipment/helpers/helper.php';

                $tracking_result = VMTrackerHelper::getData($order_number, $virtuemart_order_id);

                if ($tracking_result[0]->tracking_code != '') {

                    // vmJsApi::js ('facebox');
                    // vmJsApi::css ('facebox');
                    $document = &JFactory::getDocument();
                    $document->addScriptDeclaration ("
                    //<![CDATA[
                        SqueezeBox.assign(jQuery('a.url_track'), {
                            parse: 'rel'
                        });
                        /*jQuery(document).ready(function($) {        
                            $('a.url_track').click( function(event){            
                                event.preventDefault();
                                $.facebox(\"<iframe src='\"+($(this).attr('href'))+\"' width='600' height='800'></iframe>\");                                    
                                return false;
                            });
                        });
                        */
                    //]]>
                    ");
                    $url_assets_track = JURI::root().'/plugins/system/vmtrackingshipment/assets/images/';                   

                    $modelOrder = new VirtueMartModelOrders();
                    $order_data = $modelOrder->getOrder($virtuemart_order_id);                
                    $virtuemart_shipmentmethod_id = $order_data['details']['BT']->virtuemart_shipmentmethod_id;

                    $img_preview_rastreio = $url_assets_track.'rastreio_mini.png';

                    $data_postagem = vmJsApi::date($tracking_result[0]->post_date,'LC3',true);
                    $data_estimada = vmJsApi::date($tracking_result[0]->estimate_date,'LC3', true );
                    //$data_postagem = vmJsApi::date(str_replace(' 00:00:00',' 23:59:59',$tracking_result->post_date),'LC3',true);
                    //$data_estimada = vmJsApi::date(str_replace(' 00:00:00',' 23:59:59',$tracking_result->estimate_date),'LC3', true );

                    $tracking_preview_url = JURI::root () . 'plugins/system/vmtrackingshipment/backend/url.php?tracking_code='.$tracking_result[0]->tracking_code.'&shipment_id='.$virtuemart_shipmentmethod_id;
                    $tracking_modal_link = "<br /><a href=".$tracking_preview_url."  class='url_track' rel=\"{handler:'iframe',size:{x:600,y:800}}\">" . '<span title="Visualizar código de rastreio: '.$tracking_result[0]->tracking_code.'"><img src="'.$img_preview_rastreio.'" border="0"/> #'.$tracking_result[0]->tracking_code.'</span></a>';                

                    // mensagem de rastreio
                    $mensagem_rastreio = "Rastreio da Mercadoria para este pedido: <b>".$tracking_modal_link." </b><br/>Data da Postagem: <b>".$data_postagem."</b> <br/>Data Estimada da Entrega: <b>".$data_estimada."</b> <br/>";

                    JFactory::getApplication()->enqueueMessage( $mensagem_rastreio );

                }
            }
        }
    }

}

?>