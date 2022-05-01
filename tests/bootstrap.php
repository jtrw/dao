<?php
require __DIR__."/../vendor/autoload.php";

echo "Current User: ".get_current_user()."\n";

$output=null;
$retval=null;
exec('whoami', $output, $retval);
echo "Returned with status $retval and output:\n";
print_r($output);

try {
    $result = file_put_contents(__DIR__."/reports/test.xml", "Hello");
    if (!$result) {
        echo "LOL!";
    }
} catch (Throwable $th) {
    echo $th->getMessage();
}


$cmd = "chmod -R 0777 ".__DIR__."/reports";
`$cmd`;

try {
    $result = file_put_contents(__DIR__."/reports/test.xml", "Hello");
    if (!$result) {
        echo "2)LOL!";
    }
} catch (Throwable $th) {
    echo "2)".$th->getMessage();
}

\Jtrw\DAO\Tests\DbConnector::init();

