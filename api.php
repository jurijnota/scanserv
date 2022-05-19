<?php
// Enable buffering
ob_start();

include_once("classes_php/Scanimage.php");
include_once("classes_php/ScanRequest.php");
include_once("classes_php/ScannerOptions.php");
include_once("classes_php/FileInfo.php");

// Write ajax header
//Content-Type: application/json; charset=utf-8

header('Content-Type: application/json');
//date_default_timezone_set('Europe/London');

class Api {
    public static function HandleCmdlineRequest($request) {
        $cmd = $request->data;
        System::Execute($cmd, $output, $ret);
        return array("cmd" => $cmd, "output" => $output, "ret" => $ret);
    }

    public static function HandlePreviewRequest($request) {        
        $clientRequest = $request->data;
        
        $scanRequest = new ScanRequest();
        
        if (isset($clientRequest->mode)) $scanRequest->options["mode"] = $clientRequest->mode;
        if (isset($clientRequest->brightness)) $scanRequest->options["brightness"] = (int)$clientRequest->brightness;
        if (isset($clientRequest->contrast)) $scanRequest->options["contrast"] = (int)$clientRequest->contrast;
        if (isset($clientRequest->source)) $scanRequest->options["source"] = $clientRequest->source;
        
        // Force JPG preview
        $scanRequest->format = Format::JPG;
        
        if (isset($clientRequest->device)) {
            $scanRequest->device = $clientRequest->device;
            $scanner = ScannerOptions::get($scanRequest->device);
            $scannerOptions = $scanner["options"];
            $scanRequest->options["resolution"] = array_key_exists("resolution",$scannerOptions) ? $scannerOptions["resolution"]->values[0] : 0;
        }
        
        $scanRequest->outputFilepath = Config::PreviewDirectory."preview.jpg";
        $scanner = new Scanimage();
        $scanResponse = $scanner->Execute($scanRequest);    
        return $scanResponse;
    }

    public static function HandlePreviewToJpegRequest() {
        $jpg=file_get_contents(Config::PreviewDirectory.'preview.jpg');
        return array( "jpg" => base64_encode($jpg) );
    }

    public static function HandleScanRequest($request) {
        $clientRequest = $request->data;

        $scanRequest = new ScanRequest();
        
        if (isset($clientRequest->resolution)) $scanRequest->options["resolution"] = (int)$clientRequest->resolution;
        if (isset($clientRequest->mode)) $scanRequest->options["mode"] = $clientRequest->mode;
        if (isset($clientRequest->brightness)) $scanRequest->options["brightness"] = (int)$clientRequest->brightness;
        if (isset($clientRequest->contrast)) $scanRequest->options["contrast"] = (int)$clientRequest->contrast;
        if (isset($clientRequest->source)) $scanRequest->options["source"] = $clientRequest->source;
        if (isset($clientRequest->depth)) $scanRequest->options["depth"] = (int)$clientRequest->depth;
        if (isset($clientRequest->top)) $scanRequest->options["t"] = (float)$clientRequest->top + Config::TOffset;
        if (isset($clientRequest->left)) $scanRequest->options["l"] = (float)$clientRequest->left + Config::LOffset;
        if (isset($clientRequest->height)) $scanRequest->options["y"] = (float)$clientRequest->height;
        if (isset($clientRequest->width)) $scanRequest->options["x"] = (float)$clientRequest->width;
        
        if (isset($clientRequest->device)) 
            $scanRequest->device = $clientRequest->device;
            
        if (isset($clientRequest->format)) 
            $scanRequest->format = $clientRequest->format;
        else 
            $scanRequest->format = Config::OutputExtension;
        
        $outputfile = Config::OutputDirectory . "Scan_" . time() . "." . $scanRequest->format;
        $scanRequest->outputFilepath = $outputfile;
        $scanner = new Scanimage();
        $scanResponse = $scanner->Execute($scanRequest);        
        return $scanResponse;
    }

    public static function HandleFormatListRequest() {
        if (!System::HasConvert()) return array(Format::OutputExtension);
        
        $formats = array(Format::PDF,
                         Format::OCR,
                         Format::BMP,
                         Format::PNM,
                         Format::JPG,
                         Format::PNG,
                         Format::TIFF);
        sort($formats);
        return $formats;
    }

    public static function HandleOptionListRequest() {
        return ScannerOptions::getAll();
    }

    public static function HandleFileListRequest() {
        $files = array();
        $outdir = Config::OutputDirectory;

        foreach (new DirectoryIterator($outdir) as $fileinfo) {
            if(!is_dir($outdir.$fileinfo) && preg_match('/^Scan_[0-9]*/',$fileinfo->getFilename())) {        
                $files[$fileinfo->getMTime()] = $fileinfo->getFilename();
            }
        }

        krsort($files);
        $dirArray = array_values($files);

        $files = array();

        foreach ($dirArray as $filepath) {
            array_push($files, new FileInfo($outdir.$filepath));
        }

        return $files;
    }

    public static function HandleFileDeleteRequest($request) {
        if (!isset($request->data)) return FALSE;
        $fileInfo = new FileInfo($request->data);
        return $fileInfo->Delete();
    }
    
    public static function HandleRefreshDevicesRequest() {
        $devices = System::ScannerDevices();
        file_put_contents(ScannerOptions::DEVICES_FILE, implode("\n", $devices));
        $options = System::ScannerOptions();
        file_put_contents(ScannerOptions::OPTIONS_FILE, implode("\n", $options));
        return true;
    }

    public static function Main() {
        if($_SERVER["REQUEST_METHOD"] == "POST") {
            $input = file_get_contents('php://input');
            $request = json_decode($input);

            $responseType = $request->type;
                
            switch ($request->type) {
                case "scan":
                    $responseData = self::HandleScanRequest($request);
                    break;

                case "preview":
                    $responseData = self::HandlePreviewRequest($request);
                    break;

                case "fileList":
                    $responseData = self::HandleFileListRequest();
                    break;

                case "fileDelete":
                    $responseData = self::HandleFileDeleteRequest($request);
                    break;

                case "previewToJpeg":
                    $responseData = self::HandlePreviewToJpegRequest();
                    break;

                case "cmdline":
                    // $responseData = self::HandleCmdlineRequest($request);
                    $responseData = "cmdline is disabled. If you wish to debug httpdusr permissions you will need to manually enable this in the source.";
                    break;
                    
                case "getFormats":
                    $responseData = self::HandleFormatListRequest();
                    break;
                    
                case "getOptions":
                    $responseData = self::HandleOptionListRequest();
                    break;
                    
                case "refreshDevices":
                    $responseData = self::HandleRefreshDevicesRequest();
                    break;

                case "ping":
                    $responseData = "Pong@".date("Y-m-d H.i.s",time());
                    break;
                    
                default:
                    $responseType = "unknown";
                    $responseData = null;
                    break;
            };
        
            $response = array(
                "type" => $responseType,
                "data" => $responseData
            );

            echo json_encode($response);
        
        } else {
            echo "GET METHOD NOT SUPPORTED";
        }
    }
}

Api::Main();
?>
