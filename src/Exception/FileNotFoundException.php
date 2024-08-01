<?php


namespace Dkvhin\Flysystem\OneDrive\Exception;

use Throwable;

class FileNotFoundException extends AbstractException
{
	/**
	 * @var string
	 */
	protected $path;

	/**
	 * Constructor.
	 *
	 * @param string $path
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct(string $path, $code = 0, Throwable $previous = null)
	{
		$this->path = $path;
		parent::__construct('File not found at path: ' . $this->getPath(), $code, $previous);
	}

	/**
	 * Get the path which was not found.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}
	public static function create($path){
		return new static($path);
	}
}
