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
		$owner,			// Owning dataobject
		$urlName,		// Name of source field
		$sourceName;	// Name of code field

	/**
	 * __construct
	 * @param string		$urlName		Name of the source URL field
	 * @param string		$sourceName		Name of the HTML source field
	 * @param DataObject	$owner			DataObject with which to link
	 * @param string		$title			Title for field (not explicitly used)
	 * @param Form			$form			Form object
	 */
	function __construct($urlName, $sourceName, DataObject $owner, $title = null, $form = null) {
		$this->urlName = $urlName;
		$this->sourceName = $sourceName;
		$name = 'Composite_' . $urlName;
		$this->urlField = new TextField($name.'[_URL]', 'Source URL', $owner->$urlName, $form);
		$this->sourceField = new TextareaField($name.'[_Source]', 'Embed Code', null, null, $owner->$sourceName, $form);
		$this->sourceField->setDisabled(true);
		$this->owner = $owner;
		$this->children = new FieldSet(array($this->urlField, $this->sourceField));
		return parent::__construct($name, $title, null, $form);
	}

	/**
	 * retrieve_embed
	 * @param string $url The source URL
	 * @throws SS_HTTPResponse_Exception If there is an error with the API call
	 * @return string The response HTML source for the embed
	 */
	protected static function retrieve_embed($url) {

		$settings = array(
			'url' => $url,
			'maxwidth' => self::$embed_max_width
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

		return $response['html'];
	}

	/**
	 * saveInto
	 * Sets the corresponding fields on the $record to their values
	 */
	function saveInto(DataObjectInterface $record) {

		$submittedURL = $this->urlField->dataValue();
		$record->setCastedField($this->urlName, $submittedURL);

		try {
			$html = self::retrieve_embed($submittedURL);
			$record->setCastedField($this->sourceName, $html);
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
			$this->sourceField->setValue($value['_Source']);
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