<?php

namespace PR\DHL\REST_API\Drivers;

use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use PR\DHL\REST_API\Drivers\JSON_API_Driver;
use PR\DHL\REST_API\Request;
use PR\DHL\REST_API\Response;

/**
 * A REST API driver decorator that automatically encodes/decodes JSON in POST requests/responses respectively.
 *
 * This is a REST API driver DECORATOR class, which means that it is not a standalone driver but instead decorates
 * another driver. It does so to add JSON encoding and parsing functionality to that "inner" driver.
 *
 * It ensures that the necessary headers are sent to the remote resource that indicate that the content body is a
 * JSON string and it also ensures that incoming responses are correctly parsed as JSON strings if the remote
 * resource indicates that it is such.
 *
 * For more information on REST API drivers, refer to the documentation for the {@link API_Driver_Interface}.
 *
 * @since [*next-version*]
 *
 * @see API_Driver_Interface
 */
class FORM_JSON_API_Driver extends JSON_API_Driver {

	/**
	 * The Form URL Encoded HTTP content type string.
	 *
	 * @since [*next-version*]
	 */
	const FORM_CONTENT_TYPE = 'application/x-www-form-urlencoded';

	/**
	 * Encodes the request body into a JSON string and ensures the request headers are correctly set.
	 *
	 * @since [*next-version*]
	 *
	 * @param Request $request The request to encode.
	 *
	 * @return Request The encoded request.
	 */
	protected function encode_request( Request $request ) {
		// Add the header that tells the remote that we accept JSON responses
		if (empty($request->headers[ parent::H_ACCEPT ])) {
			$request->headers[ parent::H_ACCEPT ] = parent::JSON_CONTENT_TYPE;
		}

		// For POST requests, encode the body and set the content type and length
		if ( $request->type === Request::TYPE_POST ) {

			if( empty( $request->headers[ parent::H_CONTENT_TYPE ] ) ){

				$request->headers[ parent::H_CONTENT_TYPE ] = static::FORM_CONTENT_TYPE;

			}
			
		}

		return $request;
	}

}
