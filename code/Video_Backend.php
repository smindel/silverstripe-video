<?php

interface Video_Backend
{
    public function generateImage($filename, $width = null, $height = null, $offset = 1);

    public function generateFormat($filename, $width = null, $height = null);

    public function getDuration($filename = null);

    public function onBeforeDelete();
}