<?php
/**
 * @package silverstripe-newsletter-pagesource
 */

set_include_path(
	BASE_PATH . '/newsletter-pagesource/thirdparty' . PATH_SEPARATOR . get_include_path()
);

Object::add_extension('Newsletter', 'NewsletterPageSourceExtension');
Object::add_extension('NewsletterEmail', 'NewsletterEmailPageSourceExtension');
