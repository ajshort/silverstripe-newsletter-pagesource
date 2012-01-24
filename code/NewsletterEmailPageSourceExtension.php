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

		$page		= $newsletter->SourcePage();
		$response	= Director::test($page->RelativeLink());
		$body		= $this->emogrify($response->getBody());
		$body		= str_replace('xmlns="http://www.w3.org/1999/xhtml"', '', HTTP::absoluteURLs($body));
		$re			= '/\.src\s*=' . str_replace('/', '\/', Director::absoluteBaseURL()).'/';
		$body		= preg_replace($re, '.src =', $body);
		
		// undo the fudging that happens to keywords
		$body = preg_replace('/"[^"]*%7B%24(\w+)%7D/', '"{\$$1}', $body);

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

		// order here is seemingly important; 'tidy' seems to strip stuff important for detecting encoding??
		$encoding	= mb_detect_encoding($content);
		$content	= $this->tidy($content, $encoding);
		$content	= mb_convert_encoding($content, 'HTML-ENTITIES', $encoding);

		$emog = new Emogrifier($content);
		$css = array();

		if (!$encoding) {
			$encoding = 'UTF-8';
		}

		$document = new DOMDocument();
		$document->encoding = $encoding;
		$document->strictErrorChecking = false;
		
		// some versions of tidy don't remove duplicate attrs
		libxml_use_internal_errors(true);
		$document->loadHTML($content);
		$document->normalizeDocument();

		$xpath = new DOMXPath($document);

		foreach ($xpath->query("//link[@rel='stylesheet']") as $link) {
			$media = $link->getAttribute('media');
			$file = $this->findCSSFile($link->getAttribute('href'));
			if (file_exists($file)) {
				$contents = trim(file_get_contents($file));
				if ($contents && (!$media || in_array($media, array('all', 'screen')))) {
					$css[] = $contents;
				}
			}
		}

		foreach ($xpath->query('//style') as $style) {
			$type = $style->getAttribute('type');
			$content = trim($style->textContent);

			if ($content && (!$type || $type == 'text/css')) {
				$css[] = $content;
			}
		}

		$emog->setCSS(implode("\n", $css));
		$content	= $emog->emogrify();
		// clean up crap from emogrify
		$content	= str_replace('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">', '', $content);
		return $content;
	}
	
	/**
	 * Try and find the css file for a given href
	 *
	 * @param type $href 
	 */
	private function findCSSFile($href) {
		if (strpos($href, '//') !== false) {
			$href = str_replace(Director::absoluteBaseURL(), '', $href);
		}
		if (strpos($href, '?')) {
			$href = substr($href, 0, strpos($href, '?'));
		}
		
		return Director::baseFolder() . '/' . $href;
	}

	/**
	 * Cleans and returns XHTML which is needed for use in DOMDocument
	 *
	 * @param type $content
	 * @param type $encoding
	 * @return string
	 */
	protected function tidy($content, $encoding = 'UTF-8') {
		// Try to use the extension first
		if (extension_loaded('tidy')) {
			$tidy = tidy_parse_string($content, array(
				'clean' => true,
				'output-xhtml' => true,
				'show-body-only' => false,
				'wrap' => 0,
				'input-encoding' => $encoding,
				'output-encoding' => $encoding,
				'doctype'		=> 'omit',
				'anchor-as-name'	=> false,
			));

			$tidy->cleanRepair();
			return $this->rewriteShortcodes('' . $tidy);
		}

		// No PHP extension available, attempt to use CLI tidy.
		$retval = null;
		$output = null;
		@exec('tidy --version', $output, $retval);
		if ($retval === 0) {
			$tidy = '';
			$input = escapeshellarg($content);
			$encoding = str_replace('-', '', $encoding);
			$encoding = escapeshellarg($encoding);
			// Doesn't work on Windows, sorry, stick to the extension.
			$tidy = @`echo $input | tidy -q --show-body-only no --tidy-mark no --doctype omit --input-encoding $encoding --output-encoding $encoding --wrap 0 --anchor-as-name no --clean yes --output-xhtml yes`;
			return $this->rewriteShortcodes($tidy);
		}

		// Fall back to default
		$doc = new SS_HTMLValue($content);
		return $doc->getContent();
	}

	protected function rewriteShortcodes($string) {
		return preg_replace('/(\[[^]]*?)(%20)([^]]*?\])/m', '$1 $3', $string);
	}
}