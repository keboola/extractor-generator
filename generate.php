<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 05/11/14
 * Time: 17:30
 */

$version = '0.0.2';

//print "" . PHP_EOL . PHP_EOL;
print <<<EOT
▓█████ ▒██   ██▒▄▄▄█████▓ ██▀███   ▄▄▄       ▄████▄  ▄▄▄█████▓ ▒█████   ██▀███
▓█   ▀ ▒▒ █ █ ▒░▓  ██▒ ▓▒▓██ ▒ ██▒▒████▄    ▒██▀ ▀█  ▓  ██▒ ▓▒▒██▒  ██▒▓██ ▒ ██▒
▒███   ░░  █   ░▒ ▓██░ ▒░▓██ ░▄█ ▒▒██  ▀█▄  ▒▓█    ▄ ▒ ▓██░ ▒░▒██░  ██▒▓██ ░▄█ ▒
▒▓█  ▄  ░ █ █ ▒ ░ ▓██▓ ░ ▒██▀▀█▄  ░██▄▄▄▄██ ▒▓▓▄ ▄██▒░ ▓██▓ ░ ▒██   ██░▒██▀▀█▄
░▒████▒▒██▒ ▒██▒  ▒██▒ ░ ░██▓ ▒██▒ ▓█   ▓██▒▒ ▓███▀ ░  ▒██▒ ░ ░ ████▓▒░░██▓ ▒██▒
░░ ▒░ ░▒▒ ░ ░▓ ░  ▒ ░░   ░ ▒▓ ░▒▓░ ▒▒   ▓▒█░░ ░▒ ▒  ░  ▒ ░░   ░ ▒░▒░▒░ ░ ▒▓ ░▒▓░
 ░ ░  ░░░   ░▒ ░    ░      ░▒ ░ ▒░  ▒   ▒▒ ░  ░  ▒       ░      ░ ▒ ▒░   ░▒ ░ ▒░
   ░    ░    ░    ░        ░░   ░   ░   ▒   ░          ░      ░ ░ ░ ▒    ░░   ░
   ░  ░ ░    ░              ░           ░  ░░ ░                   ░ ░     ░
                                            ░
