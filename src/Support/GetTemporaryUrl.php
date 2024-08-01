<?php

namespace Dkvhin\Flysystem\OneDrive\Support;

trait GetTemporaryUrl
{
    public function getTemporaryUrl($path, $expiration=null, $options=[])
    {
        if($expiration===null){
            $expiration=time()+3600;
        }
        if(is_int($expiration)){
            $expiration=(new \DateTime())->setTimestamp($expiration);
        }
        if(isset($this->prefixer)){
            $path=$this->prefixer->prefixPath($path);
        }else{
            $path=$this->applyPathPrefix($path);
        }
        return $this->storage->temporaryUrl($path,$expiration,$this->convertConfig($options));
    }
}