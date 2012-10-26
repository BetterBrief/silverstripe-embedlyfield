<?php

/**
 * EmbedlyField
 * Retrieves the embed code from a URL using the Embed.ly API
 * @author Will Morgan <will.morgan@betterbrief.co.uk>
 * @author Dan Hensby <daniel.hensby@betterbrief.co.uk>
 * @copyright 2012 Better Brief; Will Morgan; Daniel Hensby
 * @link http://betterbrief.co.uk/
 */

class EmbedlyField extends FormField {

	public static
		$embed_max_width = 500,	// Ideal width of embeds; px; required
		$api_key;				// API KEY; optional; set in _config.php

	protected
		$sourceField,	// Local ref to FormField
		$urlField,		// Local ref to FormField
		$urlName,		// Name of source field
		$sourceName,	// Name of code field
		$embedWidth,	// Embed width
		$thumbnailField,// Thumbnail field to save in to
		$thumbnailValue;// Thumbnail image value

	/**
	 * __construct
	 * @param DataObject	$model			The model to retrieve from
	 * @param string		$urlName		Name of the source URL field
	 * @param string		$sourceName		Name of the HTML source field
	 * @param string		$title			Title for field (not explicitly used)
	 * @param array			$options		An associative array of options
	 * @param Form			$form			Form object
	 */
	function __construct($model, $urlName, $sourceName, $title = null, array $options = array(), $form = null) {
		$this->urlName = $urlName;
		$this->sourceName = $sourceName;
		$name = $urlName;
		if(!$title) {
			$title = $urlName;
		}
		$this->urlField = new TextField($name.'[_URL]', 'Source URL');
		$this->urlField->setValue($model->$urlName);

		$this->sourceField = new TextareaField($name.'[_Source]', 'Embed Code');
		$this->sourceField->setValue($model->$sourceName);
		$this->sourceField->setDisabled(true);
		$this->children = new FieldSet($this->urlField, $this->sourceField);

		foreach($options as $option => $value) {
			$value = ucfirst($value);
			if(method_exists($this, 'set' . $option)) {
				$this->{'set' . $option}($value);
			}
		}

		return parent::__construct($name, $title, null, $form);
	}

	/**
	 * retrieveEmbed
	 * @param string $url The source URL
	 * @throws SS_HTTPResponse_Exception If there is an error with the API call
	 * @return string The response HTML source for the embed
	 */
	protected function retrieveEmbed($url) {

		$settings = array(
			'url' => $url,
			'maxwidth' => $this->getEmbedWidth(),
		);

		// API key is optional - only required if you expect > 10k requests
		if(!empty(self::$api_key)) {
			$settings['key'] = self::$api_key;
		}

		// Make the API request
		$rest = new RestfulService('http://api.embed.ly/1/oembed');
		$rest->setQueryString($settings);
		$responseJSON = $rest->request()->getBody();

		// Decode the (probably) JSON response, handle basic error conditions:
		$response = Convert::json2array($responseJSON);

		if(empty($response['html'])) {
			throw new SS_HTTPResponse_Exception('Failed to retrieve embed code - invalid JSON? Response: ' . var_export($responseJSON, 1), 500);
		}

		if($this->getThumbnailField()) {
			$this->thumbnailValue = $response['thumbnail_url'];
		}

		return $response['html'];
	}

	function setEmbedWidth($width) {
		$this->embedWidth = (int) $width;
	}

	function getEmbedWidth() {
		return $this->embedWidth ? $this->embedWidth : self::$embed_max_width;
	}

	function setThumbnailField($field) {
		$this->thumbnailField = $field;
	}

	function getThumbnailField() {
		return $this->thumbnailField;
	}

	/**
	 * saveInto
	 * Sets the corresponding fields on the $record to their values
	 */
	function saveInto(DataObjectInterface $record) {

		$submittedURL = $this->urlField->dataValue();
		$record->setCastedField($this->urlName, $submittedURL);

		try {
			$html = $this->retrieveEmbed($submittedURL);
			$this->setValue(array('_URL' => $submittedURL, '_Source' => $html));
			$record->setCastedField($this->sourceName, $html);
			if($this->getThumbnailField()) {
				$record->setCastedField($this->getThumbnailField(), $this->thumbnailValue);
			}
		}
		catch(SS_HTTPResponse_Exception $e) {
			// If there was an error retrieving embed, update the source...
			if($e->getCode() == 500) {
				$record->setCastedField($this->sourceName, '<!-- Error retrieving embed -->');
			}
			else {
				// If unexpected, then throw it up.
				throw $e;
			}
		}
	}

	function setValue($value) {
		if(is_array($value)) {
			$this->urlField->setValue($value['_URL']);
			if(isset($value['_Source'])) {
				$this->sourceField->setValue($value['_Source']);
			}
		}
		else {
			$this->urlField->setValue($value);
		}
	}

	function setForm($form) {
		parent::setForm($form);
		$this->sourceField->setForm($form);
		$this->urlField->setForm($form);
	}

	function Field() {
		$content = '';
		foreach($this->children as $field) {
			$content .= $field->FieldHolder();
		}
		return $content;
	}

	function validate(Validator $validator) {
		$urlValue = $this->urlField->dataValue();
		if(!empty($urlValue)) {
			$parsed = parse_url($urlValue);
			if($parsed === false || empty($parsed['host'])) {
				$validator->validationError($this->name, 'Please enter a valid URL.', 'validation');
				return false;
			}
		}
		return true;
	}

}
