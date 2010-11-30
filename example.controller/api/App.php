<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

class ExPageController extends DevblocksControllerExtension {
    const ID = 'cerberusweb.controller.example';
	private $_TPL_PATH = '';
	
	public function __construct($manifest) {
		parent::__construct($manifest);
		$this->_TPL_PATH = dirname(dirname(__FILE__)) . '/templates';
	}
    
	/**
	 * Enter description here...
	 *
	 * @param string $uri
	 * @return string $id
	 */
	public function _getPageIdByUri($uri) {
        $pages = DevblocksPlatform::getExtensions('example.controller.page', false);
        foreach($pages as $manifest) { /* @var $manifest DevblocksExtensionManifest */
            if(0 == strcasecmp($uri,$manifest->params['uri'])) {
                return $manifest->id;
            }
        }
        return NULL;
	}    
    
	public function handleRequest(DevblocksHttpRequest $request) { /* @var $request DevblocksHttpRequest */
		
		$path = $request->path;
		$prefixUri = array_shift($path);		// $uri should be "examplecontroller"
		$page = array_shift($path);	// sub controller to take

        $page_id = $this->_getPageIdByUri($page);
//		var_dump($page_id);		
        $pages = DevblocksPlatform::getExtensions('example.controller.page', true);
        @$page = $pages[$page_id]; /* @var $page CerberusPageExtension */
//		var_dump($pages);
		if(empty($page)) {
			switch($controller) {
				
//				case "portal":
//					die(); // 404
//					break;
				default:
					$action = DevblocksPlatform::importGPC($_REQUEST['a']);
					$action = $action . 'Action';
					
					if(method_exists($this,$action)) {
						call_user_func(array(&$this, $action));
					}
					break;
			}
		}
//		} else {
////			$action = "render";
//			switch($action) {
//		        default:
//				    // Default action, call arg as a method suffixed with Action
//				    if($page->isVisible()) {
//						if(method_exists($page,$action)) {
//							call_user_func(array(&$page, $action)); // [TODO] Pass HttpRequest as arg?
//						}
//					} else {
//						// if Ajax [TODO] percolate isAjax from platform to handleRequest
//						// die("Access denied.  Session expired?");
//					}
//	
//		            break;
//		    }
//		}
	}
	
	public function writeResponse(DevblocksHttpResponse $response) { /* @var $response DevblocksHttpResponse */
	    $path = $response->path;
	    $uri_prefix = array_shift($path); // should be mobile
	    
		// [JAS]: Ajax? // [TODO] Explore outputting whitespace here for Safari
//	    if(empty($path))
//			return;

		$tpl = DevblocksPlatform::getTemplateService();
		$session = DevblocksPlatform::getSessionService();
		$settings = DevblocksPlatform::getPluginSettingsService();
		$translate = DevblocksPlatform::getTranslationService();
		$visit = $session->getVisit();

		$controller = array_shift($path);

		$pages = DevblocksPlatform::getExtensions('example.controller.page', true);

		// Default page [TODO] This is supposed to come from framework.config.php
//		if(empty($controller)) 
//			$controller = 'home';

	    // [JAS]: Require us to always be logged in for Cerberus pages
	    // [TODO] This should probably consult with the page itself for ::authenticated()
//		if(empty($visit))
//			$controller = 'login';
		
		$page_id = $this->_getPageIdByUri($controller);
		@$page = DevblocksPlatform::getExtension($page_id, true); /* @var $page CerberusPageExtension */
        
        if(empty($page)) {
   		    header("Status: 404");
        	return; // [TODO] 404
		}
        
	    
		// [TODO] Reimplement
		if(!empty($visit) && !is_null($visit->getWorker())) {
		    DAO_Worker::logActivity($page->getActivity());
		}
		
		// [JAS]: Listeners (Step-by-step guided tour, etc.)
	    $listenerManifests = DevblocksPlatform::getExtensions('devblocks.listener.http');
	    foreach($listenerManifests as $listenerManifest) { /* @var $listenerManifest DevblocksExtensionManifest */
	         $inst = $listenerManifest->createInstance(); /* @var $inst DevblocksHttpRequestListenerExtension */
	         $inst->run($response, $tpl);
	    }
		
        // [JAS]: Variables provided to all page templates
		$tpl->assign('settings', $settings);
		$tpl->assign('session', $_SESSION);
		$tpl->assign('translate', $translate);
		$tpl->assign('visit', $visit);
		
	    $active_worker = CerberusApplication::getActiveWorker();
	    $tpl->assign('active_worker', $active_worker);
	
	    if(!empty($active_worker)) {
	    	$active_worker_memberships = $active_worker->getMemberships();
	    	$tpl->assign('active_worker_memberships', $active_worker_memberships);
	    }
		
		$tpl->assign('pages',$pages);		
		$tpl->assign('page',$page);

		$tpl->assign('response_uri', implode('/', $response->path));
		
		$tpl->assign('core_tpl', $this->_TPL_PATH);
		
		// Timings
		$tpl->assign('render_time', (microtime(true) - DevblocksPlatform::getStartTime()));
		if(function_exists('memory_get_usage') && function_exists('memory_get_peak_usage')) {
			$tpl->assign('render_memory', memory_get_usage() - DevblocksPlatform::getStartMemory());
			$tpl->assign('render_peak_memory', memory_get_peak_usage() - DevblocksPlatform::getStartPeakMemory());
		}
		
//		var_dump($path);
		$tpl->display($this->_TPL_PATH . '/index.tpl');
	}
	
