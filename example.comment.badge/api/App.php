<?php
if (class_exists('Extension_CommentBadge')):
class ExCommentBadge extends Extension_CommentBadge {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render(Model_Comment $comment) {
		if(null != ($email_address = $comment->getAddress())) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('example_comment_email', $email_address->email);
			$tpl->display('devblocks:example.comment.badge::comment_badge.tpl');
		}
	}
};
endif;