Keboola Extractor Generator v$version
(forked from Syrup Component generator
https://github.com/keboola/syrup-component-generator)

You can provide --namespace and --short-name either as arguments or via interactive interface.
(Note that namespace must contain Bundle at the end).

Options:
--namespace     		- Namespace of your component ie. "Keboola/DbExtractorBundle"
--short-name    		- Short name for you component ie. "ex-db"
--ex-bundle-version		- Version of Extractor bundle to use ie. "~1.1.0" [default: ~1.1.0](composer version string)

Example:
php generate.php --namespace="Keboola/DbExtractorBundle" --short-name="ex-db"


EOT;

$namespace = null;
$shortName = null;
// Extractor
$extractorVer = null;
$apiType = null;
$parser = null;
$oauth = null;
$cnfColumns = null;

//var_dump($argv); die;

foreach ($argv as $arg) {
	if (false !== strstr($arg, '--namespace')) {
		$argArr = explode('=', $arg);
		$namespace = $argArr[1];
		continue;
	}

	if (false !== strstr($arg, '--short-name')) {
		$argArr = explode('=', $arg);
		$shortName = $argArr[1];
		continue;
	}

	if (false !== strstr($arg, '--ex-bundle-version')) {
		$argArr = explode('=', $arg);
		$extractorVer = $argArr[1];
		continue;
	}

	if (false !== strstr($arg, '--api-type')) {
		$argArr = explode('=', $arg);
		$apiType = $argArr[1];
		continue;
	}

	if (false !== strstr($arg, '--parser')) {
		$argArr = explode('=', $arg);
		$parser = $argArr[1];
		continue;
	}

	if (false !== strstr($arg, '--oauth')) {
		$argArr = explode('=', $arg);
		$oauth = $argArr[1];
		continue;
	}

	if (false !== strstr($arg, '--config-columns')) {
		$argArr = explode('=', $arg);
		$cnfColumns = $argArr[1];
		continue;
	}
}


if ($namespace == null) {
	print "Enter namespace for your component (ie.: Keboola/DbExtractorBundle): " . PHP_EOL;
	$namespace = stream_get_line(STDIN, 128, "\n");
}

if ($shortName == null) {
	print "Enter short name for your component (ie.: ex-db): " . PHP_EOL;
	$shortName = stream_get_line(STDIN, 64, "\n");
}

if ($extractorVer == null) {
	$extractorVer = "~1.1.0";
}
print "Using Extractor bundle version '{$extractorVer}'" . PHP_EOL;

$classPrefix = str_replace("Keboola/", "", str_replace("ExtractorBundle", "", $namespace));

// create parameters.yml

print str_pad('Creating parameters.yml', 50, ' ');
createParametersYaml($namespace, $shortName);
print 'OK' . PHP_EOL;

// create composer.json
print str_pad('Creating composer.json', 50, ' ');
createComposerJson($namespace, $shortName, $extractorVer, $classPrefix);
print 'OK' . PHP_EOL;

// download composer
print str_pad('Downloading composer', 50, ' ');
exec('curl -sS https://getcomposer.org/installer | php');
print 'OK' . PHP_EOL;

// run composer install
passthru('php -d memory_limit=-1 composer.phar install');

// generate component
print str_pad('Generating component skeleton', 50, ' ');
passthru('php vendor/keboola/syrup/app/console syrup:generate:component --namespace="'.$namespace.'" --short-name="'.$shortName.'"');

// Register ex-bundle
passthru('php ./vendor/keboola/syrup/app/console syrup:register-bundle Keboola/ExtractorBundle');

$exCommand = 'php ./vendor/keboola/syrup/app/console extractor:generate-extractor \
--app-name="'.$shortName.'" \
--class-prefix="' . $classPrefix . '"';
//  --api-type=json --parser=json --oauth=2 for --no-interactive
if (!empty($apiType)) {
	$exCommand .= " --api-type={$apiType}";
}

if (!empty($parser)) {
	$exCommand .= " --parser={$parser}";
}

if (!empty($oauth)) {
	$exCommand .= " --oauth={$oauth}";
}

if (!empty($cnfColumns)) {
	$exCommand .= " --config-columns={$cnfColumns}";
}
// Create extractor
passthru($exCommand);

function printError($message)
{
	print <<<EOT

	Error: $message

EOT;
}

function generateEncryptionKey()
{
	$size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
	$iv = mcrypt_create_iv($size, MCRYPT_DEV_RANDOM);

	return bin2hex($iv);
}

function createParametersYaml($namespace, $shortName)
{
	$encryptionKey = generateEncryptionKey();

	$filename = 'parameters.yml';

	fopen($filename, 'w+');

	$content = <<<EOT
parameters:
    app_name: $shortName

    encryption_key: $encryptionKey

    components:
EOT;

	file_put_contents($filename, $content);
}

function createComposerJson($namespace, $shortName, $extractorVer, $classPrefix)
{
	$json = [
		'name'  => "keboola/" . strtolower($classPrefix) . "-extractor-bundle",
		'type'  => 'symfony-bundle',
		'description'   => 'Some new component',
		'keywords'  => [],
		'authors'   => [],
		'repositories'  => [],
		'require'   => [
			'keboola/extractor-bundle'    => $extractorVer
		],
		'require-dev'   => [
			'phpunit/phpunit'   => '3.7.*'
		],
		'scripts'   => [
			'post-install-cmd'  => [
				"Syrup\\CoreBundle\\DeploymentHandler\\ScriptHandler::getParameters",
				"Syrup\\CoreBundle\\DeploymentHandler\\ScriptHandler::getSharedParameters",
				"Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::buildBootstrap"
			],
			'post-update-cmd'  => [
				"Syrup\\CoreBundle\\DeploymentHandler\\ScriptHandler::getParameters",
				"Syrup\\CoreBundle\\DeploymentHandler\\ScriptHandler::getSharedParameters",
				"Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::buildBootstrap",
				"Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::clearCache"
			]
		],
		'minimum-stability' => 'stable',
		'autoload'  => [
			'psr-0' => [
				str_replace('/','\\',$namespace)    => ''
			]
		],
		'target-dir'    => $namespace,
		'extra' => [
			"symfony-app-dir"   => "vendor/keboola/syrup/app",
	        "symfony-web-dir"   => "vendor/keboola/syrup/web",
            "syrup-app-name"    => $shortName
		]
	];

	$filename = 'composer.json';
	fopen($filename, 'w+');
	file_put_contents($filename, json_encode($json, JSON_PRETTY_PRINT));
}
