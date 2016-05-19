<?php

use Illuminate\Routing\Controller;

class BaseController extends Controller {

	/**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
	protected function setupLayout()
	{
		if ( ! is_null($this->layout))
		{
			$this->layout = View::make($this->layout);
		}
	}

}

/**
 * Output all separate assets, as defined in assets.json
 * For development use only
 * In production, gulp-built concatenated files will be used
 */
function output_dev_assets() {
    $file = '../assets.json';
    $file_handle = fopen($file, 'r');
    $assets = json_decode(fread($file_handle, filesize($file)));
    // CSS
    print "\n<!--- bower css -->\n";
    foreach ($assets->css->bower as $css) {
        print '<link href="/bower_components/' . $css . '" type="text/css" rel="stylesheet">' . "\n";
    }
    print "\n<!--- main css -->\n";
    foreach ($assets->css->main as $css) {
        print '<link href="/assets/css/' . $css . '" type="text/css" rel="stylesheet">' . "\n";
    }
    // JAVASCRIPT
    print "\n<!--- bower javascript -->\n";
    foreach ($assets->javascript->bower as $js) {
        print '<script src="/bower_components/' . $js . '"></script>' . "\n";
    }
    print "\n<!--- main javascript -->\n";
    foreach ($assets->javascript->main as $js) {
        print '<script src="/assets/js/' . $js . '"></script>' . "\n";
    }
    print "\n\n";
}

/**
 * Debugging function
 * @param $var
 * @param bool $die
 */
function d($var, $die = true) {
    var_dump($var);
    if ($die) {
        die;
    }
}

/**
 * Get timestamp in verbal format (example: 1 hour ago)
 * @param $date
 * @param int $granularity
 * @return string
 */
function time_ago($date, $granularity = 2) {
    $date = strtotime($date);
    $difference = time() - $date;
    $periods = array('decade' => 315360000,
        'year' => 31536000,
        'month' => 2628000,
        'week' => 604800,
        'day' => 86400,
        'hour' => 3600,
        'min' => 60,
        'sec' => 1);
    $retval = '';
    foreach ($periods as $key => $value) {
        if ($difference >= $value) {
            $time = floor($difference/$value);
            $difference %= $value;
            $retval .= ($retval ? ' ' : '').$time.' ';
            $retval .= (($time > 1) ? $key.'s' : $key);
            $granularity--;
        }
        if ($granularity == '0') { break; }
    }
    $retval = $retval ? $retval.' ago' : 'Just now';
    // If today
    if (time() - $date <= $periods['day']) {
        $retval = '<strong style="color:#38B44A;font-size:11px">' .$retval . '</strong>';
    }
    return $retval;
}

/**
 * Map colors to string (by their 1st letter)
 * @return array
 */
function get_colors() {
    return [
        'A' => '7FCAFF',
        'B' => '3765da',
        'C' => '7F97FF',
        'D' => '475CC3',
        'E' => 'A77FFF',
        'F' => 'EF6136',
        'G' => 'E77FFF',
        'H' => 'd45888',
        'I' => 'CEDF29',
        'J' => '8CE95F',
        'K' => 'd45888',
        'L' => '17B8F4',
        'M' => 'd45888',
        'N' => '85af1f',
        'O' => 'f2b93b',
        'P' => '17f4b3',
        'Q' => 'e7d435',
        'R' => '7F97FF',
        'S' => 'd6e637',
        'T' => '7FCAFF',
        'U' => 'b9ed3b',
        'V' => 'c1d220',
        'W' => '62F5C8',
        'X' => 'd45888',
        'Y' => '7FCAFF',
        'Z' => '85af1f'
    ];
}

/**
 * Check if string contains at least 1 Hebrew character
 * @param $string
 * @return int
 */
function is_str_contains_hebrew($string) {
    return preg_match("/[א-ת]/", $string);
}

/**
 * Wrap string with a div with rtl/ltr styling
 * @param $str
 * @param bool $readmore
 * @return string
 */
function do_rtl_if_heb($str, $readmore = true) {
    $dir = is_str_contains_hebrew($str) ? 'rtl' : 'ltr';
    // Remove previously added to db dir tags
    $str = str_replace(['<div style="direction:rtl">', '<div style="direction:ltr">', '</div>'], '', $str);
    $str = htmlentities($str, null, 'UTF-8', false);
    if ($readmore) {
        $str = cut_long_text($str, $dir);
    }
    $str = '<div class="' . $dir . '" style="direction:' . $dir .'">' . nl2br($str) . '</div>';
    return $str;
}

/**
 * Cut long text by adding an ellipsis & wrapping the tail with an html tag
 * @param $str
 * @param $dir
 * @return string
 */
function cut_long_text($str, $dir) {
    $maxlen = 200;
    if (mb_strlen($str) <= $maxlen) {
        return $str;
    }
    $readmore_text = $dir === 'rtl' ? 'המשך לקרוא' : 'Read more';
    $before_readmore = mb_substr($str, 0, $maxlen);
    $after_readmore = mb_substr($str, $maxlen);
    $str = $before_readmore . '<em class="tr-readmore">... ' . $readmore_text . '</em>' .
        '<span class="text-readmore">' . $after_readmore . '</span>';
    return $str;
}

/**
 * Get current env based on domain
 * @return string
 */
function ac_env() {
    $env = '';
    switch ($_SERVER['SERVER_NAME']) {
        case 'dev.afterclass.co.il': $env = 'dev'; break;
        case 'qa.afterclass.co.il':  $env = 'qa'; break;
        case 'www.afterclass.co.il': $env = 'prod'; break;
    }
    if (!$env) {
        exit('Unknown environment');
    }
    return $env;
}