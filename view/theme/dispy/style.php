<?php
    $line_height = false;
    $dispy_font_size = false;
    $resolution = false;
    $colour = false;
    $site_line_height = get_config("dispy","line_height");
    $site_dispy_font_size = get_config("dispy", "font_size" );
    $site_colour = get_config("dispy", "colour" );

    if (local_user()) {
        $line_height = get_pconfig(local_user(), "dispy","line_height");
        $dispy_font_size = get_pconfig(local_user(), "dispy", "font_size");
        $colour = get_pconfig(local_user(), "dispy", "colour");
    }

    if ($line_height === false) { $line_height = $site_line_height; }
    if ($line_height === false) { $line_height = "1.2"; }
    if ($dispy_font_size === false) { $dispy_font_size = $site_dispy_font_size; }
    if ($dispy_font_size === false) { $dispy_font_size = "12"; }
    if ($colour === false) { $colour = $site_colour; }
    if ($colour === false) { $colour = "light"; }
    
    if($colour == "light") {
        if (file_exists("$THEMEPATH/light/style.css")) {
            echo file_get_contents("$THEMEPATH/light/style.css");
        }
        if($dispy_font_size == "16") {
            echo "
                .wall-item-container .wall-item-content {
                    font-size: 16px;
                }
                .wall-item-photo-container .wall-item-content {
                    font-size: 16px;
                }";  
        }
        if($dispy_font_size == "15") {
            echo "
                .wall-item-container .wall-item-content {
                    font-size: 15px;
                }
                .wall-item-photo-container .wall-item-content {
                    font-size: 15px;
                }";
        }	
        if($dispy_font_size == "14") {
            echo "
                .wall-item-container .wall-item-content {
                    font-size: 14px;
                }
                .wall-item-photo-container .wall-item-content {
                    font-size: 14px;
                }";
        }
        if($dispy_font_size == "13.5") {
            echo "
                .wall-item-container .wall-item-content {
                    font-size: 13.5px;
                }
                .wall-item-photo-container .wall-item-content {
                    font-size: 13.5px;
                }";
        }
        if($dispy_font_size == "13") {
            echo "
                .wall-item-container .wall-item-content {
                    font-size: 13px;
                }
                .wall-item-photo-container .wall-item-content {
                    font-size: 13px;
                }";
        }
        if($dispy_font_size == "12.5") {
            echo "
                .wall-item-container .wall-item-content {
                    font-size: 12.5px;
                }
                .wall-item-photo-container .wall-item-content {
                    font-size: 12.5px;
                }";
        }
        if($dispy_font_size == "12") {
            echo "
                .wall-item-container .wall-item-content {
                    font-size: 12px;
                }
                .wall-item-photo-container .wall-item-content {
                    font-size: 12px;
                }";
        }
        if($line_height == "1.5") {
            echo "
                .wall-item-container .wall-item-content {
                    line-height: 1.5;
                }
                .wall-item-photo-container .wall-item-content {
                    line-height: 1.5;
                }";
        }	
        if($line_height == "1.4") {
            echo "
                .wall-item-container .wall-item-content {
                    line-height: 1.4;
                }
                .wall-item-photo-container .wall-item-content {
                    line-height: 1.4;
                }";
        }
        if($line_height == "1.3") {
            echo "
                .wall-item-container .wall-item-content {
                    line-height: 1.3;
                }
                .wall-item-photo-container .wall-item-content {
                    line-height: 1.3;
                }";
        }
        if($line_height == "1.2") {
            echo "
                .wall-item-container .wall-item-content {
                    line-height: 1.2;
                }
                .wall-item-photo-container .wall-item-content {
                    line-height: 1.2;
                }";
        }
        if($line_height == "1.1") {
            echo "
                .wall-item-container .wall-item-content {
                    line-height: 1.1;
                }
                .wall-item-photo-container .wall-item-content {
                    line-height: 1.1;
                }";
        }
    }

    if($colour == "dark") {
        if (file_exists("$THEMEPATH/dark/style.css")) { 
            echo file_get_contents("$THEMEPATH/dark/style.css");
        }
        if($dispy_font_size == "16") {
            echo "
                .wall-item-container .wall-item-content {
                    font-size: 16px;
                }
                .wall-item-photo-container .wall-item-content {
                    font-size: 16px;
                }";  
        }
        if($dispy_font_size == "15") {
            echo "
                .wall-item-container .wall-item-content {
                    font-size: 15px;
                }
                .wall-item-photo-container .wall-item-content {
                    font-size: 15px;
                }";
        }	
        if($dispy_font_size == "14") {
            echo "
                .wall-item-container .wall-item-content {
                    font-size: 14px;
                }
                .wall-item-photo-container .wall-item-content {
                    font-size: 14px;
                }";
        }
        if($dispy_font_size == "13.5") {
            echo "
                .wall-item-container .wall-item-content {
                    font-size: 13.5px;
                }
                .wall-item-photo-container .wall-item-content {
                    font-size: 13.5px;
                }";
        }
        if($dispy_font_size == "13") {
            echo "
                .wall-item-container .wall-item-content {
                    font-size: 13px;
                }
                .wall-item-photo-container .wall-item-content {
                    font-size: 13px;
                }";
        }
        if($dispy_font_size == "12.5") {
            echo "
                .wall-item-container .wall-item-content {
                    font-size: 12.5px;
                }
                .wall-item-photo-container .wall-item-content {
                    font-size: 12.5px;
                }";
        }
        if($dispy_font_size == "12") {
            echo "
                .wall-item-container .wall-item-content {
                    font-size: 12px;
                }
                .wall-item-photo-container .wall-item-content {
                    font-size: 12px;
                }";
        }
        if($line_height == "1.5") {
            echo "
                .wall-item-container .wall-item-content {
                    line-height: 1.5;
                }
                .wall-item-photo-container .wall-item-content {
                    line-height: 1.5;
                }";
        }	
        if($line_height == "1.4"){
            echo "
                .wall-item-container .wall-item-content {
                    line-height: 1.4;
                }
                .wall-item-photo-container .wall-item-content {
                    line-height: 1.4;
                }";
        }
        if($line_height == "1.3") {
            echo "
                .wall-item-container .wall-item-content {
                    line-height: 1.3;
                }
                .wall-item-photo-container .wall-item-content {
                    line-height: 1.3;
                }";
        }
        if($line_height == "1.2") {
            echo "
                .wall-item-container .wall-item-content {
                    line-height: 1.2;
                }
                .wall-item-photo-container .wall-item-content {
                    line-height: 1.2;
                }";
        }
        if($line_height == "1.1") {
            echo "
                .wall-item-container .wall-item-content {
                    line-height: 1.1;
                }
                .wall-item-photo-container .wall-item-content {
                    line-height: 1.1;
                }";
        }
    }

