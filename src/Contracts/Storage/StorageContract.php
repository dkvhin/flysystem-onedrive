<?php


namespace Dkvhin\Flysystem\OneDrive\Contracts\Storage;


use Dkvhin\Flysystem\OneDrive\Exception\FileNotFoundException;
use Dkvhin\Flysystem\OneDrive\Exception\InvalidVisibilityProvided;
use Dkvhin\Flysystem\OneDrive\Exception\UnableToCopyFile;
use Dkvhin\Flysystem\OneDrive\Exception\UnableToCreateDirectory;
use Dkvhin\Flysystem\OneDrive\Exception\UnableToDeleteDirectory;
use Dkvhin\Flysystem\OneDrive\Exception\UnableToDeleteFile;
use Dkvhin\Flysystem\OneDrive\Exception\UnableToMoveFile;
use Dkvhin\Flysystem\OneDrive\Exception\UnableToReadFile;
use Dkvhin\Flysystem\OneDrive\Exception\UnableToRetrieveMetadata;
use Dkvhin\Flysystem\OneDrive\Exception\UnableToWriteFile;
use Dkvhin\Flysystem\OneDrive\Service\GoogleDrive;
use Dkvhin\Flysystem\OneDrive\Service\OneDrive;
use Dkvhin\Flysystem\OneDrive\Support\FileAttributes;
use Dkvhin\Flysystem\OneDrive\Support\StorageAttributes;
use Dkvhin\Flysystem\OneDrive\Support\Config;
use Dkvhin\Flysystem\OneDrive\Exception\FilesystemException;
use Traversable;

interface StorageContract
{

	/**
	 * @const  VISIBILITY_PUBLIC  public visibility
	 */
	const VISIBILITY_PUBLIC = 'public';

	/**
	 * @const  VISIBILITY_PRIVATE  private visibility
	 */
	const VISIBILITY_PRIVATE = 'private';

	/**
	 * @return mixed | GoogleDrive | OneDrive
	 */
	public function getService();

	/**
	 * @param string $path
	 * @param $contents
	 * @param Config|null $config
	 * @throws UnableToWriteFile
	 * @throws FilesystemException
	 */
	public function writeStream(string $path, $contents, Config $config=null): void;

	/**
	 * @param string $path
	 * @return resource
	 * @throws UnableToReadFile
	 * @throws FileNotFoundException
	 * @throws FilesystemException
	 */
	public function readStream(string $path);

	/**
	 * @param string $path
	 * @throws UnableToDeleteFile
	 * @throws FilesystemException
	 * @throws FileNotFoundException
	 */
	public function delete(string $path): void;

	/**
	 * @param string $path
	 * @throws UnableToDeleteDirectory
	 * @throws FilesystemException
	 * @throws FileNotFoundException
	 */
	public function deleteDirectory(string $path): void;

	/**
	 * @param string $path
	 * @param Config|null $config
	 * @throws UnableToCreateDirectory
	 * @throws FilesystemException
	 */
	public function createDirectory(string $path, Config $config=null): void;

	/**
	 * @param string $path
	 * @param mixed $visibility
	 * @throws InvalidVisibilityProvided
	 * @throws FilesystemException
	 */
	public function setVisibility(string $path, $visibility): void;

	/**
	 * @param string $path
	 * @param bool $deep
	 * @return Traversable<StorageAttributes>
	 * @throws FilesystemException
	 */
	public function listContents(string $path, bool $deep): Traversable;

	/**
	 * @param string $source
	 * @param string $destination
	 * @param Config|null $config
	 * @throws UnableToMoveFile
	 * @throws FilesystemException
	 */
	public function move(string $source, string $destination, Config $config=null): void;

	/**
	 * @param string $source
	 * @param string $destination
	 * @param Config|null $config
	 * @throws UnableToCopyFile
	 * @throws FilesystemException
	 */
	public function copy(string $source, string $destination, Config $config=null): void;

	/**
	 * @param $path
	 * @return FileAttributes
	 * @throws FileNotFoundException
	 * @throws UnableToRetrieveMetadata
	 * @throws FilesystemException
	 */
	public function getMetadata($path): FileAttributes;

    public function temporaryUrl(string $path, \DateTimeInterface $expiresAt, Config $config=null): string;
}
