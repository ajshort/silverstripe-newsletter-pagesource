<?php
/**
 * Allows a page to be selected to provide the newsletter content.
 *
 * @package silverstripe-newsletter-pagesource
 */
class NewsletterPageSourceExtension extends DataObjectDecorator {

	public function extraStatics() {
		return array(
			'db'      => array('ContentSource' => 'Enum("content, page", "content")'),
			'has_one' => array('SourcePage' => 'SiteTree')
		);
	}

	public function updateCMSFields(FieldSet $fields) {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-livequery/jquery.livequery.js');
		Requirements::javascript('newsletter-pagesource/javascript/NewsletterAdmin.js');

		$source = new OptionsetField('ContentSource', 'Content Source', array(
			'content' => 'A content block',
			'page'    => 'A page in the site tree'
		));
		$fields->insertBefore($source, 'Content');

		$page = new TreeDropdownField('SourcePageID', '', 'SiteTree');
		$fields->insertBefore($page, 'Content');

		$fields->dataFieldByName('Content')->setTitle('');
	}

}
