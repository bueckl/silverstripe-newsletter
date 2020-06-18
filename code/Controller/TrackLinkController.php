<?php
/**
 * @package  newsletter
 */

/**
 * {@link Newsletter} objects have their links rewritten to use tracking hashs
 * so when a user receives an email links will be in the form newsletterlinks/track/8sad8903458sa
 * which is a link to this controller.
 *
 * This controller then determines the correct location for that hashcode and redirects
 * the user to the webpage
 */
namespace Newsletter\Controller;

use Newsletter\Model\NewsletterTrackedLink;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\Cookie;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;

class TrackLinkController extends ContentController {

	function init() {
		parent::init();

		if($params = $this->getURLParams()) {
			if(isset($params['Hash']) && ($hash = Convert::raw2sql($params['Hash']))) {

				$link = DataObject::get_one(NewsletterTrackedLink::class, "\"Hash\" = '$hash'");

				if($link) {
					// check for them visiting this link before
					if(!Cookie::get('ss-newsletter-link-'.$hash)) {
						$link->Visits++;
						$link->write();

						Cookie::set('ss-newsletter-link-'. $hash, true);
					}

					return $this->redirect($link->Original, 301);
				}
			}
		}

		return $this->httpError(404);
	}
}
