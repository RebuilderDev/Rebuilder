<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 그누보드 5.6.36 이전 버전과의 호환 처리입니다.
 * 5.6.36 이상에서는 코어에 정의된 원본 함수를 그대로 사용합니다.
 */
if (!function_exists('get_versioned_asset_url')) {
    function get_versioned_asset_url($url)
    {
        if (!is_string($url) || $url === '' || !defined('G5_PATH') || G5_PATH === '') {
            return $url;
        }

        $asset_url = preg_replace('/[?#].*$/', '', $url);
        $g5_url = defined('G5_URL') ? rtrim(G5_URL, '/') : '';
        $relative_path = '';

        if ($g5_url !== '' && strpos($asset_url, $g5_url.'/') === 0) {
            $relative_path = substr($asset_url, strlen($g5_url) + 1);
        } else {
            if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $asset_url)
                || strpos($asset_url, '//') === 0
                || substr($asset_url, 0, 1) !== '/') {
                return $url;
            }

            $g5_url_path = $g5_url !== '' ? parse_url($g5_url, PHP_URL_PATH) : '';
            $g5_url_path = rtrim((string) $g5_url_path, '/');
            if ($g5_url_path !== '') {
                if (strpos($asset_url, $g5_url_path.'/') !== 0) {
                    return $url;
                }
                $relative_path = substr($asset_url, strlen($g5_url_path) + 1);
            } else {
                $relative_path = ltrim($asset_url, '/');
            }
        }

        $relative_path = str_replace('\\', '/', rawurldecode($relative_path));
        if ($relative_path === '' || strpos($relative_path, "\0") !== false) {
            return $url;
        }

        $root_path = @realpath(G5_PATH);
        $file_path = @realpath(G5_PATH.'/'.$relative_path);
        if ($root_path === false || $file_path === false || !is_file($file_path)) {
            return $url;
        }

        $root_path = rtrim(str_replace('\\', '/', $root_path), '/');
        $file_path = str_replace('\\', '/', $file_path);
        if (strpos($file_path, $root_path.'/') !== 0) {
            return $url;
        }

        $filetime = @filemtime($file_path);
        if ($filetime === false) {
            return $url;
        }

        $fragment = '';
        $fragment_pos = strpos($url, '#');
        if ($fragment_pos !== false) {
            $fragment = substr($url, $fragment_pos);
            $url = substr($url, 0, $fragment_pos);
        }
        if (preg_match('/([?&](?:amp;)?)ver=[^&#]*/i', $url)) {
            $url = preg_replace('/([?&](?:amp;)?)ver=[^&#]*/i', '${1}ver='.$filetime, $url, 1);
        } else {
            $url .= (strpos($url, '?') === false ? '?' : '&').'ver='.$filetime;
        }

        return $url.$fragment;
    }
}
