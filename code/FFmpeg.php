<?php

class FFmpeg extends Object implements Video_Backend
{
    private static $ffmpeg_path = '/opt/local/bin/ffmpeg';

    protected $original_video_name;

	public function __construct($filename)
    {
        $this->original_video_name = $filename;
    }

    public function generateImage($filename, $width = null, $height = null, $offset = 1)
    {
        $input_options = array('ss' => $offset);
        $output_options = $width && $height ? array('s' => $width . 'x' . $height) : array();
        $this->run($this->original_video_name, $input_options, $filename, $output_options);
    }

    public function generateFormat($filename, $width = null, $height = null)
    {
        $format = substr($filename, strrpos($filename, '.') + 1);

        $vcodecs = array(
            'mp4' => 'libx264',
            'ogv' => 'libtheora',
            'webm' => 'libvpx',
        );

        $acodecs = array(
            'mp4' => 'copy',
            'ogv' => 'libvorbis',
            'webm' => 'libvorbis',
        );

        $output_options = array(
            'vcodec' => $vcodecs[$format],
            'b:v' => '600k',
            'acodec' => $acodecs[$format],
            'ac' => '2',
            'ar' => '48000',
            'b:a' => '96k',
        );

        if ($width && $height) $output_options['s'] = ((int)$width) . 'x' . ((int)$height);

        $this->run($this->original_video_name, array('y' => null), $filename, $output_options);
    }

    public function getDuration($filename = null)
    {
        $filename = $filename ?: $this->original_video_name;
        $output = $this->run($filename);
        $duration = _t('Video.DURATION_UNKNOWN','unknown');
        foreach ($output as $line) {
            if (preg_match('/^\s*Duration:\s*(\d+:\d+:\d+\.\d+)/', $line, $matches)) $duration = $matches[1];
        }
        return $duration;
    }

    protected function run($infile, $infile_options = array(), $outfile = null, $outfile_options = array())
    {
        $output = array();
        $return = null;
        $cmd = $this->config()->get('ffmpeg_path');
        foreach ($infile_options as $key => $val) $cmd .= ' -' . $key . ' ' . $val;
        $cmd .= ' -i ' . $infile;
        foreach ($outfile_options as $key => $val) $cmd .= ' -' . $key . ' ' . $val;
        if ($outfile) {
            $cmd .= ' ' . $outfile;
            if (!file_exists(dirname($outfile))) mkdir(dirname($outfile));
        }
        $cmd .= ' 2>&1';
        $out = exec($cmd, $output, $return);
        file_put_contents(
            ASSETS_PATH . DIRECTORY_SEPARATOR . '.ffmpeg.log',
            date('c') . "\t" . $cmd . " ($return)\n\t" . implode("\n\t", $output) . "\n\n\t" . $out . "\n",
            FILE_APPEND
        );
        return $output;
    }

	public function onBeforeDelete() {}
}
