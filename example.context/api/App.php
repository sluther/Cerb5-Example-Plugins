<?php>
class Context_Example extends Extension_DevblocksContext {
    function __construct($manifest) {
        parent::__construct($manifest);
    }

    function getPermalink($context_id) {
    	$url_writer = DevblocksPlatform::getUrlService();
    	return $url_writer->write('c=example&tab=example', true);
    }
    
	function getContext($address, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Email:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Address::ID);
		
		// Polymorph
		if(is_numeric($address)) {
			$address = DAO_Address::get($address);
		} elseif($address instanceof Model_Address) {
			// It's what we want already.
		} elseif(is_string($address)) {
			$address = DAO_Address::getByEmail($address);
		} else {
			$address = null;
		}
			
		// Token labels
		$token_labels = array(
			'address' => $prefix.$translate->_('common.email'),
			'first_name' => $prefix.$translate->_('address.first_name'),
			'last_name' => $prefix.$translate->_('address.last_name'),
			'num_spam' => $prefix.$translate->_('address.num_spam'),
			'num_nonspam' => $prefix.$translate->_('address.num_nonspam'),
			'is_registered' => $prefix.$translate->_('address.is_registered'),
			'is_banned' => $prefix.$translate->_('address.is_banned'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Address token values
		if(null != $address) {
			$token_values['id'] = $address->id;
			if(!empty($address->email))
				$token_values['address'] = $address->email;
			if(!empty($address->first_name))
				$token_values['first_name'] = $address->first_name;
			if(!empty($address->last_name))
				$token_values['last_name'] = $address->last_name;
			$token_values['num_spam'] = $address->num_spam;
			$token_values['num_nonspam'] = $address->num_nonspam;
			$token_values['is_registered'] = $address->is_registered;
			$token_values['is_banned'] = $address->is_banned;
			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Address::ID, $address->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $address)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $address)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}
		
		// Email Org
		$org_id = (null != $address && !empty($address->contact_org_id)) ? $address->contact_org_id : null;
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ORG, $org_id, $merge_token_labels, $merge_token_values, null, true);

		CerberusContexts::merge(
			'org_',
			'',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);		
		
		return true;		
	}

	function getChooserView() {
		// View
		$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = 'View_Address';
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Organizations';
		
		$view->view_columns = array(
			SearchFields_Address::FIRST_NAME,
			SearchFields_Address::LAST_NAME,
			SearchFields_Address::ORG_NAME,
		);
		
		$view->paramsDefault = array(
			SearchFields_Address::IS_BANNED => new DevblocksSearchCriteria(SearchFields_Address::IS_BANNED,'=',0),
		);
		$view->paramsHidden = array(
			SearchFields_Address::ID,
			SearchFields_Address::CONTACT_ORG_ID,
		);
		$view->addParams($view->paramsDefault, true);
		
		$view->renderSortBy = SearchFields_Address::EMAIL;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;		
	}
	
	function getView($context, $context_id, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = 'View_Address';
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'E-mail Addresses';
		
		$params = array(
			new DevblocksSearchCriteria(SearchFields_Address::CONTEXT_LINK,'=',$context),
			new DevblocksSearchCriteria(SearchFields_Address::CONTEXT_LINK_ID,'=',$context_id),
		);
		
		if(isset($options['filter_open']))
			true; // Do nothing
		
		$view->addParams($params, true);
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}