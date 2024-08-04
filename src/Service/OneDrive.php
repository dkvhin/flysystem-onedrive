<?php


namespace Dkvhin\Flysystem\OneDrive\Service;

use Exception;
use Generator;
use ArrayObject;
use GuzzleHttp\Client;
use Microsoft\Graph\Graph;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;
use GuzzleHttp\Psr7\MultipartStream;
use Microsoft\Graph\Model\DriveItem;
use Microsoft\Graph\Http\GraphRequest;
use Microsoft\Graph\Http\GraphResponse;
use Microsoft\Graph\Model\UploadSession;
use Dkvhin\Flysystem\OneDrive\Support\Path;
use Microsoft\Graph\Exception\GraphException;
use Dkvhin\Flysystem\OneDrive\Support\StorageAttributes;
use Dkvhin\Flysystem\OneDrive\Exception\StorageException;
use Dkvhin\Flysystem\OneDrive\Contracts\Storage\StorageContract;

class OneDrive
{
	protected $graph;
	const ROOT = '/me/drive/root';
	protected $publishPermission = [
		'role' => 'read',
		'scope' => 'anonymous',
		'withLink' => true
	];
	protected $options;
	//Add prefix / when original root has /
	protected $rootPrefix = '';
	use HasLogger;
	public function __construct(Graph $graph, $options = [])
	{
		$default_options = [
			'request_timeout' => 90,
			'chunk_size' => 320 * 1024,
		];

		$this->options = array_merge($default_options, $options);
		$root = $options['root'] ?? '';

		if ($root) {
			$firstChar = substr($root, 0, 1);
			if ($firstChar === '/' || $firstChar === '\\') {
				$this->rootPrefix = $firstChar;
			}
		}
		$this->setupLogger($options);
		$this->graph = $graph;
	}

	function normalizeMetadata(array $response, string $path): array
	{
		$permissions = $response['permissions'] ?? [];
		$visibility = StorageContract::VISIBILITY_PRIVATE;
		$shareLink = null;
		foreach ($permissions as $permission) {
			if (!isset($permission['link']['scope']) || !isset($permission['roles'])) {
				continue;
			}
			if (
				in_array($this->publishPermission['role'], $permission['roles'])
				&& $permission['link']['scope'] == $this->publishPermission['scope']
			) {
				$visibility = StorageContract::VISIBILITY_PUBLIC;
				$shareLink = $permission['link']['webUrl'] ?? null;
				break;
			}
		}

		$meta = [
			StorageAttributes::ATTRIBUTE_PATH => $this->rootPrefix . ltrim($path, '\/'),
			StorageAttributes::ATTRIBUTE_LAST_MODIFIED => strtotime($response['lastModifiedDateTime']),
			StorageAttributes::ATTRIBUTE_FILE_SIZE => $response['size'],
			StorageAttributes::ATTRIBUTE_TYPE => isset($response['file']) ? 'file' : 'dir',
			StorageAttributes::ATTRIBUTE_MIME_TYPE => $response['file']['mimeType'] ?? null,
			StorageAttributes::ATTRIBUTE_VISIBILITY => $visibility,
			'@id' => $response['id'] ?? null,
			'@link' => $response['webUrl'] ?? null,
			'@shareLink' => $shareLink,
			'@downloadUrl' => $response['@microsoft.graph.downloadUrl'] ?? null,
		];

		return $meta;
	}
	function getEndpoint($path = '', $action = '', $params = [])
	{
		$this->validatePath($path);
		$path = Path::clean($path);
		$path = trim($path, '\\/');
		$path = static::ROOT . ':/' . $path;
		/**
		 * Path should not end with /
		 * /me/drive/root:/path/to/file
		 * /me/drive/root
		 */
		$path = rtrim($path, ':/');
		if ($action === true) { //path reference
			if (strpos($path, ':') === false) {
				$path .= ':'; //root path should end with :
			}
		}
		if ($action && is_string($action)) {
			/**
			 * Append action to path
			 * /me/drive/root:/path:/action
			 * trim : for root
			 * /me/drive/root/action
			 */
			$path = rtrim($path, ':');
			if (strpos($path, ':') !== false) {
				$path .= ':/' . $action; //root:/path:/action
			} else {
				$path .= '/' . $action; //root/action
			}
		}
		if ($params) {
			$path .= '?' . http_build_query($params);
		}
		return $path;
	}

