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
		$body     = $this->emogrify($response->getBody());
		$body     = HTTP::absoluteURLs($body);

		$email->setBody(DBField::create('HTMLText', $body));
	}

	/**
	 * Performs processing on the email content to make CSS styles inline. This
	 * wraps the emogrified library, but extracts external an inline css
	 * defitions.
	 *
	 * @param  string $content
	 * @return string
	 */
	protected function emogrify($content) {
		require_once 'emogrifier/emogrifier.php';

		$emog = new Emogrifier($content);
		$css  = array();

		$encoding = mb_detect_encoding($content);
		$content  = mb_convert_encoding($content, 'HTML-ENTITIES', $encoding);

		$document = new DOMDocument();
		$document->encoding            = $encoding;
		$document->strictErrorChecking = false;
		$document->loadHTML($content);
		$document->normalizeDocument();

		$xpath = new DOMXPath($document);

		foreach ($xpath->query("//link[@rel='stylesheet']") as $link) {
			$media    = $link->getAttribute('media');
			$contents = trim(file_get_contents($link->getAttribute('href')));

			if ($contents && (!$media || in_array($media, array('all', 'screen')))) {
				$css[] = $contents;
			}
		}

		foreach ($xpath->query('//style') as $style) {
			$type    = $style->getAttribute('type');
			$content = trim($style->textContent);

			if ($content && (!$type || $type == 'text/css')) {
				$css[] = $content;
			}
		}

		$emog->setCSS(implode("\n", $css));
		return $emog->emogrify();
	}

}
