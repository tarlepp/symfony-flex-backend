#!/usr/bin/env php
<?php
declare(strict_types = 1);
require __DIR__ . '/vendor/autoload.php';

use SebastianBergmann\FinderFacade\FinderFacade;
use TheSeer\fDOM\fDOMDocument;

$input = new FinderFacade([$argv[1]], [], ['*.xml']);
$output = $argv[2];

$outXml = new fDOMDocument();
$outXml->formatOutput = true;

$outTestSuites = $outXml->createElement('testsuites');
$outXml->appendChild($outTestSuites);

$outTestSuite = $outXml->createElement('testsuite');
$outTestSuites->appendChild($outTestSuite);

$tests = 0;
$assertions = 0;
$failures = 0;
$errors = 0;
$time = 0;

try {
    foreach ($input->findFiles() as $file) {
        $inXml = new fDOMDocument();
        $inXml->load($file);

        foreach ($inXml->getElementsByTagName('testsuite') as $inElement) {
            $outElement = $outXml->importNode($inElement, true);
            $outTestSuite->appendChild($outElement);
            $tests += $inElement->getAttribute('tests');
            $assertions += $inElement->getAttribute('assertions');
            $failures += $inElement->getAttribute('failures');
            $errors += $inElement->getAttribute('errors');
            $time += $inElement->getAttribute('time');
        }
    }

    $outTestSuite->setAttribute('tests', $tests);
    $outTestSuite->setAttribute('assertions', $assertions);
    $outTestSuite->setAttribute('failures', $failures);
    $outTestSuite->setAttribute('errors', $errors);
    $outTestSuite->setAttribute('time', $time);

    $outXml->save($output);
} catch (\Exception $exception) {
    echo $exception->getMessage();
}
