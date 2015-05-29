<?
require_once 'vendor/autoload.php';
use Slobel\PHPCron\Command\Application;
$cron = new Application();
$cron->run();
?>