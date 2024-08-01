<?php


namespace Dkvhin\Flysystem\OneDrive\Storage;

use Dkvhin\Flysystem\OneDrive\Cache\PathCache;
use Dkvhin\Flysystem\OneDrive\Contracts\Storage\StorageContract;
use Dkvhin\Flysystem\OneDrive\Service\HasLogger;
use Closure;

abstract class Storage implements StorageContract
{
	/**
	 * @var PathCache
	 */
	protected $cache;
	use HasLogger;
	protected function setupCache($options){
		$cache=$options['cache']??null;
		if($cache instanceof Closure){
			$cache=$cache();
		}
		if(!$cache instanceof PathCache){
			$cache=new PathCache();
		}
		$this->setCache($cache);
	}
	public function setCache(PathCache $cache){
		$this->cache=$cache;
		return $this;
	}
	public function getCache(){
		return $this->cache;
	}
}