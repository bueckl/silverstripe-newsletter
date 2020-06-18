<?php

/**
 * Page type for creating a page that contains a form that visitors can
 * use to unsubscribe from all mailing lists.
 *
 * @package newsletter
 */
namespace Newsletter\Pagetype;

use Page;
use PageController;

class UnsubscriptionPage extends Page {

	private static $defaults = array(
		'Content' => 'Enter your email address and we will send you an email with an unsubscribe link'
	);
}

class UnsubscriptionPageController extends PageController {

	function Form() {
		return $this->renderWith('UnsubscribeRequestForm');
	}
}