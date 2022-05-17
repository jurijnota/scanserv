<?php
include("IScanner.php");
include("ScanResponse.php");
include_once("ScannerOptions.php");
class Scanimage implements IScanner {
    private function CommandLine($scanRequest) {
        $cmd = Config::Scanimage;

        // Set device
        if (isset($scanRequest->device)) {
            $cmd .= " --device-name='" . $scanRequest->device . "'";
        }

        // Set device-specific options
        $scanner = ScannerOptions::get($scanRequest->device);
        $scannerOptions = $scanner["options"];
        foreach ($scanRequest->options as $key => $value) {
            // Apparently, on some HP All-In-One (OfficeJet?), as long as --source flag is set, 
            // the quality will automatically drop to ADF (lower), even though flatbed is selected
            if (strtolower($key) == "source" && 
                strtolower($value) == "flatbed" && 
                strstr(strtolower($scanner["name"]),"hp") &&
                strstr(strtolower($scanner["description"]),"officejet")) {
                continue;
            } 
            
            $cmd .= " " . $scannerOptions[$key]->name . " '" . $value . "'";
        }

        // Select format
        switch ($scanRequest->format) {
            case Format::PNM:
            case Format::PNG:
            case Format::TIFF:
            case Format::JPG:
                $cmd .= " --format='" . $scanRequest->format . "'";
                $cmd = $cmd . ' > "' . $scanRequest->outputFilepath . '"';
                break;
            case Format::PDF:
                $cmd .= " --format='" . Format::JPG . "'";
                $cmd = $cmd . ' | ' . Config::Convert . ' - -compress jpeg -quality 100 pdf:- > "' . $scanRequest->outputFilepath . '"';
                break;
            case Format::BMP:
                $cmd .= " --format='" . Format::TIFF . "'";
                $cmd = $cmd . ' | ' . Config::Convert . ' - bmp:- > "' . $scanRequest->outputFilepath . '"';
                break;
        }

        return $cmd;
    }

    public function Execute($scanRequest) {
        $scanResponse = new ScanResponse();
        $scanResponse->errors = $scanRequest->Validate();
        if (count($scanResponse->errors) == 0) {
            $scanResponse->cmdline = $this->CommandLine($scanRequest);
            System::Execute($scanResponse->cmdline, $scanResponse->output, $scanResponse->returnCode);
            $scanResponse->image = $scanRequest->outputFilepath;
        }

        return $scanResponse;
    }
}
?>
