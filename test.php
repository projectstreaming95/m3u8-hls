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

function fetchM3U8($channel){
    $maxTries = 10;
    $try = 0;
    while(true && $try < $maxTries){
        $try++;
        $m3u8Link = "http://mhiptv.info:2095/live/giro069/2243768906/$channel.m3u8";

        $guzzleClient = new Client();
    
        $hlsURL = "";
        
        try{
            $m3u8Request = $guzzleClient->get(
                $m3u8Link,
                [
                    'on_stats' => function (TransferStats $stats) use (&$hlsURL) {
                        $hlsURL = $stats->getEffectiveUri();
                    },
                ]
            );
        
            $m3u8Content = $m3u8Request->getBody()->getContents();
        }catch(Exception $e){
            dump($e->getMessage());
            sleep(1);
            continue;
        }

    
        return compact("m3u8Content", "hlsURL");
    }

}

function fetchHLSFiles($channel = 22) {
    deleteOldFiles(__DIR__ . "/hls");

    RemoveEmptySubFolders(__DIR__ . "/hls");
    
    $guzzleClient = new Client();

    $fetchedM3U8 = fetchM3U8($channel);

    $hlsURL = $fetchedM3U8["hlsURL"];
    $m3u8Content = $fetchedM3U8["m3u8Content"];

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

    if(md5($m3u8Content) === md5_file(__DIR__ . "/$channel.m3u8")){
        dump("No Changes");
        sleep(1);
        return;
    }

    file_put_contents(__DIR__ . "/$channel.m3u8", $m3u8Content);

    dump($HLSFiles);

    exec("git add .");
    exec("git commit -m 'Update HLS Files Automatically'");
    exec("git push origin main --force");

    sleep(1);
}

while(true):
    try{
        fetchHLSFiles(22);
    }catch(Exception $e){
        dump($e->getMessage());
    }

    $guzzleClient = new Client();

    $m3u8Request = $guzzleClient->get(
        "https://google.com",
        [
            'on_stats' => function (TransferStats $stats) use (&$hlsURL) {
                $hlsURL = $stats->getEffectiveUri();
            },
        ]
    );

    $m3u8Content = $m3u8Request->getBody()->getContents();

    dump("hereee", $m3u8Content);

    break;
endwhile;