	function customResponseAction() {
		// we can do nearly anything inside of this function
		$array = array('Coder', 'QA');
		echo json_encode($array);
	}
	
	function changeRequest(DevblocksHttpRequest $request) {
		// handle request can call this function to modify the request (dynami)
		DevblocksPlatform::setHttpRequest($request);
		
	}
	function ajaxRequestAction() {
        $x = DevblocksPlatform::importGPC($_REQUEST['x']);
        $y = DevblocksPlatform::importGPC($_REQUEST['y']);
        $op = DevblocksPlatform::importGPC($_REQUEST['op']);
        $result = 0;
        switch($op) {
            case 0:
                $result = $x + $y;
                break;
            case 1:
                $result = $x - $y;
                break;
            case 2:
                $result = $x * $y;
                break;
            case 3:
                $result = $x / $y;
        }
        echo $result;
	}
};


class ExHomePage extends CerberusPageExtension {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
		
	function isVisible() {
		// check login
		$visit = CerberusApplication::getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function getActivity() {
		return new Model_Activity('activity.activity');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$response = DevblocksPlatform::getHttpResponse();

		// Remember the last tab/URL
        $visit = CerberusApplication::getVisit();
        if(null == ($selected_tab = @$response->path[1])) {
            $selected_tab = $visit->get(CerberusVisit::KEY_ACTIVITY_TAB, '');
        }
        $tpl->assign('selected_tab', $selected_tab); 
		
        $tab_manifests = DevblocksPlatform::getExtensions('example.page.tab', false);
        // var_dump($tab_manifests);
        uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
        $tpl->assign('tab_manifests', $tab_manifests);
		
		$tpl->display('devblocks:example.controller::exhomepage/index.tpl');
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_ExampleTab) {
			$inst->showTab();
		}
	}
	
};

abstract class Extension_ExampleTab extends DevblocksExtension {
	
	public function showTab() {}
}

class ExHomeTabOne extends Extension_ExampleTab {
	
	public function showTab() {
		
		// do something
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->display('devblocks:example.page::exampletabone.tpl');
	}	
}
class ExHomeTabTwo extends Extension_ExampleTab {
	
	public function showTab() {
		
		// do something
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->display('devblocks:example.page::exampletabtwo.tpl');
	}	
}

class ExTestPage extends CerberusPageExtension {
	private $_TPL_PATH = '';
	
	public function __construct($manifest) {
		parent::__construct($manifest);
		$this->_TPL_PATH = dirname(dirname(__FILE__)) . '/templates';
	}
	
	public function isVisible() { return true; }
	public function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:example.controller::testpage/index.tpl');
	}
	
	/**
	 * @return Model_Activity
	 */
	public function getActivity() {
        return new Model_Activity('activity.default');
	}
	
};