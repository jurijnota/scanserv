<?php

class Config {
    const IsTrace = false;
    const TraceLineEnding = "<br>\n";

    // You may need to update the paths to scanimage and convert according to 
    // your installation.
    const Scanimage  = "/usr/bin/scanimage";
    const Convert  = "/usr/bin/convert";
    
    // Correct scanner driver with offset
    const TOffset = 2.2997884;
    const LOffset = 2.999724;

    // As with the output filter, the default implementation prefers non-lossy
    // output. Should you wish you override this then you can change the output 
    // type below
    // TIFF is supported by most scanners by default and it is a good option if
    // no filters are used
    const OutputExtension = Format::JPG;

    // Only useful for development debugging
    const BypassSystemExecute = false;
    const OutputDirectory = "./output/";
    const PreviewDirectory = "./preview/";
}
?>
