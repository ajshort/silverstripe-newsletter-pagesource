<?php
/**
 * @package silverstripe-newsletter-pagesource
 */
class NewsletterEmailPageSourceExtension extends Extension {

	public function updateNewsletterEmail(NewsletterEmail $email) {
		$newsletter = $email->Newsletter();

		if ($newsletter->ContentSource != 'page' || !$newsletter->SourcePageID) {
			return;
		}

		$page     = $newsletter->SourcePage();
		$response = Director::test($page->Link());
		$content  = HTTP::absoluteURLs($response->getBody());

		$email->setBody(DBField::create('HTMLText', $content));
	}

}
