<?php


namespace Dkvhin\Flysystem\OneDrive\Service;

use GuzzleHttp\Psr7\Utils;


/**
 * Custom implement Psr7 Stream to fix AssemblyStream
 * Google require min chunk size: 262144 but assembly stream may have smaller chunk size
 * Eg NextCloud chunk size: 8192 and you will got following error
 * Invalid request. The number of bytes uploaded is required to be equal or greater than 262144, except for the final request (it's recommended to be the exact multiple of 262144). The received request contained 8192 bytes, which does not meet this requirement.
 * Class Stream
 * @package Dkvhin\Flysystem\OneDrive\Service
 */
class StreamWrapper
{
	public static function wrap($stream,$options=[]){
		return Utils::streamFor($stream,$options);
	}
}
