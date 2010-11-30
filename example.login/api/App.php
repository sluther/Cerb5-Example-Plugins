<?php



class ExLogin extends Extension_LoginAuthenticator {
	
	public function renderLoginForm() {
		// you probably want to include from a smarty template, but you don't have to..
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->display('devblocks:example.login::index.tpl');
		
	}
	
	public function authenticate() {
		// your custom authentication logic would go here
		return 1;
		
	}
	
	
}