	/**
	 * @param $path
	 * @param $newPath
	 * @return array|null
	 * @throws GraphException
	 */
	public function copy($path, $newPath)
	{
		$endpoint = $this->getEndpoint($path, 'copy');
		$name = basename($newPath);
		$this->createDirectory(dirname($newPath));
		$newPathParent = $this->getEndpoint(dirname($newPath), true);
		$body = [
			'name' => $name,
			'parentReference' => [
				'path' => $newPathParent,
			],
		];
		return $this->createRequest('POST', $endpoint)
			->attachBody($body)
			->execute()->getBody();
	}

	/**
	 * @param $path
	 * @return array|null
	 * @throws GraphException
	 */
	public function createDirectory($path)
	{
		$path = Path::clean($path);
		if ($path === '/') {
			return $this->getItem('/');
		}
		$endpoint = $this->getEndpoint($path);
		return $this->createRequest('PATCH', $endpoint)
			->attachBody([
				'folder' => new ArrayObject(),
			])->execute()->getBody();
	}

	/**
	 * @param $path
	 * @return array|null
	 * @throws GraphException
	 */
	public function delete($path)
	{
		$endpoint = $this->getEndpoint($path);
		return $this->createRequest('DELETE', $endpoint)->execute()->getBody();
	}

	/**
	 * @param $path
	 * @param null $format
	 * @return resource|null
	 * @throws GraphException
	 */
	public function download($path, $format = null)
	{
		$args = [];
		if ($format) {
			if (is_string($format)) {
				$args = ['format' => $format];
			} elseif (is_array($format)) {
				$args = $format;
			}
		}
		$endpoint = $this->getEndpoint($path, 'content', $args);
		$response = $this->createRequest('GET', $endpoint)->setReturnType('GuzzleHttp\Psr7\Stream')->execute();
		/**
		 * @var StreamWrapper $response
		 */
		return $response->detach();
	}

	/**
	 * @param $path
	 * @param array $args
	 * @return array|null
	 * @throws GraphException
	 */
	public function getItem($path, $args = [])
	{
		$endpoint = $this->getEndpoint($path, '', $args);
		$response = $this->createRequest('GET', $endpoint)->execute();
		return $response->getBody();
	}

	/**
	 * @param $path
	 * @return Generator
	 * @throws GraphException
	 */
	public function listChildren($path)
	{
		$endpoint = $this->getEndpoint($path, 'children');
		$nextPage = null;

		do {
			if ($nextPage) {
				$endpoint = $nextPage;
			}
			$response = $this->createRequest('GET', $endpoint)
				->execute();
			$nextPage = $response->getNextLink();
			$items = $response->getBody()['value'] ?? [];
			if (!is_array($items)) {
				$items = [];
			}
			yield from $items;
		} while ($nextPage);
	}

	/**
	 * @param $path
	 * @param $newPath
	 * @return array|null
	 * @throws GraphException
	 */
	public function move($path, $newPath)
	{
		$endpoint = $this->getEndpoint($path);
		$name = basename($newPath);
		$this->createDirectory(dirname($newPath));
		$newPathParent = $this->getEndpoint(dirname($newPath), true);
		$body = [
			'name' => $name,
			'parentReference' => [
				'path' => $newPathParent,
			],
		];
		return $this->createRequest('PATCH', $endpoint)
			->attachBody($body)
			->execute()->getBody();
	}

	/**
	 * @param $path
	 * @param $contents
	 * @return array|null
	 * @throws GraphException
	 */
	public function upload($path, $contents)
	{
		$endpoint = $this->getEndpoint($path, 'createUploadSession');
		try {
			$stream = StreamWrapper::wrap($contents);
		} catch (InvalidArgumentException $e) {
			throw new StorageException("Invalid contents. " . $e->getMessage());
		}

		$this->createDirectory(dirname($path));

		$uploadSession =  $this->createRequest('POST', $endpoint)
			->setReturnType(UploadSession::class)
			->execute();

		$upload_url = $uploadSession->getUploadUrl();
		$meta = fstat($contents);
		$fragSize = $this->options['chunk_size'];
		$offset = 0;

		$guzzle = new Client();
		while ($chunk = fread($contents, $fragSize)) {
			$this->writeChunk($guzzle, $upload_url, $meta['size'], $chunk, $offset);
			$offset += $fragSize;
		}
	}

