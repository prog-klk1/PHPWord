<?php

$vendorDirPath = realpath(__DIR__ . '/../vendor');

if ((defined('USE_AUTOLOADER') && USE_AUTOLOADER == true)
    || !file_exists($vendorDirPath . '/autoload.php')) {
    // PhpWord
    require_once __DIR__ . '/../src/PhpWord/Autoloader.php';
    PhpOffice\PhpWord\Autoloader::register();
} else {
    require $vendorDirPath . '/autoload.php';
    $dompdfPath = $vendorDirPath . '/dompdf/dompdf';
    if (file_exists($dompdfPath)) {
        define('DOMPDF_ENABLE_AUTOLOAD', false);
    }
}

use PhpOffice\PhpWord\Settings;

date_default_timezone_set('UTC');
error_reporting(E_ALL);
define('CLI', (PHP_SAPI == 'cli') ? true : false);
define('EOL', CLI ? PHP_EOL : '<br />');
define('SCRIPT_FILENAME', basename($_SERVER['SCRIPT_FILENAME'], '.php'));
define('IS_INDEX', SCRIPT_FILENAME == 'index');

Settings::loadConfig();

if (defined('DOMPDF_ENABLE_AUTOLOAD')) {
    Settings::setPdfRenderer(Settings::PDF_RENDERER_DOMPDF, $vendorDirPath . '/dompdf/dompdf');
}

// Set writers
$writers = ['Word2007' => 'docx', 'ODText' => 'odt', 'RTF' => 'rtf', 'HTML' => 'html', 'PDF' => 'pdf', 'EPub3' => 'epub'];

// Set PDF renderer
if (null === Settings::getPdfRendererPath()) {
    $writers['PDF'] = null;
}

// Turn output escaping on
Settings::setOutputEscapingEnabled(true);

// Return to the caller script when runs by CLI
if (CLI) {
    return;
}

// Set titles and names
$pageHeading = str_replace('_', ' ', SCRIPT_FILENAME);
$pageTitle = IS_INDEX ? 'Welcome to ' : "{$pageHeading} - ";
$pageTitle .= 'PHPWord';
$pageHeading = IS_INDEX ? '' : "<h1>{$pageHeading}</h1>";

// Populate samples
$files = '';
if ($handle = opendir('.')) {
    $sampleFiles = [];
    while (false !== ($sampleFile = readdir($handle))) {
        $sampleFiles[] = $sampleFile;
    }
    sort($sampleFiles);
    closedir($handle);

    foreach ($sampleFiles as $file) {
        if (preg_match('/^Sample_\d+_/', $file)) {
            $name = str_replace('_', ' ', preg_replace('/(Sample_|\.php)/', '', $file));
            $files .= "<li><a href='{$file}'>{$name}</a></li>";
        }
    }
}

/**
 * Write documents.
 */
function write(PhpOffice\PhpWord\PhpWord $phpWord, string $filename, array $writers): string
{
    $result = '';

    // Write documents
    foreach ($writers as $format => $extension) {
        $result .= date('H:i:s') . " Write to {$format} format";
        if (null !== $extension) {
            $targetFile = __DIR__ . "/results/{$filename}.{$extension}";
            $phpWord->save($targetFile, $format);
        } else {
            $result .= ' ... NOT DONE!';
        }
        $result .= EOL;
    }

    $result .= getEndingNotes($writers, $filename);

    return $result;
}

/**
 * Get ending notes.
 */
function getEndingNotes(array $writers, string $filename): string
{
    $result = '';

    // Do not show execution time for index
    if (!IS_INDEX) {
        $result .= date('H:i:s') . ' Done writing file(s)' . EOL;
        $result .= date('H:i:s') . ' Peak memory usage: ' . (memory_get_peak_usage(true) / 1024 / 1024) . ' MB' . EOL;
    }

    // Return
    if (CLI) {
        $result .= 'The results are stored in the "results" subdirectory.' . EOL;
    } else {
        if (!IS_INDEX) {
            $types = array_values($writers);
            $result .= '<p>&nbsp;</p>';
            $result .= '<p>Results: ';
            foreach ($types as $type) {
                if (null !== $type) {
                    $resultFile = 'results/' . SCRIPT_FILENAME . '.' . $type;
                    if (file_exists($resultFile)) {
                        $result .= "<a href='{$resultFile}' class='btn btn-primary'>{$type}</a> ";
                    }
                }
            }
            $result .= '</p>';

            $result .= '<pre>';
            if (file_exists($filename . '.php')) {
                $result .= highlight_file($filename . '.php', true);
            }
            $result .= '</pre>';
        }
    }

    return $result;
}
?>
<title><?php echo $pageTitle; ?></title>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css" />
<link rel="stylesheet" href="bootstrap/css/font-awesome.min.css" />
<link rel="stylesheet" href="bootstrap/css/phpword.css" />
</head>
<body>
<div class="container">
<div class="navbar navbar-default" role="navigation">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="./">PHPWord</a>
        </div>
        <div class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
                <li class="dropdown active">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-code fa-lg"></i>&nbsp;Samples<strong class="caret"></strong></a>
                    <ul class="dropdown-menu"><?php echo $files; ?></ul>
                </li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li><a href="https://github.com/PHPOffice/PHPWord"><i class="fa fa-github fa-lg" title="GitHub"></i>&nbsp;</a></li>
                <li><a href="https://phpoffice.github.io/PHPWord/"><i class="fa fa-book fa-lg" title="Docs"></i>&nbsp;</a></li>
                <li><a href="http://twitter.com/PHPOffice"><i class="fa fa-twitter fa-lg" title="Twitter"></i>&nbsp;</a></li>
            </ul>
        </div>
    </div>
</div>
<?php echo $pageHeading; ?>
