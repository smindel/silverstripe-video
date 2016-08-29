<?php

/**
 * Represents a Video
 */

class Video extends File implements Flushable
{
    protected static $flush = false;

    private static $backend = "FFmpeg";
    private static $formats = array(
        'mp4' => 'video/mp4;codecs="avc1.42E01E, mp4a.40.2"',
        'webm' => 'video/webm;codecs="vp8, vorbis"',
        'ogv' => 'video/ogv;codecs=dirac, speex, theora, vorbis',
    );

    private static $db = array(
        'Duration' => 'Varchar',
    );

    protected $attributes = array();

    public function StripThumbnail()
    {
        return $this->PlaceholderImage->StripThumbnail();
    }

    public function getThumbnail($width, $height)
    {
        return $this->PlaceholderImage->Fit($width, $height);
    }

    public function CMSThumbnail()
    {
        return $this->getThumbnail(50, 50);
    }

    public function getPlaceholderImage()
    {
        $path = $this->Filename;
        $dir = dirname($path);
        $base = basename($path);
        $placeholdername = $dir . '/_resampled/' . $base . '.png';
        if (!file_exists($placeholdername)) {
            $this->getBackend()->generateImage(Director::baseFolder() . DIRECTORY_SEPARATOR . $placeholdername);
        }
        return Image_Cached::create(
            $placeholdername,
            false,
            Image::create(array(
                'Filename' => $this->Filename,
                'ParentID' => $this->ParentID,
            ))
        );
    }

    public function getFileType() {
        $types = array(
            'mp4' => _t('Video.Mp4Type', 'MP4 video'),
            'ogv' => _t('Video.OgvType', 'OGV video'),
            'webm' => _t('Video.WebMType', 'WebM video'),
        );

        $ext = strtolower($this->getExtension());

        return isset($types[$ext]) ? $types[$ext] : 'unknown video format';
    }

    public function getVersions()
    {
        $versions = array();
        foreach ($this->config()->get('formats') as $format => $codec) {
            $versions[$format] = $this->getFormat($format);
        }
        return ArrayList::create($versions);
    }

    public function getFormat($format)
    {
        $formats = $this->config()->get('formats');
        if (!isset($formats[$format])) return false;
        $path = $this->Filename;
        $dir = dirname($path);
        $base = basename($path);
        $cachename = $dir . '/_resampled/' . $base . '.' . $format;
        $fullname = Director::baseFolder() . DIRECTORY_SEPARATOR . $cachename;
        if (!file_exists($fullname) || self::$flush) {
            $this->getBackend()->generateFormat($fullname);
        }
        return ArrayData::create(array(
            'Filename' => $cachename,
            'Type' => $formats[$format],
        ));
    }

    public function getBackend()
    {
        return Injector::inst()->createWithArgs(self::config()->backend, array(
            $this->getFullPath()
        ));
    }

    public static function flush()
    {
        self::$flush = true;
    }

    public function forTemplate()
    {
        return $this->getTag();
    }

    public function getTag()
    {
        return $this->renderWith(__CLASS__);
    }

    public function setAttribute($name, $value = null)
    {
        if (!$value && isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        } else {
            $this->attributes[$name] = $value;
        }
    }

    public function getAttributes()
    {
        $attributes = array();
        foreach ($this->attributes as $name => $val) {
            if ($val === true) $attributes[] = $name;
            else $attributes[] = "$name = \"$val\"";
        }
        return implode(' ', $attributes);
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $preview = $fields->fieldByName('Root.Main.FilePreview');
        $meta = $preview->fieldByName('FilePreviewData');
        $image = $preview->fieldByName('FilePreviewImage')->fieldByName('ImageFull');

        $meta->FieldList()->First()->unshift(ReadonlyField::create("Duration", _t('Video.DURATION','Duration') . ':'));
        $image->setContent($this->setSize(160,120)->controls()->getTag());

        return $fields;
    }

    public function controls() { $this->setAttribute('controls', true); return $this; }
    public function autoplay() { $this->setAttribute('autoplay', true); return $this; }
    public function loop()     { $this->setAttribute('loop',     true); return $this; }
    public function muted()    { $this->setAttribute('muted',    true); return $this; }
    public function setWidth($width) { $this->setAttribute('width', $width); return $this; }
    public function setHeight($height) { $this->setAttribute('height', $height); return $this; }
    public function setSize($width, $height) { $this->setAttribute('width', $width); $this->setAttribute('height', $height); return $this; }
    public function poster()   { $this->setAttribute('poster',   $this->PlaceholderImage->Filename); return $this; }
    public function rep() { unlink($this->PlaceholderImage->getFullPath()); __('unlinked'); }
    public function rip() { $this->PlaceholderImage->delete(); __('unlinked'); }



    public function deleteCachedFiles() {
        if(!$this->Filename) return;

        $path = $this->Filename;
        $dir = dirname($this->Filename);
        $base = basename($this->Filename);
        if (file_exists(Director::baseFolder() . DIRECTORY_SEPARATOR . dirname($this->Filename) . '/_resampled/' . basename($this->Filename) . '.png')) {
            $this->PlaceholderImage->delete();
        }

        foreach ($this->config()->get('formats') as $format => $codec) {
            $cachename = dirname($this->Filename) . '/_resampled/' . basename($this->Filename) . '.' . $format;
            $fullname = Director::baseFolder() . DIRECTORY_SEPARATOR . $cachename;
            if (file_exists($fullname)) {
                unlink($fullname);
            }
        }
    }

    public function onAfterUpload()
    {
        $this->deleteCachedFiles();
        parent::onAfterUpload();
    }

    public function onBeforeWrite()
    {
        $this->Duration = $this->getBackend()->getDuration();
        parent::onBeforeWrite();
    }

    protected function onBeforeDelete()
    {

        $this->getBackend()->onBeforeDelete($this);

        $this->deleteCachedFiles();

        parent::onBeforeDelete();
    }
}
