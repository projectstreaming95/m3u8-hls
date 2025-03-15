<?php
require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;

function RemoveEmptySubFolders($path)
{
  $empty=true;
  foreach (glob($path.DIRECTORY_SEPARATOR."*") as $file)
  {
     if (is_dir($file))
     {
        if (!RemoveEmptySubFolders($file)) $empty=false;
     }
     else
     {
        $empty=false;
     }
  }
  if ($empty) rmdir($path);
  return $empty;
}

function deleteOldFiles($path)
{
  $threshold = strtotime('-5 minute');
    
  foreach (glob($path.DIRECTORY_SEPARATOR."/*/*") as $file)
  {
     if (!is_dir($file))
     {
        if ($threshold >= filemtime($file)) {
            unlink($file);
        }
     }
  }
}

function createFilesFolder() {
    if(!is_dir(__DIR__."/hls")){
        mkdir(__DIR__."/hls");
    }

    file_put_contents(__DIR__."/hls/.keep", "KEEP ME!");
}

createFilesFolder();

while(true):
    deleteOldFiles(__DIR__ . "/hls");

    RemoveEmptySubFolders(__DIR__ . "/hls");
    
    $m3u8Link = "http://mhiptv.info:2095/live/giro069/2243768906/22.m3u8";

    $guzzleClient = new Client();

    $hlsURL = "";

    $m3u8Request = $guzzleClient->get(
        $m3u8Link,
        [
            'on_stats' => function (TransferStats $stats) use (&$hlsURL) {
                $hlsURL = $stats->getEffectiveUri();
            },
        ]
    );

    $m3u8Content = $m3u8Request->getBody()->getContents();

    $splitedM3u8Content = explode("\n", $m3u8Content);
    $finalHLSURL = explode("/live", $hlsURL)[0];
    $HLSFiles = [];

    foreach($splitedM3u8Content as $hlsFile){
        if(strpos($hlsFile, ".ts") !== false){
            $HLSFiles[] = [
                "url" => $finalHLSURL . $hlsFile,
                "name" => explode("/", $hlsFile)[count(explode("/", $hlsFile)) - 1],
                "folder" => explode("/", $hlsFile)[count(explode("/", $hlsFile)) - 2],
            ];
        }
    }

    foreach($HLSFiles as $hlsFile){
        if(!is_dir(__DIR__ . "/hls/{$hlsFile["folder"]}")){
            mkdir(__DIR__ . "/hls/{$hlsFile["folder"]}");
        }

        if(file_exists(__DIR__ . "/hls/{$hlsFile["folder"]}/{$hlsFile["name"]}")){
            continue;
        }

        $tsRequest = $guzzleClient->get(
            $hlsFile["url"],
            [
                'sink' => __DIR__ . "/hls/{$hlsFile["folder"]}/{$hlsFile["name"]}",
            ]
        );
    }

    file_put_contents(__DIR__ . "/22.m3u8", $m3u8Content);

    dump($HLSFiles);

    sleep(1);
endwhile;