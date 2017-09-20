<?php
use App\UUID;
use App\Models\User;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

// Create app
$app = new \Slim\App([
	'settings' => [
		'displayErrorDetails' => $conf['app.debug'],
		'addContentLengthHeader' => false
	]
]);
// Get container
$container = $app->getContainer();

// Register component on container
$container['view'] = function ($container) use ($conf){

	$view = new \Slim\Views\Twig($conf['app.template.dir'],
	[
		'cache' => $conf['app.template.cache'],
		'debug' => $conf['app.template.debug'],
		'auto_reload' => $conf['app.template.auto_reload'],
	]);
    $view->addExtension(
        new \App\TwigExtension(
            $container->router,
            $container->request->getUri()
        )
    );

	return $view;
};


// Render Twig template in route
$app->get('/', function ($request, $response, $args) {
	
	$configFile = include "../propel/propel.sample";
	
	return $this->view->render($response, 'install.twig', [
		'conf' => $configFile['propel']['database']['connections']['default']
	]);
})->setName('profile');

$app->post('/installer/set', function ($request, $response, $args) {
	
	$postData = filter_var_array($request->getParams());
	
	$log = "";
	$data = [];
	switch ($postData['step'])
	{
		case "0":
			$configFile = include "../propel/propel.sample";
			$configFile['propel']['database']['connections']['default']['adapter'] = $postData['adapter'];
			$configFile['propel']['database']['connections']['default']['dsn'] = $postData['dsn'];
			$configFile['propel']['database']['connections']['default']['user'] = $postData['user'];
			$configFile['propel']['database']['connections']['default']['password'] = $postData['password'];
			$configFile['propel']['database']['connections']['default']['settings']['charset'] = $postData['charset'];
		
			$configString = var_export($configFile, true);
			
			$configString = str_replace(['array (',')','&#40','&#41', '  '], ['[',']','(',')', "\t"], $configString); // To php5.4 Array
			
			file_put_contents("../propel/propel.php", '<?php'.PHP_EOL.'return '.$configString.';');
			
			$cmd = new Application("CMD");
			$ns = '\\Propel\\Generator\\Command\\';
			$cmd->setAutoExit(false);
			$b = new \ReflectionClass($ns."ConfigConvertCommand");
			
			$cmd->add($b->newInstance());
			
			$input = new ArrayInput(array(
				'command' => 'config:convert',
				'--output-dir' => '../propel/generated-conf/',
				'--config-dir' => '../propel/'
			));
			// You can use NullOutput() if you don't need the output
			$output = new BufferedOutput();
			$cmd->run($input, $output);
			$log = $output->fetch();
			
			$data['log'] = $log;
			$data['result'] = true;
			$data['d'] = $configFile;
			break;

		case "1":
			
			$cmd = new Application("CMD");
			$ns = '\\Propel\\Generator\\Command\\';
			$cmd->setAutoExit(false);
			
			$refClass = new \ReflectionClass($ns."SqlBuildCommand");
			$cmd->add($refClass->newInstance());
			
			$refClass = new \ReflectionClass($ns."SqlInsertCommand");
			$cmd->add($refClass->newInstance());			
			
			$refClass = new \ReflectionClass($ns."ModelBuildCommand");
			$cmd->add($refClass->newInstance());
			
			if(isset($postData['sqlBuild']) && $postData['sqlBuild'] == "1")
			{
				$params = [
					'command' => 'sql:build',
					'--output-dir' => '../propel/generated-sql/',
					'--schema-dir' => '../propel/',
					'--config-dir' => '../propel/'
				];
				
				if(isset($postData['sqlForce']) && $postData['sqlForce'] == "1")
				{
					$params += ['--overwrite' => true];
				}

				$input = new ArrayInput($params);
				
				// You can use NullOutput() if you don't need the output
				$output = new BufferedOutput();
				$cmd->run($input, $output);
				$log = $output->fetch();
			}
			
			if(isset($postData['sqlInsert']) && $postData['sqlInsert'] == 1)
			{
				$params = [
					'command' => 'sql:insert',
					'--sql-dir' => '../propel/generated-sql/',
					'--config-dir' => '../propel/'
				];

				$input = new ArrayInput($params);
				
				// You can use NullOutput() if you don't need the output
				$output = new BufferedOutput();
				$cmd->run($input, $output);
				$log = $output->fetch();
			}
			
			if(isset($postData['modelBuild']) && $postData['modelBuild'] == 1)
			{
				$params = [
					'command' => 'model:build',
					'--schema-dir' => '../propel/',
					'--config-dir' => '../propel/'
				];
				
				$input = new ArrayInput($params);
				
				// You can use NullOutput() if you don't need the output
				$output = new BufferedOutput();
				$cmd->run($input, $output);
				$log = $output->fetch();
			}
			
			$data['log'] = $log;
			$data['result'] = (strlen($log) == 0);
			break;
			
		case "2":
			require '../propel/generated-conf/config.php';
			
			if(!empty($postData['firstName']) && !empty($postData['lastName']) && !empty($postData['email']) && !empty($postData['password']))
			{
				$user = new User();
				$user->setFirstName($postData['firstName']);
				$user->setLastName($postData['lastName']);
				$user->setEmail($postData['email']);
				$user->setPassword(password_hash($postData['password'], PASSWORD_DEFAULT));
				$user->setUUID(UUID::v4());
				
				$data['result'] = $user->save();
			}
			
			break;
		case "3":
			if(isset($postData['disableWizard']) && $postData['disableWizard'] == 1)
			{
				touch("../propel/.disabled");
				
			}
			break;
	}

	return $response->withJson($data);

})->setName('setForm');