	private function writeChunk(Client $http, string $upload_url, int $file_size, string $chunk, int $first_byte, int $retries = 0): void
	{
		$last_byte_pos = $first_byte + strlen($chunk) - 1;
		$headers = [
			'Content-Range' => "bytes $first_byte-$last_byte_pos/$file_size",
			'Content-Length' => strlen($chunk),
		];

		$request = new Request(
			'PUT',
			$upload_url,
			$headers,
			$chunk
		);

		$response = $http->send($request);

		// $response = $http->put($upload_url, $headers)
		// 	->withBody($chunk, 'application/octet-stream')
		// 	->timeout($this->options['request_timeout'])
		// 	->put($upload_url);

		if ($response->getStatusCode() === 404) {
			throw new Exception('Upload URL has expired, please create new upload session');
		}

		if ($response->getStatusCode() === 429) {
			sleep($response->header('Retry-After')[0] ?? 1);
			$this->writeChunk($http, $upload_url, $file_size, $chunk, $first_byte, $retries + 1);
		}

		if ($response->getStatusCode() >= 500) {
			if ($retries > 9) {
				throw new Exception('Upload failed after 10 attempts.');
			}
			sleep(pow(2, $retries));
			$this->writeChunk($http, $upload_url, $file_size, $chunk, $first_byte, $retries + 1);
		}

		if (($file_size - 1) == $last_byte_pos) {
			if ($response->getStatusCode() === 409) {
				throw new Exception('File name conflict. A file with the same name already exists at target destination.');
			}

			if (in_array($response->getStatusCode(), [200, 201])) {
				$response = new GraphResponse(
					$this->graph->createRequest('', ''),
					$response->getBody(),
					$response->getStatusCode(),
					$response->getHeaders()
				);

				$response->getResponseAsObject(DriveItem::class);

				return;
			}

			throw new Exception(
				'Unknown error occurred while uploading last part of file. HTTP response code is ' . $response->status()
			);
		}

		if ($response->status() !== 202) {
			throw new Exception('Unknown error occurred while trying to upload file chunk. HTTP status code is ' . $response->status());
		}
	}

	/**
	 * @param $path
	 * @return array|mixed
	 * @throws GraphException
	 */
	public function getPermissions($path)
	{
		$endpoint = $this->getEndpoint($path, 'permissions');
		$response = $this->createRequest('GET', $endpoint)->execute();
		return $response->getBody()['value'] ?? [];
	}

	/**
	 * @param $path
	 * @return array
	 * @throws GraphException
	 */
	function publish($path)
	{
		$endpoint = $this->getEndpoint($path, 'createLink');
		$body = ['type' => 'view', 'scope' => 'anonymous'];
		$response = $this->createRequest('POST', $endpoint)
			->attachBody($body)->execute();
		return $response->getBody();
	}

	/**
	 * @param $path
	 * @throws GraphException
	 */
	function unPublish($path)
	{
		$permissions = $this->getPermissions($path);
		$idToRemove = '';
		foreach ($permissions as $permission) {
			if (
				in_array($this->publishPermission['role'], $permission['roles'])
				&& $permission['link']['scope'] == $this->publishPermission['scope']
			) {
				$idToRemove = $permission['id'];
				break;
			}
		}
		if (!$idToRemove) {
			return;
		}
		$endpoint = $this->getEndpoint($path, 'permissions/' . $idToRemove);
		$this->createRequest('DELETE', $endpoint)->execute();
	}

	/**
	 * @param $requestType
	 * @param $endpoint
	 * @return GraphRequest
	 * @throws GraphException
	 */
	protected function createRequest($requestType, $endpoint)
	{
		$this->logger->request($requestType, $endpoint);
		return $this->graph->createRequest($requestType, $endpoint);
	}
	protected function validatePath($path)
	{
		$invalidChars = ['"', '*', ':', '<', '>', '?', '|'];
		foreach ($invalidChars as $char) {
			if (strpos($path, $char) !== false) {
				throw new StorageException("Invalid character $char in path $path");
			}
		}
	}
}
