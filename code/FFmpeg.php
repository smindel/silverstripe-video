<?php

class FFmpeg extends Object implements Video_Backend
{
    // setup the FFMPEG backup in your mysite/_config/config.yml
    private static $ffmpeg_path = '/usr/bin/ffmpeg';    // path to ffmpeg binary
                                                        // if you don't have ffmpeg installed, you can
                                                        // download static builds to the thirdparty folder
    private static $log_file = '.ffmpeg.log';           // log file name, path relative to assets folder
    private static $log_level = 0;                      // 0 = log nothing,
                                                        // 1 = command and return code
                                                        // 2 = verbose

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
        $logfile = $this->config()->get('log_file');
        $loglevel = $this->config()->get('log_level');
        if ($loglevel) {
            $message = date('c') . "\t" . $cmd . " ($return)";
            if ($loglevel > 1) $message .= "\n\t" . implode("\n\t", $output);
            file_put_contents(
                ASSETS_PATH . DIRECTORY_SEPARATOR . $logfile,
                $message . "\n",
                FILE_APPEND
            );
        }
        return $output;
    }

    public function onBuild()
    {
        $output = $return = false;
        $cmd = $this->config()->get('ffmpeg_path');
        exec($cmd . ' 2>&1', $output, $return);
        DB::alteration_message($output[0], $return == 1 ? 'created' : 'error');
    }

    public function onBeforeDelete() {}
}
