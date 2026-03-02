<?php
$zip = new ZipArchive;

if ($zip->open('deploy.zip') === TRUE) {
    $zip->extractTo('./');
    $zip->close();
    unlink('deploy.zip');
    echo "Deployment complete";
} else {
    echo "Failed";
}
?>