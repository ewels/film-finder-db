<?php
/*
Film Finder DB
==============
Automatically generate a local film database from a directory
of film paths.
*/

error_reporting(-1);

//
// CONFIG
//

$film_dir = '/Users/philewels/Films & TV';
$filetypes = ['mp4', 'avi', 'mkv'];
$ignore = ['720p', '1080p', '720', '1080', 'xvid', 'hdtv', 'x264', 'season', 'series', 'episode', 'bluray', 'yify', 'brrip', 'dvdscr'];
$include_filenames = false;

// List all found movie files
$iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($film_dir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
    RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
);

$videos = array();
$imdb_results = array();
foreach ($iter as $path => $dir) {
    $rel_path = str_replace($film_dir, '', $path);
    if(!is_dir($path)){
        $path_parts = pathinfo($path);
        if(in_array($path_parts['extension'], $filetypes)){
            echo '<h3><code>'.$rel_path.'</code></h3>';
            $videos[$rel_path] = [];
            // Pull out search phrases
            $phrases = explode('/', str_replace($film_dir, '', $path_parts['dirname']));
            if($include_filenames){
                $phrases[] = $path_parts['filename'];
            }
            foreach($phrases as $phrase){
                $phrase = preg_replace('/[^0-9a-zA-Z\']/', ' ', $phrase);
                $phrase = str_replace($ignore, '', strtolower($phrase));
                $phrase = trim(preg_replace('/\s+/', ' ', $phrase));
                if(strlen($phrase) > 2){
                    $videos[$rel_path]['search_phrases'][] = $phrase;
                    if(!array_key_exists($phrase, $imdb_results)){
                        echo '<h4>No previous search for this phrase.</h4>';
                        $imdb_results[$phrase] = search_imdb($phrase);
                    }
                    if($imdb_results[$phrase]){
                        $videos[$rel_path]['imdb_results'][] = $imdb_results[$phrase];
                    }
                }
            }
        }
    }
    echo '<pre>'.print_r($videos[$rel_path], true).'</pre>';
    flush();
}

// echo '<pre>'.print_r($videos, true).'</pre>';




function search_imdb ($phrase){
    $results = json_decode(file_get_contents('http://www.imdb.com/xml/find?json=1&nr=1&tt=on&q='.$phrase), true);
    if($results && array_key_exists('title_popular', $results)){
        return $results['title_popular'][0];
    } else if($results && array_key_exists('title_approx', $results)){
        $approx_exact = [];
        foreach($results['title_approx'] as $approx){
            $approx_title = preg_replace('/[^0-9a-zA-Z\']/', ' ', $approx['title']);
            $approx_title = str_replace($ignore, '', strtolower($approx_title));
            $approx_title = trim(preg_replace('/\s+/', ' ', $approx_title));
            if($approx_title == $phrase){
                $approx_exact[] = $approx;
            }
        }
        if(count($approx_exact) == 1){
            return $approx_exact[0];
        } else if(count($approx_exact) > 1){
            return $approx_exact;
        }
    }
    return $results;
}





?>